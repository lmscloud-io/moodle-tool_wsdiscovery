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
 */
final class helper_test extends \advanced_testcase {
    /**
     * Set up
     */
    protected function setUp(): void {
        global $CFG;
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
        $this->assertEquals(1, count($data['functions']));
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

        // Add a function to the service.
        $wsmethod = new \stdClass();
        $wsmethod->externalserviceid = $externalserviceid;
        $wsmethod->functionname = 'core_course_get_contents';
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
}
