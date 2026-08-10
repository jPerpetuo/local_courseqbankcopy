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
 * Request-scoped registry for the active restore operation.
 *
 * @package    local_courseqbankcopy
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_courseqbankcopy\local;

/**
 * Keeps the active restore ID available to synchronous event observers.
 */
final class runtime_registry {
    /** @var string|null */
    private static ?string $restoreid = null;

    /**
     * Records the restore currently processed in this PHP request.
     *
     * @param string $restoreid Restore ID.
     */
    public static function set_restoreid(string $restoreid): void {
        self::$restoreid = $restoreid;
    }

    /**
     * Returns the current restore ID.
     *
     * @return string|null
     */
    public static function get_restoreid(): ?string {
        return self::$restoreid;
    }
}
