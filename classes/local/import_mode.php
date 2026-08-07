<?php
// This file is part of Moodle - https://moodle.org/.

namespace local_courseqbankcopy\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolves the question-bank strategy selected for a course import.
 */
final class import_mode {
    /** Reuse the source question bank references. */
    public const REUSE = 'reuse';

    /** Create independent copies in the target course. */
    public const COPY = 'copy';

    /**
     * Gets the configured default mode.
     *
     * @return string
     */
    public static function get_default(): string {
        return self::COPY;
    }

    /**
     * Resolves a requested mode while enforcing the course capability.
     *
     * @param string|null $requestedmode Value supplied by the import form.
     * @param \context_course $context Destination course context.
     * @return string
     */
    public static function resolve(?string $requestedmode, \context_course $context): string {
        $canreuse = is_siteadmin()
            || (get_config('local_courseqbankcopy', 'allowreuseselection')
                && has_capability('local/courseqbankcopy:choosereusemode', $context));

        if ($requestedmode === self::REUSE && $canreuse) {
            return self::REUSE;
        }

        return self::COPY;
    }

}
