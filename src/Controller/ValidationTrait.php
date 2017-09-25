<?php
namespace Test\One\Controller;

use Chanmix51\ParameterJuicer\Exception\ValidationException;

trait ValidationTrait
{
    private function renderException(ValidationException $e): array {
        $data = [
            'message' => $e->getMessage(),
            'errors' => []
        ];

        foreach ($e->getExceptions() as $field => $exceptions) {
            $data['errors'][$field] = [];

            foreach ($exceptions as $exception) {
                if ($exception instanceof ValidationException && $exception->hasExceptions()) {
                    $data['errors'][$field][] = $this->renderException($exception);
                    continue;
                }

                $data['errors'][$field][] = $exception->getMessage();
            }
        }

        return $data;
    }
}
