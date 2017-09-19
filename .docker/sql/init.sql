CREATE TABLE user (
    `id` INT AUTO_INCREMENT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,

    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

INSERT INTO user(`email`, `name`) VALUES
    ('foo@localhost', 'foo'),
    ('bar@localhost', 'bar'),
    ('baz@localhost', 'baz')
;

CREATE TABLE task (
    `id` INT AUTO_INCREMENT NOT NULL,
    `user_id` INT DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `status` VARCHAR(255) NOT NULL,

    INDEX IDX_527EDB25A76ED395 (user_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

ALTER TABLE task ADD CONSTRAINT FK_527EDB25A76ED395 FOREIGN KEY (user_id) REFERENCES user (id);

INSERT INTO task(`user_id`, `title`, `description`, `created_at`, `status`) VALUES
    (1, 'foo #1', null, NOW(), 'todo'),
    (1, 'foo #2', 'another foo thas', NOW(), 'in_progress'),
    (1, 'foo #3', null, NOW(), 'done'),
    (2, 'bar #1', null, NOW(), 'todo'),
    (3, 'baz #1', null, NOW(), 'done')
;
