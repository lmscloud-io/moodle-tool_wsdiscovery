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

namespace tool_wsdiscovery;

use tool_wsdiscovery\fixtures\helper_testing;

/**
 * Tests for Web service discovery
 *
 * @covers    \tool_wsdicovery\helper
 * @package   tool_wsdiscovery
 * @category  test
 * @copyright 2025 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @runTestsInSeparateProcesses
 */
final class helper_test extends \advanced_testcase {
    /**
     * Set up
     */
    protected function setUp(): void {
        global $CFG;
        parent::setUp();
        require_once($CFG->dirroot . '/admin/tool/wsdiscovery/tests/fixtures/helper_testing.php');
    }

    /**
     * Test for the whole output
     */
    public function test_output_contents(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->generate_web_service($user);
        $this->enable_protocol();

        $helper = new helper_testing();
        $helper->set_wstoken('testtoken');

        ob_start();
        $helper->output_content();
        $output = ob_get_contents();
        ob_end_clean();

        $data = json_decode($output, true);
        $this->assertEquals(['rest'], $data['protocols']);
        $this->assertEquals(2, count($data['functions']));
        $function = reset($data['functions']);
        $this->assertEquals('core_course_get_contents', $function['name']);
        $this->assertEquals(
            'external_function_parameters,external_single_structure',
            $function['parameters_desc']['class']
        );
        $this->assertEquals(
            'external_multiple_structure',
            $function['returns_desc']['class']
        );
    }

    /**
     * Generate a web service, a token for the user and add one function to this web service
     *
     * @param \stdClass $user
     */
    protected function generate_web_service($user): void {
        global $DB, $USER;
        // Set current user.
        $this->setAdminUser();

        // Add a web service.
        $webservice = new \stdClass();
        $webservice->name = 'Test web service';
        $webservice->enabled = true;
        $webservice->restrictedusers = false;
        $webservice->component = 'moodle';
        $webservice->timecreated = time();
        $webservice->downloadfiles = true;
        $webservice->uploadfiles = true;
        $externalserviceid = $DB->insert_record('external_services', $webservice);

        // Add token.
        $externaltoken = new \stdClass();
        $externaltoken->token = 'testtoken';
        $externaltoken->tokentype = 0;
        $externaltoken->userid = $user->id;
        $externaltoken->externalserviceid = $externalserviceid;
        $externaltoken->contextid = 1;
        $externaltoken->creatorid = $USER->id;
        $externaltoken->timecreated = time();
        $DB->insert_record('external_tokens', $externaltoken);

        // Add two functions to the service that contain variety of parameter types.
        $wsmethod = new \stdClass();
        $wsmethod->externalserviceid = $externalserviceid;
        $wsmethod->functionname = 'core_course_get_contents';
        $DB->insert_record('external_services_functions', $wsmethod);

        $wsmethod->functionname = 'core_course_get_courses';
        $DB->insert_record('external_services_functions', $wsmethod);
    }

    /**
     * Enables a webservice protocol
     */
    protected function enable_protocol() {
        global $CFG;
        set_config('webserviceprotocols', 'rest');
        assign_capability(
            'webservice/rest:use',
            CAP_ALLOW,
            $CFG->defaultuserroleid,
            \context_system::instance()->id,
            true
        );
    }

    public function test_get_standard_plugins(): void {
        $helper = new helper();
        $standardplugins = $helper->get_standard_plugins();
        $this->assertGreaterThan(200, count($standardplugins));
        $this->assertTrue(in_array('mod_forum', $standardplugins));
        $this->assertFalse(in_array('tool_wsdiscovery', $standardplugins));
    }

    public function test_get_functions(): void {
        $helper = new helper();
        $functions1 = $helper->get_all_functions('', '');
        $this->assertGreaterThan(600, count($functions1));
        $functions2 = $helper->get_all_functions('mod_assign', '');
        $this->assertGreaterThan(3, count($functions2));
        $functions3 = $helper->get_all_functions('', 'mod_assign');
        $this->assertGreaterThan(600, count($functions3));
        $this->assertEquals(count($functions1), count($functions3) + count($functions2));

        $functions4 = $helper->get_all_functions('moodle', '');
        $this->assertGreaterThan(100, count($functions4));
        $this->assertLessThan(count($functions1), count($functions4));

        $functions5 = $helper->get_all_functions('core,mod_assign', '');
        $this->assertGreaterThan(100, count($functions5));
        $this->assertEquals(count($functions5), count($functions2) + count($functions4));
    }

    public function test_get_component_version(): void {
        $helper = new helper();

        $version = $helper->get_component_version('mod_forum');
        $this->assertTrue((bool)preg_match('/^20\d{8}$/', $version));

        $version = $helper->get_component_version('moodle');
        $this->assertTrue((bool)preg_match('/^20\d{8}\.\d\d$/', $version));

        $version = $helper->get_component_version('non_existing_component_xyz');
        $this->assertNull($version);

        $version = $helper->get_component_version('tool_wsdiscovery');
        $this->assertEquals((string)get_config('tool_wsdiscovery', 'version'), $version);
    }
}
