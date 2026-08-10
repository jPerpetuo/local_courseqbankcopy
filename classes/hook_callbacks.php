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
 * Hook callbacks used by the plugin.
 *
 * @package    local_courseqbankcopy
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_courseqbankcopy;

use core\hook\output\before_standard_head_html_generation;

/**
 * Hooks used to integrate with the native Moodle import page.
 */
final class hook_callbacks {
    /**
     * Loads the option early only on backup/import.php.
     *
     * @param before_standard_head_html_generation $hook Output hook.
     */
    public static function before_standard_head_html_generation(
        before_standard_head_html_generation $hook
    ): void {
        global $PAGE;

        if (!$PAGE->has_set_url() || $PAGE->url->get_path() !== '/backup/import.php') {
            return;
        }
        if (!$PAGE->context instanceof \context_course) {
            return;
        }

        $canchoose = is_siteadmin()
            || ((bool) get_config('local_courseqbankcopy', 'allowreuseselection')
                && has_capability('local/courseqbankcopy:choosereusemode', $PAGE->context));

        $config = [
            'canchoose' => $canchoose,
            'copylabel' => get_string('copyquestions', 'local_courseqbankcopy'),
            'modeparameter' => 'local_courseqbankcopy_mode',
        ];

        $configjson = json_encode(
            $config,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
        );
        $hook->add_html(\html_writer::tag('script', $configjson, [
            'id' => 'local-courseqbankcopy-config',
            'type' => 'application/json',
        ]));

        $scripturl = new \moodle_url('/local/courseqbankcopy/js/import_options_early.js', [
            'v' => '2026080702',
        ]);
        $PAGE->requires->js($scripturl, true);
    }
}
