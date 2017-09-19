<?php
namespace Test\One\Controller;

use PDO;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\DBAL\Connection;

class AllUsersController
{
    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function supports(Request $request): bool
    {
        return '/users' === $request->getPathInfo()
            && Request::METHOD_GET === $request->getMethod()
        ;
    }

    public function __invoke(Request $request): JsonResponse
    {
        if (!$this->supports($request)) {
            throw new HttpException(400, "Only `GET /users` is supported by this contoller.");
        }

        $sql = <<<'SQL'
SELECT id, user_id
FROM task
WHERE user_id IS NOT null
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute();

        $tasks = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $task) {
            $uid = (int) $task['user_id'];

            if (!isset($tasks[$uid])) {
                $tasks[$uid] = [];
            }

            $tasks[$uid][] = "/tasks/{$task['id']}";
        }

        $sql = <<<'SQL'
SELECT id, name, email
FROM user
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute();

        $users = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $user) {
            $user['id'] = (int) $user['id'];
            $user['tasks'] = $tasks[$user['id']] ?? [];

            $users[] = [
                '@id' => "/users/{$user['id']}",
                'data' => $user
            ];
        }

        return new JsonResponse($users, 200);
    }
}
