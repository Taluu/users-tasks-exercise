@http @rest @api
Feature: Assign a task through the API

Scenario: Try to assign an unknown user to a task
  When I create a "PUT" request to "/tasks/1"
    And I set the following body:
    """
{
  "user": "/users/1000"
}
    """
    And I send the request
  Then the status code should be 400
    And the response should be a valid json response
    And in the json, "message" should be equal to "validation failed"
    And in the json, "errors.user[0]" should be equal to "/users/1000 not found."

Scenario: Assign a task to an user
  When I create a "PUT" request to "/tasks/1"
    And I set the following body:
    """
{
  "user": "/users/2"
}
    """
    And I send the request
  Then the status code should be 200
    And the response should be a valid json response
    And in the json, "@id" should be "/tasks/2"
    And in the json, "data.user" should be equal to "/users/2"

Scenario: Revoke a task from a user
  When I create a "PUT" request to "/tasks/1"
    And I set the following body:
    """
{
  "user": null
}
    """
    And I send the request
  Then the status code should be 200
    And the response should be a valid json response
    And in the json, "@id" should be "/tasks/2"
    And in the json, "data.user" should be null
