<?php
// This file is part of Moodle - https://moodle.org/.

namespace local_courseqbankcopy\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Persists an import operation and the old-to-new ID mappings needed after restore.
 */
final class operation_repository {
    public const STATUS_PREPARED = 'prepared';
    public const STATUS_RESTORING = 'restoring';
    public const STATUS_RECONCILING = 'reconciling';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_FAILED = 'failed';

    public const TYPE_CATEGORY = 'category';
    public const TYPE_QBE = 'qbankentry';
    public const TYPE_MODULE = 'module';

    /**
     * Creates or resets an operation.
     *
     * @param string $restoreid Backup/restore controller ID.
     * @param int $sourcecourseid Source course ID.
     * @param int $targetcourseid Target course ID.
     * @param string $token Random operation token.
     * @return \stdClass
     */
    public static function create(
        string $restoreid,
        int $sourcecourseid,
        int $targetcourseid,
        string $token
    ): \stdClass {
        global $DB;

        $now = time();
        $record = $DB->get_record('local_cqbc_operation', ['restoreid' => $restoreid]);
        if ($record) {
            $record->sourcecourseid = $sourcecourseid;
            $record->targetcourseid = $targetcourseid;
            $record->token = $token;
            $record->status = self::STATUS_PREPARED;
            $record->categorycount = 0;
            $record->questioncount = 0;
            $record->lasterror = null;
            $record->timemodified = $now;
            $DB->update_record('local_cqbc_operation', $record);
            $DB->delete_records('local_cqbc_mapping', ['restoreid' => $restoreid]);
            return $record;
        }

        $record = (object) [
            'restoreid' => $restoreid,
            'sourcecourseid' => $sourcecourseid,
            'targetcourseid' => $targetcourseid,
            'token' => $token,
            'status' => self::STATUS_PREPARED,
            'categorycount' => 0,
            'questioncount' => 0,
            'lasterror' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $record->id = $DB->insert_record('local_cqbc_operation', $record);
        return $record;
    }

    /**
     * Returns an operation by restore ID.
     *
     * @param string $restoreid Restore ID.
     * @return \stdClass|null
     */
    public static function get(string $restoreid): ?\stdClass {
        global $DB;

        return $DB->get_record('local_cqbc_operation', ['restoreid' => $restoreid]) ?: null;
    }

    /**
     * Finds the newest active operation for an import.
     *
     * @param int $sourcecourseid Source course ID.
     * @param int $targetcourseid Target course ID.
     * @return \stdClass|null
     */
    public static function find_active(int $sourcecourseid, int $targetcourseid): ?\stdClass {
        global $DB;

        $params = [
            'sourcecourseid' => $sourcecourseid,
            'targetcourseid' => $targetcourseid,
            'prepared' => self::STATUS_PREPARED,
            'restoring' => self::STATUS_RESTORING,
        ];
        $sql = "sourcecourseid = :sourcecourseid
                  AND targetcourseid = :targetcourseid
                  AND (status = :prepared OR status = :restoring)";
        $records = $DB->get_records_select(
            'local_cqbc_operation',
            $sql,
            $params,
            'timemodified DESC',
            '*',
            0,
            1,
        );
        return $records ? reset($records) : null;
    }

    /**
     * Updates the operation status and counters.
     *
     * @param string $restoreid Restore ID.
     * @param string $status New status.
     * @param string|null $error Error message.
     * @param int|null $categorycount Number of transformed categories.
     * @param int|null $questioncount Number of questions in the package.
     */
    public static function set_status(
        string $restoreid,
        string $status,
        ?string $error = null,
        ?int $categorycount = null,
        ?int $questioncount = null
    ): void {
        global $DB;

        $record = (object) [
            'id' => $DB->get_field('local_cqbc_operation', 'id', ['restoreid' => $restoreid], MUST_EXIST),
            'status' => $status,
            'lasterror' => $error,
            'timemodified' => time(),
        ];
        if ($categorycount !== null) {
            $record->categorycount = $categorycount;
        }
        if ($questioncount !== null) {
            $record->questioncount = $questioncount;
        }
        $DB->update_record('local_cqbc_operation', $record);
    }

    /**
     * Inserts or updates an ID mapping.
     *
     * @param string $restoreid Restore ID.
     * @param string $itemtype Mapping type.
     * @param int $oldid Source ID.
     * @param int $newid Destination ID, or zero while pending.
     * @param int $oldparentid Source parent/context ID.
     * @param int $newparentid Destination parent/context ID.
     * @param string|null $marker Temporary marker.
     */
    public static function upsert_mapping(
        string $restoreid,
        string $itemtype,
        int $oldid,
        int $newid = 0,
        int $oldparentid = 0,
        int $newparentid = 0,
        ?string $marker = null
    ): void {
        global $DB;

        $conditions = ['restoreid' => $restoreid, 'itemtype' => $itemtype, 'oldid' => $oldid];
        $now = time();
        $existing = $DB->get_record('local_cqbc_mapping', $conditions);
        if ($existing) {
            $existing->newid = $newid ?: $existing->newid;
            $existing->oldparentid = $oldparentid ?: $existing->oldparentid;
            $existing->newparentid = $newparentid ?: $existing->newparentid;
            $existing->marker = $marker ?? $existing->marker;
            $existing->timemodified = $now;
            $DB->update_record('local_cqbc_mapping', $existing);
            return;
        }

        $DB->insert_record('local_cqbc_mapping', (object) [
            'restoreid' => $restoreid,
            'itemtype' => $itemtype,
            'oldid' => $oldid,
            'newid' => $newid,
            'oldparentid' => $oldparentid,
            'newparentid' => $newparentid,
            'marker' => $marker,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * Returns mappings indexed by old ID.
     *
     * @param string $restoreid Restore ID.
     * @param string $itemtype Mapping type.
     * @return \stdClass[]
     */
    public static function get_mappings(string $restoreid, string $itemtype): array {
        global $DB;

        return $DB->get_records(
            'local_cqbc_mapping',
            ['restoreid' => $restoreid, 'itemtype' => $itemtype],
            '',
            '*',
        );
    }
}
