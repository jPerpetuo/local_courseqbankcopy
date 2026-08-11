@local @local_courseqbankcopy
Feature: Import a course with an independent question bank
  In order to archive old courses safely
  As a course administrator
  I need imported quizzes and question banks to be independent from the source course

  Background:
    Given the following config values are set as admin:
      | enableasyncbackup | 0 |
    And the following "courses" exist:
      | fullname      | shortname | category |
      | Source course | CQBC1     | 0        |
      | Target course | CQBC2     | 0        |
    And the following "question categories" exist:
      | contextlevel | reference | name                  |
      | Course       | CQBC1     | Source question bank  |
    And the following "questions" exist:
      | questioncategory     | qtype     | name            | template |
      | Source question bank | truefalse | Question in quiz | true     |
      | Source question bank | truefalse | Unused question  | true     |
    And the following "activities" exist:
      | activity | name        | course | idnumber   |
      | quiz     | Source quiz | CQBC1  | sourcequiz |
    And quiz "Source quiz" contains the following questions:
      | Question in quiz | 1 |

  @javascript
  Scenario: Import copies the complete bank and repoints the imported quiz
    Given I log in as "admin"
    When I import "Source course" course into "Target course" course using this options:
    Then course "CQBC2" has an independent bank copied from "CQBC1" for quiz "Source quiz"

  @javascript
  Scenario: Import repoints random questions to the copied category
    Given the following "questions" exist:
      | questioncategory     | qtype  | name                    |
      | Source question bank | random | Random from source bank |
    And quiz "Source quiz" contains the following questions:
      | question                | page |
      | Random from source bank | 1    |
    And I log in as "admin"
    When I import "Source course" course into "Target course" course using this options:
    Then quiz "Source quiz" in course "CQBC2" uses random questions copied from course "CQBC1"
