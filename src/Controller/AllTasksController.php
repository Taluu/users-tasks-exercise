<?php
namespace Test\One\Controller;

use PDO;
use DateTimeImmutable;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\DBAL\Connection;

use Test\One\HttpException;

class AllTasksController
{
    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function supports(Request $request): bool
    {
        return '/tasks' === $request->getPathInfo()
            && Request::METHOD_GET === $request->getMethod()
        ;
    }

    public function __invoke(Request $request): JsonResponse
    {
        if (!$this->supports($request)) {
            throw new HttpException(400, "Only `GET /tasks` is supported by this contoller.");
        }

        $sql = <<<'SQL'
SELECT id, user_id, title, description, created_at, `status`
FROM task
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute();

        $tasks = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $task) {
            $task['id'] = (int) $task['id'];
            $task['user'] = null;
            $task['created_at'] = (new DateTimeImmutable($task['created_at']))->format('c');

            $user_id = $task['user_id'];
            unset($task['user_id']);

            if (null !== $user_id) {
                $task['user'] = "/users/{$user_id}";
            }

            $tasks[] = [
                '@id' => "/tasks/{$task['id']}",
                'data' => $task
            ];
        }

        return new JsonResponse($tasks, 200);
    }
}
