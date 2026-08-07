<?php
// This file is part of Moodle - https://moodle.org/.

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\\core\\event\\course_backup_created',
        'callback' => '\\local_courseqbankcopy\\observer::course_backup_created',
        'priority' => 1000,
    ],
    [
        'eventname' => '\\core\\event\\course_restored',
        'callback' => '\\local_courseqbankcopy\\observer::course_restored',
        'priority' => 1000,
    ],
];
