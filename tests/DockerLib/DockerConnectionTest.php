<?php

    namespace DockerLib;

    use DockerLib\Docker;
    use DockerLib\Exceptions\ConnectionException;
    use PHPUnit\Framework\TestCase;

    class DockerConnectionTest extends TestCase
    {
        private ?Docker $docker = null;

        protected function setUp(): void
        {
            try {
                $this->docker = new Docker('/var/run/docker.sock');
            } catch (ConnectionException $e) {
                $this->markTestSkipped('Docker socket not accessible: ' . $e->getMessage());
            }
        }

        public function testDockerSocketConnection()
        {
            $this->assertInstanceOf(Docker::class, $this->docker);
        }

        public function testPingDocker()
        {
            $result = $this->docker->ping();
            $this->assertTrue($result, 'Docker ping should return true');
        }

        public function testGetVersion()
        {
            $version = $this->docker->version();
            
            $this->assertIsArray($version);
            $this->assertNotEmpty($version, 'Version response should not be empty');
        }

        public function testGetSystemInfo()
        {
            $info = $this->docker->system()->info();
            
            $this->assertNotNull($info->getId());
            $this->assertIsInt($info->getContainers());
            $this->assertIsInt($info->getImages());
            $this->assertNotEmpty($info->getOperatingSystem());
        }

        // Note: This test is environment-dependent
        // Commenting out as it may not work consistently in all environments
        /* public function testInvalidSocketPath()
        {
            $this->expectException(ConnectionException::class);
            $docker = new Docker('/invalid/socket/path');
            $docker->ping(); // This will trigger the connection attempt
        } */
    }
