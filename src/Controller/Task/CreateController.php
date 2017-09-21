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

class CreateController
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
        return Request::METHOD_POST === $request->getMethod()
            && '/tasks' === $request->getPathInfo()
        ;
    }

    public function __invoke(Request $request): JsonResponse
    {
        if (!$this->supports($request)) {
            throw new HttpException(400, "Only `POST /tasks` is supported by this controller.");
        }

        if (null === $request->getContent()) {
            throw new HttpException(400, "Expected a body, got none");
        }

        $body = json_decode($request->getContent(), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $error = json_last_error_msg();
            throw new HttpException(400, "Unable to decode JSON ({$error}");
        }

        $juicer = (new Juicer)
            ->addField('title')
                ->addCleaner('title', function ($v): string { return trim($v); })
                ->addValidator('title', function ($v) {
                    if (strlen($v) > 250) {
                        throw new ValidationException("Length must be between 1 and 250 chars, {$v} given.");
                    }
                })
            ->addField('description', false)
            ->addField('user', false)
                ->addCleaner('user', function ($v) {
                    $matches = [];

                    if (!preg_match('{^/users/(?P<id>[0-9]+)$}', $v, $matches)) {
                        return;
                    }

                    return (int) $matches['id'];
                })
                ->addValidator('user', function (?int $v) {
                    // already validated by cleaner
                    if (null === $v) {
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
INSERT INTO task (user_id, title, description, status, created_at) VALUES
    (?, ?, ?, 'todo', NOW())
;
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(1, $data['user'] ?? null);
        $statement->bindValue(2, $data['title'], PDO::PARAM_STR);
        $statement->bindValue(3, $data['description'] ?? null);
        $statement->execute();

        if (!$statement->rowCount()) {
            throw new HttpException(500, "Could not create task.");
        }

        $id = (int) $this->connection->lastInsertId();

        $task = [
            '@id' => "/tasks/{$id}",
            'data' => [
                'id' => $id,
                'user' => isset($data['user']) ? "/users/{$data['user']}" : null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'created_at' => (new DateTimeImmutable())->format('c'),
                'status' => 'todo'
            ]
        ];

        return new JsonResponse($task, 201);
    }
}
