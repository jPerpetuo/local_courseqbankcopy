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
 * Backup package transformation service.
 *
 * @package    local_courseqbankcopy
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_courseqbankcopy\local;

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
        $this->transform_question_bank_modules($tempdir, $restoreid, $token);

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
        $categorynamepending = false;
        $categorycount = 0;
        $questioncount = 0;

        try {
            while (($line = fgets($input)) !== false) {
                if (preg_match('/<question_category\s+id="(\d+)"/', $line, $matches)) {
                    $currentcategoryid = (int) $matches[1];
                    $categorynamepending = true;
                }
                if (
                    $currentcategoryid !== null
                        && $categorynamepending
                        && preg_match('/<name>.*?<\/name>/', $line)
                ) {
                    $namemarker = self::category_name_marker($token, $currentcategoryid);
                    $escapedmarker = htmlspecialchars($namemarker, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                    $line = preg_replace('/<name>.*?<\/name>/', '<name>' . $escapedmarker . '</name>', $line, 1);
                    $categorynamepending = false;
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
                        self::category_name_marker($token, $currentcategoryid),
                    );
                    $categorycount++;
                }
                if (str_contains($line, '</question_category>')) {
                    $currentcategoryid = null;
                    $categorynamepending = false;
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
     * Gives each qbank module a temporary identity that survives the restore.
     *
     * @param string $tempdir Backup temporary directory.
     * @param string $restoreid Backup/restore ID.
     * @param string $token Operation token.
     */
    private function transform_question_bank_modules(string $tempdir, string $restoreid, string $token): void {
        $activitiesdir = rtrim($tempdir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'activities';
        $directories = glob($activitiesdir . DIRECTORY_SEPARATOR . 'qbank_*', GLOB_ONLYDIR);
        if (!$directories) {
            return;
        }

        foreach ($directories as $directory) {
            if (!preg_match('/^qbank_(\d+)$/', basename($directory), $matches)) {
                continue;
            }

            $sourcecmid = (int) $matches[1];
            $qbankfile = $directory . DIRECTORY_SEPARATOR . 'qbank.xml';
            if (!is_readable($qbankfile)) {
                throw new \moodle_exception('cannottransformquestions', 'local_courseqbankcopy');
            }

            $previous = libxml_use_internal_errors(true);
            $xml = simplexml_load_file($qbankfile);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $namenodes = $xml ? $xml->xpath('/activity/qbank/name') : false;
            if (!$xml || !$namenodes || count($namenodes) !== 1) {
                throw new \moodle_exception('cannottransformquestions', 'local_courseqbankcopy');
            }

            $marker = self::module_marker($token, $sourcecmid);
            $namenodes[0][0] = $marker;
            $temporaryfile = $qbankfile . '.courseqbankcopy-' . $token . '.tmp';
            if ($xml->asXML($temporaryfile) === false || !rename($temporaryfile, $qbankfile)) {
                @unlink($temporaryfile);
                throw new \moodle_exception('cannottransformquestions', 'local_courseqbankcopy');
            }

            operation_repository::upsert_mapping(
                $restoreid,
                operation_repository::TYPE_MODULE,
                $sourcecmid,
                0,
                0,
                0,
                $marker,
            );
        }
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

    /**
     * Builds a temporary category-name marker that survives stamp replacement.
     *
     * @param string $token Operation token.
     * @param int $categoryid Original category ID.
     * @return string
     */
    public static function category_name_marker(string $token, int $categoryid): string {
        return 'cqbc_category_' . substr(hash('sha256', $token . ':' . $categoryid), 0, 40);
    }

    /**
     * Builds a deterministic module marker for one operation.
     *
     * @param string $token Operation token.
     * @param int $sourcecmid Original course-module ID.
     * @return string
     */
    public static function module_marker(string $token, int $sourcecmid): string {
        return 'cqbc_module_' . substr(hash('sha256', $token . ':' . $sourcecmid), 0, 40);
    }
}
