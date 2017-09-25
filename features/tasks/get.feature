@http @api @rest
Feature: Fetch Tasks through API

Scenario: Fetch the list of tasks
  When I send a "GET" request to "/tasks"
  Then the status code should be 200
    And the response should be a valid json response

Scenario: Fetch the list of tasks for a specific user
  When I create a "GET" request to "/tasks"
    And I set the value "1" to the parameter "user"
    And I send the request
  Then the status code should be 200
    And the response should be a valid json response

Scenario: Fetch a task
  When I send a "GET" request to "/tasks/1"
  Then the status code should be 200
    And the response should be a valid json response
    And in the json, "@id" should be equal to "/tasks/1"
    And in the json, "data.id" should be equal to "1"
    And in the json, "data.title" should be equal to "foo #1"
    And in the json, "data.description" should be null
    And in the json, "data.status" should be equal to "todo"
    And in the json, "data.user" should be equal to "/users/1"

Scenario: An unknown task should trigger a 404
  When I send a "GET" request to "/tasks/1000"
  Then the status code should be 404
    And the response should be a valid json response
    And in the json, "error" should be equal to "Task 1000 not found."
