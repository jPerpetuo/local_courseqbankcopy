<?php
// This file is part of Moodle - https://moodle.org/.

namespace local_courseqbankcopy\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for a plugin that does not store personal data.
 */
final class provider implements \core_privacy\local\metadata\null_provider {
    /**
     * Explains why this plugin does not store personal data.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
