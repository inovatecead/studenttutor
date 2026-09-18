<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for the plugin library functions and course navigation.
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::local_studenttutor_extend_navigation_course
 * @covers     ::local_studenttutor_get_tutor_roles
 * @covers     ::local_studenttutor_is_tutor
 */

namespace local_studenttutor;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/studenttutor/lib.php');

/**
 * Library function tests.
 *
 * @coversNothing
 */
class lib_test extends \advanced_testcase {
    /** @var string Default tutor role shortname. */
    const DEFAULT_TUTOR_ROLE = 'tutortematico';

    /**
     * The configured tutor role list is returned with defaults applied.
     */
    public function test_get_tutor_roles_default() {
        $this->resetAfterTest();

        unset_config('tutor_role', 'local_studenttutor');
        unset_config('additional_tutor_roles', 'local_studenttutor');

        $this->assertSame([self::DEFAULT_TUTOR_ROLE], local_studenttutor_get_tutor_roles());
    }

    /**
     * The primary role falls back to the default when configured empty.
     */
    public function test_get_tutor_roles_empty_configuration_uses_default() {
        $this->resetAfterTest();

        set_config('tutor_role', '', 'local_studenttutor');

        $this->assertSame([self::DEFAULT_TUTOR_ROLE], local_studenttutor_get_tutor_roles());
    }

    /**
     * Additional roles are trimmed, deduplicated and empty values dropped.
     */
    public function test_get_tutor_roles_with_additional_roles() {
        $this->resetAfterTest();

        set_config('tutor_role', 'tutor', 'local_studenttutor');
        set_config('additional_tutor_roles', ' tutorx , ,tutory ,tutor', 'local_studenttutor');

        $this->assertSame(['tutor', 'tutorx', 'tutory'], local_studenttutor_get_tutor_roles());
    }

    /**
     * A user without the tutor role is not a tutor.
     */
    public function test_is_tutor_is_false_for_plain_user() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $this->assertFalse(local_studenttutor_is_tutor($user->id));
        $this->assertFalse(local_studenttutor_is_tutor($user->id, $course->id));
        $this->assertFalse(local_studenttutor_is_tutor(999999));
    }

    /**
     * A user holding the configured tutor role is detected, globally and per course.
     */
    public function test_is_tutor_is_true_for_tutor_role() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();
        $tutor = $this->getDataGenerator()->create_user();

        $this->assign_tutor_role($tutor->id, \context_course::instance($course->id));

        $this->assertTrue(local_studenttutor_is_tutor($tutor->id));
        $this->assertTrue(local_studenttutor_is_tutor($tutor->id, $course->id));
        $this->assertFalse(
            local_studenttutor_is_tutor($tutor->id, $othercourse->id),
            'The check must be scoped to the given course.'
        );
    }

    /**
     * The course navigation node is added for tutors only.
     */
    public function test_extend_navigation_course_adds_node_for_tutor() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $tutor = $this->getDataGenerator()->create_user();
        $this->assign_tutor_role($tutor->id, $context);
        $this->setUser($tutor);

        $node = new \navigation_node(['text' => 'Course', 'type' => \navigation_node::TYPE_COURSE, 'key' => 'course']);
        local_studenttutor_extend_navigation_course($node, $course, $context);

        $child = $this->get_child_node($node, 'studenttutor_mystudents');
        $this->assertNotNull($child, 'The "My students" node must be added for tutors.');
        $this->assertTrue($child->showinflatnavigation);
    }

    /**
     * The course navigation node is not added for other users.
     */
    public function test_extend_navigation_course_ignores_other_users() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $this->setUser($this->getDataGenerator()->create_user());

        $node = new \navigation_node(['text' => 'Course', 'type' => \navigation_node::TYPE_COURSE, 'key' => 'course']);
        local_studenttutor_extend_navigation_course($node, $course, $context);

        $this->assertNull(
            $this->get_child_node($node, 'studenttutor_mystudents'),
            'The "My students" node must not be added for other users.'
        );
    }

    /**
     * Find a child node by key, or null when it does not exist.
     *
     * navigation_node::$children is a lazily built collection and not an array,
     * so it is traversed instead of using array assertions.
     *
     * @param \navigation_node $node The parent node.
     * @param string $key The child key to look for.
     * @return \navigation_node|null
     */
    private function get_child_node(\navigation_node $node, string $key): ?\navigation_node {
        if ($node->children === null) {
            return null;
        }

        foreach ($node->children as $child) {
            if ($child->key === $key) {
                return $child;
            }
        }

        return null;
    }

    /**
     * Assign the tutor role to a user in a context.
     *
     * @param int $userid The user id.
     * @param \context $context The context where the role is assigned.
     * @return int The role id.
     */
    private function assign_tutor_role(int $userid, \context $context): int {
        $roleid = $this->getDataGenerator()->create_role(['shortname' => self::DEFAULT_TUTOR_ROLE]);
        set_config('tutor_role', self::DEFAULT_TUTOR_ROLE, 'local_studenttutor');
        role_assign($roleid, $userid, $context->id);

        return $roleid;
    }
}
