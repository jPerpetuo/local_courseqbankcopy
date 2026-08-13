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
 * Administrative settings for the plugin.
 *
 * @package    local_courseqbankcopy
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_courseqbankcopy_diagnostics',
        get_string('diagnosticstitle', 'local_courseqbankcopy'),
        new moodle_url('/local/courseqbankcopy/diagnostics.php'),
        'moodle/site:config',
    ));
}
