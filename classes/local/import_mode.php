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
 * Import mode selection and authorisation service.
 *
 * @package    local_courseqbankcopy
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_courseqbankcopy\local;

/**
 * Resolves the question-bank strategy selected for a course import.
 */
final class import_mode {
    /** Reuse the source question bank references. */
    public const REUSE = 'reuse';

    /** Create independent copies in the target course. */
    public const COPY = 'copy';

    /**
     * Gets the configured default mode.
     *
     * @return string
     */
    public static function get_default(): string {
        $defaultcopymode = get_config('local_courseqbankcopy', 'defaultcopymode');

        return $defaultcopymode === '0' ? self::REUSE : self::COPY;
    }

    /**
     * Determines whether the user may override the configured default mode.
     *
     * @param \context_course $context Destination course context.
     * @return bool
     */
    public static function can_choose(\context_course $context): bool {
        if (get_config('local_courseqbankcopy', 'defaultcopymode_locked')) {
            return false;
        }

        return is_siteadmin()
            || (get_config('local_courseqbankcopy', 'allowreuseselection')
                && has_capability('local/courseqbankcopy:choosereusemode', $context));
    }

    /**
     * Resolves a requested mode while enforcing the course capability.
     *
     * @param string|null $requestedmode Value supplied by the import form.
     * @param \context_course $context Destination course context.
     * @return string
     */
    public static function resolve(?string $requestedmode, \context_course $context): string {
        if (self::can_choose($context) && in_array($requestedmode, [self::COPY, self::REUSE], true)) {
            return $requestedmode;
        }

        return self::get_default();
    }
}
