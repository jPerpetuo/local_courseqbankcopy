<?php
// This file is part of Moodle - https://moodle.org/.

namespace local_courseqbankcopy\local;

defined('MOODLE_INTERNAL') || die();

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
