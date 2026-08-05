<?php
// This file is part of Moodle - https://moodle.org/.

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/courseqbankcopy:choosereusemode' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
