<?php

namespace App\Tests\Controller;

use Faker\Factory as FakerFactoryAlias;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiResultsControllerTest
 *
 * @package App\Tests\Controller
 *
 * @coversDefaultClass \App\Controller\ApiResultsController
 */
class ApiResultsControllerTest extends BaseTestCase
{
    private const RUTA_API = '/api/v1/results';

    /**
     * Test OPTIONS /results[/resultId] 200 Ok
     *
     * @return void
     * @covers ::optionsAction()
     */
    public function testOptionsResultAction200(): void
    {
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API
        );
        $response = self::$client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotEmpty($response->headers->get('Allow'));

        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API . '/' . self::$faker->numberBetween(1, 100)
        );

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotEmpty($response->headers->get('Allow'));
    }

    /**
     * Test POST /results 201 Created
     *
     * @return array result data
     * @covers ::postAction()
     */
    public function testPostResultAction201(): array
    {
        $p_data = [
            'result' => self::$faker->randomDigit,
            'user' => 1,
        ];

        // 201
        $headers = $this->getTokenHeaders();
        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            $headers,
            json_encode($p_data)
        );
        $response = self::$client->getResponse();

        self::assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($response->isSuccessful());
        self::assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        self::assertNotEmpty($result['result']['id']);
        self::assertEquals($p_data['result'], $result['result']['result']);

        return $result['result'];
    }

    /**
     * Test GET /results 200 Ok
     *
     * @return void
     * @covers ::cgetAction()
     * @depends testPostResultAction201
     */
    public function testCGetAction200(): void
    {
        $headers = $this->getTokenHeaders();
        self::$client->request(Request::METHOD_GET, self::RUTA_API, [], [], $headers);
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertJson($response->getContent());
        $results = json_decode($response->getContent(), true);
        self::assertArrayHasKey('results', $results);
    }

    /**
     * Test GET /results 200 Ok (XML)
     *
     * @return void
     * @covers ::cgetAction()
     * @covers \App\Controller\Utils::getFormat()
     * @covers \App\Controller\Utils::apiResponse()
     * @depends testPostResultAction201
     */
    public function testCGetAction200XML(): void
    {
        $headers = $this->getTokenHeaders();
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API . '.xml',
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertArrayHasKey('content-type', $response->headers->all());
        self::assertEquals('application/xml', $response->headers->get('content-type'));
    }

    /**
     * Test GET /results/{resultId} 200 Ok
     *
     * @param   array $result result returned by testPostResultAction201()
     * @return  void
     * @covers  ::getAction()
     * @depends testPostResultAction201
     */
    public function testGetResultAction200(array $result): void
    {
        $headers = $this->getTokenHeaders();
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertJson((string) $response->getContent());
        $result_aux = json_decode((string) $response->getContent(), true);
        self::assertEquals($result['id'], $result_aux['result']['id']);
    }

    /**
     * Test POST /results 400 Bad Request
     *
     * @param   array $result result returned by testPostResultAction201()
     * @return  void
     * @covers  ::postAction()
     * @depends testPostResultAction201
     */
    public function testPostResultAction400(array $result): void
    {
        $headers = $this->getTokenHeaders();

        $p_data = [
            'result' => self::$faker->randomDigit,
            'user' => 99999,
        ];
        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            $headers,
            json_encode($p_data)
        );
        $response = self::$client->getResponse();

        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $r_body = (string) $response->getContent();
        self::assertJson($r_body);
        self::assertContains('code', $r_body);
        self::assertContains('message', $r_body);
        $r_data = json_decode($r_body, true);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $r_data['message']['code']);
        self::assertEquals(
            Response::$statusTexts[400],
            $r_data['message']['message']
        );
    }

    /**
     * Test PUT /results/{resultId} 209 Content Returned
     *
     * @param   array $result result returned by testPostResultAction201()
     * @return  array modified user data
     * @covers  ::putAction()
     * @depends testPostResultAction201
     */
    public function testPutResultAction209(array $result): array
    {
        $headers = $this->getTokenHeaders();
        $p_data = [
            'result' => self::$faker->randomDigit,
            'user' => 2,
        ];

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            $headers,
            json_encode($p_data)
        );
        $response = self::$client->getResponse();

        self::assertEquals(209, $response->getStatusCode());
        self::assertJson((string) $response->getContent());
        $result_aux = json_decode((string) $response->getContent(), true);
        self::assertEquals($result['id'], $result_aux['result']['id']);
        self::assertEquals($p_data['user'], $result_aux['result']['user']['id']);

        return $result_aux['result'];
    }

    /**
     * Test PUT /results/{resultId} 400 Bad Request
     *
     * @param   array $result result returned by testPutResultAction209()
     * @return  void
     * @covers  ::putAction()
     * @depends testPutResultAction209
     */
    public function testPutResultAction400(array $result): void
    {
        $headers = $this->getTokenHeaders();
        // e-mail already exists
        $p_data = [
            'result' => self::$faker->randomDigit,
            'user' => 99999,
        ];
        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            $headers,
            json_encode($p_data)
        );
        $response = self::$client->getResponse();

        self::assertEquals(
            Response::HTTP_BAD_REQUEST,
            $response->getStatusCode()
        );
        $r_body = (string) $response->getContent();
        self::assertJson($r_body);
        self::assertContains('code', $r_body);
        self::assertContains('message', $r_body);
        $r_data = json_decode($r_body, true);
        self::assertEquals(
            Response::HTTP_BAD_REQUEST,
            $r_data['message']['code']
        );
        self::assertEquals(
            Response::$statusTexts[400],
            $r_data['message']['message']
        );
    }

    /**
     * Test DELETE /results/{resultId} 204 No Content
     *
     * @param   array $result result returned by testPostResultAction201()
     * @return  int resultId
     * @covers  ::deleteAction()
     * @depends testPostResultAction201
     * @depends testPostResultAction400
     * @depends testGetResultAction200
     * @depends testPutResultAction400
     */
    public function testDeleteResultAction204(array $result): int
    {
        $headers = $this->getTokenHeaders();
        self::$client->request(
            Request::METHOD_DELETE,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();

        self::assertEquals(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertEmpty((string) $response->getContent());

        return $result['id'];
    }

    /**
     * Test POST /results 422 Unprocessable Entity
     *
     * @covers ::postAction()
     * @param null|int $result
     * @param null|int $user
     * @dataProvider resultProvider422
     * @return void
     */
    public function testPostResultAction422(?int $result, ?int $user): void
    {
        $headers = $this->getTokenHeaders();
        $p_data = [
            'result' => $result,
            'user' => $user,
        ];

        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            $headers,
            json_encode($p_data)
        );
        $response = self::$client->getResponse();

        self::assertEquals(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $response->getStatusCode()
        );
        $r_body = (string) $response->getContent();
        self::assertJson($r_body);
        self::assertContains('code', $r_body);
        self::assertContains('message', $r_body);
        $r_data = json_decode($r_body, true);
        self::assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $r_data['message']['code']);
        self::assertEquals(
            Response::$statusTexts[422],
            $r_data['message']['message']
        );
    }

    /**
     * Test GET    /results 401 UNAUTHORIZED
     * Test POST   /results 401 UNAUTHORIZED
     * Test GET    /results/{resultId} 401 UNAUTHORIZED
     * Test PUT    /results/{resultId} 401 UNAUTHORIZED
     * Test DELETE /results/{resultId} 401 UNAUTHORIZED
     *
     * @param string $method
     * @param string $uri
     * @dataProvider routeProvider401()
     * @return void
     * @covers ::cgetAction()
     * @covers ::getAction()
     * @covers ::postAction()
     * @covers ::putAction()
     * @covers ::deleteAction()
     * @covers \App\EventListener\ExceptionListener::onKernelException()
     */
    public function testResultStatus401(string $method, string $uri): void
    {
        self::$client->request(
            $method,
            $uri,
            [],
            [],
            [ 'HTTP_ACCEPT' => 'application/json' ]
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertJson((string) $response->getContent());
        $r_body = (string) $response->getContent();
        self::assertContains('code', $r_body);
        self::assertContains('message', $r_body);
        $r_data = json_decode($r_body, true);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $r_data['message']['code']);
        self::assertSame(
            'Invalid credentials.',
            $r_data['message']['message']
        );
    }

    /**
     * Test GET    /results/{resultId} 404 NOT FOUND
     * Test DELETE /results/{resultId} 404 NOT FOUND
     *
     * @param string $method
     * @param int $resultId result id. returned by testDeleteResultAction204()
     * @dataProvider routeProvider404
     * @return void
     * @covers ::getAction()
     * @covers ::deleteAction()
     * @depends testDeleteResultAction204
     */
    public function testResultStatus404(string $method, int $resultId): void
    {
        $headers = $this->getTokenHeaders(
            self::$role_admin['email'],
            self::$role_admin['passwd']
        );
        self::$client->request(
            $method,
            self::RUTA_API . '/' . $resultId,
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $r_body = (string) $response->getContent();
        self::assertContains('code', $r_body);
        self::assertContains('message', $r_body);
        $r_data = json_decode($r_body, true);
        self::assertSame(Response::HTTP_NOT_FOUND, $r_data['message']['code']);
        self::assertSame(Response::$statusTexts[404], $r_data['message']['message']);
    }
//
//    /**
//     * *********
//     * PROVIDERS
//     * *********
//     */
//
    /**
     * Result provider (incomplete) -> 422 status code
     *
     * @return array result data
     */
    public function resultProvider422(): array
    {
        $faker = FakerFactoryAlias::create('es_ES');
        $result = $faker->randomDigit;
        $user = 1;

        return [
            'nulo_01' => [ null,   $result ],
            'nulo_02' => [ $user, null      ],
            'nulo_03' => [ null,   null      ],
        ];
    }

    /**
     * Route provider (expected status: 401 UNAUTHORIZED)
     *
     * @return array [ method, url ]
     */
    public function routeProvider401(): array
    {
        return [
            'cgetAction401'   => [ Request::METHOD_GET,    self::RUTA_API ],
            'getAction401'    => [ Request::METHOD_GET,    self::RUTA_API . '/1' ],
            'postAction401'   => [ Request::METHOD_POST,   self::RUTA_API ],
            'putAction401'    => [ Request::METHOD_PUT,    self::RUTA_API . '/1' ],
            'deleteAction401' => [ Request::METHOD_DELETE, self::RUTA_API . '/1' ],
        ];
    }

    /**
     * Route provider (expected status 404 NOT FOUND)
     *
     * @return array [ method ]
     */
    public function routeProvider404(): array
    {
        return [
            'getAction404'    => [ Request::METHOD_GET ],
            'deleteAction404' => [ Request::METHOD_DELETE ],
        ];
    }
}
