<?php
// This file is part of Moodle - https://moodle.org/.

$string['pluginname'] = 'Independent question bank copy';
$string['copyquestions'] = 'Copy question banks to this course';
$string['copyquestions_desc'] = 'Creates independent copies of question banks from the source course during course import.';
$string['copyquestions_help'] = 'Checked: creates independent banks and points imported quizzes to the new questions. Unchecked: keeps Moodle\'s standard reuse behaviour.';
$string['copyquestions_locked'] = 'This option is mandatory for your role. Imported question banks will be independent from the source course.';
$string['modecopy'] = 'Copy question banks to this course';
$string['modereuse'] = 'Reuse existing question banks';
$string['allowreuseselection'] = 'Allow authorised users to choose reuse mode';
$string['allowreuseselection_desc'] = 'Users with the local/courseqbankcopy:choosereusemode capability may select reuse mode during import.';
$string['courseqbankcopy:choosereusemode'] = 'Choose question bank reuse mode during course import';
$string['privacy:metadata'] = 'The Independent question bank copy plugin does not store personal data.';
$string['cannottransformquestions'] = 'The independent question-bank copy could not be prepared.';
$string['copyinterceptionfailed'] = 'The import was stopped because the plugin could not prepare an independent question-bank copy.';
$string['copyreconciliationfailed'] = 'The import finished, but validation of the new question-bank references failed. Ask an administrator to review it before using the imported quiz.';
$string['independencevalidationfailed'] = '{$a} references still point to source question-bank entries.';
$string['categorymappingmissing'] = 'No destination was found for source question category {$a}.';
$string['categoryoutsidecourse'] = 'Copied question category {$a} does not belong to the target course.';
$string['randomreferencevalidationfailed'] = 'A random-question reference still points to a category or context from the source course.';
$string['backupmanifestmissing'] = 'The temporary import manifest was not found.';
$string['backupmanifestinvalid'] = 'The temporary import manifest does not contain valid XML.';
$string['incompletequestionbanks'] = 'To guarantee an independent copy, every question bank from the source course must be imported. These question-bank modules were not included: {$a}.';
$string['taskcleanupoperations'] = 'Clean up old question-bank copy operation records';
