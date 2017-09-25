@http @api @rest
Feature: Create tasks through API

Scenario: Create a task with missing title field
  When I create a "POST" request to "/tasks"
    And I set the following body:
    """
{
  "description": "fubar"
}
    """
    And I send the request
  Then the status code should be 400
    And the response should be a valid json response
    And in the json, "message" should be equal to "validation failed"
    And in the json, "errors.title" should have 1 element
    And in the json, "errors.title[0]" should be equal to "missing mandatory field"

Scenario: Create a task with invalid user
  When I create a "POST" request to "/tasks"
    And I set the following body:
    """
{
  "title": "fubar test :}",
  "user": "fubar"
}
    """
    And I send the request
  Then the status code should be 400
    And the response should be a valid json response
    And in the json, "message" should be equal to "validation failed"
    And in the json, "errors.user" should have 1 element
    And in the json, "errors.user[0]" should be equal to "wrong format"

Scenario: Create a task
  When I create a "POST" request to "/tasks"
    And I set the following body:
    """
{
  "title": "fubar test",
}
    """
    And I send the request
  Then the status code should be 201
    And the response should be a valid json response
    And in the json, "@id" should match "{^/tasks/[0-9]+$}"
    And in the json, "data.id" should match "{^[0-9]+$}"
    And in the json, "data.title" should be equal to "fubar test"
    And in the json, "data.description" should be null
    And in the json, "data.user" should be null
    And in the json, "data.status" should be equal to "todo"
