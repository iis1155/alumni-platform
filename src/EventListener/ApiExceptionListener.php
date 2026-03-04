<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ApiExceptionListener
{
    public function __construct(private string $env)
    {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();

        [$message, $status] = match (true) {
                $exception instanceof AccessDeniedHttpException => ['Access denied. You do not have permission.', 403],
                $exception instanceof AuthenticationException => ['Authentication required. Please login.', 401],
                $exception instanceof NotFoundHttpException => ['Resource not found.', 404],
                $exception instanceof HttpExceptionInterface => [$exception->getMessage(), $exception->getStatusCode()],
                default => [
                // 👇 Show real error in dev/test, hide in prod
                $this->env !== 'prod'
                ? $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine()
                : 'An unexpected error occurred.',
                500
            ],
            };

        $event->setResponse(new JsonResponse([
            'status' => 'error',
            'message' => $message,
            'data' => null
        ], $status));
    }
}