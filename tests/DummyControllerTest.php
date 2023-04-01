<?php

namespace Syrup\Input\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;

use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Syrup\Input\TestApp\DataResponse;
use Syrup\Input\TestApp\TestKernel;

class DummyControllerTest extends WebTestCase
{
	protected static function createKernel(array $options = []): KernelInterface
	{
		return new TestKernel('test', true);
	}

	private function checkResponse(KernelBrowser $client, int $status = 200): mixed
	{
		$this->assertResponseStatusCodeSame($status);
		$response = $client->getResponse();
		$this->assertInstanceOf(DataResponse::class, $response);
		return $response->getData();
	}

	/**
	 * @covers \Syrup\Input\EventSubscribers\ControllerSubscriber::processJSONRequest
	 * @testdox JSON
	 */
	public function testJSON(): void
	{
		$client = static::createClient();

		$data = ['foo' => 'BAR', 'test' => ['a' => 123, 'b' => 456]];

		$client->jsonRequest('POST', '/dummy/json', $data);

		$result = $this->checkResponse($client);

		$this->assertArrayHasKey('request', $result);
		$result = $result['request'];
		$this->assertInstanceOf(ParameterBag::class, $result);
		$this->assertSame('BAR', $result->get('foo'));
		$this->assertSame(['a' => 123, 'b' => 456], $result->get('test'));
	}

	/**
	 * @covers \Syrup\Input\Attributes\Data
	 * @testdox Data 1
	 */
	public function testData1(): void
	{
		$client = static::createClient();

		$data = ['foo' => 'test', 'nobar' => '123'];

		$client->jsonRequest('POST', '/dummy/data1', $data);

		$result = $this->checkResponse($client);

		$this->assertSame('test', $result['foo'] ?? null);
		$this->assertSame(123, $result['bar'] ?? null);
	}

	/**
	 * @covers \Syrup\Input\Attributes\Data
	 * @testdox Data 2
	 */
	public function testData2(): void
	{
		$client = static::createClient();

		$client->jsonRequest('GET', '/dummy/data2');

		$this->assertNull($this->checkResponse($client));

		$data = ['foo' => 'BAR', 'test' => ['a' => 123, 'b' => 456]];

		$client->jsonRequest('POST', '/dummy/data2', $data);

		$result = $this->checkResponse($client);

		$this->assertSame($data, $result);
	}

	/**
	 * @covers \Syrup\Input\Attributes\Data
	 * @testdox Data 3
	 */
	public function testData3(): void
	{
		$client = static::createClient();

		$client->jsonRequest('POST', '/dummy/data3', []);

		$result = $this->checkResponse($client, 400);
		$this->assertEquals(BadRequestHttpException::class, $result['exception'] ?? null);

		$client->jsonRequest('POST', '/dummy/data3', ['foo' => 123, 'json' => json_encode(['a' => 1])]);

		$result = $this->checkResponse($client);

		$this->assertSame(['foo' => '123', 'bar' => 'force', 'json' => ['a' => 1]], $result);
	}

	/**
	 * @covers \Syrup\Input\Attributes\Data
	 * @testdox Data 4
	 */
	public function testData4(): void
	{
		$client = static::createClient();

		$client->jsonRequest('GET', '/dummy/data4?foo=123&bar=456');

		$result = $this->checkResponse($client);

		$this->assertSame(['foo' => 123, 'query' => ['bar' => '456']], $result);
	}

	/**
	 * @covers \Syrup\Input\Attributes\CSRF
	 * @testdox CSRF 1
	 */
	public function testCSRF1(): void
	{
		$client = static::createClient();

		$client->request('GET', '/dummy/csrf');
		$result = $this->checkResponse($client);

		$token = $result['t1'] ?? null;
		$this->assertNotEmpty($token);

		$client->request('POST', '/dummy/csrf1');

		$result = $this->checkResponse($client, 400);
		$this->assertEquals(InvalidCsrfTokenException::class, $result['exception'] ?? null);

		$client->request('POST', '/dummy/csrf1', ['_token' => $token]);

		$this->assertResponseIsSuccessful();
	}

	/**
	 * @covers \Syrup\Input\Attributes\CSRF
	 * @testdox CSRF 2
	 */
	public function testCSRF2(): void
	{
		$client = static::createClient();

		$client->request('GET', '/dummy/csrf');
		$result = $this->checkResponse($client);

		$token = $result['t2'] ?? null;
		$this->assertNotEmpty($token);

		$client->request('POST', '/dummy/csrf2');

		$result = $this->checkResponse($client, 400);
		$this->assertEquals(InvalidCsrfTokenException::class, $result['exception'] ?? null);

		$client->request('POST', '/dummy/csrf2', ['_token' => $token]);

		$result = $this->checkResponse($client, 400);
		$this->assertEquals(InvalidCsrfTokenException::class, $result['exception'] ?? null);

		$client->request('GET', '/dummy/csrf2');

		$this->assertResponseIsSuccessful();

		$client->request('POST', '/dummy/csrf2', [], [], ['HTTP_X_TOKEN' => $token]);

		$this->assertResponseIsSuccessful();
	}
}