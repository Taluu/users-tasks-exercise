<?php
namespace Test\One\Controller\Task;

use PDO;
use DateTimeImmutable;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Chanmix51\ParameterJuicer\ParameterJuicer as Juicer;
use Chanmix51\ParameterJuicer\Exception\ValidationException;

use Doctrine\DBAL\Connection;

use Test\One\HttpException;
use Test\One\Controller\ValidationTrait;

class EditController
{
    use ValidationTrait;

    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function supports(Request $request): bool
    {
        $matches = [];

        if (Request::METHOD_PUT !== $request->getMethod()) {
            return false;
        }

        if (!preg_match('{^/tasks/(?P<id>[0-9]+)$}', $request->getPathInfo(), $matches)) {
            return false;
        }

        $request->attributes->set('task-id', $matches['id']);

        return true;
    }

    public function __invoke(Request $request): JsonResponse
    {
        if (!$this->supports($request)) {
            throw new HttpException(400, "Only `PUT /tasks/{id}` is supported by this controller.");
        }

        if (null === $request->getContent()) {
            throw new HttpException(400, "Expected a body, got none");
        }

        $body = json_decode($request->getContent(), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $error = json_last_error_msg();
            throw new HttpException(400, "Unable to decode JSON ({$error}");
        }

        $sql = <<<'SQL'
SELECT id, user_id, title, description, created_at, `status`
FROM task
WHERE id = ?
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(1, $request->attributes->get('task-id'), PDO::PARAM_INT);
        $statement->execute();

        if (!$statement->rowCount()) {
            throw new HttpException(404, "Task {$request->attributes->get('task-id')} not found.");
        }

        $task = $statement->fetch(PDO::FETCH_ASSOC);

        $juicer = (new Juicer)
            ->addField('description', false)
                ->setDefaultValue('description', $task['description'])
            ->addField('status', false)
                ->setDefaultValue('status', $task['status'])
                ->addValidator('status', function ($v) {
                    if (in_array($v, ['todo', 'in_progress', 'done'])) {
                        return;
                    }

                    throw new ValidationException("Wrong value {$v}, expected one of ['todo', 'in_progress', 'done']");
                })
            ->addField('user', false)
                ->setDefaultValue('user', "/users/{$task['user']}")
                ->addCleaner('user', function ($v) {
                    $matches = [];

                    if (!preg_match('{^/users/(?P<id>[0-9]+)$}', $v, $matches)) {
                        return $v;
                    }

                    return (int) $matches['id'];
                })
                ->addValidator('user', function ($v) {
                    // already validated by cleaner
                    if (null === $v) {
                        return;
                    }

                    if (!filter_var($v, FILTER_VALIDATE_INT)) {
                        throw new ValidationException("wrong format");
                    }

                    $sql = <<<'SQL'
SELECT COUNT(*) FROM user WHERE id = ?;
SQL;

                    $statement = $this->connection->prepare($sql);
                    $statement->bindValue(1, $v, PDO::PARAM_INT);
                    $statement->execute();

                    if (0 === $d = (int) $statement->fetchColumn(0)) {
                        throw new ValidationException("/users/{$v} not found.");
                    }

                    $statement->closeCursor();
                })
        ;

        try {
            $data = $juicer->squash($body);
        } catch (ValidationException $e) {
            return new JsonResponse($this->renderException($e), 400);
        }

        $sql = <<<'SQL'
UPDATE task SET
    user_id = ?,
    description = ?,
    status = ?
WHERE id = ?
;
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(1, $data['user'] ?? null);
        $statement->bindValue(2, $data['description'], PDO::PARAM_STR);
        $statement->bindValue(3, $data['status'], PDO::PARAM_STR);
        $statement->bindValue(4, $task['id'], PDO::PARAM_STR);
        $statement->execute();

        if (!$statement->rowCount()) {
            throw new HttpException(500, "Could not edit task.");
        }

        $task = [
            '@id' => "/tasks/{$task['id']}",
            'data' => [
                'id' => (int) $task['id'],
                'user' => isset($data['user']) ? "/users/{$data['user']}" : null,
                'title' => $task['title'],
                'description' => $data['description'] ?? null,
                'created_at' => (new DateTimeImmutable($task['created_at']))->format('c'),
                'status' => $data['status']
            ]
        ];

        return new JsonResponse($task, 200);
    }
}
