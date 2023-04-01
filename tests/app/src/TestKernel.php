<?php

namespace Syrup\Input\TestApp;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class TestKernel extends BaseKernel
{
	use MicroKernelTrait;

	public function getProjectDir(): string
	{
		return dirname(__DIR__);
	}
}