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
 * Upcoming deadlines for one course.
 *
 * @package    report_deadlines
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

use report_deadlines\local\deadlines;

$courseid = required_param('course', PARAM_INT);
$days = optional_param('days', 14, PARAM_INT);
if (!in_array($days, [7, 14, 30, 60], true)) {
    $days = 14;
}

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('report/deadlines:view', $context);

$url = new moodle_url('/report/deadlines/index.php', ['course' => $course->id, 'days' => $days]);
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pluginname', 'report_deadlines') . ': ' . format_string($course->shortname));
$PAGE->set_heading(format_string($course->fullname, true, ['context' => $context]));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_deadlines'));

$options = [];
foreach ([7, 14, 30, 60] as $n) {
    $options[$n] = get_string('ndays', 'report_deadlines', $n);
}
$select = new single_select(
    new moodle_url('/report/deadlines/index.php', ['course' => $course->id]),
    'days',
    $options,
    $days,
    null
);
$select->set_label(get_string('days', 'report_deadlines'));
echo $OUTPUT->render($select);

$rows = deadlines::for_course($course->id, $days);
if (!$rows) {
    echo $OUTPUT->notification(get_string('nodeadlines', 'report_deadlines', $days), 'info');
} else {
    $table = new html_table();
    $table->head = [get_string('activity', 'report_deadlines'), get_string('due', 'report_deadlines'),
        get_string('students', 'report_deadlines'), get_string('submitted', 'report_deadlines'),
        get_string('missing', 'report_deadlines'), ''];
    $table->attributes['class'] = 'generaltable report_deadlines';
    foreach ($rows as $r) {
        $cmurl = new moodle_url('/mod/assign/view.php', ['id' => $r->cmid]);
        $gradeurl = new moodle_url('/mod/assign/view.php', ['id' => $r->cmid, 'action' => 'grading']);
        $table->data[] = [
            html_writer::link($cmurl, format_string($r->name, true, ['context' => $context])),
            userdate($r->duedate, get_string('strftimedaydatetime', 'langconfig')),
            $r->students,
            $r->submitted,
            $r->missing ? html_writer::tag('strong', $r->missing) : 0,
            html_writer::link($gradeurl, get_string('open', 'report_deadlines')),
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
