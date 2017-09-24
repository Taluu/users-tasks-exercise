var one = {
    "tasks": {},
    "users": {},

    "redraw": function (document) {
        let that = this;

        this.redraw_users(document);
        this.redraw_tasks(document);

        this.register(document, 'task', 'edit', function (task) {
            let div = document.getElementById('edit_div');

            let fields = {};
            let task_id = "/tasks/" + task.id;

            document.querySelectorAll('[name^=edit_task]').forEach(function (field) {
                const regex = /^edit_task\[([a-z]+)\]$/u;

                if (!field.name.match(regex)) {
                    return;
                }

                const name = field.name.replace(regex, '$1');

                fields[name] = fields[name] || [];

                fields[name].push(field);
            });

            fields.title[0].value = task.title;

            if (null !== task.description) {
                fields.description[0].value = task.description;
            }

            for (let i = 0; i < fields.status[0].options.length; i++) {
                let option = fields.status[0].options[i];

                if (option.value !== task.status) {
                    continue;
                }

                option.selected = true;
            };

            for (let i = 0; i < fields.user[0].options.length; i++) {
                let option = fields.user[0].options[i];
                let user = task.user ? "/users/" + task.user.id : null;
                let value = "Nobody" === option.value ? null : option.value;

                if (value !== user) {
                    continue;
                }

                option.selected = true;
            };

            fields.user[0].disabled = false;
            fields.status[0].disabled = false;
            fields.description[0].disabled = false;

            div.style.display = 'block';

            document.getElementById('edit_task').addEventListener('click', function () {
                const status = fields.status[0].selectedOptions[0].value;
                const user = "Nobody" == fields.user[0].selectedOptions[0].value ? null : fields.user[0].selectedOptions[0].value;

                if (!fields.description[0].checkValidity()) {
                    return;
                }

                that.edit_task(document, task.id, fields.description[0].value, status, user);

                div.style.display = 'none';
            });
        });

        this.register(document, 'task', 'delete', function (task) {
            let id = "/tasks/" + task.id;

            that.send("DELETE", "//localhost" + id, null, 204, function () {
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

            that.send("DELETE", "//localhost" + id, null, 204, function () {
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

    "redraw_users": function (document) {
        let old_list = document.getElementById("users_list");
        let new_list  = document.createElement("tbody");

        let old_select = document.getElementById("task_edit_user_list");
        let new_select = document.createElement("select");

        let option = document.createElement("option");
        option.appendChild(document.createTextNode("Nobody"));
        new_select.appendChild(option);

        for (let id in this.users) {
            // table first
            let row, cell, span;
            let user = this.users[id];

            row = document.createElement("tr");

            cell = document.createElement("td");
            cell.appendChild(document.createTextNode(user.name));
            row.appendChild(cell);

            cell = document.createElement("td");
            cell.appendChild(document.createTextNode(user.email));
            row.appendChild(cell);

            let tasks = [];

            user.tasks.forEach(function (task) {
                tasks.push( task.title + " (" + task.status + ")");
            });

            cell = document.createElement("td");
            cell.appendChild(document.createTextNode(tasks.join(' - ')));
            row.appendChild(cell);

            cell = document.createElement("td");
                span = document.createElement("span");
                span.setAttribute("name", "user_delete");
                span.setAttribute("user-id", user.id);
                span.appendChild(document.createTextNode("Delete"));
            cell.appendChild(span);
            row.appendChild(cell);

            new_list.appendChild(row);

            // select items
            option = document.createElement("option");
            option.value = "/users/" + user.id;
            option.appendChild(document.createTextNode(user.name));
            new_select.append(option);
        }

        new_select.id = old_select.id;
        new_select.name = old_select.name;
        old_select.parentNode.replaceChild(new_select, old_select);

        new_list.id = old_list.id;
        old_list.parentNode.replaceChild(new_list, old_list);
    },

    "redraw_tasks": function (document) {
        let old_list = document.getElementById("tasks_list");
        let new_list  = document.createElement("tbody");

        for (let id in this.tasks) {
            let row, cell, span;
            let task = this.tasks[id];

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

    "create_user": function (document, name, email) {
        let input = {
            name: name,
            email: email
        };

        let that = this;

        this.send("POST", "//localhost/users", input, 201, function (data) {
            that.users[data['@id']] = data['data'];

            that.redraw(document);
        });
    },

    "create_task": function (document, title, description) {
        let input = {
            title: title,
            description: description || null
        };

        let that = this;

        this.send("POST", "//localhost/tasks", input, 201, function (data) {
            data.data.created_at = new Date(data.data.created_at);
            that.tasks[data['@id']] = data['data'];

            that.redraw(document);
        });
    },

    "edit_task": function (document, id, description, status, user) {
        let input = {
            user: user,
            status: status,
            description: description || null
        };

        let that = this;

        this.send("PUT", "//localhost/tasks/" + id, input, 200, function (data) {
            let id = data["@id"];
            data = data.data;

            // unassign the user (will be reassigned if not changed)
            if (null !== that.tasks[id].user) {
                const index = that.tasks[id].user.tasks.indexOf(that.tasks[id]);

                that.tasks[id].user.tasks.splice(index, 1);
                that.tasks[id].user = null;
            }

            that.tasks[id] = data;

            data.created_at = new Date(data.created_at);

            if (null !== data.user) {
                data.user = that.users[data.user];
                data.user.tasks.push(data);
            }

            that.redraw(document);
        });
    },

    "send": function (method, path, body, status, success, error) {
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

        if (null !== body && 'object' === typeof body) {
            body = JSON.stringify(body);
        }

        xhr.open(method, path, true);
        xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
        xhr.send(body);
    }
};
