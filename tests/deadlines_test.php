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

namespace report_deadlines;

use report_deadlines\local\deadlines;

/**
 * Tests for the upcoming deadlines data.
 *
 * @package    report_deadlines
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_deadlines\local\deadlines
 */
final class deadlines_test extends \advanced_testcase {
    public function test_counts_who_has_not_submitted(): void {
        global $DB;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $s1 = $gen->create_and_enrol($course, 'student');
        $s2 = $gen->create_and_enrol($course, 'student');
        $gen->create_and_enrol($course, 'student');
        $gen->create_and_enrol($course, 'editingteacher');
        $soon = $gen->create_module('assign', ['course' => $course->id, 'name' => 'Essay', 'duedate' => time() + 3 * DAYSECS]);
        $gen->create_module('assign', ['course' => $course->id, 'name' => 'Later', 'duedate' => time() + 40 * DAYSECS]);
        $gen->create_module('assign', ['course' => $course->id, 'name' => 'Past', 'duedate' => time() - DAYSECS]);
        $gen->create_module('assign', ['course' => $course->id, 'name' => 'No date', 'duedate' => 0]);

        $DB->insert_record('assign_submission', (object)['assignment' => $soon->id, 'userid' => $s1->id,
            'status' => 'submitted', 'latest' => 1, 'attemptnumber' => 0, 'timecreated' => time(), 'timemodified' => time()]);
        $DB->insert_record('assign_submission', (object)['assignment' => $soon->id, 'userid' => $s2->id,
            'status' => 'draft', 'latest' => 1, 'attemptnumber' => 0, 'timecreated' => time(), 'timemodified' => time()]);

        $rows = deadlines::for_course($course->id, 14);
        $this->assertCount(1, $rows);
        $this->assertSame('Essay', $rows[0]->name);
        $this->assertSame(3, $rows[0]->students);
        $this->assertSame(1, $rows[0]->submitted);
        $this->assertSame(2, $rows[0]->missing);
        $this->assertSame((int)$soon->cmid, $rows[0]->cmid);
    }

    public function test_sorted_by_due_date_and_hidden_ones_skipped(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $gen->create_and_enrol($course, 'student');
        $gen->create_module('assign', ['course' => $course->id, 'name' => 'B', 'duedate' => time() + 5 * DAYSECS]);
        $gen->create_module('assign', ['course' => $course->id, 'name' => 'A', 'duedate' => time() + 2 * DAYSECS]);
        $gen->create_module('assign', ['course' => $course->id, 'name' => 'Hidden', 'duedate' => time() + DAYSECS,
            'visible' => 0]);
        $names = array_map(fn($r) => $r->name, deadlines::for_course($course->id, 14));
        $this->assertSame(['A', 'B'], $names);
    }
}
