<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolationListInterface;

trait ApiResponseTrait
{
    // 💡 Consistent success response across ALL controllers
    private function successResponse(mixed $data, string $message = 'Success', int $status = 200): JsonResponse
    {
        return $this->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $status);
    }

    // 💡 Consistent error response across ALL controllers
    private function errorResponse(string $message, int $status = 400, mixed $data = null): JsonResponse
    {
        return $this->json([
            'status' => 'error',
            'message' => $message,
            'data' => $data
        ], $status);
    }

    // 💡 Format validation errors consistently
    private function validationErrorResponse(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $field = str_replace(['[', ']'], '', $violation->getPropertyPath());
            $errors[$field][] = $violation->getMessage();
        }

        return $this->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'data' => ['errors' => $errors]
        ], 422);
    }

    // 💡 Map raw JSON data onto a DTO object
    private function mapToDTO(array $data, object $dto): object
    {
        foreach ($data as $key => $value) {
            if (property_exists($dto, $key)) {
                $dto->$key = $value;
            }
        }
        return $dto;
    }
}