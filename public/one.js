var one = {
    "tasks": {},
    "users": {},

    "redraw": function (document) {
        this.redraw_users(document, this.users);
        this.redraw_tasks(document, this.tasks);

        this.register(document, 'task', 'edit', function (target) { console.log(target); });
        this.register(document, 'task', 'delete', function (target) { console.log(target); });
        this.register(document, 'user', 'delete', function (target) { console.log(target); });
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
                            if ("user" === name) {
                                let target = that.users["/users/" + span.attributes['user-id'].value];
                                let targetName = target.name;
                            } else {
                                let target = that.tasks["/tasks/" + span.attributes['task-id'].value];
                                let targetName = target.title;
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

    "load": function (method, path, status, success, error) {
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

        xhr.open(method, path, true);
        xhr.send();
    }
};
