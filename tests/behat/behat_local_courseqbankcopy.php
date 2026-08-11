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

use Behat\Mink\Exception\ExpectationException;
use local_courseqbankcopy\local\operation_repository;
use mod_quiz\quiz_settings;

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat steps for independent question-bank imports.
 *
 * @package    local_courseqbankcopy
 * @category   test
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class behat_local_courseqbankcopy extends behat_base {
    /**
     * Adds one random question using the quiz structure API shared by Moodle 5.1 and 5.2.
     *
     * @Given /^quiz "([^"]*)" in course "([^"]*)" has a random question from category "([^"]*)"$/
     * @param string $quizname Quiz name.
     * @param string $courseshortname Course short name.
     * @param string $categoryname Question category name.
     */
    public function quiz_should_have_random_question_from_category(
        string $quizname,
        string $courseshortname,
        string $categoryname
    ): void {
        global $DB;

        $course = $DB->get_record('course', ['shortname' => $courseshortname], '*', MUST_EXIST);
        $quiz = $DB->get_record('quiz', [
            'course' => $course->id,
            'name' => $quizname,
        ], '*', MUST_EXIST);
        $coursecontext = context_course::instance($course->id);
        $pathlike = $DB->sql_like('ctx.path', ':contextpath');
        $category = $DB->get_record_sql(
            "SELECT qc.*
               FROM {question_categories} qc
               JOIN {context} ctx ON ctx.id = qc.contextid
              WHERE qc.name = :categoryname AND {$pathlike}",
            [
                'categoryname' => $categoryname,
                'contextpath' => $coursecontext->path . '/%',
            ],
            MUST_EXIST,
        );

        $filtercondition = [
            'filter' => [
                'category' => [
                    'jointype' => \qbank_managecategories\category_condition::JOINTYPE_DEFAULT,
                    'values' => [(int) $category->id],
                    'filteroptions' => ['includesubcategories' => false],
                ],
            ],
        ];
        $settings = quiz_settings::create($quiz->id);
        $structure = \mod_quiz\structure::create_for_quiz($settings);
        $structure->add_random_questions(1, 1, $filtercondition);
    }

    /**
     * Confirms that an imported quiz and the complete bank are independent from the source course.
     *
     * @Then /^course "([^"]*)" has an independent bank copied from "([^"]*)" for quiz "([^"]*)"$/
     * @param string $targetshortname Target course short name.
     * @param string $sourceshortname Source course short name.
     * @param string $quizname Imported quiz name.
     */
    public function course_should_have_independent_question_bank(
        string $targetshortname,
        string $sourceshortname,
        string $quizname
    ): void {
        global $DB;

        $sourcecourse = $DB->get_record('course', ['shortname' => $sourceshortname], '*', MUST_EXIST);
        $targetcourse = $DB->get_record('course', ['shortname' => $targetshortname], '*', MUST_EXIST);
        $sourceentries = $this->get_course_question_bank_entries($sourcecourse);
        $targetentries = $this->get_course_question_bank_entries($targetcourse);

        if (!$sourceentries || count($sourceentries) !== count($targetentries)) {
            throw new ExpectationException(
                'The target course does not contain a complete copy of the source question bank.',
                $this->getSession(),
            );
        }
        if (array_intersect($sourceentries, $targetentries)) {
            throw new ExpectationException(
                'The source and target courses still share question-bank entry IDs.',
                $this->getSession(),
            );
        }

        $sourcequizentries = $this->get_quiz_question_bank_entries($sourcecourse, $quizname);
        $targetquizentries = $this->get_quiz_question_bank_entries($targetcourse, $quizname);
        if (!$sourcequizentries || !$targetquizentries) {
            throw new ExpectationException(
                'The source or imported quiz has no fixed question references.',
                $this->getSession(),
            );
        }
        if (array_diff($targetquizentries, $targetentries) || array_intersect($targetquizentries, $sourceentries)) {
            throw new ExpectationException(
                'The imported quiz still uses a question-bank entry outside the target course.',
                $this->getSession(),
            );
        }

        $operations = $DB->get_records(
            'local_courseqbankcopy_ops',
            [
                'sourcecourseid' => $sourcecourse->id,
                'targetcourseid' => $targetcourse->id,
            ],
            'timemodified DESC',
            '*',
            0,
            1,
        );
        $operation = $operations ? reset($operations) : null;
        if (!$operation || $operation->status !== operation_repository::STATUS_COMPLETE) {
            throw new ExpectationException(
                'The independent question-bank copy operation did not finish successfully.',
                $this->getSession(),
            );
        }
    }

    /**
     * Confirms that imported random questions use only copied categories and contexts.
     *
     * @Then /^quiz "([^"]*)" in course "([^"]*)" uses random questions copied from course "([^"]*)"$/
     * @param string $quizname Imported quiz name.
     * @param string $targetshortname Target course short name.
     * @param string $sourceshortname Source course short name.
     */
    public function quiz_should_use_copied_random_question_categories(
        string $quizname,
        string $targetshortname,
        string $sourceshortname
    ): void {
        global $DB;

        $sourcecourse = $DB->get_record('course', ['shortname' => $sourceshortname], '*', MUST_EXIST);
        $targetcourse = $DB->get_record('course', ['shortname' => $targetshortname], '*', MUST_EXIST);
        $sourcecontext = context_course::instance($sourcecourse->id);
        $targetcontext = context_course::instance($targetcourse->id);
        $sourcereferences = $this->get_quiz_question_set_references($sourcecourse, $quizname);
        $targetreferences = $this->get_quiz_question_set_references($targetcourse, $quizname);
        $operations = $DB->get_records(
            'local_courseqbankcopy_ops',
            [
                'sourcecourseid' => $sourcecourse->id,
                'targetcourseid' => $targetcourse->id,
            ],
            'timemodified DESC',
            '*',
            0,
            1,
        );
        $operation = $operations ? reset($operations) : null;
        $categorymappings = $operation ? operation_repository::get_mappings(
            $operation->restoreid,
            operation_repository::TYPE_CATEGORY,
        ) : [];

        if (!$sourcereferences || count($sourcereferences) !== count($targetreferences)) {
            throw new ExpectationException(
                'The source or imported quiz does not contain the expected random question references.',
                $this->getSession(),
            );
        }

        foreach ($targetreferences as $reference) {
            $questionscontext = context::instance_by_id((int) $reference->questionscontextid, IGNORE_MISSING);
            $categoryids = $this->get_reference_category_ids($reference);
            if (!$categoryids) {
                throw new ExpectationException(
                    'An imported random question has no category in its filter condition.',
                    $this->getSession(),
                );
            }
            if (
                !$questionscontext
                    || !$targetcontext->is_parent_of($questionscontext, true)
                    || $sourcecontext->is_parent_of($questionscontext, true)
            ) {
                $details = json_encode([
                    'questionscontextid' => (int) $reference->questionscontextid,
                    'questionscontextpath' => $questionscontext->path ?? null,
                    'sourcecontextpath' => $sourcecontext->path,
                    'targetcontextpath' => $targetcontext->path,
                    'filtercondition' => $reference->filtercondition,
                    'categorymappings' => array_values($categorymappings),
                ], JSON_UNESCAPED_SLASHES);
                throw new ExpectationException(
                    'The imported random question still uses a context outside the target course: ' . $details,
                    $this->getSession(),
                );
            }
            foreach ($categoryids as $categoryid) {
                $categorycontextid = $DB->get_field(
                    'question_categories',
                    'contextid',
                    ['id' => $categoryid],
                    MUST_EXIST,
                );
                $categorycontext = context::instance_by_id((int) $categorycontextid, IGNORE_MISSING);
                if (
                    !$categorycontext
                        || !$targetcontext->is_parent_of($categorycontext, true)
                        || $sourcecontext->is_parent_of($categorycontext, true)
                ) {
                    $details = json_encode([
                        'categoryid' => $categoryid,
                        'categorycontextpath' => $categorycontext->path ?? null,
                        'sourcecontextpath' => $sourcecontext->path,
                        'targetcontextpath' => $targetcontext->path,
                        'filtercondition' => $reference->filtercondition,
                    ], JSON_UNESCAPED_SLASHES);
                    throw new ExpectationException(
                        'The imported random question filter contains a category outside the target course: ' . $details,
                        $this->getSession(),
                    );
                }
            }
        }

        if (!$operation || $operation->status !== operation_repository::STATUS_COMPLETE) {
            throw new ExpectationException(
                'The random question-bank copy operation did not finish successfully.',
                $this->getSession(),
            );
        }
    }

    /**
     * Returns all question-bank entry IDs owned by a course context hierarchy.
     *
     * @param stdClass $course Course record.
     * @return int[]
     */
    private function get_course_question_bank_entries(stdClass $course): array {
        global $DB;

        $coursecontext = context_course::instance($course->id);
        $pathlike = $DB->sql_like('ctx.path', ':contextpath');
        $sql = "SELECT qbe.id
                  FROM {question_bank_entries} qbe
                  JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
                  JOIN {context} ctx ON ctx.id = qc.contextid
                 WHERE {$pathlike}";
        $entries = $DB->get_fieldset_sql($sql, [
            'contextpath' => $coursecontext->path . '/%',
        ]);

        return array_map('intval', $entries);
    }

    /**
     * Returns the fixed question-bank entry IDs used by a quiz.
     *
     * @param stdClass $course Course record.
     * @param string $quizname Quiz name.
     * @return int[]
     */
    private function get_quiz_question_bank_entries(stdClass $course, string $quizname): array {
        global $DB;

        $quiz = $DB->get_record('quiz', [
            'course' => $course->id,
            'name' => $quizname,
        ], '*', MUST_EXIST);
        $coursemodule = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
        $quizcontext = context_module::instance($coursemodule->id);
        $entries = $DB->get_fieldset_select(
            'question_references',
            'questionbankentryid',
            'usingcontextid = :contextid AND component = :component',
            [
                'contextid' => $quizcontext->id,
                'component' => 'mod_quiz',
            ],
        );

        return array_map('intval', $entries);
    }

    /**
     * Returns the random-question references used by a quiz.
     *
     * @param stdClass $course Course record.
     * @param string $quizname Quiz name.
     * @return stdClass[]
     */
    private function get_quiz_question_set_references(stdClass $course, string $quizname): array {
        global $DB;

        $quiz = $DB->get_record('quiz', [
            'course' => $course->id,
            'name' => $quizname,
        ], '*', MUST_EXIST);
        $coursemodule = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
        $quizcontext = context_module::instance($coursemodule->id);

        return $DB->get_records('question_set_references', [
            'usingcontextid' => $quizcontext->id,
            'component' => 'mod_quiz',
        ]);
    }

    /**
     * Reads all category IDs supported by the current and legacy random filter structures.
     *
     * @param stdClass $reference Random-question reference.
     * @return int[]
     */
    private function get_reference_category_ids(stdClass $reference): array {
        $condition = json_decode($reference->filtercondition, true);
        if (!is_array($condition)) {
            return [];
        }

        $categoryids = [];
        foreach ($condition['filter']['category']['values'] ?? [] as $categoryid) {
            $categoryids[] = (int) $categoryid;
        }
        if (!empty($condition['cat'])) {
            $categoryids[] = (int) explode(',', (string) $condition['cat'])[0];
        }

        return array_values(array_unique(array_filter($categoryids)));
    }
}
