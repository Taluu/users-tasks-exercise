@http @api @rest
Feature: Fetch Users through API

Scenario: Fetch the list of users
  When I send a "GET" request to "/users"
  Then the status code should be 200
    And the response should be a valid json response

Scenario: Fetch a user
  When I send a "GET" request to "/users/1"
  Then the status code should be 200
    And the response should be a valid json response
    And in the json, "@id" should be equal to "/users/1"
    And in the json, "data.id" should be equal to "1"
    And in the json, "data.name" should be equal to "foo"
    And in the json, "data.email" should be equal to "foo@localhost"
    And in the json, "data.tasks" should have 3 elements

Scenario: An unknown user should trigger a 404
  When I send a "GET" request to "/users/1000"
  Then the status code should be 404
    And the response should be a valid json response
    And in the json, "error" should be equal to "User 1000 not found."
