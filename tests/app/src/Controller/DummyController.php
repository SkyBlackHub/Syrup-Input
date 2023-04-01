<?php

namespace Syrup\Input\TestApp\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use Syrup\Input\Attributes\CSRF;
use Syrup\Input\Attributes\Data;
use Syrup\Input\Input;

use Syrup\Input\TestApp\DataResponse;

#[Route(path: '/dummy')]
class DummyController extends AbstractController
{
	#[Route(path: '/json', methods: ['POST'])]
	public function testJSON(Request $request): Response
	{
		return new DataResponse(['request' => $request->request]);
	}

	#[Route(path: '/data1', methods: ['POST'])]
	#[Data('foo', sources: Input::REQUEST, type: 'string')]
	#[Data('bar', sources: Input::REQUEST, key: 'nobar', type: 'int')]
	public function testData1(string $foo, string|int $bar): Response
	{
		return new DataResponse(['foo' => $foo, 'bar' => $bar]);
	}

	#[Route(path: '/data2', methods: ['GET', 'POST'])]
	#[Data('data', sources: Input::REQUEST, key: '*', methods: 'POST')]
	public function testData2(?array $data = null): Response
	{
		return new DataResponse($data);
	}

	#[Route(path: '/data3', methods: ['POST'])]
	#[Data('foo', sources: Input::REQUEST, required: true)]
	#[Data('bar', sources: Input::REQUEST, default: 'force')]
	#[Data('json', sources: Input::REQUEST, json: true)]
	public function testData3(string $foo = '', string $bar = '', array $json = []): Response
	{
		return new DataResponse(['foo' => $foo, 'bar' => $bar, 'json' => $json]);
	}

	#[Route(path: '/data4', methods: ['GET'])]
	#[Data('foo', sources: Input::QUERY, type: 'int', take: true)]
	public function testData4(Request $request, mixed $foo): Response
	{
		return new DataResponse(['foo' => $foo, 'query' => $request->query->all()]);
	}

	#[Route(path: '/csrf', methods: ['GET'])]
	public function testGetCSRF(CsrfTokenManagerInterface $manager): Response
	{
		return new DataResponse([
			't1' => $manager->getToken('test1')->getValue(),
			't2' => $manager->getToken('test2')->getValue()
		]);
	}

	#[Route(path: '/csrf1', methods: ['POST'])]
	#[CSRF(intention: 'test1')]
	public function testCSRF1(): Response
	{
		return new Response();
	}

	#[Route(path: '/csrf2', methods: ['GET', 'POST'])]
	#[CSRF(intention: 'test2', parameter: 'x-token', sources: Input::HEADERS, methods: 'POST')]
	public function testCSRF2(): Response
	{
		return new Response();
	}
}