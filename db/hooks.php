<?php
// This file is part of Moodle - https://moodle.org/.

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core\hook\output\before_standard_head_html_generation::class,
        'callback' => [\local_courseqbankcopy\hook_callbacks::class, 'before_standard_head_html_generation'],
        'priority' => 1000,
    ],
];
