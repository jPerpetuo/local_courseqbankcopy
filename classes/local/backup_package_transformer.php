<?php
// This file is part of Moodle - https://moodle.org/.

namespace local_courseqbankcopy\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Gives question categories unique identities in an import package.
 *
 * The restore precheck maps categories by stamp. Replacing each stamp in the
 * temporary questions.xml makes the native restore create a new category and,
 * consequently, new entries, versions and questions through the regular APIs.
 */
final class backup_package_transformer {
    /**
     * Ensures copy mode contains every question-bank module from the source course.
     *
     * @param string $tempdir Backup temporary directory.
     * @param int $sourcecourseid Source course ID.
     */
    public function require_complete_question_banks(string $tempdir, int $sourcecourseid): void {
        global $DB;

        $sourceids = $DB->get_fieldset_sql(
            "SELECT cm.id
               FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module
              WHERE cm.course = :courseid
                AND cm.deletioninprogress = 0
                AND m.name = :modulename",
            ['courseid' => $sourcecourseid, 'modulename' => 'qbank'],
        );
        if (!$sourceids) {
            return;
        }

        $manifestfile = rtrim($tempdir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'moodle_backup.xml';
        if (!is_readable($manifestfile)) {
            throw new \moodle_exception('backupmanifestmissing', 'local_courseqbankcopy');
        }

        $previous = libxml_use_internal_errors(true);
        $manifest = simplexml_load_file($manifestfile);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$manifest) {
            throw new \moodle_exception('backupmanifestinvalid', 'local_courseqbankcopy');
        }

        $includedids = [];
        foreach ($manifest->information->contents->activities->activity ?? [] as $activity) {
            if ((string) $activity->modulename === 'qbank') {
                $includedids[] = (int) $activity->moduleid;
            }
        }
        $missingids = array_diff(array_map('intval', $sourceids), $includedids);
        if ($missingids) {
            throw new \moodle_exception(
                'incompletequestionbanks',
                'local_courseqbankcopy',
                '',
                implode(', ', $missingids),
            );
        }
    }

    /**
     * Transforms the questions file atomically.
     *
     * @param string $tempdir Backup temporary directory.
     * @param string $restoreid Backup/restore ID.
     * @param string $token Operation token.
     * @return array{categories:int,questions:int}
     */
    public function transform(string $tempdir, string $restoreid, string $token): array {
        $questionsfile = rtrim($tempdir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'questions.xml';
        if (!is_readable($questionsfile)) {
            return ['categories' => 0, 'questions' => 0];
        }

        $temporaryfile = $questionsfile . '.courseqbankcopy-' . $token . '.tmp';
        $input = fopen($questionsfile, 'rb');
        $output = fopen($temporaryfile, 'xb');
        if (!$input || !$output) {
            if (is_resource($input)) {
                fclose($input);
            }
            if (is_resource($output)) {
                fclose($output);
            }
            throw new \moodle_exception('cannottransformquestions', 'local_courseqbankcopy');
        }

        $currentcategoryid = null;
        $categorycount = 0;
        $questioncount = 0;

        try {
            while (($line = fgets($input)) !== false) {
                if (preg_match('/<question_category\s+id="(\d+)"/', $line, $matches)) {
                    $currentcategoryid = (int) $matches[1];
                }
                if (preg_match('/<question\s+id="\d+"/', $line)) {
                    $questioncount++;
                }
                if ($currentcategoryid !== null && preg_match('/<stamp>(.*?)<\/stamp>/', $line, $matches)) {
                    $marker = self::category_marker($token, $currentcategoryid, html_entity_decode($matches[1], ENT_XML1));
                    $escapedmarker = htmlspecialchars($marker, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                    $line = preg_replace('/<stamp>.*?<\/stamp>/', '<stamp>' . $escapedmarker . '</stamp>', $line, 1);
                    operation_repository::upsert_mapping(
                        $restoreid,
                        operation_repository::TYPE_CATEGORY,
                        $currentcategoryid,
                        0,
                        0,
                        0,
                        $marker,
                    );
                    $categorycount++;
                }
                if (str_contains($line, '</question_category>')) {
                    $currentcategoryid = null;
                }
                if (fwrite($output, $line) === false) {
                    throw new \moodle_exception('cannottransformquestions', 'local_courseqbankcopy');
                }
            }
        } catch (\Throwable $exception) {
            fclose($input);
            fclose($output);
            @unlink($temporaryfile);
            throw $exception;
        }

        fclose($input);
        if (!fclose($output) || !rename($temporaryfile, $questionsfile)) {
            @unlink($temporaryfile);
            throw new \moodle_exception('cannottransformquestions', 'local_courseqbankcopy');
        }

        return ['categories' => $categorycount, 'questions' => $questioncount];
    }

    /**
     * Builds a deterministic and unique category stamp for one operation.
     *
     * @param string $token Operation token.
     * @param int $categoryid Original category ID.
     * @param string $oldstamp Original stamp.
     * @return string
     */
    public static function category_marker(string $token, int $categoryid, string $oldstamp): string {
        return 'cqbc_' . substr(hash('sha256', $token . ':' . $categoryid . ':' . $oldstamp), 0, 40);
    }
}
