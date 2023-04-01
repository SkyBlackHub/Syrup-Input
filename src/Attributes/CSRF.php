<?php

namespace Syrup\Input\Attributes;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use Syrup\Input\Input;
use Syrup\Input\Traits\RequestSourceTrait;

#[\Attribute(\Attribute::TARGET_METHOD)]
class CSRF implements Input
{
	use RequestSourceTrait;

	private string $intention;
	private string $parameter;


	public function __construct(
		string       $intention,
		string       $parameter = '_token',
		array|string $sources = [],
		array|string $methods  = []
	)	{
		$this->setIntention($intention);
		$this->setParameter($parameter);
		$this->setSources($sources);
		$this->setMethods($methods);
	}

	public function getIntention(): string
	{
		return $this->intention;
	}

	public function setIntention(string $intention): static
	{
		$this->intention = trim($intention);
		return $this;
	}

	public function getParameter(): string
	{
		return $this->parameter;
	}

	public function setParameter(string $parameter): static
	{
		$this->parameter = trim($parameter);
		return $this;
	}

	public function findToken(Request $request): mixed
	{
		if ($this->getSources() == false) {
			return $request->get($this->getParameter());
		}

		$parameter = $this->getParameter();
		$token = null;

		foreach ($this->getSources() as $source) {
			switch ($source) {
				case self::QUERY:
					$token = $request->query->get($parameter);
					break;

				case self::REQUEST:
					$token = $request->request->get($parameter);
					break;

				case self::COOKIES:
					$token = $request->cookies->get($parameter);
					break;

				case self::HEADERS:
					$token = $request->headers->get($parameter);
					break;

				case self::SERVER:
					$token = $request->server->get($parameter);
					break;

				case self::ATTRIBUTES:
					$token = $request->attributes->get($parameter);
					break;
			}

			if ($token != null) {
				return $token;
			}
		}

		return null;
	}

	public function validate(Request $request, CsrfTokenManagerInterface $manager): bool
	{
		if ($this->isMethodIncluded($request->getMethod()) == false) {
			return true;
		}

		$token = $this->findToken($request);
		if ($token == null) {
			return false;
		}

		return $manager->isTokenValid(new CsrfToken($this->getIntention(), $token));
	}
}