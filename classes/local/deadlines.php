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

namespace report_deadlines\local;

/**
 * Assignments due soon in a course, with how many students have not submitted.
 *
 * @package    report_deadlines
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class deadlines {
    /**
     * Visible assignments due between now and $days from now, soonest first.
     *
     * Students are active enrolled users who can submit to that assignment. Submitted counts their latest attempt
     * with status "submitted"; drafts count as not submitted.
     *
     * @param int $courseid
     * @param int $days
     * @return \stdClass[] rows with cmid, name, duedate, students, submitted, missing
     */
    public static function for_course(int $courseid, int $days): array {
        global $DB;
        $now = time();
        $modinfo = get_fast_modinfo($courseid);
        $assigns = $DB->get_records_select(
            'assign',
            'course = :course AND duedate > :now AND duedate <= :until',
            ['course' => $courseid, 'now' => $now, 'until' => $now + $days * DAYSECS],
            'duedate ASC, id ASC',
            'id, name, duedate, teamsubmission, teamsubmissiongroupingid'
        );
        $rows = [];
        foreach ($assigns as $assign) {
            $cm = $modinfo->instances['assign'][$assign->id] ?? null;
            if (!$cm || !$cm->visible) {
                continue;
            }
            $context = \context_module::instance($cm->id);
            [$esql, $params] = get_enrolled_sql($context, 'mod/assign:submit', 0, true);
            $students = $DB->count_records_sql("SELECT COUNT(1) FROM ($esql) je", $params);
            $params['assignment'] = $assign->id;
            $params['status'] = 'submitted';
            if ($assign->teamsubmission) {
                // Team assignments store one submission per group (userid 0); every member of that group has submitted.
                $grouping = '';
                if ($assign->teamsubmissiongroupingid) {
                    $grouping = 'AND gm.groupid IN (SELECT gg.groupid FROM {groupings_groups} gg WHERE gg.groupingid = :grouping)';
                    $params['grouping'] = $assign->teamsubmissiongroupingid;
                }
                $submitted = $DB->count_records_sql("SELECT COUNT(DISTINCT gm.userid)
                      FROM {groups_members} gm
                      JOIN ($esql) je ON je.id = gm.userid
                      JOIN {assign_submission} s ON s.groupid = gm.groupid AND s.userid = 0
                     WHERE s.assignment = :assignment AND s.latest = 1 AND s.status = :status $grouping", $params);
            } else {
                $submitted = $DB->count_records_sql("SELECT COUNT(DISTINCT s.userid)
                      FROM {assign_submission} s
                      JOIN ($esql) je ON je.id = s.userid
                     WHERE s.assignment = :assignment AND s.latest = 1 AND s.status = :status", $params);
            }
            $rows[] = (object)[
                'cmid' => (int)$cm->id,
                'name' => $assign->name,
                'duedate' => (int)$assign->duedate,
                'students' => (int)$students,
                'submitted' => (int)$submitted,
                'missing' => (int)$students - (int)$submitted,
            ];
        }
        return $rows;
    }
}
