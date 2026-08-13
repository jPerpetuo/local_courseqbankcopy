@local @local_courseqbankcopy
Feature: Import a course with an independent question bank
  In order to archive old courses safely
  As a course administrator
  I need imported quizzes and question banks to be independent from the source course

  Background:
    Given the following config values are set as admin:
      | enableasyncbackup | 0 |
    And the following "courses" exist:
      | fullname            | shortname | category |
      | Original bank course | CQBC0     | 0        |
      | Source course       | CQBC1     | 0        |
      | Intermediate course | CQBCMID   | 0        |
      | Target course       | CQBC2     | 0        |
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
    Given quiz "Source quiz" in course "CQBC1" has a random question from category "Source question bank"
    And I log in as "admin"
    When I import "Source course" course into "Target course" course using this options:
    Then quiz "Source quiz" in course "CQBC2" uses random questions copied from course "CQBC1"

  @javascript
  Scenario: Import repoints random questions stored in the legacy filter format
    Given quiz "Source quiz" in course "CQBC1" has a random question from category "Source question bank"
    And random questions in quiz "Source quiz" in course "CQBC1" use the legacy filter format
    And I log in as "admin"
    When I import "Source course" course into "Target course" course using this options:
    Then quiz "Source quiz" in course "CQBC2" uses random questions copied from course "CQBC1"

  @javascript
  Scenario: Import repoints random questions from a bank copied by a previous import
    Given I log in as "admin"
    When I import "Source course" course into "Intermediate course" course using this options:
    And the following "activities" exist:
      | activity | name       | course  | idnumber  |
      | quiz     | Chain quiz | CQBCMID | chainquiz |
    And quiz "Chain quiz" in course "CQBCMID" has a random question from category "Source question bank"
    And I import "Intermediate course" course into "Target course" course using this options:
    Then quiz "Chain quiz" in course "CQBC2" uses random questions copied from course "CQBCMID"

  @javascript
  Scenario: Import repoints five random questions added through the shared-bank interface
    Given the following "activities" exist:
      | activity | name          | course | idnumber     | type   |
      | qbank    | Imported bank | CQBC0  | importedbank | system |
    And the following "question categories" exist:
      | contextlevel    | reference    | name              |
      | Activity module | importedbank | Imported category |
    And the following "questions" exist:
      | questioncategory  | qtype     | name       | template |
      | Imported category | truefalse | Imported 1 | true     |
      | Imported category | truefalse | Imported 2 | true     |
      | Imported category | truefalse | Imported 3 | true     |
      | Imported category | truefalse | Imported 4 | true     |
      | Imported category | truefalse | Imported 5 | true     |
    And I log in as "admin"
    And I import "Original bank course" course into "Intermediate course" course using this options:
    And the following "activities" exist:
      | activity | name           | course  | idnumber   |
      | quiz     | Five random quiz | CQBCMID | randomquiz |
    And I am on the "Five random quiz" "mod_quiz > Edit" page
    And I open the "last" add to quiz menu
    And I follow "a random question"
    And I click on "Switch bank" "button"
    And I click on "Imported bank" "link" in the "Select question bank" "dialogue"
    And I apply question bank filter "Category" with value "Imported category"
    And I select "5" from the "randomcount" singleselect
    And I press "Add random question"
    When I import "Intermediate course" course into "Target course" course using this options:
    Then quiz "Five random quiz" in course "CQBC2" uses random questions copied from course "CQBCMID"
