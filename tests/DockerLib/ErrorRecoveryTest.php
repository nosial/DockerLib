<?php

    namespace DockerLib;

    use DockerLib\Docker;
    use PHPUnit\Framework\TestCase;

    class ErrorRecoveryTest extends TestCase
    {
        private Docker $docker;
        private array $testContainers = [];

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }
        }

        protected function tearDown(): void
        {
            foreach ($this->testContainers as $containerId) {
                try {
                    $this->docker->containers()->remove($containerId, true);
                } catch (\Exception $e) {
                    // Ignore cleanup errors
                }
            }
            $this->testContainers = [];
        }

        public function testRestartAfterError()
        {
            // Ensure alpine image is available
            try {
                $this->docker->images()->pull('alpine:latest');
            } catch (\Exception $e) {
                $this->markTestSkipped('Could not pull alpine image');
            }

            // Create container that exits with error
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sh', '-c', 'exit 1']
            ]);

            $this->testContainers[] = $container->getId();

            // Start container (it will exit with error)
            $this->docker->containers()->start($container->getId());
            
            // Wait for it to exit
            $this->docker->containers()->wait($container->getId());

            // Check it exited with error
            $inspected = $this->docker->containers()->inspect($container->getId());
            $state = $inspected->getStateArray();
            
            $this->assertArrayHasKey('ExitCode', $state);
            $this->assertEquals(1, $state['ExitCode']);

            // Restart the container
            $this->docker->containers()->restart($container->getId());

            // Wait again
            $this->docker->containers()->wait($container->getId());

            // Should still have exit code 1
            $inspected = $this->docker->containers()->inspect($container->getId());
            $state = $inspected->getState();
            $this->assertEquals(1, $state['ExitCode']);
        }

        public function testRetryOnNetworkError()
        {
            // Create network
            $networkName = 'dockerlib-test-retry-' . uniqid();
            $network = $this->docker->networks()->create([
                'Name' => $networkName,
                'Driver' => 'bridge'
            ]);

            try {
                // Try to create network with same name (should fail)
                $this->expectException(\Exception::class);
                $this->docker->networks()->create([
                    'Name' => $networkName,
                    'Driver' => 'bridge'
                ]);
            } finally {
                // Cleanup
                try {
                    $this->docker->networks()->remove($network->getId());
                } catch (\Exception $e) {
                    // Ignore
                }
            }
        }

        public function testHandleInvalidConfiguration()
        {
            // Try to create container with invalid config
            $this->expectException(\Exception::class);
            
            $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'HostConfig' => [
                    'Memory' => -1 // Invalid memory value
                ]
            ]);
        }

        public function testHandleNonExistentImage()
        {
            $this->expectException(\Exception::class);
            
            $this->docker->containers()->create([
                'Image' => 'nonexistent-image-' . uniqid() . ':latest',
                'Cmd' => ['echo', 'test']
            ]);
        }

        public function testStopAlreadyStoppedContainer()
        {
            // Ensure alpine image is available
            try {
                $this->docker->images()->pull('alpine:latest');
            } catch (\Exception $e) {
                $this->markTestSkipped('Could not pull alpine image');
            }

            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['echo', 'test']
            ]);

            $this->testContainers[] = $container->getId();

            // Container is already stopped (not started)
            // Stop should either succeed or throw specific exception
            try {
                $this->docker->containers()->stop($container->getId());
                $this->assertTrue(true); // Stop succeeded on already stopped container
            } catch (\Exception $e) {
                // Some Docker versions throw error, others don't
                $this->assertStringContainsString('not running', strtolower($e->getMessage()));
            }
        }

        public function testRemoveNonExistentContainer()
        {
            $this->expectException(\Exception::class);
            
            $this->docker->containers()->remove('nonexistent-' . uniqid(), false);
        }

        public function testListWithInvalidFilter()
        {
            // Invalid filter should either be ignored or throw exception
            try {
                $containers = $this->docker->containers()->list([
                    'invalid-filter-key-' . uniqid() => ['value']
                ], true);
                
                // If it doesn't throw, we should still get an array
                $this->assertIsArray($containers);
            } catch (\Exception $e) {
                // Some Docker versions may throw error for invalid filters
                $this->assertInstanceOf(\Exception::class, $e);
            }
        }

        public function testPauseNonRunningContainer()
        {
            // Ensure alpine image is available
            try {
                $this->docker->images()->pull('alpine:latest');
            } catch (\Exception $e) {
                $this->markTestSkipped('Could not pull alpine image');
            }

            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30']
            ]);

            $this->testContainers[] = $container->getId();

            // Try to pause without starting (should fail)
            $this->expectException(\Exception::class);
            $this->docker->containers()->pause($container->getId());
        }

        public function testKillNonRunningContainer()
        {
            // Ensure alpine image is available
            try {
                $this->docker->images()->pull('alpine:latest');
            } catch (\Exception $e) {
                $this->markTestSkipped('Could not pull alpine image');
            }

            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30']
            ]);

            $this->testContainers[] = $container->getId();

            // Try to kill without starting (should fail)
            $this->expectException(\Exception::class);
            $this->docker->containers()->kill($container->getId());
        }
    }
