@mod @mod_clickableboxes @javascript
Feature: Add and manage clickable boxes activity
  In order to provide visual grid navigation for students
  As a teacher
  I need to be able to add a clickableboxes activity to a course and edit its boxes

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: Add a clickableboxes activity to a course and edit a box via JS grid
    Given I am on the "Course 1" course page logged in as teacher1
    And I turn editing mode on
    When I add a "Clickable boxes" to section "1"
    And I set the following fields to these values:
      | Name        | Visual Navigation Grid                 |
      | Description | Welcome to the visual grid navigation. |
    # Interact with the custom JS admin grid to open the first box's fields
    And I click on ".clickablebox-admin-card[data-target='0']" "css_element"
    # Now that the group is visible, we can set the link
    And I set the field "boxlink[0]" to "https://moodle.org"
    And I press "Save and return to course"
    Then I should see "Visual Navigation Grid"
    # View the activity as the teacher
    And I click on "Visual Navigation Grid" "link"
    And I should see "Welcome to the visual grid navigation."
    # Verify that the frontend grid is rendered
    And ".clickablebox-item" "css_element" should exist
    And I log out

  Scenario: A student views an existing clickableboxes activity
    Given the following "activities" exist:
      | activity       | course | idnumber | name         | intro                     |
      | clickableboxes | C1     | cb1      | Student Grid | Please click a box below. |
    And I am on the "Course 1" course page logged in as student1
    When I click on "Student Grid" "link"
    Then I should see "Please click a box below."
    And ".mod_clickableboxes_grid" "css_element" should exist

