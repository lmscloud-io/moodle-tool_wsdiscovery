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

/**
 * Class helper
 *
 * @package   tool_wsdiscovery
 * @copyright 2025 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /**
     * Returns the list of all web service protocols that are enabled and user has permission to use
     *
     * @param \stdClass $user
     * @return array
     */
    protected function get_available_protocols($user) {
        $available = [];
        $allprotocols = array_keys(\core_component::get_plugin_list('webservice'));
        foreach ($allprotocols as $protocol) {
            if (
                webservice_protocol_is_enabled($protocol) &&
                has_capability("webservice/$protocol:use", \context_system::instance(), $user)
            ) {
                $available[] = $protocol;
            }
        }
        return $available;
    }

    /**
     * Outputs list of web service protocols and functions available to the user in JSON format.
     *
     * @throws \moodle_exception
     */
    public function output_content() {
        global $CFG;
        require_once($CFG->dirroot . '/webservice/lib.php');
        require_once($CFG->dirroot . '/lib/externallib.php');

        if (!$wstoken = $this->get_wstoken()) {
            return;
        }

        $wsmanager = new \webservice();
        try {
            $authenticationinfo = $wsmanager->authenticate_user($wstoken);
        } catch (\moodle_exception $ex) {
            $this->output_error($ex, 401);
            return;
        }
        $user = $authenticationinfo['user'];
        $protocols = $this->get_available_protocols($user);
        if (empty($protocols)) {
            $this->output_error(new \moodle_exception('noprotocols', 'tool_wsdiscovery'), 403);
            return;
        }
        $service = $authenticationinfo['service'];
        $functions = $wsmanager->get_external_functions([$service->id => $service->id]);
        $res = [];
        foreach ($functions as $function) {
            $res[] = (new wsfunction($function))->to_array();
        }
        echo json_encode([
            'protocols' => $protocols,
            'functions' => $res,
        ], JSON_PRETTY_PRINT) . "\n";
    }

    /**
     * Build the error information in JSON format and outputs it
     *
     * @param \Throwable $ex the exception we are converting in the server rest format
     * @param int $code response code
     * @return void
     */
    public function output_error($ex, $code = 500) {
        http_response_code($code);
        $errorobject = new \stdClass();
        $errorobject->exception = get_class($ex);
        if (isset($ex->errorcode)) {
            $errorobject->errorcode = $ex->errorcode;
        }
        $errorobject->message = $ex->getMessage();
        if (debugging() && isset($ex->debuginfo)) {
            $errorobject->debuginfo = $ex->debuginfo;
        }
        $error = json_encode($errorobject);
        echo $error;
    }

    /**
     * Get headers from Apache websever.
     *
     * @return array $returnheaders The headers from Apache.
     */
    protected function get_apache_headers() {
        $capitalizearray = [
            'content-type',
            'accept',
            'authorization',
            'content-length',
            'user-agent',
            'host',
        ];
        $headers = apache_request_headers();
        $returnheaders = [];

        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $capitalizearray)) {
                $header = 'HTTP_' . strtoupper($key);
                $header = str_replace('-', '_', $header);
                $returnheaders[$header] = $value;
            }
        }

        return $returnheaders;
    }

    /**
     * Extract the HTTP headers out of the request.
     *
     * @param array $headers Optional array of headers, to assist with testing.
     * @return array $headers HTTP headers.
     */
    protected function get_headers($headers = null) {
        $returnheaders = [];

        if (!$headers) {
            if (function_exists('apache_request_headers')) {
                // Apache webserver.
                $headers = $this->get_apache_headers();
            } else {
                // Nginx webserver.
                $headers = $_SERVER;
            }
        }

        foreach ($headers as $key => $value) {
            if (substr($key, 0, 5) == 'HTTP_') {
                $returnheaders[$key] = $value;
            }
        }

        return $returnheaders;
    }

    /**
     * Get the webservice authorization token from the request.
     * Throws error and notifies caller on failure.
     *
     * @return string|null $wstoken The extracted webservice authorization token.
     */
    protected function get_wstoken() {
        $headers = $this->get_headers();

        if (isset($headers['HTTP_AUTHORIZATION'])) {
            $wstoken = $headers['HTTP_AUTHORIZATION'];
        } else {
            // Raise an error if auth header not supplied.
            $ex = new \moodle_exception('noauthheader', 'tool_wsdiscovery', '');
            $this->output_error($ex, 401);
            return null;
        }

        // Remove "Bearer " from the token.
        $wstoken = str_replace('Bearer ', '', $wstoken);

        return $wstoken;
    }
}
