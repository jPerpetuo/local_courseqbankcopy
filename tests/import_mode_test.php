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
 * Tests for import mode configuration and authorisation.
 *
 * @package    local_courseqbankcopy
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_courseqbankcopy;

use advanced_testcase;
use context_course;
use local_courseqbankcopy\local\import_mode;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests the import mode configuration and authorisation service.
 */
#[CoversClass(import_mode::class)]
final class import_mode_test extends advanced_testcase {
    /**
     * Copy remains the default when the new setting has not been saved yet.
     */
    public function test_copy_is_default_when_setting_is_missing(): void {
        $this->resetAfterTest();

        unset_config('defaultcopymode', 'local_courseqbankcopy');

        $this->assertSame(import_mode::COPY, import_mode::get_default());
    }

    /**
     * The administrative setting may make reuse the default mode.
     */
    public function test_reuse_can_be_configured_as_default(): void {
        $this->resetAfterTest();

        set_config('defaultcopymode', 0, 'local_courseqbankcopy');

        $this->assertSame(import_mode::REUSE, import_mode::get_default());
    }

    /**
     * Users without permission receive the configured default.
     */
    public function test_user_without_permission_cannot_override_default(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $context = context_course::instance($course->id);
        $this->setUser($user);
        set_config('defaultcopymode', 1, 'local_courseqbankcopy');

        $this->assertFalse(import_mode::can_choose($context));
        $this->assertSame(import_mode::COPY, import_mode::resolve(import_mode::REUSE, $context));
    }

    /**
     * An authorised user may override an unlocked default.
     */
    public function test_authorised_user_can_override_unlocked_default(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $context = context_course::instance($course->id);
        $roleid = create_role('Question bank reuse chooser', 'qbankreusechooser', '');
        assign_capability(
            'local/courseqbankcopy:choosereusemode',
            CAP_ALLOW,
            $roleid,
            $context->id,
        );
        role_assign($roleid, $user->id, $context->id);
        $this->setUser($user);
        set_config('allowreuseselection', 1, 'local_courseqbankcopy');
        set_config('defaultcopymode', 1, 'local_courseqbankcopy');
        set_config('defaultcopymode_locked', 0, 'local_courseqbankcopy');

        $this->assertTrue(import_mode::can_choose($context));
        $this->assertSame(import_mode::REUSE, import_mode::resolve(import_mode::REUSE, $context));
    }

    /**
     * The administrative lock also prevents a site administrator from overriding the default.
     */
    public function test_locked_default_cannot_be_overridden_by_admin(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $this->setAdminUser();
        set_config('defaultcopymode', 1, 'local_courseqbankcopy');
        set_config('defaultcopymode_locked', 1, 'local_courseqbankcopy');

        $this->assertFalse(import_mode::can_choose($context));
        $this->assertSame(import_mode::COPY, import_mode::resolve(import_mode::REUSE, $context));
    }
}
