<?php
namespace Test\One\Controller;

use PDO;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\DBAL\Connection;

use Test\One\HttpException;

class UserDeleteController
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

        if (Request::METHOD_DELETE !== $request->getMethod()) {
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
            throw new HttpException(400, "Only `DELETE /users/{id}` is supported by this controller.");
        }

        $sql = <<<'SQL'
DELETE FROM user
WHERE id = ?
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(1, $request->attributes->get('user-id'), PDO::PARAM_INT);
        $statement->execute();

        if (0 === $statement->rowCount()) {
            throw new HttpException(404, "User {$request->attributes->get('user-id')} not found.");
        }

        return new JsonResponse(null, 204);
    }
}
