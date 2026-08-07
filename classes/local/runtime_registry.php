<?php
// This file is part of Moodle - https://moodle.org/.

namespace local_courseqbankcopy\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Keeps the active restore ID available to synchronous event observers.
 */
final class runtime_registry {
    /** @var string|null */
    private static ?string $restoreid = null;

    /**
     * Records the restore currently processed in this PHP request.
     *
     * @param string $restoreid Restore ID.
     */
    public static function set_restoreid(string $restoreid): void {
        self::$restoreid = $restoreid;
    }

    /**
     * Returns the current restore ID.
     *
     * @return string|null
     */
    public static function get_restoreid(): ?string {
        return self::$restoreid;
    }
}
