var one = {
    "tasks": {},
    "users": {},

    "redraw": function (document) {
        one.redraw_users(document, one.users);
        one.redraw_tasks(document, one.tasks);

        one.register(document, 'task', 'edit', function (target) { console.log(target); });
        one.register(document, 'task', 'delete', function (target) { console.log(target); });
        one.register(document, 'user', 'delete', function (target) { console.log(target); });
    },

    "redraw_users": function (document, users) {
        let old_list = document.getElementById("users_list");
        let new_list  = document.createElement("tbody");

        for (var id in users) {
            let row, cell;
            let user = users[id];

            row = document.createElement("tr");

            cell = document.createElement("td");
            cell.appendChild(document.createTextNode(user.name));
            row.appendChild(cell);

            cell = document.createElement("td");
            cell.appendChild(document.createTextNode(user.email));
            row.appendChild(cell);

            cell = document.createElement("td");

            user.tasks.forEach(function (task) {
                let span = document.createElement("span");
                span.setAttribute("name", "user_tasks");
                span.appendChild(document.createTextNode(task.title + " (" + task.status + ")"));

                cell.appendChild(span);
            });

            row.appendChild(cell);

            cell = document.createElement("td");
            let span = document.createElement("span");
            span.setAttribute("name", "user_delete");
            span.setAttribute("user-id", user.id);
            span.appendChild(document.createTextNode("Delete"));
            cell.appendChild(span);
            row.appendChild(cell);

            new_list.appendChild(row);
        }

        old_list.parentNode.replaceChild(new_list, old_list);
    },

    "redraw_tasks": function (document, tasks) {
        let old_list = document.getElementById("tasks_list");
        let new_list  = document.createElement("tbody");

        for (var id in tasks) {
            let row, cell, span;
            let task = tasks[id];

            row = document.createElement("tr");

            cell = document.createElement("td");
            cell.appendChild(document.createTextNode(task.title));
            row.appendChild(cell);

            cell = document.createElement("td");
            cell.appendChild(document.createTextNode(task.description));
            row.appendChild(cell);

            cell = document.createElement("td");
            cell.appendChild(document.createTextNode(task.status));
            row.appendChild(cell);

            cell = document.createElement("td");
            cell.appendChild(document.createTextNode(task.created_at.toLocaleDateString() + " " + task.created_at.toLocaleTimeString()));
            row.appendChild(cell);

            cell = document.createElement("td");
            cell.appendChild(document.createTextNode(task.user ? task.user.name : 'nobody'));
            row.appendChild(cell);

            cell = document.createElement("td");
                span = document.createElement("span");
                span.setAttribute("name", "task_edit");
                span.setAttribute("task-id", task.id);
                span.appendChild(document.createTextNode("Edit"));
            cell.appendChild(span);
            cell.appendChild(document.createTextNode(" - "));
                span = document.createElement("span");
                span.setAttribute("name", "task_delete");
                span.setAttribute("task-id", task.id);
                span.appendChild(document.createTextNode("Delete"));
            cell.appendChild(span);
            row.appendChild(cell);

            new_list.appendChild(row);
        }

        old_list.parentNode.replaceChild(new_list, old_list);
    },

    "register": function (document, name, action, callable) {
        var selector = "span[name=\"" + name + "_" + action + "\"]";

        document
            .querySelectorAll(selector)
                .forEach(function (span) {
                    span.addEventListener(
                        'click',
                        function () {
                            var target = {};
                            var targetName = "";

                            if ("user" === name) {
                                target = one.users["/users/" + span.attributes['user-id'].value];
                                targetName = target.name;
                            } else {
                                target = one.tasks["/tasks/" + span.attributes['task-id'].value];
                                targetName = target.title;
                            }

                            if ("delete" === action && !confirm("Delete " + name + " " + targetName + " ?")) {
                                return;
                            }

                            callable(target);
                        },
                        false
                    );
                })
        ;
    },

    "load": function (path, status, success, error) {
        let xhr = new XMLHttpRequest();

        xhr.onreadystatechange = function() {
            if (XMLHttpRequest.DONE === xhr.readyState) {
                if (status === xhr.status && success) {
                    success(JSON.parse(xhr.responseText));
                } else if (status !== xhr && error) {
                    error(xhr);
                }
            }
        };

        xhr.open("GET", path, true);
        xhr.send();
    }
};

(function (document) {
                one.load("//localhost/tasks", 200, function (tasks) {
                    tasks.forEach(function (task) {
                        task.data.created_at = new Date(task.data.created_at);
                        one.tasks[task["@id"]] = task.data;
                    });

                    one.load("//localhost/users", 200, function (users) {
                        users.forEach(function (user) {
                            user.data.tasks.forEach(function (task_id, key) {
                                user.data.tasks[key] = one.tasks[task_id];
                            });

                            one.users[user["@id"]] = user.data;
                        });

                        tasks.forEach(function (task) {
                            if (null === task.data.user) {
                                return;
                            }

                            task.data.user = one.users[task.data.user];
                        });

                        one.redraw(document);
                    });
                }, function (xhr) { console.error(xhr); });
            })(document);
