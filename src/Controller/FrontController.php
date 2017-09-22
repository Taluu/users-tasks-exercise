<?php
namespace Test\One\Controller;

use PDO;
use DateTimeImmutable;

use Twig_Environment;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Doctrine\DBAL\Connection;

class FrontController
{
    /** @var Twig_Environment */
    private $twig;

    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection, Twig_Environment $twig)
    {
        $this->twig = $twig;
        $this->connection = $connection;
    }

    public function supports(Request $request)
    {
        return Request::METHOD_GET === $request->getMethod()
            && '/front' === $request->getPathInfo()
        ;
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->supports($request)) {
            throw new HttpException(400, "Invalid request, only `GET /front` is supported.");
        }

        $sql = <<<'SQL'
SELECT id, user_id, title, description, status, created_at
FROM task;
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute();

        $tasks = $statement->fetchAll(PDO::FETCH_OBJ);
        $statement->closeCursor();

        $sql = <<<'SQL'
SELECT id, name, email
FROM user;
SQL;

        // organize task by user-id
        $user_tasks = [];

        foreach ($tasks as $task) {
            $task->user = $task->user_id;
            unset($task->user_id);

            $task->created_at = new DateTimeImmutable($task->created_at);

            if (null === $task->user) {
                continue;
            }

            if (!isset($user_tasks[$task->user])) {
                $user_tasks[$task->user] = [];
            }

            $user_tasks[$task->user][] = $task;
        }

        $statement = $this->connection->prepare($sql);
        $statement->execute();

        $users = $statement->fetchAll(PDO::FETCH_OBJ);
        $statement->closeCursor();

        // bind tasks to users, and vice-versa
        foreach ($users as $user) {
            $user->tasks = [];

            foreach ($user_tasks[$user->id] as $task) {
                $user->tasks[] = $task;
                $task->user = $user;
            }
        }

        unset($user_tasks);

        return new Response($this->twig->render('page.html.twig', ['users' => $users, 'tasks' => $tasks]), 200);
    }
}
