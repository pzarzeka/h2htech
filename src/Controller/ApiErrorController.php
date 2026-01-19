<?php

declare(strict_types=1);

namespace App\Controller;

use \Throwable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ApiErrorController
{
    public function show(Request $request, Throwable $exception): JsonResponse
    {
        $status = $exception->getStatusCode();
        $previous = $exception->getPrevious();
        if (
            true === in_array($status, [Response::HTTP_UNPROCESSABLE_ENTITY, Response::HTTP_NOT_FOUND])
            && $previous instanceof ValidationFailedException
        ) {
            $errors = [];
            foreach ($previous->getViolations() as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }

            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
