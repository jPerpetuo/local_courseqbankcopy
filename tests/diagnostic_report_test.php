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
 * Tests for the read-only diagnostic report.
 *
 * @package    local_courseqbankcopy
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_courseqbankcopy;

use advanced_testcase;
use context_course;
use context_module;
use local_courseqbankcopy\local\diagnostic_report;
use local_courseqbankcopy\local\operation_repository;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests the diagnostic report service.
 */
#[CoversClass(diagnostic_report::class)]
final class diagnostic_report_test extends advanced_testcase {
    /**
     * The report distinguishes internal and external random references.
     */
    public function test_build_identifies_external_random_references(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $sourcecourse = $generator->create_course([
            'fullname' => 'Source course',
            'shortname' => 'SOURCE',
        ]);
        $targetcourse = $generator->create_course([
            'fullname' => 'Target course',
            'shortname' => 'TARGET',
        ]);
        $targetquiz = $generator->create_module('quiz', [
            'course' => $targetcourse->id,
            'name' => 'Diagnostic quiz',
        ]);

        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $sourcecategory = $questiongenerator->create_question_category([
            'contextid' => context_course::instance($sourcecourse->id)->id,
            'name' => 'Source category',
        ]);
        $targetcategory = $questiongenerator->create_question_category([
            'contextid' => context_course::instance($targetcourse->id)->id,
            'name' => 'Target category',
        ]);

        $restoreid = str_repeat('a', 32);
        operation_repository::create(
            $restoreid,
            $sourcecourse->id,
            $targetcourse->id,
            str_repeat('b', 32),
        );
        operation_repository::upsert_mapping(
            $restoreid,
            operation_repository::TYPE_CATEGORY,
            $sourcecategory->id,
            $targetcategory->id,
        );
        operation_repository::set_status($restoreid, operation_repository::STATUS_COMPLETE);

        $quizcontext = context_module::instance($targetquiz->cmid);
        $this->insert_random_reference(
            $quizcontext->id,
            1,
            $targetcategory->contextid,
            $targetcategory->id,
        );
        $this->insert_random_reference(
            $quizcontext->id,
            2,
            $sourcecategory->contextid,
            $sourcecategory->id,
        );

        $report = (new diagnostic_report())->build($targetcourse->id);

        $this->assertSame($targetcourse->id, $report['course']['id']);
        $this->assertSame(1, $report['summary']['operations']);
        $this->assertGreaterThanOrEqual(1, $report['summary']['questionbanks']);
        $this->assertGreaterThanOrEqual(1, $report['summary']['categories']);
        $this->assertSame(2, $report['summary']['randomreferences']);
        $this->assertSame(1, $report['summary']['externalrandomreferences']);
        $this->assertSame('complete', $report['operations'][0]['status']);
        $this->assertSame($targetcategory->id, $report['operations'][0]['mappings'][0]['newid']);

        $references = array_column($report['randomreferences'], null, 'slotid');
        $this->assertSame('independent', $references[1]['status']);
        $this->assertSame($targetcourse->id, $references[1]['questionscontext']['ownercourseid']);
        $this->assertSame('external', $references[2]['status']);
        $this->assertSame($sourcecourse->id, $references[2]['questionscontext']['ownercourseid']);
    }

    /**
     * Inserts one random-question reference for a quiz slot.
     *
     * @param int $usingcontextid Quiz module context ID.
     * @param int $itemid Quiz slot ID.
     * @param int $questionscontextid Question-bank context ID.
     * @param int $categoryid Question category ID.
     */
    private function insert_random_reference(
        int $usingcontextid,
        int $itemid,
        int $questionscontextid,
        int $categoryid,
    ): void {
        global $DB;

        $DB->insert_record('question_set_references', (object) [
            'usingcontextid' => $usingcontextid,
            'component' => 'mod_quiz',
            'questionarea' => 'slot',
            'itemid' => $itemid,
            'questionscontextid' => $questionscontextid,
            'filtercondition' => json_encode([
                'filter' => [
                    'category' => ['values' => [$categoryid]],
                ],
                'cat' => $categoryid . ',' . $questionscontextid,
            ], JSON_THROW_ON_ERROR),
        ]);
    }
}
