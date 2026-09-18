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
 * Integrity tests: database schema contract and declared capabilities.
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_studenttutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Integrity tests.
 *
 * These tests check the data and the file layout of the plugin rather than a
 * single class, so they do not cover any code unit in particular.
 *
 * @coversNothing
 */
class plugin_integrity_test extends \advanced_testcase {
    /**
     * install.xml declares the three tables with the expected columns.
     */
    public function test_install_xml_declares_expected_schema() {
        global $CFG;

        $file = new \xmldb_file($CFG->dirroot . '/local/studenttutor/db/install.xml');
        $this->assertTrue($file->loadXMLStructure(), 'install.xml must be valid XMLDB.');

        $expected = [
            'local_studenttutor_assign' => [
                'id', 'studentid', 'tutorid', 'courseid', 'assignedby', 'timeassigned', 'timemodified', 'status',
            ],
            'local_studenttutor_history' => [
                'id', 'studentid', 'tutorid', 'courseid', 'activitytype', 'description',
                'timecreated', 'timemodified', 'createdby', 'activity_date',
            ],
            'local_studenttutor_activity_types' => [
                'id', 'name', 'shortname', 'description', 'icon', 'color', 'active',
                'sortorder', 'timecreated', 'timemodified',
            ],
        ];

        $tables = [];
        foreach ($file->getStructure()->getTables() as $table) {
            $columns = [];
            foreach ($table->getFields() as $field) {
                $columns[] = $field->getName();
            }
            $tables[$table->getName()] = $columns;
        }

        $this->assertSame(array_keys($expected), array_keys($tables));

        foreach ($expected as $tablename => $columns) {
            $this->assertSame($columns, $tables[$tablename], 'Unexpected columns declared for ' . $tablename);
        }
    }

    /**
     * The schema installed in the database matches the declared columns.
     */
    public function test_database_matches_install_xml() {
        global $CFG, $DB;

        $file = new \xmldb_file($CFG->dirroot . '/local/studenttutor/db/install.xml');
        $this->assertTrue($file->loadXMLStructure());

        $dbman = $DB->get_manager();

        foreach ($file->getStructure()->getTables() as $table) {
            $tablename = $table->getName();
            $this->assertTrue(
                $dbman->table_exists(new \xmldb_table($tablename)),
                'Missing table in the database: ' . $tablename
            );

            $declared = [];
            foreach ($table->getFields() as $field) {
                $declared[] = $field->getName();
            }
            $installed = array_keys($DB->get_columns($tablename));

            sort($declared);
            sort($installed);

            $this->assertSame(
                $declared,
                $installed,
                'The installed schema of ' . $tablename . ' differs from install.xml.'
            );
        }
    }

    /**
     * Columns that were never created must not be reintroduced.
     *
     * Regression test: the code used to write to timecreated/createdby in
     * local_studenttutor_assign and to read title from
     * local_studenttutor_history, columns that do not exist in production.
     */
    public function test_legacy_columns_are_not_declared() {
        global $CFG, $DB;

        $assign = $DB->get_columns('local_studenttutor_assign');
        $this->assertArrayHasKey('timeassigned', $assign);
        $this->assertArrayNotHasKey('timecreated', $assign);
        $this->assertArrayNotHasKey('createdby', $assign);

        $history = $DB->get_columns('local_studenttutor_history');
        $this->assertArrayHasKey('activity_date', $history);
        $this->assertArrayNotHasKey('title', $history);

        $file = new \xmldb_file($CFG->dirroot . '/local/studenttutor/db/install.xml');
        $this->assertTrue($file->loadXMLStructure());
        foreach ($file->getStructure()->getTables() as $table) {
            foreach ($table->getFields() as $field) {
                $this->assertNotEquals('title', $field->getName(), 'install.xml must not declare a title field.');
            }
        }
    }

    /**
     * Every capability checked in the code is declared in db/access.php.
     *
     * Regression test: three capabilities were used in the code but never
     * declared, which made has_capability() return false for everybody,
     * including site administrators.
     */
    public function test_capabilities_used_in_code_are_declared() {
        global $CFG;

        $capabilities = [];
        require($CFG->dirroot . '/local/studenttutor/db/access.php');
        $declared = array_keys($capabilities);
        $this->assertNotEmpty($declared);

        $used = [];
        $directory = $CFG->dirroot . '/local/studenttutor';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileinfo) {
            if ($fileinfo->getExtension() !== 'php') {
                continue;
            }
            $path = $fileinfo->getPathname();
            if (strpos($path, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR) !== false) {
                continue;
            }
            $contents = file_get_contents($path);
            if (
                preg_match_all(
                    "/(?:has_capability|require_capability)\(\s*'(local\/studenttutor:[a-z_]+)'/",
                    $contents,
                    $matches
                )
            ) {
                foreach ($matches[1] as $capability) {
                    $used[$capability] = true;
                }
            }
        }

        $this->assertNotEmpty($used, 'The capability scan should find at least one capability.');

        foreach (array_keys($used) as $capability) {
            $this->assertContains(
                $capability,
                $declared,
                'Capability used in the code but not declared in db/access.php: ' . $capability
            );
        }
    }

    /**
     * Every string requested by the code exists in English and in Portuguese.
     *
     * Regression test: fifteen strings were used in the code but were not
     * defined, so the interface displayed the untranslated string markers.
     */
    public function test_strings_used_in_code_are_defined() {
        global $CFG;

        $stringmanager = get_string_manager();
        $used = [];
        $directory = $CFG->dirroot . '/local/studenttutor';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileinfo) {
            if ($fileinfo->getExtension() !== 'php') {
                continue;
            }
            $path = $fileinfo->getPathname();
            if (
                strpos($path, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR) !== false
                    || strpos($path, DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR) !== false
            ) {
                continue;
            }
            $contents = file_get_contents($path);
            if (
                preg_match_all(
                    "/get_string\(\s*'([a-zA-Z0-9_:]+)'\s*,\s*'local_studenttutor'/",
                    $contents,
                    $matches
                )
            ) {
                foreach ($matches[1] as $key) {
                    $used[$key] = true;
                }
            }
        }

        $this->assertNotEmpty($used);

        foreach (array_keys($used) as $key) {
            $this->assertTrue(
                $stringmanager->string_exists($key, 'local_studenttutor', 'en'),
                'String used in the code but missing in English: ' . $key
            );
        }
    }

    /**
     * Strings taken from other components and help buttons must exist too.
     *
     * Regression test: the activity type form asked for get_string('alphanumeric',
     * 'core'), a key that does not exist, so the client side validation message was
     * rendered as "[[[alphanumeric]]]"; the assignment form asked for the help
     * button "course_help", whose "course_help_help" string was missing.
     */
    public function test_external_strings_and_help_buttons_exist() {
        global $CFG;

        $stringmanager = get_string_manager();
        $external = [];
        $help = [];
        $directory = $CFG->dirroot . '/local/studenttutor';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileinfo) {
            if ($fileinfo->getExtension() !== 'php') {
                continue;
            }
            $path = $fileinfo->getPathname();
            if (
                strpos($path, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR) !== false
                    || strpos($path, DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR) !== false
            ) {
                continue;
            }

            $contents = file_get_contents($path);

            // get_string() calls that read a string from another component.
            if (
                preg_match_all(
                    "/get_string\\(\\s*'([a-zA-Z0-9_:]+)'\\s*,\\s*'([^']+)'/",
                    $contents,
                    $matches,
                    PREG_SET_ORDER
                )
            ) {
                foreach ($matches as $match) {
                    if ($match[2] === 'local_studenttutor') {
                        continue;
                    }
                    $external[$match[2] . ':' . $match[1]] = true;
                }
            }

            // addHelpButton() looks for the "{identifier}_help" string.
            if (
                preg_match_all(
                    "/addHelpButton\\(\\s*'[^']+'\\s*,\\s*'([^']+)'\\s*,\\s*'local_studenttutor'/",
                    $contents,
                    $matches
                )
            ) {
                foreach ($matches[1] as $identifier) {
                    $help[$identifier . '_help'] = true;
                }
            }
        }

        $this->assertNotEmpty($external, 'No external strings were found to check.');
        $this->assertNotEmpty($help, 'No help buttons were found to check.');

        foreach (array_keys($external) as $entry) {
            [$component, $key] = explode(':', $entry, 2);
            $this->assertTrue(
                $stringmanager->string_exists($key, $component, 'en'),
                'String used in the code but missing in component ' . $component . ': ' . $key
            );
        }

        foreach (array_keys($help) as $key) {
            $this->assertTrue(
                $stringmanager->string_exists($key, 'local_studenttutor', 'en'),
                'Help string missing for a help button: ' . $key
            );
        }
    }

    /**
     * Both language files declare exactly the same keys, with no duplicates.
     *
     * Regression test: the English pack had 16 duplicated keys and the
     * Portuguese pack was missing 100 keys, which made a large part of the
     * interface fall back to English.
     */
    public function test_language_packs_are_in_sync() {
        global $CFG;

        $english = $this->get_declared_string_keys($CFG->dirroot . '/local/studenttutor/lang/en/local_studenttutor.php');
        $portuguese = $this->get_declared_string_keys($CFG->dirroot . '/local/studenttutor/lang/pt_br/local_studenttutor.php');

        $this->assertNotEmpty($english);
        $this->assertNotEmpty($portuguese);

        sort($english);
        sort($portuguese);

        $this->assertSame(
            $english,
            $portuguese,
            'The English and Portuguese language packs must declare the same keys.'
        );
    }

    /**
     * Read the string keys declared in a language file, keeping duplicates.
     *
     * @param string $path Path to the language file.
     * @return array List of declared keys (duplicates included).
     */
    private function get_declared_string_keys(string $path): array {
        $contents = file_get_contents($path);
        preg_match_all("/^\\\$string\['([^']+)'\]/m", $contents, $matches);

        return $matches[1];
    }
}
