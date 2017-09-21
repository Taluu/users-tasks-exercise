<?php
namespace Test\One\Controller\User;

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
            && '/users' === $request->getPathInfo()
        ;
    }

    public function __invoke(Request $request): JsonResponse
    {
        if (!$this->supports($request)) {
            throw new HttpException(400, "Only `POST /users` is supported by this controller.");
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
            ->addField('name')
                ->addCleaner('title', function ($v): string { return trim($v); })
                ->addValidator('title', function ($v) {
                    if (strlen($v) > 250) {
                        throw new ValidationException("Length must be between 1 and 250 chars, {$v} given.");
                    }
                })
            ->addField('email')
                ->addCleaner('email', function ($v): string { return trim($v); })
                ->addValidator('email', function ($v) {
                    if (false === filter_var($v, FILTER_VALIDATE_EMAIL)) {
                        throw new ValidationException("Length must be between 1 and 250 chars, {$v} given.");
                    }
                })
        ;

        try {
            $data = $juicer->squash($body);
        } catch (ValidationException $e) {
            return new JsonResponse($this->renderException($e), 400);
        }

        $sql = <<<'SQL'
INSERT INTO user (name, email) VALUES
    (?, ?)
;
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(1, $data['name'], PDO::PARAM_STR);
        $statement->bindValue(2, $data['email'], PDO::PARAM_STR);
        $statement->execute();

        if (!$statement->rowCount()) {
            throw new HttpException(500, "Could not create user.");
        }

        $id = (int) $this->connection->lastInsertId();

        $user = [
            '@id' => "/users/{$id}",
            'data' => [
                'id' => $id,
                'name' => $data['name'],
                'email' => $data['email']
            ]
        ];

        return new JsonResponse($user, 201);
    }
}
