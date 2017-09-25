@http @api @rest
Feature: Create users through API

Scenario Outline: Create a user with missing name and/or email field
  When I create a "POST" request to "/users"
    And I set the following body:
    """
{
  "<field>": "<value>"
}
    """
    And I send the request
  Then the status code should be 400
    And the response should be a valid json response
    And in the json, "message" should be equal to "validation failed"
    And in the json, "errors.<error>" should have 1 element
    And in the json, "errors.<error>[0]" should be equal to "missing mandatory field"

  Examples:
      | field | value               | error |
      | name  | fubar               | email |
      | email | fubar@localhost.tld | name  |

Scenario: Create a user with invalid email
  When I create a "POST" request to "/users"
    And I set the following body:
    """
{
  "name": "fubar wrong email",
  "email": "fubar"
}
    """
    And I send the request
  Then the status code should be 400
    And the response should be a valid json response
    And in the json, "message" should be equal to "validation failed"
    And in the json, "errors.email" should have 1 element
    And in the json, "errors.email[0]" should be equal to "Value must be an email, fubar given."

Scenario: Create a user
  When I create a "POST" request to "/users"
    And I set the following body:
    """
{
  "name": "fubar test",
  "email": "fubar@localhost.tld"
}
    """
    And I send the request
  Then the status code should be 201
    And the response should be a valid json response
    And in the json, "@id" should match "{^/users/[0-9]+$}"
    And in the json, "data.id" should match "{^[0-9]+$}"
    And in the json, "data.name" should be equal to "fubar test"
    And in the json, "data.email" should be equal to "fubar@localhost.tld"
    And in the json, "data.tasks" should be empty
