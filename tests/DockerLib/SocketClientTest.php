<?php

    namespace DockerLib;

    use DockerLib\Classes\SocketClient;
    use DockerLib\Exceptions\ConnectionException;
    use DockerLib\Exceptions\ResponseException;
    use PHPUnit\Framework\TestCase;

    class SocketClientTest extends TestCase
    {
        private ?SocketClient $client = null;

        protected function setUp(): void
        {
            if (!file_exists('/var/run/docker.sock')) {
                $this->markTestSkipped('Docker socket not available');
            }
            
            $this->client = new SocketClient('/var/run/docker.sock');
        }

        public function testSocketConnection()
        {
            $this->assertInstanceOf(SocketClient::class, $this->client);
        }

        public function testInvalidSocketPath()
        {
            $this->expectException(ConnectionException::class);
            $client = new SocketClient('/invalid/path/to/socket');
            $client->request('GET', '/_ping');
        }

        public function testGetRequest()
        {
            $response = $this->client->request('GET', '/_ping');
            
            $this->assertIsArray($response);
            $this->assertArrayHasKey('statusCode', $response);
            $this->assertEquals(200, $response['statusCode']);
        }

        public function testGetRequestWithQuery()
        {
            $response = $this->client->request('GET', '/containers/json', null, [
                'all' => 1,
                'limit' => 10
            ]);
            
            $this->assertIsArray($response);
            $this->assertEquals(200, $response['statusCode']);
            $this->assertArrayHasKey('data', $response);
        }

        public function testPostRequest()
        {
            // Test creating a container (we'll remove it immediately)
            $response = $this->client->request('POST', '/containers/create', [
                'Image' => 'alpine:latest',
                'Cmd' => ['echo', 'test']
            ]);
            
            $this->assertIsArray($response);
            $this->assertEquals(201, $response['statusCode']);
            $this->assertArrayHasKey('data', $response);
            $this->assertArrayHasKey('Id', $response['data']);
            
            // Cleanup
            $containerId = $response['data']['Id'];
            $this->client->request('DELETE', "/containers/{$containerId}", null, ['force' => 1]);
        }

        public function test404Response()
        {
            $this->expectException(ResponseException::class);
            $this->expectExceptionCode(404);
            
            $this->client->request('GET', '/containers/nonexistent-container-id/json');
        }

        public function testSetTimeout()
        {
            $this->client->setTimeout(60);
            
            // If no exception is thrown, timeout was set successfully
            $this->assertTrue(true);
        }

        public function testRequestRaw()
        {
            $raw = $this->client->requestRaw('GET', '/version');
            
            $this->assertIsString($raw);
            $this->assertNotEmpty($raw);
            
            $decoded = json_decode($raw, true);
            $this->assertIsArray($decoded);
            $this->assertArrayHasKey('Version', $decoded);
        }

        public function testStreamResponse()
        {
            $stream = $this->client->stream('GET', '/_ping');
            
            $this->assertEquals(200, $stream->getStatusCode());
            $this->assertIsArray($stream->getHeaders());
            
            $stream->close();
        }

        public function testMultipleRequests()
        {
            // Test that multiple requests work (connection is properly managed)
            $response1 = $this->client->request('GET', '/_ping');
            $this->assertEquals(200, $response1['statusCode']);
            
            $response2 = $this->client->request('GET', '/version');
            $this->assertEquals(200, $response2['statusCode']);
            
            $response3 = $this->client->request('GET', '/info');
            $this->assertEquals(200, $response3['statusCode']);
        }

        public function testResponseHeaders()
        {
            $response = $this->client->request('GET', '/version');
            
            $this->assertArrayHasKey('headers', $response);
            $this->assertIsArray($response['headers']);
        }

        public function testJsonDecoding()
        {
            $response = $this->client->request('GET', '/version');
            
            $this->assertArrayHasKey('data', $response);
            $this->assertIsArray($response['data']);
            $this->assertArrayHasKey('Version', $response['data']);
        }
    }
