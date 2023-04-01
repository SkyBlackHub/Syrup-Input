<?php

namespace Syrup\Input\EventSubscribers;

use Psr\Container\ContainerInterface;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use Syrup\Input\Attributes\CSRF;
use Syrup\Input\Attributes\Data;

class ControllerSubscriber implements EventSubscriberInterface, ServiceSubscriberInterface
{
	public static function getSubscribedEvents(): array
	{
		return [
			KernelEvents::CONTROLLER => ['onKernelController', 100],
		];
	}

	public static function getSubscribedServices(): array
	{
		return [
			CsrfTokenManagerInterface::class
		];
	}

	public function __construct(
		protected ContainerInterface $container,
		private bool $data = false,
		private bool $csrf = false,
		private bool $json = false
	)	{	}

	public function isDataProcessing(): bool
	{
		return $this->data;
	}

	public function setDataProcessing(bool $data): self
	{
		$this->data = $data;
		return $this;
	}

	public function isCSRFProcessing(): bool
	{
		return $this->csrf;
	}

	public function setCSRFProcessing(bool $csrf): self
	{
		$this->csrf = $csrf;
		return $this;
	}

	public function isJSONProcessing(): bool
	{
		return $this->json;
	}

	public function setJSONProcessing(bool $json): self
	{
		$this->json = $json;
		return $this;
	}

	public function onKernelController(ControllerEvent $event): void
	{
		$request = $event->getRequest();

		if ($this->isJSONProcessing()) {
			$this->processJSONRequest($request);
		}

		$controller = $event->getController();

		if (is_array($controller) == false && method_exists($controller, '__invoke')) {
			$controller = [$controller, '__invoke'];
		}

		if (is_array($controller) == false) {
			return;
		}

		$reflection = new \ReflectionClass(\get_class($controller[0]));
		$method = $reflection->getMethod($controller[1]);

		if ($this->isDataProcessing()) {
			$this->processDataAttributes($method, $request);
		}
		if ($this->isCSRFProcessing()) {
			$this->processCSRFAttributes($method, $request);
		}
	}

	protected function processJSONRequest(Request $request): void
	{
		// workaround deprecated method since Symphony 6.2
		try {
			$type = $request->getContentTypeFormat();
		} catch (\Error) {
			$type = $request->getContentType();
		}
		if ($type == 'json') {
			try {
				$request->request = new ParameterBag($request->toArray());
			} catch (JsonException) {	}
		}
	}

	protected function processDataAttributes(\ReflectionMethod $method, Request $request): void
	{
		foreach ($method->getAttributes(Data::class) as $attribute) {
			/** @var Data $data */
			$data = $attribute->newInstance();
			if ($data->isMethodIncluded($request->getMethod()) == false) {
				continue;
			}
			if ($data->obtain($request) == false && $data->isRequired()) {
				$message = $data->getRequired();
				if (is_string($message) == false) {
					$message = 'Required data "' . $data->getName() . '" was not found in the request.';
				}
				throw new BadRequestException($message);
			}
		}
	}

	protected function processCSRFAttributes(\ReflectionMethod $method, Request $request): void
	{
		$manager = $this->container->get(CsrfTokenManagerInterface::class);
		foreach ($method->getAttributes(CSRF::class) as $attribute) {
			/** @var CSRF $csrf */
			$csrf = $attribute->newInstance();
			if ($csrf->validate($request, $manager) == false) {
				throw new InvalidCsrfTokenException('Invalid CSRF token.');
			}
		}
	}
}