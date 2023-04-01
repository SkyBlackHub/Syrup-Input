<?php

namespace Syrup\Input\TestApp;

use Symfony\Component\HttpFoundation\Response;

class DataResponse extends Response
{
	public function __construct(private mixed $data = null, int $status = 200)
	{
		parent::__construct('', $status);
	}

	public function getData(): mixed
	{
		return $this->data;
	}

	public function setData(mixed $data): self
	{
		$this->data = $data;
		return $this;
	}
}