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
$string['copyquestions'] = 'Include independent copies of question banks';
$string['copyquestions_desc'] = 'Creates independent copies of question banks from the source course during course import.';
$string['copyquestions_help'] = 'Checked: creates independent banks and points imported quizzes to the new questions. Unchecked: keeps Moodle\'s standard reuse behaviour.';
$string['copyquestions_locked'] = 'This option is mandatory for your role. Imported question banks will be independent from the source course.';
$string['copyreconciliationfailed'] = 'The import finished, but validation of the new question-bank references failed. Ask an administrator to review it before using the imported quiz.';
$string['courseqbankcopy:choosereusemode'] = 'Choose question bank reuse mode during course import';
$string['defaultcopymode'] = 'Include independent copies of question banks';
$string['defaultcopymode_desc'] = 'When enabled, question banks are copied to the destination course without retaining references to the source course. When disabled, Moodle reuses the existing question banks.';
$string['diagnosticsbanks'] = 'Question banks';
$string['diagnosticscategories'] = 'Question categories';
$string['diagnosticscategoryids'] = 'Category IDs';
$string['diagnosticscourse'] = 'Course';
$string['diagnosticscourseid'] = 'Destination course ID';
$string['diagnosticsexternalfound'] = '{$a} external random-question reference(s) were found.';
$string['diagnosticsexternalreferences'] = 'External random references';
$string['diagnosticsgenerate'] = 'Generate report';
$string['diagnosticsindependent'] = 'No external random-question references were found.';
$string['diagnosticsintro'] = 'This read-only report identifies question-bank and random-question references for a destination course. It does not change Moodle data.';
$string['diagnosticsitem'] = 'Item';
$string['diagnosticsjson'] = 'Complete technical report (JSON)';
$string['diagnosticsmigrationtasks'] = 'Pending migration tasks';
$string['diagnosticsnoreferences'] = 'No random-question references were found in this course.';
$string['diagnosticsoperations'] = 'Copy operations';
$string['diagnosticsownercourse'] = 'Owner course';
$string['diagnosticspluginrelease'] = 'Plugin release';
$string['diagnosticspluginversions'] = 'Plugin version on disk / database';
$string['diagnosticsquestionscontext'] = 'Question context';
$string['diagnosticsquiz'] = 'Quiz';
$string['diagnosticsrandomreferences'] = 'Random-question references';
$string['diagnosticsreferencestable'] = 'Random-question references';
$string['diagnosticsslot'] = 'Slot';
$string['diagnosticsstatus'] = 'Status';
$string['diagnosticsstatusexternal'] = 'External';
$string['diagnosticsstatusindependent'] = 'Independent';
$string['diagnosticstitle'] = 'Question bank copy diagnostics';
$string['diagnosticsvalue'] = 'Value';
$string['incompletequestionbanks'] = 'To guarantee an independent copy, every question bank from the source course must be imported. These question-bank modules were not included: {$a}.';
$string['independencevalidationfailed'] = '{$a} references still point to source question-bank entries.';
$string['modecopy'] = 'Include independent copies of question banks';
$string['modereuse'] = 'Reuse existing question banks';
$string['pluginname'] = 'Independent question bank copy';
$string['privacy:metadata'] = 'The Independent question bank copy plugin does not store personal data.';
$string['questionbankmappingmissing'] = 'The source course {$a} contains question banks, but the import did not record any destination mapping.';
$string['randomreferencevalidationfailed'] = 'A random-question reference still points to a category or context from the source course.';
$string['targetcourseidentificationfailed'] = 'local_courseqbankcopy: The target course could not be identified safely.';
$string['taskcleanupoperations'] = 'Clean up old question-bank copy operation records';
