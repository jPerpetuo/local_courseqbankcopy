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
 * Read-only diagnostic report service.
 *
 * @package    local_courseqbankcopy
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_courseqbankcopy\local;

/**
 * Collects the data needed to diagnose question-bank import references.
 */
final class diagnostic_report {
    /** @var array<string, array<string, mixed>> Cached context descriptions. */
    private array $contextcache = [];

    /**
     * Builds a report for one target course without changing any data.
     *
     * @param int $courseid Course ID.
     * @return array<string, mixed>
     */
    public function build(int $courseid): array {
        global $CFG, $DB;

        $course = get_course($courseid);
        $coursecontext = \context_course::instance($courseid);
        $plugininfo = \core_plugin_manager::instance()->get_plugin_info('local_courseqbankcopy');
        $operations = $this->get_operations($courseid);
        $qbanks = $this->get_question_banks($courseid);
        $categories = $this->get_categories($coursecontext);
        $questionstatistics = $this->get_question_statistics($coursecontext);
        $quizzes = $this->get_quizzes_by_context($courseid);
        $fixedreferences = $this->get_fixed_references($quizzes, $coursecontext);
        $randomreferences = $this->get_random_references($quizzes, $coursecontext);
        $migrationtasks = $this->get_migration_tasks();

        $externalfixedreferences = $this->count_references_by_status($fixedreferences, 'external');
        $invalidfixedreferences = $this->count_references_by_status($fixedreferences, 'invalid');
        $externalrandomreferences = $this->count_references_by_status($randomreferences, 'external');
        $invalidrandomreferences = $this->count_references_by_status($randomreferences, 'invalid');

        return [
            'generatedat' => time(),
            'moodle' => [
                'release' => $CFG->release,
                'version' => (int) $CFG->version,
            ],
            'plugin' => [
                'release' => $plugininfo->release ?? null,
                'versiondisk' => isset($plugininfo->versiondisk) ? (int) $plugininfo->versiondisk : null,
                'versiondb' => isset($plugininfo->versiondb) ? (int) $plugininfo->versiondb : null,
            ],
            'course' => [
                'id' => (int) $course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
                'contextid' => (int) $coursecontext->id,
                'contextpath' => $coursecontext->path,
            ],
            'summary' => [
                'operations' => count($operations),
                'questionbanks' => count($qbanks),
                'categories' => count($categories),
                'mainquestions' => $questionstatistics['mainquestions'],
                'internalsubquestions' => $questionstatistics['internalsubquestions'],
                'internalquestionentries' => $questionstatistics['internalquestionentries'],
                'questionversions' => $questionstatistics['questionversions'],
                'fixedreferences' => count($fixedreferences),
                'independentfixedreferences' => $this->count_references_by_status(
                    $fixedreferences,
                    'independent',
                ),
                'externalfixedreferences' => $externalfixedreferences,
                'invalidfixedreferences' => $invalidfixedreferences,
                'randomreferences' => count($randomreferences),
                'independentrandomreferences' => $this->count_references_by_status(
                    $randomreferences,
                    'independent',
                ),
                'externalrandomreferences' => $externalrandomreferences,
                'invalidrandomreferences' => $invalidrandomreferences,
                'nonindependentreferences' => $externalfixedreferences + $invalidfixedreferences
                    + $externalrandomreferences + $invalidrandomreferences,
                'migrationtasks' => count($migrationtasks),
            ],
            'operations' => $operations,
            'questionbanks' => $qbanks,
            'categories' => $categories,
            'questionstatistics' => $questionstatistics,
            'fixedreferences' => $fixedreferences,
            'randomreferences' => $randomreferences,
            'migrationtasks' => $migrationtasks,
        ];
    }

    /**
     * Returns copy operations and their mappings.
     *
     * @param int $courseid Target course ID.
     * @return array<int, array<string, mixed>>
     */
    private function get_operations(int $courseid): array {
        global $DB;

        $records = $DB->get_records(
            'local_courseqbankcopy_ops',
            ['targetcourseid' => $courseid],
            'timemodified DESC',
        );
        $operations = [];
        foreach ($records as $record) {
            $sourcecourse = $DB->get_record(
                'course',
                ['id' => $record->sourcecourseid],
                'id,fullname,shortname',
            );
            $mappings = $DB->get_records(
                'local_courseqbankcopy_map',
                ['restoreid' => $record->restoreid],
                'itemtype,oldid',
                'id,itemtype,oldid,newid,oldparentid,newparentid,marker',
            );
            $operations[] = [
                'restoreid' => $record->restoreid,
                'sourcecourseid' => (int) $record->sourcecourseid,
                'sourcecourse' => $sourcecourse ? $sourcecourse->fullname : null,
                'sourceshortname' => $sourcecourse ? $sourcecourse->shortname : null,
                'status' => $record->status,
                'categorycount' => (int) $record->categorycount,
                'questioncount' => (int) $record->questioncount,
                'lasterror' => $record->lasterror,
                'timecreated' => (int) $record->timecreated,
                'timemodified' => (int) $record->timemodified,
                'mappings' => array_values(array_map(
                    static fn(\stdClass $mapping): array => [
                        'type' => $mapping->itemtype,
                        'oldid' => (int) $mapping->oldid,
                        'newid' => (int) $mapping->newid,
                        'oldparentid' => (int) $mapping->oldparentid,
                        'newparentid' => (int) $mapping->newparentid,
                        'marker' => $mapping->marker,
                    ],
                    $mappings,
                )),
            ];
        }
        return $operations;
    }

    /**
     * Returns question-bank modules in the course.
     *
     * @param int $courseid Course ID.
     * @return array<int, array<string, mixed>>
     */
    private function get_question_banks(int $courseid): array {
        global $DB;

        $sql = "SELECT cm.id AS cmid, qb.id AS qbankid, qb.name, qb.type, ctx.id AS contextid, ctx.path AS contextpath
                  FROM {course_modules} cm
                  JOIN {modules} md ON md.id = cm.module AND md.name = :modulename
                  JOIN {qbank} qb ON qb.id = cm.instance
                  JOIN {context} ctx ON ctx.contextlevel = :contextlevel AND ctx.instanceid = cm.id
                 WHERE cm.course = :courseid
              ORDER BY cm.id";
        $records = $DB->get_records_sql($sql, [
            'modulename' => 'qbank',
            'contextlevel' => CONTEXT_MODULE,
            'courseid' => $courseid,
        ]);
        return array_values(array_map(
            static fn(\stdClass $record): array => [
                'cmid' => (int) $record->cmid,
                'qbankid' => (int) $record->qbankid,
                'name' => $record->name,
                'type' => $record->type,
                'contextid' => (int) $record->contextid,
                'contextpath' => $record->contextpath,
            ],
            $records,
        ));
    }

    /**
     * Returns every question category owned by the course context hierarchy.
     *
     * @param \context_course $coursecontext Course context.
     * @return array<int, array<string, mixed>>
     */
    private function get_categories(\context_course $coursecontext): array {
        global $DB;

        $pathlike = $DB->sql_like('ctx.path', ':contextpath');
        $sql = "SELECT qc.id, qc.name, qc.parent, qc.contextid, qc.stamp, qc.idnumber,
                       qc.sortorder, ctx.contextlevel, ctx.instanceid, ctx.path AS contextpath,
                       COUNT(qbe.id) AS entrycount
                  FROM {question_categories} qc
                  JOIN {context} ctx ON ctx.id = qc.contextid
             LEFT JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = qc.id
                 WHERE ctx.id = :coursecontextid OR {$pathlike}
              GROUP BY qc.id, qc.name, qc.parent, qc.contextid, qc.stamp, qc.idnumber,
                       qc.sortorder, ctx.contextlevel, ctx.instanceid, ctx.path
              ORDER BY ctx.path, qc.parent, qc.sortorder, qc.id";
        $records = $DB->get_records_sql($sql, [
            'coursecontextid' => $coursecontext->id,
            'contextpath' => $coursecontext->path . '/%',
        ]);
        return array_values(array_map(
            static fn(\stdClass $record): array => [
                'id' => (int) $record->id,
                'name' => $record->name,
                'parent' => (int) $record->parent,
                'contextid' => (int) $record->contextid,
                'contextlevel' => (int) $record->contextlevel,
                'contextinstanceid' => (int) $record->instanceid,
                'contextpath' => $record->contextpath,
                'stamp' => $record->stamp,
                'idnumber' => $record->idnumber,
                'sortorder' => (int) $record->sortorder,
                'entrycount' => (int) $record->entrycount,
            ],
            $records,
        ));
    }

    /**
     * Counts user-facing questions separately from internal child questions and versions.
     *
     * Question types such as multianswer (Cloze) store one parent question and one
     * child question for each embedded answer field. The question bank hides those
     * child records, so diagnostic totals must report them separately.
     *
     * @param \context_course $coursecontext Course context.
     * @return array{mainquestions: int, internalsubquestions: int,
     *     internalquestionentries: int, questionversions: int}
     */
    private function get_question_statistics(\context_course $coursecontext): array {
        global $DB;

        $pathlike = $DB->sql_like('ctx.path', ':contextpath');
        $sql = "SELECT COUNT(DISTINCT CASE WHEN q.parent = 0 THEN qbe.id END) AS mainquestions,
                       COUNT(DISTINCT CASE WHEN q.parent <> 0 THEN qbe.id END) AS internalsubquestions,
                       COUNT(DISTINCT qbe.id) AS internalquestionentries,
                       COUNT(DISTINCT qv.id) AS questionversions
                  FROM {question_categories} qc
                  JOIN {context} ctx ON ctx.id = qc.contextid
             LEFT JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = qc.id
             LEFT JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
             LEFT JOIN {question} q ON q.id = qv.questionid
                 WHERE ctx.id = :coursecontextid OR {$pathlike}";
        $record = $DB->get_record_sql($sql, [
            'coursecontextid' => $coursecontext->id,
            'contextpath' => $coursecontext->path . '/%',
        ], MUST_EXIST);

        return [
            'mainquestions' => (int) $record->mainquestions,
            'internalsubquestions' => (int) $record->internalsubquestions,
            'internalquestionentries' => (int) $record->internalquestionentries,
            'questionversions' => (int) $record->questionversions,
        ];
    }

    /**
     * Returns quizzes from the target course indexed by module context ID.
     *
     * @param int $courseid Course ID.
     * @return array<int, \stdClass>
     */
    private function get_quizzes_by_context(int $courseid): array {
        global $DB;

        $sql = "SELECT ctx.id AS contextid, cm.id AS cmid, q.id AS quizid, q.name
                  FROM {course_modules} cm
                  JOIN {modules} md ON md.id = cm.module AND md.name = :modulename
                  JOIN {quiz} q ON q.id = cm.instance
                  JOIN {context} ctx ON ctx.contextlevel = :contextlevel AND ctx.instanceid = cm.id
                 WHERE cm.course = :courseid";
        $records = $DB->get_records_sql($sql, [
            'modulename' => 'quiz',
            'contextlevel' => CONTEXT_MODULE,
            'courseid' => $courseid,
        ]);

        $quizbycontext = [];
        foreach ($records as $quiz) {
            $quizbycontext[(int) $quiz->contextid] = $quiz;
        }
        return $quizbycontext;
    }

    /**
     * Returns fixed-question references from every quiz in the target course.
     *
     * @param array $quizbycontext Quizzes indexed by module context ID.
     * @param \context_course $coursecontext Course context.
     * @return array<int, array<string, mixed>>
     */
    private function get_fixed_references(array $quizbycontext, \context_course $coursecontext): array {
        global $DB;

        if (!$quizbycontext) {
            return [];
        }

        [$contextsql, $params] = $DB->get_in_or_equal(
            array_keys($quizbycontext),
            SQL_PARAMS_NAMED,
            'fixedcontext',
        );
        $params['component'] = 'mod_quiz';
        $params['questionarea'] = 'slot';
        $sql = "SELECT qr.id AS referenceid, qr.usingcontextid, qr.itemid,
                       qr.questionbankentryid, qbe.id AS qbeid,
                       qc.id AS categoryid, qc.name AS categoryname,
                       qc.parent AS categoryparent, qc.contextid AS categorycontextid,
                       qc.stamp AS categorystamp
                  FROM {question_references} qr
             LEFT JOIN {question_bank_entries} qbe ON qbe.id = qr.questionbankentryid
             LEFT JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
                 WHERE qr.usingcontextid {$contextsql}
                       AND qr.component = :component
                       AND qr.questionarea = :questionarea
              ORDER BY qr.usingcontextid, qr.itemid, qr.id";
        $records = $DB->get_records_sql($sql, $params);

        $details = [];
        foreach ($records as $record) {
            $contextid = isset($record->categorycontextid) ? (int) $record->categorycontextid : 0;
            $questionscontext = $this->describe_context($contextid, $coursecontext);
            $categoryexists = isset($record->categoryid);
            $qbeexists = isset($record->qbeid);
            if (!$qbeexists || !$categoryexists || !$questionscontext['exists']) {
                $status = 'invalid';
            } else if (!$questionscontext['insidetargetcourse']) {
                $status = 'external';
            } else {
                $status = 'independent';
            }

            $categorydetails = $categoryexists ? [[
                'id' => (int) $record->categoryid,
                'exists' => true,
                'name' => $record->categoryname,
                'parent' => (int) $record->categoryparent,
                'contextid' => $contextid,
                'stamp' => $record->categorystamp,
                'context' => $questionscontext,
            ]] : [];
            $quiz = $quizbycontext[(int) $record->usingcontextid];
            $details[] = [
                'type' => 'fixed',
                'referenceid' => (int) $record->referenceid,
                'quizid' => (int) $quiz->quizid,
                'quizcmid' => (int) $quiz->cmid,
                'quizname' => $quiz->name,
                'slotid' => (int) $record->itemid,
                'usingcontextid' => (int) $record->usingcontextid,
                'questionbankentryid' => (int) $record->questionbankentryid,
                'questionbankentryexists' => $qbeexists,
                'questionscontextid' => $contextid,
                'questionscontext' => $questionscontext,
                'referencecontextid' => $contextid,
                'referencecontext' => $questionscontext,
                'categoryids' => $categoryexists ? [(int) $record->categoryid] : [],
                'categories' => $categorydetails,
                'status' => $status,
            ];
        }
        return $details;
    }

    /**
     * Returns random references from every quiz in the target course.
     *
     * @param array $quizbycontext Quizzes indexed by module context ID.
     * @param \context_course $coursecontext Course context.
     * @return array<int, array<string, mixed>>
     */
    private function get_random_references(array $quizbycontext, \context_course $coursecontext): array {
        global $DB;

        if (!$quizbycontext) {
            return [];
        }

        $references = $DB->get_records_list(
            'question_set_references',
            'usingcontextid',
            array_keys($quizbycontext),
            'usingcontextid,itemid,id',
        );

        $details = [];
        foreach ($references as $reference) {
            $condition = json_decode($reference->filtercondition, true);
            $categoryids = is_array($condition) ? $this->get_category_ids($condition) : [];
            $questionscontext = $this->describe_context((int) $reference->questionscontextid, $coursecontext);
            $categorydetails = [];
            $categoriesinside = true;
            $categoriesvalid = (bool) $categoryids;
            foreach ($categoryids as $categoryid) {
                $category = $DB->get_record(
                    'question_categories',
                    ['id' => $categoryid],
                    'id,name,parent,contextid,stamp',
                );
                $categorycontext = $category
                    ? $this->describe_context((int) $category->contextid, $coursecontext)
                    : null;
                $inside = $categorycontext && $categorycontext['insidetargetcourse'];
                $categoriesinside = $categoriesinside && (bool) $inside;
                $categoriesvalid = $categoriesvalid && (bool) $category && (bool) ($categorycontext['exists'] ?? false);
                $categorydetails[] = [
                    'id' => $categoryid,
                    'exists' => (bool) $category,
                    'name' => $category->name ?? null,
                    'parent' => isset($category->parent) ? (int) $category->parent : null,
                    'contextid' => isset($category->contextid) ? (int) $category->contextid : null,
                    'stamp' => $category->stamp ?? null,
                    'context' => $categorycontext,
                ];
            }
            $referencecontext = $questionscontext;
            $referencecontextid = (int) $reference->questionscontextid;
            foreach ($categorydetails as $categorydetail) {
                $categorycontext = $categorydetail['context'];
                if ($categorycontext && (!$categorycontext['exists'] || !$categorycontext['insidetargetcourse'])) {
                    $referencecontext = $categorycontext;
                    $referencecontextid = (int) $categorycontext['id'];
                    break;
                }
            }
            if (!$questionscontext['exists'] || !$categoriesvalid) {
                $status = 'invalid';
            } else if (!$questionscontext['insidetargetcourse'] || !$categoriesinside) {
                $status = 'external';
            } else {
                $status = 'independent';
            }
            $quiz = $quizbycontext[(int) $reference->usingcontextid];
            $details[] = [
                'type' => 'random',
                'referenceid' => (int) $reference->id,
                'quizid' => (int) $quiz->quizid,
                'quizcmid' => (int) $quiz->cmid,
                'quizname' => $quiz->name,
                'slotid' => (int) $reference->itemid,
                'usingcontextid' => (int) $reference->usingcontextid,
                'questionscontextid' => (int) $reference->questionscontextid,
                'questionscontext' => $questionscontext,
                'referencecontextid' => $referencecontextid,
                'referencecontext' => $referencecontext,
                'categoryids' => $categoryids,
                'categories' => $categorydetails,
                'status' => $status,
                'filtercondition' => $condition,
                'filterconditionraw' => $reference->filtercondition,
            ];
        }
        return $details;
    }

    /**
     * Counts references with one diagnostic status.
     *
     * @param array $references References to count.
     * @param string $status Diagnostic status.
     * @return int
     */
    private function count_references_by_status(array $references, string $status): int {
        return count(array_filter(
            $references,
            static fn(array $reference): bool => $reference['status'] === $status,
        ));
    }

    /**
     * Reads category IDs from current and legacy random-filter structures.
     *
     * @param array $condition Filter condition.
     * @return int[]
     */
    private function get_category_ids(array $condition): array {
        $categoryids = [];
        foreach ($condition['filter']['category']['values'] ?? [] as $categoryid) {
            $categoryids[] = (int) $categoryid;
        }
        if (!empty($condition['questioncategoryid'])) {
            $categoryids[] = (int) $condition['questioncategoryid'];
        }
        if (!empty($condition['cat'])) {
            $categoryids[] = (int) explode(',', (string) $condition['cat'])[0];
        }
        return array_values(array_unique(array_filter($categoryids)));
    }

    /**
     * Describes one context and identifies its owning course.
     *
     * @param int $contextid Context ID.
     * @param \context_course $targetcoursecontext Target course context.
     * @return array<string, mixed>
     */
    private function describe_context(int $contextid, \context_course $targetcoursecontext): array {
        global $DB;

        $cachekey = $contextid . ':' . $targetcoursecontext->id;
        if (isset($this->contextcache[$cachekey])) {
            return $this->contextcache[$cachekey];
        }
        $context = $contextid > 0 ? \context::instance_by_id($contextid, IGNORE_MISSING) : false;
        if (!$context) {
            return $this->contextcache[$cachekey] = [
                'exists' => false,
                'id' => $contextid,
                'insidetargetcourse' => false,
                'contextlevel' => null,
                'instanceid' => null,
                'path' => null,
                'ownercourseid' => null,
                'ownercourse' => null,
                'ownershortname' => null,
            ];
        }

        $ownercourse = null;
        $pathids = array_reverse(array_map('intval', explode('/', trim($context->path, '/'))));
        foreach ($pathids as $pathid) {
            $ancestor = $DB->get_record('context', ['id' => $pathid], 'id,contextlevel,instanceid');
            if ($ancestor && (int) $ancestor->contextlevel === CONTEXT_COURSE) {
                $ownercourse = $DB->get_record(
                    'course',
                    ['id' => $ancestor->instanceid],
                    'id,fullname,shortname',
                );
                break;
            }
        }
        $inside = $context->id === $targetcoursecontext->id
            || str_starts_with($context->path . '/', $targetcoursecontext->path . '/');
        return $this->contextcache[$cachekey] = [
            'exists' => true,
            'id' => (int) $context->id,
            'insidetargetcourse' => $inside,
            'contextlevel' => (int) $context->contextlevel,
            'instanceid' => (int) $context->instanceid,
            'path' => $context->path,
            'ownercourseid' => $ownercourse ? (int) $ownercourse->id : null,
            'ownercourse' => $ownercourse->fullname ?? null,
            'ownershortname' => $ownercourse->shortname ?? null,
        ];
    }

    /**
     * Returns pending or failed Moodle question-bank migration tasks.
     *
     * @return array<int, array<string, mixed>>
     */
    private function get_migration_tasks(): array {
        global $DB;

        $classnames = [
            '\\mod_qbank\\task\\transfer_question_categories',
            '\\mod_qbank\\task\\transfer_questions',
        ];
        $records = $DB->get_records_list(
            'task_adhoc',
            'classname',
            $classnames,
            'id',
            'id,classname,nextruntime,faildelay,customdata',
        );
        return array_values(array_map(
            static fn(\stdClass $record): array => [
                'id' => (int) $record->id,
                'classname' => $record->classname,
                'nextruntime' => (int) $record->nextruntime,
                'faildelay' => (int) $record->faildelay,
                'customdata' => $record->customdata ? json_decode($record->customdata, true) : null,
            ],
            $records,
        ));
    }
}
