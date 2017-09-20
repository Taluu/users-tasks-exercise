<?php
namespace Test\One\Controller;

use PDO;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\DBAL\Connection;

use Test\One\HttpException;

class UserGetController
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

        if (!preg_match('{^/users/(?P<id>[0-9]+)$}', $request->getPathInfo(), $matches)) {
            return false;
        }

        $request->attributes->set('user-id', $matches['id']);

        return true;
    }

    public function __invoke(Request $request): JsonResponse
    {
        if (!$this->supports($request)) {
            throw new HttpException(400, "Only `GET /users` is supported by this contoller.");
        }

        $sql = <<<'SQL'
SELECT id, name, email
FROM user
WHERE id = ?
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(1, $request->attributes->get('user-id'), PDO::PARAM_INT);
        $statement->execute();

        if (0 === $statement->rowCount()) {
            throw new HttpException(404, "User {$request->attributes->get('user-id')} not found.");
        }

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        $user['id'] = (int) $user['id'];

        $sql = <<<'SQL'
SELECT id
FROM task
WHERE user_id = ?
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(1, $request->attributes->get('user-id'), PDO::PARAM_INT);
        $statement->execute();

        $user['tasks'] = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $task) {
            $user['tasks'][] = "/tasks/{$task['id']}";
        }

        $user = [
            '@id' => "/users/{$user['id']}",
            'data' => $user
        ];

        return new JsonResponse($user, 200);
    }
}
