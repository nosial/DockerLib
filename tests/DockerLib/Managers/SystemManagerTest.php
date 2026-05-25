<?php

    namespace DockerLib\Managers;

    use DockerLib\Docker;
    use DockerLib\Objects\SystemDataUsage;
    use DockerLib\Objects\SystemInfo;
    use PHPUnit\Framework\TestCase;

    class SystemManagerTest extends TestCase
    {
        private Docker $docker;

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }
        }

        public function testGetSystemInfo()
        {
            $info = $this->docker->system()->info();
            
            $this->assertInstanceOf(SystemInfo::class, $info);
            $this->assertNotNull($info->getId());
            $this->assertIsInt($info->getContainers());
            $this->assertIsInt($info->getImages());
            $this->assertNotNull($info->getOperatingSystem());
            $this->assertNotNull($info->getArchitecture());
            $this->assertGreaterThan(0, $info->getNCPU());
            $this->assertGreaterThan(0, $info->getMemTotal());
        }

        public function testGetVersion()
        {
            $version = $this->docker->system()->version();
            
            $this->assertIsArray($version);
            $this->assertArrayHasKey('Version', $version);
            $this->assertArrayHasKey('ApiVersion', $version);
            $this->assertArrayHasKey('Os', $version);
            $this->assertArrayHasKey('Arch', $version);
            $this->assertNotEmpty($version['Version']);
        }

        public function testPing()
        {
            $result = $this->docker->system()->ping();
            
            $this->assertNotEmpty($result);
        }

        public function testPingHead()
        {
            $result = $this->docker->system()->pingHead();

            $this->assertIsArray($result);
        }

        public function testPingHeadSucceeds()
        {
            $result = $this->docker->system()->pingHead();

            $this->assertNotEmpty($result);
            // HEAD /_ping returns an empty body but should not error
        }

        public function testPingAndPingHeadAreConsistent()
        {
            $pingResult = $this->docker->system()->ping();
            $pingHeadResult = $this->docker->system()->pingHead();

            $this->assertIsString($pingResult);
            $this->assertIsArray($pingHeadResult);
            // Both should succeed without throwing
        }

        public function testAuthWithInvalidCredentials()
        {
            try {
                $result = $this->docker->system()->auth([
                    'username' => 'invalid-user',
                    'password' => 'invalid-pass',
                    'serveraddress' => 'https://index.docker.io/v1/'
                ]);

                $this->assertIsArray($result);
                $this->assertArrayHasKey('Status', $result);
            } catch (\DockerLib\Exceptions\ResponseException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }

        public function testEventsWithTimeRange()
        {
            $stream = $this->docker->system()->events(
                [],
                time() - 60,
                time()
            );

            $this->assertInstanceOf(\DockerLib\Objects\StreamResponse::class, $stream);
            $stream->close();
        }

        public function testDataUsage()
        {
            $usage = $this->docker->system()->dataUsage();
            
            $this->assertInstanceOf(SystemDataUsage::class, $usage);
            $this->assertIsArray($usage->getImages());
            $this->assertIsArray($usage->getContainers());
            $this->assertIsArray($usage->getVolumes());
            $this->assertGreaterThanOrEqual(0, $usage->getLayersSize());
        }

        public function testSystemInfoProperties()
        {
            $info = $this->docker->system()->info();
            
            // Test various properties
            $this->assertIsInt($info->getContainersRunning());
            $this->assertIsInt($info->getContainersPaused());
            $this->assertIsInt($info->getContainersStopped());
            
            $this->assertNotNull($info->getDockerRootDir());
            $this->assertNotNull($info->getDriver());
            $this->assertNotNull($info->getKernelVersion());
            $this->assertNotNull($info->getOperatingSystem());
            $this->assertNotNull($info->getOSType());
            
            $this->assertIsArray($info->getDriverStatus());
            $this->assertIsArray($info->getPlugins());
        }

        public function testSystemInfoLimits()
        {
            $info = $this->docker->system()->info();
            
            // These return booleans indicating if feature is supported
            $this->assertIsBool($info->getMemoryLimit());
            $this->assertIsBool($info->getSwapLimit());
            $this->assertIsBool($info->getCpuCfsPeriod());
            $this->assertIsBool($info->getCpuCfsQuota());
            $this->assertIsBool($info->getCPUShares());
            $this->assertIsBool($info->getCPUSet());
        }

        public function testSystemInfoResources()
        {
            $info = $this->docker->system()->info();
            
            $this->assertGreaterThan(0, $info->getNCPU());
            $this->assertGreaterThan(0, $info->getMemTotal());
            
            // Should be reasonable values
            $this->assertLessThan(1024, $info->getNCPU()); // Less than 1024 CPUs
            $this->assertGreaterThan(100000000, $info->getMemTotal()); // At least 100MB
        }

        public function testVersionInformation()
        {
            $version = $this->docker->system()->version();
            
            // Check for standard version fields
            $this->assertNotEmpty($version['Version']);
            $this->assertNotEmpty($version['ApiVersion']);
            
            // Verify version format
            $this->assertMatchesRegularExpression('/\d+\.\d+/', $version['Version']);
        }

        public function testDataUsageDetails()
        {
            $usage = $this->docker->system()->dataUsage();
            
            $images = $usage->getImages();
            $this->assertIsArray($images);
            
            // If there are images, check their structure
            if (!empty($images)) {
                $firstImage = $images[0];
                $this->assertArrayHasKey('Id', $firstImage);
                $this->assertArrayHasKey('Size', $firstImage);
            }
            
            $containers = $usage->getContainers();
            $this->assertIsArray($containers);
            
            $volumes = $usage->getVolumes();
            $this->assertIsArray($volumes);
        }

        public function testRawDataAccess()
        {
            $info = $this->docker->system()->info();
            $rawData = $info->getRawData();
            
            $this->assertIsArray($rawData);
            $this->assertNotEmpty($rawData);
            $this->assertArrayHasKey('ID', $rawData);
        }
    }
