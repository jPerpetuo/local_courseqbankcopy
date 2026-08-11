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
 * Tests for the backup package transformer.
 *
 * @package    local_courseqbankcopy
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_courseqbankcopy;

use advanced_testcase;
use local_courseqbankcopy\local\backup_package_transformer;
use local_courseqbankcopy\local\operation_repository;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests the temporary backup package transformer.
 *
 */
#[CoversClass(backup_package_transformer::class)]
final class backup_package_transformer_test extends advanced_testcase {
    /**
     * Category markers are deterministic inside one operation and change between operations.
     */
    public function test_category_marker_is_scoped_to_operation(): void {
        $first = backup_package_transformer::category_marker('token-a', 10, 'original');
        $same = backup_package_transformer::category_marker('token-a', 10, 'original');
        $other = backup_package_transformer::category_marker('token-b', 10, 'original');
        $module = backup_package_transformer::module_marker('token-a', 20);

        $this->assertSame($first, $same);
        $this->assertNotSame($first, $other);
        $this->assertStringStartsWith('cqbc_', $first);
        $this->assertStringStartsWith('cqbc_module_', $module);
        $this->assertLessThanOrEqual(64, strlen($module));
    }

    /**
     * The transformer gives qbank activities a temporary identity and persists it.
     */
    public function test_transform_marks_question_bank_modules(): void {
        global $DB;

        $this->resetAfterTest();
        $source = $this->getDataGenerator()->create_course();
        $target = $this->getDataGenerator()->create_course();
        $restoreid = str_repeat('c', 32);
        $token = str_repeat('d', 32);
        operation_repository::create($restoreid, $source->id, $target->id, $token);

        $tempdir = make_request_directory();
        $activitydir = $tempdir . '/activities/qbank_456';
        mkdir($tempdir . '/activities');
        mkdir($activitydir);
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="99" moduleid="456" modulename="qbank">
  <qbank id="77">
    <name>Banco de questões original</name>
    <type>standard</type>
  </qbank>
</activity>
XML;
        file_put_contents($activitydir . '/qbank.xml', $xml);

        $result = (new backup_package_transformer())->transform($tempdir, $restoreid, $token);
        $transformed = simplexml_load_file($activitydir . '/qbank.xml');
        $mapping = $DB->get_record('local_courseqbankcopy_map', [
            'restoreid' => $restoreid,
            'itemtype' => operation_repository::TYPE_MODULE,
            'oldid' => 456,
        ], '*', MUST_EXIST);

        $this->assertSame(['categories' => 0, 'questions' => 0], $result);
        $this->assertSame(backup_package_transformer::module_marker($token, 456), (string) $transformed->qbank->name);
        $this->assertSame($mapping->marker, (string) $transformed->qbank->name);
        $this->assertEquals(0, $mapping->newid);
    }

    /**
     * The transformer gives imported quizzes a temporary identity and persists it.
     */
    public function test_transform_marks_quiz_modules(): void {
        global $DB;

        $this->resetAfterTest();
        $source = $this->getDataGenerator()->create_course();
        $target = $this->getDataGenerator()->create_course();
        $restoreid = str_repeat('e', 32);
        $token = str_repeat('f', 32);
        operation_repository::create($restoreid, $source->id, $target->id, $token);

        $tempdir = make_request_directory();
        $activitydir = $tempdir . '/activities/quiz_789';
        mkdir($tempdir . '/activities');
        mkdir($activitydir);
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="100" moduleid="789" modulename="quiz">
  <quiz id="88">
    <name>Questionário original</name>
  </quiz>
</activity>
XML;
        file_put_contents($activitydir . '/quiz.xml', $xml);

        $result = (new backup_package_transformer())->transform($tempdir, $restoreid, $token);
        $transformed = simplexml_load_file($activitydir . '/quiz.xml');
        $mapping = $DB->get_record('local_courseqbankcopy_map', [
            'restoreid' => $restoreid,
            'itemtype' => operation_repository::TYPE_MODULE,
            'oldid' => 789,
        ], '*', MUST_EXIST);

        $this->assertSame(['categories' => 0, 'questions' => 0], $result);
        $this->assertSame(backup_package_transformer::module_marker($token, 789), (string) $transformed->quiz->name);
        $this->assertSame($mapping->marker, (string) $transformed->quiz->name);
        $this->assertEquals(0, $mapping->newid);
    }

    /**
     * The transformer replaces category stamps and counts questions without loading the full XML.
     */
    public function test_transform_replaces_category_stamps(): void {
        global $DB;

        $this->resetAfterTest();
        $source = $this->getDataGenerator()->create_course();
        $target = $this->getDataGenerator()->create_course();
        $restoreid = str_repeat('a', 32);
        operation_repository::create($restoreid, $source->id, $target->id, str_repeat('b', 32));

        $tempdir = make_request_directory();
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<question_categories>
  <question_category id="10">
    <stamp>stamp-one</stamp>
    <questions><question id="101"></question></questions>
  </question_category>
  <question_category id="20">
    <stamp>stamp-two</stamp>
    <questions><question id="201"></question><question id="202"></question></questions>
  </question_category>
</question_categories>
XML;
        file_put_contents($tempdir . '/questions.xml', $xml);

        $result = (new backup_package_transformer())->transform(
            $tempdir,
            $restoreid,
            str_repeat('b', 32),
        );

        $transformed = file_get_contents($tempdir . '/questions.xml');
        $this->assertSame(2, $result['categories']);
        $this->assertSame(2, $result['questions']);
        $this->assertStringNotContainsString('stamp-one', $transformed);
        $this->assertStringNotContainsString('stamp-two', $transformed);
        $this->assertSame(
            2,
            $DB->count_records('local_courseqbankcopy_map', [
                'restoreid' => $restoreid,
                'itemtype' => operation_repository::TYPE_CATEGORY,
            ]),
        );
    }
}
