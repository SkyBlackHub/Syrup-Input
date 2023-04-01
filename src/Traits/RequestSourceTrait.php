<?php

namespace Syrup\Input\Traits;

trait RequestSourceTrait
{
	private array $sources;
	private array $methods;

	public function getSources(): array
	{
		return $this->sources;
	}

	public function setSources(array|string $sources): static
	{
		$this->sources = [];
		foreach ((array) $sources as $source) {
			if (is_string($source)) {
				$this->sources[] = strtolower(trim($source));
			}
		}
		return $this;
	}

	public function setSource(string $source): static
	{
		return $this->setSources($source);
	}

	public function getMethods(): array
	{
		return $this->methods;
	}

	public function setMethods(array|string $methods): static
	{
		$this->methods = [];
		foreach ((array) $methods as $method) {
			if (is_string($method)) {
				$this->methods[] = strtoupper(trim($method));
			}
		}
		return $this;
	}

	public function setMethod(string $method): static
	{
		return $this->setMethods($method);
	}

	public function isMethodIncluded(string $method): bool
	{
		return !$this->methods || in_array(strtoupper(trim($method)), $this->methods);
	}
}