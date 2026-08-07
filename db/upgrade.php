<?php
// This file is part of Moodle - https://moodle.org/.

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrades local_courseqbankcopy.
 *
 * @param int $oldversion Installed version.
 * @return bool
 */
function xmldb_local_courseqbankcopy_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026080600) {
        $operation = new xmldb_table('local_cqbc_operation');
        $operation->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $operation->add_field('restoreid', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);
        $operation->add_field('sourcecourseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $operation->add_field('targetcourseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $operation->add_field('token', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);
        $operation->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'prepared');
        $operation->add_field('categorycount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $operation->add_field('questioncount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $operation->add_field('lasterror', XMLDB_TYPE_TEXT, null, null, null);
        $operation->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $operation->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $operation->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $operation->add_key('restoreiduniq', XMLDB_KEY_UNIQUE, ['restoreid']);
        $operation->add_index('status-time', XMLDB_INDEX_NOTUNIQUE, ['status', 'timemodified']);
        $operation->add_index('source-target', XMLDB_INDEX_NOTUNIQUE, ['sourcecourseid', 'targetcourseid']);
        if (!$dbman->table_exists($operation)) {
            $dbman->create_table($operation);
        }

        $mapping = new xmldb_table('local_cqbc_mapping');
        $mapping->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $mapping->add_field('restoreid', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);
        $mapping->add_field('itemtype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL);
        $mapping->add_field('oldid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $mapping->add_field('newid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $mapping->add_field('oldparentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $mapping->add_field('newparentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $mapping->add_field('marker', XMLDB_TYPE_CHAR, '64', null, null);
        $mapping->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $mapping->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $mapping->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $mapping->add_index('restore-type-old', XMLDB_INDEX_UNIQUE, ['restoreid', 'itemtype', 'oldid']);
        $mapping->add_index('restore-new', XMLDB_INDEX_NOTUNIQUE, ['restoreid', 'newid']);
        if (!$dbman->table_exists($mapping)) {
            $dbman->create_table($mapping);
        }

        upgrade_plugin_savepoint(true, 2026080600, 'local', 'courseqbankcopy');
    }

    if ($oldversion < 2026080601) {
        upgrade_plugin_savepoint(true, 2026080601, 'local', 'courseqbankcopy');
    }

    if ($oldversion < 2026080700) {
        upgrade_plugin_savepoint(true, 2026080700, 'local', 'courseqbankcopy');
    }

    if ($oldversion < 2026080701) {
        upgrade_plugin_savepoint(true, 2026080701, 'local', 'courseqbankcopy');
    }

    if ($oldversion < 2026080702) {
        upgrade_plugin_savepoint(true, 2026080702, 'local', 'courseqbankcopy');
    }

    return true;
}
