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
 * CLI tool to generate JSON file with web services discovery information.
 *
 * @package    tool_wsdiscovery
 * @copyright  Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once($CFG->dirroot . '/webservice/lib.php');
require_once($CFG->dirroot . '/lib/externallib.php');


[$options, $unrecognised] = cli_get_params(
    [
        'help' => false,
        'include' => '',
        'exclude' => '',
        'group-by-component' => false,
    ],
    ['h' => 'help']
);

$help = <<<EOT
Ad hoc cron tasks.

Options:
 -h, --help                Print out this help
     --include=LIST        List of components to include (comma separated). 'moodle' for core web services,
                           'standard' for standard plugins, 'addons' for all non-standard plugins,
                           or a full pluginname for a specific plugin. By default - all components.
     --exclude=LIST        List of components to exclude (comma separated), if specified together with
                           the --include, applies after the include filter.
     --group-by-component  Groups the functions by component in the generated JSON and includes the component
                           version (also treated as web service version in `core_webservice_get_site_info`).

Unlike the web version, this CLI script does not take the web service token as argument and generates JSON
for all exisisting web service functions, not only available for the current user.

Examples:

To generate JSON for core and standard plugins and group by component:
\$sudo -u www-data /usr/bin/phpadmin/tool/wsdiscovery/cli/generate_json.php --include=moodle,standard --group-by-component

To include all non-standard plugins except for tool_idonotneed:
\$sudo -u www-data /usr/bin/phpadmin/tool/wsdiscovery/cli/generate_json.php --include=addons --exclude=tool_idonotneed

EOT;


if ($options['help']) {
    echo $help;
    exit(0);
}

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognised));
}

$helper = new \tool_wsdiscovery\helper();
$functions = $helper->get_all_functions($options['include'], $options['exclude']);
if ($options['group-by-component']) {
    $grouped = [];
    foreach ($functions as $function) {
        $component = $function['component'];
        if (!isset($grouped[$component])) {
            $grouped[$component] = [
                'version' => $helper->get_component_version($component),
                'functions' => [],
            ];
        }
        $grouped[$component]['functions'][] = $function;
    }
    echo json_encode(['components' => $grouped], JSON_PRETTY_PRINT) . "\n";
} else {
    echo json_encode(['functions' => $functions], JSON_PRETTY_PRINT) . "\n";
}
