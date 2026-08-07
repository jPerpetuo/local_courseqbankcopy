<?php
// This file is part of Moodle - https://moodle.org/.

namespace local_courseqbankcopy;

use advanced_testcase;
use local_courseqbankcopy\local\backup_package_transformer;
use local_courseqbankcopy\local\operation_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the temporary backup package transformer.
 *
 * @covers \local_courseqbankcopy\local\backup_package_transformer
 */
final class backup_package_transformer_test extends advanced_testcase {
    /**
     * Category markers are deterministic inside one operation and change between operations.
     */
    public function test_category_marker_is_scoped_to_operation(): void {
        $first = backup_package_transformer::category_marker('token-a', 10, 'original');
        $same = backup_package_transformer::category_marker('token-a', 10, 'original');
        $other = backup_package_transformer::category_marker('token-b', 10, 'original');

        $this->assertSame($first, $same);
        $this->assertNotSame($first, $other);
        $this->assertStringStartsWith('cqbc_', $first);
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
            $DB->count_records('local_cqbc_mapping', [
                'restoreid' => $restoreid,
                'itemtype' => operation_repository::TYPE_CATEGORY,
            ]),
        );
    }
}
