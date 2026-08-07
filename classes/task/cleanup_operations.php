<?php
// This file is part of Moodle - https://moodle.org/.

namespace local_courseqbankcopy\task;

defined('MOODLE_INTERNAL') || die();

use local_courseqbankcopy\local\operation_repository;

/**
 * Removes completed or failed operation metadata after the diagnostic window.
 */
final class cleanup_operations extends \core\task\scheduled_task {
    /**
     * Returns the translated task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('taskcleanupoperations', 'local_courseqbankcopy');
    }

    /**
     * Deletes operation records older than 30 days.
     */
    public function execute(): void {
        global $DB;

        $cutoff = time() - (30 * DAYSECS);
        $params = [
            'cutoff' => $cutoff,
            'complete' => operation_repository::STATUS_COMPLETE,
            'failed' => operation_repository::STATUS_FAILED,
        ];
        $operations = $DB->get_records_select(
            'local_cqbc_operation',
            'timemodified < :cutoff AND (status = :complete OR status = :failed)',
            $params,
            '',
            'id, restoreid',
        );
        foreach ($operations as $operation) {
            $DB->delete_records('local_cqbc_mapping', ['restoreid' => $operation->restoreid]);
            $DB->delete_records('local_cqbc_operation', ['id' => $operation->id]);
        }
    }
}
