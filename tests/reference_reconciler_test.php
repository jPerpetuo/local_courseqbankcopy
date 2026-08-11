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
 * Tests for the question reference reconciliation service.
 *
 * @package    local_courseqbankcopy
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_courseqbankcopy;

use advanced_testcase;
use context_course;
use context_module;
use local_courseqbankcopy\local\operation_repository;
use local_courseqbankcopy\local\reference_reconciler;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests the question reference reconciliation service.
 */
#[CoversClass(reference_reconciler::class)]
final class reference_reconciler_test extends advanced_testcase {
    /**
     * Fixed references are moved from the source entry to its destination copy.
     */
    public function test_reconcile_repoints_fixed_question_reference(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $sourcecourse = $generator->create_course();
        $targetcourse = $generator->create_course();
        $targetquiz = $generator->create_module('quiz', ['course' => $targetcourse->id]);

        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $sourcecategory = $questiongenerator->create_question_category([
            'contextid' => context_course::instance($sourcecourse->id)->id,
            'name' => 'Random bank category',
        ]);
        $targetcategory = $questiongenerator->create_question_category([
            'contextid' => context_course::instance($targetcourse->id)->id,
            'name' => 'Random bank category',
        ]);
        $sourcebankcontext = \context::instance_by_id($sourcecategory->contextid);
        $targetbankcontext = \context::instance_by_id($targetcategory->contextid);
        $sourcetopcategory = $DB->get_record(
            'question_categories',
            ['id' => $sourcecategory->parent],
            '*',
            MUST_EXIST,
        );
        $sourcequestion = $questiongenerator->create_question('truefalse', null, [
            'category' => $sourcecategory->id,
        ]);
        $targetquestion = $questiongenerator->create_question('truefalse', null, [
            'category' => $targetcategory->id,
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
            operation_repository::TYPE_MODULE,
            123,
            $targetquiz->cmid,
        );
        operation_repository::upsert_mapping(
            $restoreid,
            operation_repository::TYPE_QBE,
            $sourcequestion->questionbankentryid,
            $targetquestion->questionbankentryid,
        );

        $quizcontext = context_module::instance($targetquiz->cmid);
        $referenceid = $DB->insert_record('question_references', (object) [
            'usingcontextid' => $quizcontext->id,
            'component' => 'mod_quiz',
            'questionarea' => 'slot',
            'itemid' => 1,
            'questionbankentryid' => $sourcequestion->questionbankentryid,
            'version' => null,
        ]);

        $result = (new reference_reconciler())->reconcile($restoreid);
        $reference = $DB->get_record('question_references', ['id' => $referenceid], '*', MUST_EXIST);
        $operation = operation_repository::get($restoreid);

        $this->assertSame(['fixed' => 1, 'random' => 0], $result);
        $this->assertEquals($targetquestion->questionbankentryid, $reference->questionbankentryid);
        $this->assertFalse($DB->record_exists('question_references', [
            'usingcontextid' => $quizcontext->id,
            'questionbankentryid' => $sourcequestion->questionbankentryid,
        ]));
        $this->assertNotNull($operation);
        $this->assertSame(operation_repository::STATUS_COMPLETE, $operation->status);
    }

    /**
     * Random references are moved to the destination category and question-bank context.
     */
    public function test_reconcile_repoints_random_question_reference(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $sourcecourse = $generator->create_course();
        $targetcourse = $generator->create_course();
        $targetquiz = $generator->create_module('quiz', ['course' => $targetcourse->id]);

        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $sourcecategory = $questiongenerator->create_question_category([
            'contextid' => context_course::instance($sourcecourse->id)->id,
        ]);
        $targetcategory = $questiongenerator->create_question_category([
            'contextid' => context_course::instance($targetcourse->id)->id,
        ]);

        $restoreid = str_repeat('c', 32);
        operation_repository::create(
            $restoreid,
            $sourcecourse->id,
            $targetcourse->id,
            str_repeat('d', 32),
        );
        operation_repository::upsert_mapping(
            $restoreid,
            operation_repository::TYPE_MODULE,
            321,
            $targetquiz->cmid,
        );
        operation_repository::upsert_mapping(
            $restoreid,
            operation_repository::TYPE_MODULE,
            $sourcebankcontext->instanceid,
            $targetbankcontext->instanceid,
        );
        operation_repository::upsert_mapping(
            $restoreid,
            operation_repository::TYPE_CATEGORY,
            $sourcetopcategory->id,
        );
        operation_repository::upsert_mapping(
            $restoreid,
            operation_repository::TYPE_CATEGORY,
            $sourcecategory->id,
        );

        $quizcontext = context_module::instance($targetquiz->cmid);
        $referenceid = $DB->insert_record('question_set_references', (object) [
            'usingcontextid' => $quizcontext->id,
            'component' => 'mod_quiz',
            'questionarea' => 'slot',
            'itemid' => 1,
            'questionscontextid' => $sourcecategory->contextid,
            'filtercondition' => json_encode([
                'filter' => [
                    'category' => ['values' => [$sourcecategory->id]],
                ],
                'cat' => $sourcecategory->id . ',' . $sourcecategory->contextid,
            ], JSON_THROW_ON_ERROR),
        ]);

        $result = (new reference_reconciler())->reconcile($restoreid);
        $reference = $DB->get_record('question_set_references', ['id' => $referenceid], '*', MUST_EXIST);
        $condition = json_decode($reference->filtercondition, true, 512, JSON_THROW_ON_ERROR);
        $operation = operation_repository::get($restoreid);

        $this->assertSame(['fixed' => 0, 'random' => 1], $result);
        $this->assertEquals($targetcategory->contextid, $reference->questionscontextid);
        $this->assertEquals($targetcategory->id, $condition['filter']['category']['values'][0]);
        $this->assertSame(
            $targetcategory->id . ',' . $targetcategory->contextid,
            $condition['cat'],
        );
        $this->assertNotEquals($sourcecategory->contextid, $reference->questionscontextid);
        $this->assertNotNull($operation);
        $this->assertSame(operation_repository::STATUS_COMPLETE, $operation->status);
    }

    /**
     * Reconciliation fails when an imported activity still uses a source question-bank entry.
     */
    public function test_reconcile_rejects_remaining_source_reference(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $sourcecourse = $generator->create_course();
        $targetcourse = $generator->create_course();
        $targetquiz = $generator->create_module('quiz', ['course' => $targetcourse->id]);

        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $sourcecategory = $questiongenerator->create_question_category([
            'contextid' => context_course::instance($sourcecourse->id)->id,
        ]);
        $sourcequestion = $questiongenerator->create_question('truefalse', null, [
            'category' => $sourcecategory->id,
        ]);

        $restoreid = str_repeat('e', 32);
        operation_repository::create(
            $restoreid,
            $sourcecourse->id,
            $targetcourse->id,
            str_repeat('f', 32),
        );
        operation_repository::upsert_mapping(
            $restoreid,
            operation_repository::TYPE_MODULE,
            456,
            $targetquiz->cmid,
        );
        operation_repository::upsert_mapping(
            $restoreid,
            operation_repository::TYPE_QBE,
            $sourcequestion->questionbankentryid,
            $sourcequestion->questionbankentryid,
        );

        $quizcontext = context_module::instance($targetquiz->cmid);
        $referenceid = $DB->insert_record('question_references', (object) [
            'usingcontextid' => $quizcontext->id,
            'component' => 'mod_quiz',
            'questionarea' => 'slot',
            'itemid' => 2,
            'questionbankentryid' => $sourcequestion->questionbankentryid,
            'version' => null,
        ]);

        try {
            (new reference_reconciler())->reconcile($restoreid);
            $this->fail('The remaining source reference should have failed the independence validation.');
        } catch (\moodle_exception $exception) {
            $this->assertSame('independencevalidationfailed', $exception->errorcode);
        }

        $reference = $DB->get_record('question_references', ['id' => $referenceid], '*', MUST_EXIST);
        $operation = operation_repository::get($restoreid);

        $this->assertEquals($sourcequestion->questionbankentryid, $reference->questionbankentryid);
        $this->assertNotNull($operation);
        $this->assertNotSame(operation_repository::STATUS_COMPLETE, $operation->status);
    }
}
