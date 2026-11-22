@report @report_deadlines
Feature: Upcoming deadlines report
  In order to chase missing work before it is late
  As a teacher
  I need to see which assignments are due soon and how many students have not submitted

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username |
      | teacher1 |
      | student1 |
      | student2 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "activities" exist:
      | activity | course | name  | duedate             | assignsubmission_onlinetext_enabled |
      | assign   | C1     | Essay | ##+3 days##         | 1                                   |
      | assign   | C1     | Later | ##+40 days##        | 1                                   |

  Scenario: Teacher opens the report from the course Reports menu
    Given I am on the "Course 1" course page logged in as "teacher1"
    When I navigate to "Reports > Upcoming deadlines" in current page administration
    Then I should see "Essay"
    And I should not see "Later"
    And "Essay" row "Not submitted" column of "report_deadlines" table should contain "2"
