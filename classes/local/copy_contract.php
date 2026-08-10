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
 * Definition of the independent-copy contract.
 *
 * @package    local_courseqbankcopy
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_courseqbankcopy\local;

/**
 * Defines the data that must be independent after a copy-mode import.
 */
final class copy_contract {
    /**
     * Returns the entities that must be copied and remapped.
     *
     * This contract will drive implementation and automated acceptance tests.
     *
     * @return string[]
     */
    public static function required_entities(): array {
        return [
            'question_bank_modules',
            'question_categories',
            'question_bank_entries',
            'question_versions',
            'questions',
            'question_type_data',
            'question_files',
            'quiz_question_references',
            'random_question_category_references',
        ];
    }
}
