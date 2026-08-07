<?php
// This file is part of Moodle - https://moodle.org/.

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => '\\local_courseqbankcopy\\task\\cleanup_operations',
        'blocking' => 0,
        'minute' => 'R',
        'hour' => '3',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
