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
 * English language strings for the plugin.
 *
 * @package    local_courseqbankcopy
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['allowreuseselection'] = 'Allow authorised users to choose reuse mode';
$string['allowreuseselection_desc'] = 'Users with the local/courseqbankcopy:choosereusemode capability may select reuse mode during import.';
$string['backupmanifestinvalid'] = 'The temporary import manifest does not contain valid XML.';
$string['backupmanifestmissing'] = 'The temporary import manifest was not found.';
$string['cannottransformquestions'] = 'The independent question-bank copy could not be prepared.';
$string['categorymappingmissing'] = 'No destination was found for source question category {$a}.';
$string['categoryoutsidecourse'] = 'Copied question category {$a} does not belong to the target course.';
$string['copyinterceptionfailed'] = 'The import was stopped because the plugin could not prepare an independent question-bank copy.';
$string['copyquestions'] = 'Copy question banks to this course';
$string['copyquestions_desc'] = 'Creates independent copies of question banks from the source course during course import.';
$string['copyquestions_help'] = 'Checked: creates independent banks and points imported quizzes to the new questions. Unchecked: keeps Moodle\'s standard reuse behaviour.';
$string['copyquestions_locked'] = 'This option is mandatory for your role. Imported question banks will be independent from the source course.';
$string['copyreconciliationfailed'] = 'The import finished, but validation of the new question-bank references failed. Ask an administrator to review it before using the imported quiz.';
$string['courseqbankcopy:choosereusemode'] = 'Choose question bank reuse mode during course import';
$string['incompletequestionbanks'] = 'To guarantee an independent copy, every question bank from the source course must be imported. These question-bank modules were not included: {$a}.';
$string['independencevalidationfailed'] = '{$a} references still point to source question-bank entries.';
$string['modecopy'] = 'Copy question banks to this course';
$string['modereuse'] = 'Reuse existing question banks';
$string['pluginname'] = 'Independent question bank copy';
$string['privacy:metadata'] = 'The Independent question bank copy plugin does not store personal data.';
$string['randomreferencevalidationfailed'] = 'A random-question reference still points to a category or context from the source course.';
$string['targetcourseidentificationfailed'] = 'local_courseqbankcopy: The target course could not be identified safely.';
$string['taskcleanupoperations'] = 'Clean up old question-bank copy operation records';
