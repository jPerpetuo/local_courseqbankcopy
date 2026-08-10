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
 * Question reference reconciliation service.
 *
 * @package    local_courseqbankcopy
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_courseqbankcopy\local;

/**
 * Repoints imported activities to the copied question bank.
 */
final class reference_reconciler {
    /**
     * Reconciles and validates one completed restore.
     *
     * @param string $restoreid Restore ID.
     * @return array{fixed:int,random:int}
     */
    public function reconcile(string $restoreid): array {
        global $DB;

        $operation = operation_repository::get($restoreid);
        if (!$operation) {
            return ['fixed' => 0, 'random' => 0];
        }

        operation_repository::set_status($restoreid, operation_repository::STATUS_RECONCILING);
        $this->resolve_pending_categories($operation);

        $modulemappings = operation_repository::get_mappings($restoreid, operation_repository::TYPE_MODULE);
        $qbemappings = operation_repository::get_mappings($restoreid, operation_repository::TYPE_QBE);
        $categorymappings = operation_repository::get_mappings($restoreid, operation_repository::TYPE_CATEGORY);
        $usingcontextids = $this->get_imported_context_ids($modulemappings);

        if (!$usingcontextids) {
            operation_repository::set_status($restoreid, operation_repository::STATUS_COMPLETE);
            return ['fixed' => 0, 'random' => 0];
        }

        $transaction = $DB->start_delegated_transaction();
        $fixedcount = $this->repoint_fixed_references($usingcontextids, $qbemappings);
        $randomcount = $this->repoint_random_references($usingcontextids, $categorymappings);
        $this->validate_independence($operation, $usingcontextids, $qbemappings, $categorymappings);
        $transaction->allow_commit();

        operation_repository::set_status($restoreid, operation_repository::STATUS_COMPLETE);
        return ['fixed' => $fixedcount, 'random' => $randomcount];
    }

    /**
     * Resolves empty categories by their transformed stamp.
     *
     * @param \stdClass $operation Operation record.
     */
    private function resolve_pending_categories(\stdClass $operation): void {
        global $DB;

        $coursecontext = \context_course::instance($operation->targetcourseid);
        foreach (operation_repository::get_mappings($operation->restoreid, operation_repository::TYPE_CATEGORY) as $mapping) {
            if ($mapping->newid || !$mapping->marker) {
                continue;
            }
            $like = $DB->sql_like('ctx.path', ':contextpath');
            $record = $DB->get_record_sql(
                "SELECT qc.id, qc.contextid
                   FROM {question_categories} qc
                   JOIN {context} ctx ON ctx.id = qc.contextid
                  WHERE qc.stamp = :marker AND {$like}",
                [
                    'marker' => $mapping->marker,
                    'contextpath' => $coursecontext->path . '/%',
                ],
                IGNORE_MULTIPLE,
            );
            if ($record) {
                operation_repository::upsert_mapping(
                    $operation->restoreid,
                    operation_repository::TYPE_CATEGORY,
                    (int) $mapping->oldid,
                    (int) $record->id,
                    (int) $mapping->oldparentid,
                    (int) $record->contextid,
                    $mapping->marker,
                );
            }
        }
    }

    /**
     * Returns the contexts of imported modules.
     *
     * @param \stdClass[] $modulemappings Module mappings.
     * @return int[]
     */
    private function get_imported_context_ids(array $modulemappings): array {
        $contextids = [];
        foreach ($modulemappings as $mapping) {
            if (!$mapping->newid) {
                continue;
            }
            $context = \context_module::instance((int) $mapping->newid, IGNORE_MISSING);
            if ($context) {
                $contextids[] = (int) $context->id;
            }
        }
        return array_values(array_unique($contextids));
    }

    /**
     * Repoints fixed question references.
     *
     * @param int[] $contextids Imported module context IDs.
     * @param \stdClass[] $qbemappings QBE mappings.
     * @return int Number of updated records.
     */
    private function repoint_fixed_references(array $contextids, array $qbemappings): int {
        global $DB;

        [$contextsql, $contextparams] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'ctx');
        $count = 0;
        foreach ($qbemappings as $mapping) {
            if (!$mapping->newid || $mapping->oldid == $mapping->newid) {
                continue;
            }
            $params = $contextparams + ['oldqbe' => $mapping->oldid];
            $select = "usingcontextid {$contextsql} AND questionbankentryid = :oldqbe";
            $count += $DB->count_records_select('question_references', $select, $params);
            $DB->set_field_select(
                'question_references',
                'questionbankentryid',
                $mapping->newid,
                $select,
                $params,
            );
        }
        return $count;
    }

    /**
     * Repoints random-question category references.
     *
     * @param int[] $contextids Imported module context IDs.
     * @param \stdClass[] $categorymappings Category mappings.
     * @return int Number of updated records.
     */
    private function repoint_random_references(array $contextids, array $categorymappings): int {
        global $DB;

        $categorymap = [];
        $categorymapbynewid = [];
        foreach ($categorymappings as $mapping) {
            if ($mapping->newid && $mapping->newparentid) {
                $categorymap[(int) $mapping->oldid] = $mapping;
                $categorymapbynewid[(int) $mapping->newid] = $mapping;
            }
        }

        [$contextsql, $params] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'ctx');
        $references = $DB->get_records_select('question_set_references', "usingcontextid {$contextsql}", $params);
        $count = 0;
        foreach ($references as $reference) {
            $condition = json_decode($reference->filtercondition, true);
            if (!is_array($condition)) {
                continue;
            }

            $oldcategoryid = $this->get_filter_category_id($condition);
            if (!$oldcategoryid) {
                continue;
            }

            $mapping = $categorymap[$oldcategoryid] ?? $categorymapbynewid[$oldcategoryid] ?? null;
            if (!$mapping) {
                continue;
            }
            $this->set_filter_category($condition, (int) $mapping->newid, (int) $mapping->newparentid);
            $reference->questionscontextid = (int) $mapping->newparentid;
            $reference->filtercondition = json_encode($condition);
            $DB->update_record('question_set_references', $reference);
            $count++;
        }
        return $count;
    }

    /**
     * Reads the category ID from current and legacy filter structures.
     *
     * @param array $condition Filter condition.
     * @return int
     */
    private function get_filter_category_id(array $condition): int {
        if (isset($condition['filter']['category']['values'][0])) {
            return (int) $condition['filter']['category']['values'][0];
        }
        if (!empty($condition['cat'])) {
            return (int) explode(',', (string) $condition['cat'])[0];
        }
        return 0;
    }

    /**
     * Writes a mapped category and context into a random-question filter.
     *
     * @param array $condition Filter condition.
     * @param int $categoryid Destination category ID.
     * @param int $contextid Destination question-bank context ID.
     */
    private function set_filter_category(array &$condition, int $categoryid, int $contextid): void {
        if (isset($condition['filter']['category']['values'][0])) {
            $condition['filter']['category']['values'][0] = $categoryid;
        }
        $condition['cat'] = $categoryid . ',' . $contextid;
    }

    /**
     * Ensures imported activities no longer reference source question-bank IDs.
     *
     * @param \stdClass $operation Operation record.
     * @param int[] $contextids Imported context IDs.
     * @param \stdClass[] $qbemappings QBE mappings.
     * @param \stdClass[] $categorymappings Category mappings.
     */
    private function validate_independence(
        \stdClass $operation,
        array $contextids,
        array $qbemappings,
        array $categorymappings
    ): void {
        global $DB;

        $sourceqbeids = array_values(array_filter(array_map(
            static fn(\stdClass $mapping): int => (int) $mapping->oldid,
            $qbemappings,
        )));
        [$contextsql, $contextparams] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'ctx');
        if ($sourceqbeids) {
            [$qbesql, $qbeparams] = $DB->get_in_or_equal($sourceqbeids, SQL_PARAMS_NAMED, 'qbe');
            $remaining = $DB->count_records_select(
                'question_references',
                "usingcontextid {$contextsql} AND questionbankentryid {$qbesql}",
                $contextparams + $qbeparams,
            );
            if ($remaining) {
                throw new \moodle_exception('independencevalidationfailed', 'local_courseqbankcopy', '', $remaining);
            }
        }

        $coursecontext = \context_course::instance($operation->targetcourseid);
        foreach ($categorymappings as $mapping) {
            if (!$mapping->newid) {
                throw new \moodle_exception('categorymappingmissing', 'local_courseqbankcopy', '', $mapping->oldid);
            }
            $context = \context::instance_by_id((int) $mapping->newparentid, IGNORE_MISSING);
            if (!$context || !str_starts_with($context->path . '/', $coursecontext->path . '/')) {
                throw new \moodle_exception('categoryoutsidecourse', 'local_courseqbankcopy', '', $mapping->newid);
            }
        }

        $sourcecategoryids = array_values(array_filter(array_map(
            static fn(\stdClass $mapping): int => (int) $mapping->oldid,
            $categorymappings,
        )));
        $sourcecontextids = array_values(array_unique(array_filter(array_map(
            static fn(\stdClass $mapping): int => (int) $mapping->oldparentid,
            $categorymappings,
        ))));
        $setreferences = $DB->get_records_select(
            'question_set_references',
            "usingcontextid {$contextsql}",
            $contextparams,
        );
        foreach ($setreferences as $reference) {
            $condition = json_decode($reference->filtercondition, true);
            $categoryid = is_array($condition) ? $this->get_filter_category_id($condition) : 0;
            if (
                in_array((int) $reference->questionscontextid, $sourcecontextids, true)
                    || in_array($categoryid, $sourcecategoryids, true)
            ) {
                throw new \moodle_exception('randomreferencevalidationfailed', 'local_courseqbankcopy');
            }
        }
    }
}
