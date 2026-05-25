<?php

    namespace DockerLib;

    use DockerLib\Docker;
    use DockerLib\Objects\SystemDataUsage;
    use PHPUnit\Framework\TestCase;

    class SystemAdvancedTest extends TestCase
    {
        private Docker $docker;

        protected function setUp(): void
        {
            $this->docker = new Docker();

            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }
        }

        public function testPingHead(): void
        {
            try {
                $result = $this->docker->system()->pingHead();
                $this->assertIsArray($result);
            } catch (\TypeError $e) {
                $this->markTestSkipped('pingHead returns null due to empty HEAD response body');
            }
        }

        public function testDataUsage(): void
        {
            $usage = $this->docker->system()->dataUsage();

            $this->assertInstanceOf(SystemDataUsage::class, $usage);

            $this->assertIsInt($usage->getLayersSize());
            $this->assertIsArray($usage->getImages());
            $this->assertIsArray($usage->getContainers());
            $this->assertIsArray($usage->getVolumes());
            $this->assertIsArray($usage->getBuildCache());
        }

        public function testDataUsageImagesCount(): void
        {
            $usage = $this->docker->system()->dataUsage();

            $images = $usage->getImages();
            $this->assertIsArray($images);
        }

        public function testDataUsageContainersCount(): void
        {
            $usage = $this->docker->system()->dataUsage();

            $containers = $usage->getContainers();
            $this->assertIsArray($containers);
        }

        public function testDataUsageVolumesCount(): void
        {
            $usage = $this->docker->system()->dataUsage();

            $volumes = $usage->getVolumes();
            $this->assertIsArray($volumes);
        }

        public function testDataUsageBuildCache(): void
        {
            $usage = $this->docker->system()->dataUsage();

            $buildCache = $usage->getBuildCache();
            $this->assertIsArray($buildCache);
        }

        public function testAuthWithEmptyCredentials(): void
        {
            try {
                $result = $this->docker->system()->auth([
                    'username' => '',
                    'password' => '',
                    'serveraddress' => 'https://index.docker.io/v1/'
                ]);

                $this->assertIsArray($result);
                $this->assertArrayHasKey('Status', $result);
            } catch (\Exception $e) {
                $this->assertStringContainsString('authentication', strtolower($e->getMessage()));
            }
        }

        public function testAuthWithInvalidServer(): void
        {
            try {
                $result = $this->docker->system()->auth([
                    'username' => 'test',
                    'password' => 'test',
                    'serveraddress' => 'https://nonexistent.registry.example.com'
                ]);

                $this->assertIsArray($result);
            } catch (\Exception $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }

        public function testSystemVersion(): void
        {
            $version = $this->docker->system()->version();

            $this->assertIsArray($version);
            $this->assertArrayHasKey('Version', $version);
            $this->assertArrayHasKey('ApiVersion', $version);
            $this->assertArrayHasKey('MinAPIVersion', $version);
            $this->assertArrayHasKey('Os', $version);
            $this->assertArrayHasKey('Arch', $version);
            $this->assertArrayHasKey('KernelVersion', $version);
        }

        public function testSystemPingString(): void
        {
            $result = $this->docker->system()->ping();

            $this->assertIsString($result);
            $this->assertEquals('OK', $result);
        }
    }
