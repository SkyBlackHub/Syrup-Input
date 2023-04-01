<?php

namespace Syrup\Input;

interface Input
{
	public const QUERY      = 'query';
	public const REQUEST    = 'request';
	public const COOKIES    = 'cookies';
	public const HEADERS    = 'headers';
	public const SERVER     = 'server';
	public const ATTRIBUTES = 'attributes';

	public function getSources(): array;
}