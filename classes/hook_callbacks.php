<?php
// This file is part of Moodle - https://moodle.org/.

namespace local_courseqbankcopy;

defined('MOODLE_INTERNAL') || die();

use core\hook\output\before_standard_head_html_generation;

/**
 * Hooks used to integrate with the native Moodle import page.
 */
final class hook_callbacks {
    /**
     * Carrega antecipadamente a opção apenas em backup/import.php.
     *
     * @param before_standard_head_html_generation $hook Hook de saída.
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
