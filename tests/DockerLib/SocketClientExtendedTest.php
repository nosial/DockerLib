<?php

    namespace DockerLib;

    use DockerLib\Classes\SocketClient;
    use DockerLib\Exceptions\ConnectionException;
    use PHPUnit\Framework\TestCase;

    class SocketClientExtendedTest extends TestCase
    {
        public function testSocketClientInstantiation()
        {
            try {
                $client = new SocketClient('/var/run/docker.sock');
                $this->assertInstanceOf(SocketClient::class, $client);
            } catch (ConnectionException $e) {
                $this->markTestSkipped('Docker socket not accessible');
            }
        }

        public function testSocketClientWithInvalidPath()
        {
            $this->expectException(ConnectionException::class);
            $client = new SocketClient('/invalid/socket/path/that/does/not/exist');
            // Try to use the client to trigger connection
            $client->request('GET', '/_ping');
        }

        public function testSocketClientRequest()
        {
            try {
                $client = new SocketClient('/var/run/docker.sock');
                $response = $client->request('GET', '/_ping');
                
                $this->assertIsArray($response);
            } catch (ConnectionException $e) {
                $this->markTestSkipped('Docker socket not accessible');
            }
        }

        public function testSocketClientRequestRaw()
        {
            try {
                $client = new SocketClient('/var/run/docker.sock');
                $response = $client->requestRaw('GET', '/_ping');
                
                $this->assertIsString($response);
            } catch (ConnectionException $e) {
                $this->markTestSkipped('Docker socket not accessible');
            }
        }

        public function testInvalidHttpMethod()
        {
            try {
                $client = new SocketClient('/var/run/docker.sock');
                
                $this->expectException(\Exception::class);
                $client->request('INVALID_METHOD', '/_ping');
            } catch (ConnectionException $e) {
                $this->markTestSkipped('Docker socket not accessible');
            }
        }
    }
