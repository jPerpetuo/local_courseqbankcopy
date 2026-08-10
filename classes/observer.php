<?php
// This file is part of Moodle - https://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Event observers used by the plugin.
 *
 * @package    local_courseqbankcopy
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_courseqbankcopy;

use local_courseqbankcopy\local\backup_package_transformer;
use local_courseqbankcopy\local\import_mode;
use local_courseqbankcopy\local\operation_repository;
use local_courseqbankcopy\local\reference_reconciler;
use local_courseqbankcopy\local\runtime_registry;

/**
 * Observes the synchronous backup/restore lifecycle used by course import.
 */
final class observer {
    /**
     * Transforms the temporary package after backup and before restore.
     *
     * @param \core\event\course_backup_created $event Backup event.
     */
    public static function course_backup_created(\core\event\course_backup_created $event): void {
        if (
            (int) $event->other['mode'] !== \backup::MODE_IMPORT
                || $event->other['format'] !== \backup::FORMAT_MOODLE
        ) {
            return;
        }

        $targetcourseid = optional_param('id', 0, PARAM_INT);
        $importcourseid = optional_param('importid', 0, PARAM_INT);
        if (!$targetcourseid || ($importcourseid && $importcourseid !== (int) $event->objectid)) {
            debugging(
                get_string('targetcourseidentificationfailed', 'local_courseqbankcopy'),
                DEBUG_DEVELOPER,
            );
            return;
        }

        $targetcontext = \context_course::instance($targetcourseid, IGNORE_MISSING);
        if (!$targetcontext) {
            return;
        }

        $requestedmode = optional_param('local_courseqbankcopy_mode', import_mode::COPY, PARAM_ALPHA);
        if (import_mode::resolve($requestedmode, $targetcontext) !== import_mode::COPY) {
            return;
        }

        $restoreid = clean_param($event->other['backupid'], PARAM_ALPHANUM);
        $token = bin2hex(random_bytes(16));
        operation_repository::create(
            $restoreid,
            (int) $event->objectid,
            $targetcourseid,
            $token,
        );
        runtime_registry::set_restoreid($restoreid);

        try {
            $tempdir = make_backup_temp_directory($restoreid, false);
            $transformer = new backup_package_transformer();
            $transformer->require_complete_question_banks($tempdir, (int) $event->objectid);
            $result = $transformer->transform($tempdir, $restoreid, $token);
            operation_repository::set_status(
                $restoreid,
                operation_repository::STATUS_PREPARED,
                null,
                $result['categories'],
                $result['questions'],
            );
        } catch (\Throwable $exception) {
            operation_repository::set_status(
                $restoreid,
                operation_repository::STATUS_FAILED,
                $exception->getMessage(),
            );
            throw new \moodle_exception(
                'copyinterceptionfailed',
                'local_courseqbankcopy',
                '',
                null,
                $exception->getMessage(),
            );
        }
    }

    /**
     * Repoints and validates question references after the native restore.
     *
     * @param \core\event\course_restored $event Restore event.
     */
    public static function course_restored(\core\event\course_restored $event): void {
        if ((int) $event->other['mode'] !== \backup::MODE_IMPORT) {
            return;
        }

        $restoreid = runtime_registry::get_restoreid();
        $operation = $restoreid ? operation_repository::get($restoreid) : null;
        if (!$operation && !empty($event->other['originalcourseid'])) {
            $operation = operation_repository::find_active(
                (int) $event->other['originalcourseid'],
                (int) $event->courseid,
            );
            $restoreid = $operation->restoreid ?? null;
        }
        if (!$restoreid || !$operation) {
            return;
        }

        try {
            (new reference_reconciler())->reconcile($restoreid);
        } catch (\Throwable $exception) {
            operation_repository::set_status(
                $restoreid,
                operation_repository::STATUS_FAILED,
                $exception->getMessage(),
            );
            throw new \moodle_exception(
                'copyreconciliationfailed',
                'local_courseqbankcopy',
                '',
                null,
                $exception->getMessage(),
            );
        }
    }
}
