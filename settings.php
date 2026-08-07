<?php
// This file is part of Moodle - https://moodle.org/.

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_courseqbankcopy',
        get_string('pluginname', 'local_courseqbankcopy'),
    );
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_heading(
        'local_courseqbankcopy/settings',
        get_string('pluginname', 'local_courseqbankcopy'),
        get_string('copyquestions_desc', 'local_courseqbankcopy'),
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_courseqbankcopy/allowreuseselection',
        get_string('allowreuseselection', 'local_courseqbankcopy'),
        get_string('allowreuseselection_desc', 'local_courseqbankcopy'),
        0,
    ));
}
