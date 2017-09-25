Test One
========
Requirements
------------
- docker

How to run
----------
- copy `.env.dist` into `.env`, with the right database uri (should be left as is if using docker)
- if docker is installed, a `docker-compose up` and there you go (available on `http://localhost`)

Endpoints
---------
### API
#### Users
- `GET /users` : Gets all the users in a JSON array
- `GET /users/{id}` : Gets a user in a JSON object
- `POST /users`: Creates a user from a JSON object
- `DELETE /tasks/{id}`: Delete a task

#### Tasks
- `GET /tasks` : Gets all the tasks in a JSON array
- `GET /tasks/{id}` : Gets a task in a JSON object
- `POST /tasks`: Creates a task from a JSON object
- `PUT /tasks/{id}`: Edit a task, assign (and unassign) a task
- `DELETE /tasks/{id}`: Delete a task

The `GET /tasks` as a "user" parameter (through its id), to filter the list for
a specific user, e.g `GET /tasks?user={id}`

### Front
Just go for `localhost/front.html` :}

Tests
-----
This "app" is behat covered. Copy the `behat.yml.dist` to `behat.yml`, and if
you are running docker, just a `vendor/bin/behat` should do the trick.

**note** Because of some bugs in behat (shit happens...), it may not run
smoothly, but this should be corrected once
https://github.com/Behat/Behat/pull/1081 is merged. :}
