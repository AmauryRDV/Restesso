<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception instanceof HttpException) {
            $response = [
                'message' => $exception->getMessage(),
                'status' => $exception->getCode(),
            ];
        } else {
            $response = [
                'message' => $exception->getMessage() ?: 'An error occurred',
                'status' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
            ];
        }

        $event->setResponse(new JsonResponse($response));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
