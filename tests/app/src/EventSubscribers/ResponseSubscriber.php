<?php

namespace Syrup\Input\TestApp\EventSubscribers;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Syrup\Input\TestApp\DataResponse;

class ResponseSubscriber implements EventSubscriberInterface
{
	public static function getSubscribedEvents(): array
	{
		return [
			KernelEvents::EXCEPTION => ['onKernelException'],
		];
	}

	public function onKernelException(ExceptionEvent $event): void
	{
		$exception = $event->getThrowable();

		$event->allowCustomResponseCode();
		$response = new DataResponse([
			'exception' => get_class($exception),
			'message' => $exception->getMessage()
		]);
		$response->setStatusCode(400, $exception->getMessage());
		$event->setResponse($response);
	}
}