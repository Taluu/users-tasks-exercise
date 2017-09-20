<?php
namespace Test\One\Controller;

use PDO;
use DateTimeImmutable;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\DBAL\Connection;

use Test\One\HttpException;

class TaskGetController
{
    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function supports(Request $request): bool
    {
        $matches = [];

        if (Request::METHOD_GET !== $request->getMethod()) {
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
            throw new HttpException(400, "Only `GET /tasks/{id}` is supported by this controller.");
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

        $task['id'] = (int) $task['id'];
        $task['user'] = null;
        $task['created_at'] = (new DateTimeImmutable($task['created_at']))->format('c');

        $user_id = $task['user_id'];
        unset($task['user_id']);

        if (null !== $user_id) {
            $task['user'] = "/users/{$user_id}";
        }

        $task = [
            '@id' => "/tasks/{$task['id']}",
            'data' => $task
        ];

        return new JsonResponse($task, 200);
    }
}
