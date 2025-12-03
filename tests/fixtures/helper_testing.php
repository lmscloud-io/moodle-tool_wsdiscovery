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

namespace tool_wsdiscovery\fixtures;

/**
 * Tests for Web service discovery
 *
 * @package   tool_wsdiscovery
 * @category  test
 * @copyright 2025 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper_testing extends \tool_wsdiscovery\helper {
    /** @var string */
    protected $wstoken;

    /**
     * Set WS token for testing
     *
     * @param string $wstoken
     */
    public function set_wstoken($wstoken) {
        $this->wstoken = $wstoken;
    }

    /**
     * Overrides the get_wstoken method to return a testing token
     *
     * @return string
     */
    protected function get_wstoken() {
        return $this->wstoken;
    }
}
