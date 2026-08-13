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
 * Administrative diagnostics for independent question-bank copies.
 *
 * @package    local_courseqbankcopy
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_courseqbankcopy\local\diagnostic_report;

$courseid = optional_param('courseid', 0, PARAM_INT);

require_login();
require_capability('moodle/site:config', context_system::instance());
admin_externalpage_setup('local_courseqbankcopy_diagnostics');

$url = new moodle_url('/local/courseqbankcopy/diagnostics.php');
if ($courseid) {
    $url->param('courseid', $courseid);
}
$PAGE->set_url($url);
$PAGE->set_title(get_string('diagnosticstitle', 'local_courseqbankcopy'));
$PAGE->set_heading(get_string('diagnosticstitle', 'local_courseqbankcopy'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('diagnosticstitle', 'local_courseqbankcopy'));
echo html_writer::tag('p', get_string('diagnosticsintro', 'local_courseqbankcopy'));

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => (new moodle_url('/local/courseqbankcopy/diagnostics.php'))->out(false),
    'class' => 'mb-4',
]);
echo html_writer::label(get_string('diagnosticscourseid', 'local_courseqbankcopy'), 'courseqbankcopy-courseid');
echo html_writer::empty_tag('input', [
    'type' => 'number',
    'name' => 'courseid',
    'id' => 'courseqbankcopy-courseid',
    'value' => $courseid ?: '',
    'min' => 1,
    'required' => 'required',
    'class' => 'form-control mb-2',
]);
echo html_writer::tag('button', get_string('diagnosticsgenerate', 'local_courseqbankcopy'), [
    'type' => 'submit',
    'class' => 'btn btn-primary',
]);
echo html_writer::end_tag('form');

if ($courseid) {
    $report = (new diagnostic_report())->build($courseid);
    $summary = $report['summary'];
    $alerttype = $summary['externalrandomreferences'] ? 'danger' : 'success';
    $summarymessage = $summary['externalrandomreferences']
        ? get_string('diagnosticsexternalfound', 'local_courseqbankcopy', $summary['externalrandomreferences'])
        : get_string('diagnosticsindependent', 'local_courseqbankcopy');
    echo $OUTPUT->notification($summarymessage, $alerttype);

    $summarytable = new html_table();
    $summarytable->head = [
        get_string('diagnosticsitem', 'local_courseqbankcopy'),
        get_string('diagnosticsvalue', 'local_courseqbankcopy'),
    ];
    $summarytable->data = [
        [get_string('diagnosticscourse', 'local_courseqbankcopy'),
            s($report['course']['fullname']) . ' (' . (int) $report['course']['id'] . ')'],
        [get_string('diagnosticspluginrelease', 'local_courseqbankcopy'),
            s((string) $report['plugin']['release'])],
        [get_string('diagnosticspluginversions', 'local_courseqbankcopy'),
            (int) $report['plugin']['versiondisk'] . ' / ' . (int) $report['plugin']['versiondb']],
        [get_string('diagnosticsoperations', 'local_courseqbankcopy'), (int) $summary['operations']],
        [get_string('diagnosticsbanks', 'local_courseqbankcopy'), (int) $summary['questionbanks']],
        [get_string('diagnosticscategories', 'local_courseqbankcopy'), (int) $summary['categories']],
        [get_string('diagnosticsrandomreferences', 'local_courseqbankcopy'), (int) $summary['randomreferences']],
        [get_string('diagnosticsexternalreferences', 'local_courseqbankcopy'),
            (int) $summary['externalrandomreferences']],
        [get_string('diagnosticsmigrationtasks', 'local_courseqbankcopy'), (int) $summary['migrationtasks']],
    ];
    echo html_writer::table($summarytable);

    echo $OUTPUT->heading(get_string('diagnosticsreferencestable', 'local_courseqbankcopy'), 3);
    $referencetable = new html_table();
    $referencetable->data = [];
    $referencetable->head = [
        get_string('diagnosticsquiz', 'local_courseqbankcopy'),
        get_string('diagnosticsslot', 'local_courseqbankcopy'),
        get_string('diagnosticsquestionscontext', 'local_courseqbankcopy'),
        get_string('diagnosticscategoryids', 'local_courseqbankcopy'),
        get_string('diagnosticsownercourse', 'local_courseqbankcopy'),
        get_string('diagnosticsstatus', 'local_courseqbankcopy'),
    ];
    foreach ($report['randomreferences'] as $reference) {
        $owner = $reference['questionscontext']['ownercourse'] ?? get_string('notavailable');
        $status = get_string('diagnosticsstatus' . $reference['status'], 'local_courseqbankcopy');
        $referencetable->data[] = [
            s($reference['quizname']),
            (int) $reference['slotid'],
            (int) $reference['questionscontextid'],
            s(implode(', ', $reference['categoryids'])),
            s($owner),
            s($status),
        ];
    }
    if ($referencetable->data) {
        echo html_writer::table($referencetable);
    } else {
        echo html_writer::tag('p', get_string('diagnosticsnoreferences', 'local_courseqbankcopy'));
    }

    echo html_writer::start_tag('details', ['class' => 'mt-4']);
    echo html_writer::tag('summary', get_string('diagnosticsjson', 'local_courseqbankcopy'));
    echo html_writer::tag(
        'pre',
        s(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
        ['class' => 'border rounded bg-light p-3 mt-2', 'style' => 'white-space: pre-wrap;'],
    );
    echo html_writer::end_tag('details');
}

echo $OUTPUT->footer();
