<?php
// This file is part of Moodle - https://moodle.org/
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
 * Navigation hooks.
 *
 * @package    report_deadlines
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add the report to the course Reports menu.
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context $context
 */
function report_deadlines_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('report/deadlines:view', $context)) {
        $url = new moodle_url('/report/deadlines/index.php', ['course' => $course->id]);
        $navigation->add(
            get_string('pluginname', 'report_deadlines'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'report_deadlines',
            new pix_icon('i/report', '')
        );
    }
}
