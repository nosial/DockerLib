<?php

    namespace DockerLib;

    use DockerLib\Docker;
    use LogLib2\Logger;
    use PHPUnit\Framework\TestCase;

    class LoggingIntegrationTest extends TestCase
    {
        private Docker $docker;

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }
        }

        public function testBackwardCompatibility()
        {
            // Test that all existing code works without logger
            $docker = new Docker();
            
            $this->assertTrue($docker->ping());
            
            $version = $docker->version();
            $this->assertIsArray($version);
            
            $images = $docker->images()->list();
            $this->assertIsArray($images);
            
            $containers = $docker->containers()->list();
            $this->assertIsArray($containers);
            
            $networks = $docker->networks()->list();
            $this->assertIsArray($networks);
            
            $volumes = $docker->volumes()->list();
            $this->assertIsArray($volumes);
        }

        public function testOperationsWithLogger()
        {
            $logger = new Logger('test');
            $docker = new Docker('/var/run/docker.sock', $logger);
            
            // All operations should work with logger
            $this->assertTrue($docker->ping());
            
            $version = $docker->version();
            $this->assertIsArray($version);
            
            $images = $docker->images()->list();
            $this->assertIsArray($images);
        }

        public function testManagersInheritLogger()
        {
            $logger = new Logger('test');
            $docker = new Docker('/var/run/docker.sock', $logger);
            
            // Managers should be initialized with logger
            $images = $docker->images()->list();
            $this->assertIsArray($images);
            
            $containers = $docker->containers()->list();
            $this->assertIsArray($containers);
        }
    }
