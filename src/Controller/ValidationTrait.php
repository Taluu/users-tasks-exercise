<?php
namespace Test\One\Controller;

use Chanmix51\ParameterJuicer\Exception\ValidationException;

trait ValidationTrait
{
    private function renderException(ValidationException $e): array {
        $data = [
            'error_message' => $e->getMessage()
        ];

        foreach ($e->getExceptions() as $field => $exceptions) {
            $data[$field] = [];

            foreach ($exceptions as $exception) {
                $data[$field][] = $this->renderException($exception);
            }
        }

        return $data;
    }
}
