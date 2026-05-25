<?php

    namespace DockerLib;

    use DockerLib\Docker;
    use DockerLib\Exceptions\ResponseException;
    use PHPUnit\Framework\TestCase;

    class ContainerLifecycleTest extends TestCase
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
            // Clean up all test containers
            foreach ($this->testContainers as $containerId) {
                try {
                    $this->docker->containers()->stop($containerId, 1);
                } catch (\Exception $e) {
                    // Container might already be stopped
                }
                
                try {
                    $this->docker->containers()->remove($containerId, true, true);
                } catch (\Exception $e) {
                    // Container might already be removed
                }
            }
            
            $this->testContainers = [];
        }

        public function testCompleteContainerLifecycle()
        {
            // Create container
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '60'],
                'Labels' => ['test' => 'lifecycle']
            ]);
            
            $this->testContainers[] = $container->getId();
            $this->assertNotNull($container->getId());
            
            // Start container
            $this->docker->containers()->start($container->getId());
            
            // Inspect container
            $inspected = $this->docker->containers()->inspect($container->getId());
            $this->assertTrue($inspected->isRunning());
            
            // Pause container
            $this->docker->containers()->pause($container->getId());
            $paused = $this->docker->containers()->inspect($container->getId());
            $this->assertTrue($paused->isPaused());
            
            // Unpause container
            $this->docker->containers()->unpause($container->getId());
            $unpaused = $this->docker->containers()->inspect($container->getId());
            $this->assertFalse($unpaused->isPaused());
            
            // Stop container
            $this->docker->containers()->stop($container->getId(), 2);
            $stopped = $this->docker->containers()->inspect($container->getId());
            $this->assertFalse($stopped->isRunning());
            
            // Remove container
            $this->docker->containers()->remove($container->getId());
            
            // Verify removal
            $this->expectException(ResponseException::class);
            $this->docker->containers()->inspect($container->getId());
        }

        public function testContainerRestart()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '60']
            ]);
            
            $this->testContainers[] = $container->getId();
            
            $this->docker->containers()->start($container->getId());
            
            // Restart container
            $this->docker->containers()->restart($container->getId(), 2);
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            $this->assertTrue($inspected->isRunning());
        }

        public function testContainerRename()
        {
            $originalName = 'test-container-' . uniqid();
            $newName = 'renamed-container-' . uniqid();
            
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '10']
            ], $originalName);
            
            $this->testContainers[] = $container->getId();
            
            // Rename container
            $this->docker->containers()->rename($container->getId(), $newName);
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            $this->assertStringContainsString($newName, $inspected->getName());
        }

        public function testContainerKill()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '60']
            ]);
            
            $this->testContainers[] = $container->getId();
            
            $this->docker->containers()->start($container->getId());
            
            // Kill container
            $this->docker->containers()->kill($container->getId());
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            $this->assertFalse($inspected->isRunning());
        }

        public function testContainerWait()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sh', '-c', 'sleep 1 && exit 0']
            ]);
            
            $this->testContainers[] = $container->getId();
            
            $this->docker->containers()->start($container->getId());
            
            // Wait for container to finish
            $result = $this->docker->containers()->wait($container->getId());
            
            $this->assertIsArray($result);
            $this->assertEquals(0, $result['StatusCode']);
        }

        public function testContainerWithEnvironmentVariables()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sh', '-c', 'echo $TEST_VAR && sleep 5'],
                'Env' => ['TEST_VAR=test_value']
            ]);
            
            $this->testContainers[] = $container->getId();
            
            $this->docker->containers()->start($container->getId());
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            $env = $inspected->getEnvironment();
            
            $this->assertContains('TEST_VAR=test_value', $env);
        }

        public function testContainerWithLabels()
        {
            $labels = [
                'com.example.app' => 'myapp',
                'com.example.version' => '1.0.0'
            ];
            
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '10'],
                'Labels' => $labels
            ]);
            
            $this->testContainers[] = $container->getId();
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            $containerLabels = $inspected->getLabels();
            
            $this->assertEquals('myapp', $containerLabels['com.example.app']);
            $this->assertEquals('1.0.0', $containerLabels['com.example.version']);
        }
    }
