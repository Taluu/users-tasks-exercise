var one = {
    "tasks": {},
    "users": {},

    "redraw": function (document) {
        let that = this;

        this.redraw_users(document, this.users);
        this.redraw_tasks(document, this.tasks);

        this.register(document, 'task', 'edit', function (target) {
            console.log(target);
        });

        this.register(document, 'task', 'delete', function (task) {
            let id = "/tasks/" + task.id;

            that.send("DELETE", "//localhost" + id, 204, function () {
                for (let id in that.users) {
                    let user = that.users[id];
                    let index = user.tasks.indexOf(task);

                    if (-1 !== index) {
                        user.tasks.splice(index, 1);
                    }
                }

                delete that.tasks[id];
                that.redraw(document);
            });
        });

        this.register(document, 'user', 'delete', function (user) {
            let id = "/users/" + user.id;

            that.send("DELETE", "//localhost" + id, 204, function () {
                for (let id in that.tasks) {
                    let task = that.tasks[id];

                    if (task.user === user) {
                        delete task.user;
                        task.user = null;
                    }
                }

                delete that.users[id];
                that.redraw(document);
            });
        });
    },

    "redraw_users": function (document, users) {
        let old_list = document.getElementById("users_list");
        let new_list  = document.createElement("tbody");

        for (let id in users) {
            let row, cell, span;
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
                span = document.createElement("span");
                span.setAttribute("name", "user_delete");
                span.setAttribute("user-id", user.id);
                span.appendChild(document.createTextNode("Delete"));
            cell.appendChild(span);
            row.appendChild(cell);

            new_list.appendChild(row);
        }

        new_list.id = old_list.id;
        old_list.parentNode.replaceChild(new_list, old_list);
    },

    "redraw_tasks": function (document, tasks) {
        let old_list = document.getElementById("tasks_list");
        let new_list  = document.createElement("tbody");

        for (let id in tasks) {
            let row, cell, span;
            let task = tasks[id];

            row = document.createElement("tr");

            cell = document.createElement("td");
            cell.appendChild(document.createTextNode(task.title));
            row.appendChild(cell);

            cell = document.createElement("td");
            cell.appendChild(document.createTextNode(task.description || ""));
            row.appendChild(cell);

            cell = document.createElement("td");
            cell.appendChild(document.createTextNode(task.status));
            row.appendChild(cell);

            cell = document.createElement("td");
            cell.appendChild(document.createTextNode(task.created_at.toString()));
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

        new_list.id = old_list.id;
        old_list.parentNode.replaceChild(new_list, old_list);
    },

    "register": function (document, name, action, callable) {
        const selector = "span[name=\"" + name + "_" + action + "\"]";
        let that = this;

        document
            .querySelectorAll(selector)
                .forEach(function (span) {
                    span.addEventListener(
                        'click',
                        function () {
                            let target, targetName;

                            if ("user" === name) {
                                target = that.users["/users/" + span.attributes['user-id'].value];
                                targetName = target.name;
                            } else {
                                target = that.tasks["/tasks/" + span.attributes['task-id'].value];
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

    "send": function (method, path, status, success, error) {
        let xhr = new XMLHttpRequest();

        xhr.onreadystatechange = function() {
            if (XMLHttpRequest.DONE === xhr.readyState) {
                if (status !== xhr.status) {
                    if (error) {
                        error(xhr);
                    }

                    return;
                }

                if (success) {
                    result = {};

                    if (xhr.responseText) {
                        result = JSON.parse(xhr.responseText);
                    }

                    success(result);
                }
            }
        };

        xhr.open(method, path, true);
        xhr.send();
    }
};
