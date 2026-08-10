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
 * Scheduled task that removes expired operation metadata.
 *
 * @package    local_courseqbankcopy
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_courseqbankcopy\task;

use local_courseqbankcopy\local\operation_repository;

/**
 * Removes completed or failed operation metadata after the diagnostic window.
 */
final class cleanup_operations extends \core\task\scheduled_task {
    /**
     * Returns the translated task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('taskcleanupoperations', 'local_courseqbankcopy');
    }

    /**
     * Deletes operation records older than 30 days.
     */
    public function execute(): void {
        global $DB;

        $cutoff = time() - (30 * DAYSECS);
        $params = [
            'cutoff' => $cutoff,
            'complete' => operation_repository::STATUS_COMPLETE,
            'failed' => operation_repository::STATUS_FAILED,
        ];
        $operations = $DB->get_records_select(
            'local_cqbc_operation',
            'timemodified < :cutoff AND (status = :complete OR status = :failed)',
            $params,
            '',
            'id, restoreid',
        );
        foreach ($operations as $operation) {
            $DB->delete_records('local_cqbc_mapping', ['restoreid' => $operation->restoreid]);
            $DB->delete_records('local_cqbc_operation', ['id' => $operation->id]);
        }
    }
}
