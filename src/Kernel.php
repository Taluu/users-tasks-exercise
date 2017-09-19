<?php
namespace Test\One;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class Kernel
{
    /** @var callable[] */
    private $controllers;

    public function __construct()
    {
        $this->controllers = [];
    }

    public function handle(Request $request): Response
    {
        try {
            $controller = $this->getController($request);
            return $controller($request);
        } catch (HttpException $e) {
            $body = [
                'error' => $e->getMessage()
            ];

            return new JsonResponse($body, $e->getStatusCode(), $e->getHeaders());
        } catch (Throwable $t) {
            return new Response('Oops !', 500);
        }
    }

    private function getController(Request $request): callable
    {
        foreach ($this->controllers as $controller) {
            if (!$controller->supports($request)) {
                continue;
            }

            return $controller;
        }

        throw new HttpException(400, "Could not match anything with \"{$request->getMethod()} {$request->getPathInfo()}\"");
    }
}
