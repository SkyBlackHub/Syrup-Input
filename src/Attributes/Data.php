<?php

namespace Syrup\Input\Attributes;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

use Syrup\Input\Input;
use Syrup\Input\Traits\RequestSourceTrait;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD)]
class Data implements Input
{
	use RequestSourceTrait;

	protected ?string $key = null;
	protected ?string $type = null;

	public function __construct(
		protected string $name,
		array|string $sources = ['attributes', 'query', 'request'],
		?string $key = null,
		protected mixed $default = null,
		?string $type = null,
		protected bool $take = false,
		protected bool|string $required = false,
		protected bool $json = false,
		array|string $methods  = []
	) {
		$this->setSources($sources);
		$this->setKey($key);
		$this->setType($type);
		$this->setMethods($methods);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): static
	{
		$this->name = $name;
		return $this;
	}

	public function getKey(): ?string
	{
		return $this->key;
	}

	public function setKey(?string $key): static
	{
		$this->key = $key ? (trim($key) ?: null) : null;
		return $this;
	}

	public function getDefault(): mixed
	{
		return $this->default;
	}

	public function setDefault(mixed $default): static
	{
		$this->default = $default;
		return $this;
	}

	public function hasType(): bool
	{
		return $this->type != null;
	}

	public function getType(): ?string
	{
		return $this->type;
	}

	public function setType(?string $type): static
	{
		$this->type = $type ? (strtolower(trim($type)) ?: null) : null;
		return $this;
	}

	public function isTake(): bool
	{
		return $this->take;
	}

	public function setTake(bool $take): static
	{
		$this->take = $take;
		return $this;
	}

	public function isRequired(): bool
	{
		return $this->required;
	}

	public function getRequired(): bool|string
	{
		return $this->required;
	}

	public function setRequired(bool|string $required): static
	{
		$this->required = $required;
		return $this;
	}

	public function isJSON(): bool
	{
		return $this->json;
	}

	public function setJSON(bool $json): static
	{
		$this->json = $json;
		return $this;
	}
	
	public function obtain(Request $request): bool
	{
		$name = $this->getName();
		$key = $this->getKey() ?: $name;
		$found = false;
		$value = null;

		foreach ($this->getSources() as $source) {
			/** @var ParameterBag $bag */
			switch ($source) {
				case self::QUERY:
					$bag = $request->query;
					break;

				case self::REQUEST:
					$bag = $request->request;
					break;

				case self::COOKIES:
					$bag = $request->cookies;
					break;

				case self::HEADERS:
					// Caution! HeaderBag is not a ParameterBag, however it still has 'get', 'has' and 'all' methods
					$bag = $request->headers;
					break;

				case self::SERVER:
					$bag = $request->server;
					break;

				case self::ATTRIBUTES:
					$bag = $request->attributes;
					break;

				default:
					continue 2;
			}

			if ($key == '*') {
				$value = $bag->all();
				$found = true;
				if ($this->isTake()) {
					$bag->replace();
				}
				break;
			}
			if ($bag->has($key)) {
				$value = $bag->get($key);
				$found = true;
				if ($this->isTake()) {
					$bag->remove($key);
				}
				break;
			}
		}

		if ($found == false) {
			if ($this->getDefault() !== null) {
				$value = $this->getDefault();
				$found = true;
			}
		}
		if ($found) {
			if ($key != '*') {
				if ($this->isJSON() && is_string($value)) {
					$value = json_decode($value, true);
				}
				if ($this->hasType()) {
					$value = self::typeConvert($value, $this->getType());
				}
			}
			$request->attributes->set($name, $value);
		}
		return $found;
	}

	public static function typeConvert($value, $type)
	{
		switch ($type) {
			case 'bool':
			case 'boolean':
				return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

			case 'int':
			case 'integer':
				return intval($value);

			default:
				settype($value, $type);
		}
		return $value;
	}
} 