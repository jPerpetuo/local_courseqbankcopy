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
 * Backup integration for the plugin.
 *
 * @package    local_courseqbankcopy
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Adds lightweight source-ID markers to Moodle backups.
 *
 * The markers contain no user data. They allow this plugin to retain the
 * mappings produced by restore before Moodle removes backup_ids_temp.
 */
class backup_local_courseqbankcopy_plugin extends backup_local_plugin {
    /**
     * Adds the source course-module ID to each selected module.
     *
     * @return backup_plugin_element
     */
    protected function define_module_plugin_structure(): backup_plugin_element {
        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $marker = new backup_nested_element('module_marker', null, ['sourcecmid']);

        $plugin->add_child($wrapper);
        $wrapper->add_child($marker);
        $marker->set_source_sql(
            'SELECT id AS sourcecmid FROM {course_modules} WHERE id = ?',
            [backup::VAR_PARENTID],
        );

        return $plugin;
    }

    /**
     * Adds source question-bank IDs below every question.
     *
     * @return backup_plugin_element
     */
    protected function define_question_plugin_structure(): backup_plugin_element {
        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $marker = new backup_nested_element('question_marker', null, [
            'sourcequestionid',
            'sourceqbeid',
            'sourcecategoryid',
            'sourcecontextid',
        ]);

        $plugin->add_child($wrapper);
        $wrapper->add_child($marker);
        $marker->set_source_sql(
            "SELECT q.id AS sourcequestionid,
                    qv.questionbankentryid AS sourceqbeid,
                    qbe.questioncategoryid AS sourcecategoryid,
                    qc.contextid AS sourcecontextid
               FROM {question} q
               JOIN {question_versions} qv ON qv.questionid = q.id
               JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
               JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
              WHERE q.id = ?",
            [backup::VAR_PARENTID],
        );

        return $plugin;
    }
}
