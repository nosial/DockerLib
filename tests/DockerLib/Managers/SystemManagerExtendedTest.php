<?php

    namespace DockerLib\Managers;

    use DockerLib\Docker;
    use DockerLib\Objects\SystemInfo;
    use PHPUnit\Framework\TestCase;

    class SystemManagerExtendedTest extends TestCase
    {
        private Docker $docker;

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }
        }

        public function testSystemInfo()
        {
            $info = $this->docker->system()->info();
            
            $this->assertInstanceOf(SystemInfo::class, $info);
            $this->assertNotEmpty($info->getId());
            $this->assertNotEmpty($info->getName());
            $this->assertNotNull($info->getContainers());
            $this->assertNotNull($info->getImages());
            $this->assertNotEmpty($info->getOperatingSystem());
            $this->assertNotEmpty($info->getKernelVersion());
            $this->assertNotEmpty($info->getArchitecture());
        }

        public function testSystemVersion()
        {
            $version = $this->docker->system()->version();
            
            $this->assertIsArray($version);
            $this->assertArrayHasKey('Version', $version);
            $this->assertArrayHasKey('ApiVersion', $version);
            $this->assertArrayHasKey('Os', $version);
            $this->assertArrayHasKey('Arch', $version);
        }

        public function testSystemPing()
        {
            $result = $this->docker->system()->ping();
            
            $this->assertIsString($result);
            $this->assertEquals('OK', $result);
        }

        public function testSystemEvents()
        {
            $stream = $this->docker->system()->events([
                'since' => time() - 10,
                'until' => time()
            ]);
            
            $this->assertInstanceOf(\DockerLib\Objects\StreamResponse::class, $stream);
            
            // Read a few lines or timeout
            $timeout = time() + 2;
            while (time() < $timeout) {
                $line = $stream->readLine();
                if ($line === null) {
                    break;
                }
            }
            
            $stream->close();
            $this->assertTrue(true); // If we got here, stream worked
        }

        public function testSystemEventsWithFilters()
        {
            $stream = $this->docker->system()->events([
                'since' => time() - 60,
                'until' => time(),
                'filters' => json_encode(['type' => ['container']])
            ]);
            
            $this->assertInstanceOf(\DockerLib\Objects\StreamResponse::class, $stream);
            $stream->close();
        }

        public function testSystemInfoMemory()
        {
            $info = $this->docker->system()->info();
            
            $this->assertGreaterThan(0, $info->getMemTotal());
        }

        public function testSystemInfoCpu()
        {
            $info = $this->docker->system()->info();
            
            $this->assertGreaterThan(0, $info->getNCPU());
        }

        public function testSystemInfoDriver()
        {
            $info = $this->docker->system()->info();
            
            $this->assertNotEmpty($info->getDriver());
            $this->assertIsArray($info->getDriverStatus());
        }
    }
