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

use core\exception\coding_exception;

/**
 * Processes information about a web service function
 *
 * @package   tool_wsdiscovery
 * @copyright 2025 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wsfunction {
    /** @var \stdClass web service function information (including the parameters/returns objects)  */
    protected $function;

    /**
     * Constructor
     *
     * @param \stdClass $functionrecord record from the database table external_functions
     * @throws \coding_exception
     */
    public function __construct(\stdClass $functionrecord) {
        global $CFG;
        require_once($CFG->dirroot . '/lib/externallib.php');
        $this->function = \external_api::external_function_info($functionrecord);
    }

    /**
     * Some post-processing for preparing input/output parameters for export
     *
     * @param mixed $value
     * @param mixed $rv
     */
    protected function post_process_value($value, &$rv) {
        if (
            $value instanceof \external_value && $value->type == PARAM_INT
            && $value->default >= time() - 1 && $value->default <= time()
        ) {
            // Avoid including current time as default value, it will be outdated after 1 second...
            $rv['default'] = 0;
        }
        if ($value instanceof \external_value && $value->type == PARAM_LANG) {
            $rv['enum'] = array_keys(get_string_manager()->get_list_of_translations());
        }
        if ($value instanceof \external_value && $value->type == PARAM_THEME) {
            $rv['enum'] = array_keys(\core_component::get_plugin_list('theme'));
        }
    }

    /**
     * Prepares the value to be json-encoded (recursive method)
     *
     * @param mixed $value
     * @return array|string|float|bool
     */
    protected function prepare_value($value) {
        if ($value instanceof \external_description || is_array($value) || is_object($value)) {
            $rv = [];
            if ($value instanceof \external_description) {
                $rv['class'] = join(',', self::class_name(get_class($value)));
                if ($value->required != VALUE_DEFAULT) {
                    // Remove occasional mess in WS definitions that is not picked up by core because core ignores
                    // the property default unless the value should have a default.
                    $value->default = null;
                }
            }
            foreach ($value as $k => $v) {
                $rv[$k] = self::prepare_value($v);
            }
        } else {
            $rv = $value;
        }
        $this->post_process_value($value, $rv);
        return $rv;
    }

    /**
     * Returns a list of class names in the inheritance chain
     *
     * Only returns the last part of the namespaced classes.
     * Stops at external_description (abstract class that is always the parent).
     *
     * @param string|false $classname
     * @return array
     */
    protected function class_name($classname): array {
        $shortclassname = $classname ? preg_replace('/^(.*\\\\)/', '', $classname) : '';
        if ($shortclassname == "external_description" || empty($classname)) {
            return [];
        }
        return array_merge([$shortclassname], self::class_name(get_parent_class($classname)));
    }

    /**
     * Converts the function information to array that is ready to be JSON-encoded
     *
     * Also excludes properties that are not important for interactions with this web service function.
     *
     * @return array
     */
    public function to_array(): array {
        $info = [];
        foreach (self::prepare_value($this->function) as $key => $value) {
            if (
                !preg_match('/_method$/', $key) &&
                    !in_array($key, ['classpath', 'classname', 'methodname', 'id'])
            ) {
                $info[$key] = $value;
            }
        }
        return $info;
    }
}
