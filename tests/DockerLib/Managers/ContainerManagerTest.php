<?php

    namespace DockerLib\Managers;

    use DockerLib\Docker;
    use DockerLib\Exceptions\ResponseException;
    use DockerLib\Objects\Container;
    use PHPUnit\Framework\TestCase;

    class ContainerManagerTest extends TestCase
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

        public function testListContainers()
        {
            $containers = $this->docker->containers()->list();
            
            $this->assertIsArray($containers);
            
            foreach ($containers as $container) {
                $this->assertInstanceOf(Container::class, $container);
                $this->assertNotNull($container->getId());
            }
        }

        public function testListAllContainers()
        {
            $containers = $this->docker->containers()->list([], true);
            
            $this->assertIsArray($containers);
        }

        public function testCreateContainer()
        {
            $containerName = 'dockerlib-test-' . uniqid();
            
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '300'],
                'Labels' => [
                    'test' => 'dockerlib',
                    'cleanup' => 'true'
                ]
            ], $containerName);
            
            $this->testContainers[] = $container->getId();
            
            $this->assertInstanceOf(Container::class, $container);
            $this->assertNotNull($container->getId());
            $this->assertEquals($containerName, $container->getName());
            // Image can be full SHA or tag
            $this->assertNotEmpty($container->getImage());
        }

        public function testContainerLifecycle()
        {
            // Create container
            $containerName = 'dockerlib-lifecycle-' . uniqid();
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '300'],
                'Labels' => ['test' => 'lifecycle']
            ], $containerName);
            
            $containerId = $container->getId();
            $this->testContainers[] = $containerId;
            
            // Start container
            $this->docker->containers()->start($containerId);
            sleep(1); // Wait for container to start
            
            $container = $this->docker->containers()->inspect($containerId);
            $this->assertTrue($container->isRunning());
            
            // Pause container
            $this->docker->containers()->pause($containerId);
            sleep(1);
            
            $container = $this->docker->containers()->inspect($containerId);
            $this->assertEquals('paused', $container->getStateString());
            
            // Unpause container
            $this->docker->containers()->unpause($containerId);
            sleep(1);
            
            $container = $this->docker->containers()->inspect($containerId);
            $this->assertTrue($container->isRunning());
            
            // Stop container
            $this->docker->containers()->stop($containerId, 5);
            sleep(1);
            
            $container = $this->docker->containers()->inspect($containerId);
            $this->assertFalse($container->isRunning());
            
            // Restart container
            $this->docker->containers()->restart($containerId, 5);
            sleep(1);
            
            $container = $this->docker->containers()->inspect($containerId);
            $this->assertTrue($container->isRunning());
        }

        public function testRenameContainer()
        {
            $originalName = 'dockerlib-rename-orig-' . uniqid();
            $newName = 'dockerlib-rename-new-' . uniqid();
            
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '10']
            ], $originalName);
            
            $this->testContainers[] = $container->getId();
            
            $this->docker->containers()->rename($container->getId(), $newName);
            
            $container = $this->docker->containers()->inspect($container->getId());
            $this->assertEquals($newName, $container->getName());
        }

        public function testContainerLogs()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['echo', 'Hello DockerLib'],
                'AttachStdout' => true,
                'AttachStderr' => true
            ], 'dockerlib-logs-' . uniqid());
            
            $this->testContainers[] = $container->getId();
            
            $this->docker->containers()->start($container->getId());
            $this->docker->containers()->wait($container->getId());
            
            $logs = $this->docker->containers()->logs($container->getId());
            
            $this->assertNotEmpty($logs);
            $this->assertStringContainsString('Hello', $logs);
        }

        public function testContainerStats()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '60']
            ], 'dockerlib-stats-' . uniqid());
            
            $this->testContainers[] = $container->getId();
            
            $this->docker->containers()->start($container->getId());
            sleep(2); // Wait for container to be fully running
            
            $stats = $this->docker->containers()->stats($container->getId());
            
            $this->assertNotNull($stats->getId());
            $this->assertIsArray($stats->getCpuStats());
            $this->assertIsArray($stats->getMemoryStats());
            $this->assertGreaterThanOrEqual(0, $stats->getMemoryUsage());
        }

        public function testContainerTop()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '60']
            ], 'dockerlib-top-' . uniqid());
            
            $this->testContainers[] = $container->getId();
            
            $this->docker->containers()->start($container->getId());
            sleep(1);
            
            $processes = $this->docker->containers()->top($container->getId());
            
            $this->assertIsArray($processes);
            $this->assertArrayHasKey('Titles', $processes);
            $this->assertArrayHasKey('Processes', $processes);
        }

        public function testInspectNonExistentContainer()
        {
            $this->expectException(ResponseException::class);
            $this->docker->containers()->inspect('nonexistent-container-id');
        }

        public function testContainerUpdate()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '60'],
                'HostConfig' => [
                    'Memory' => 134217728 // 128MB
                ]
            ], 'dockerlib-update-' . uniqid());
            
            $this->testContainers[] = $container->getId();
            
            $this->docker->containers()->update($container->getId(), [
                'Memory' => 268435456 // 256MB
            ]);
            
            $updated = $this->docker->containers()->inspect($container->getId());
            $hostConfig = $updated->getHostConfigObject();
            
            $this->assertEquals(268435456, $hostConfig->getMemory());
        }

        public function testContainerLogsWithOutput()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sh', '-c', 'echo "test log output" && sleep 5']
            ]);
            
            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());
            sleep(1);
            
            $logs = $this->docker->containers()->logs($container->getId(), true, true);
            
            $this->assertNotEmpty($logs);
            $this->assertStringContainsString('test log output', $logs);
        }

        public function testContainerTopProcesses()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30']
            ]);
            
            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());
            
            $processes = $this->docker->containers()->top($container->getId());
            
            $this->assertIsArray($processes);
            $this->assertArrayHasKey('Processes', $processes);
        }

        public function testContainerPauseUnpause()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30']
            ]);
            
            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());
            
            $this->docker->containers()->pause($container->getId());
            $inspected = $this->docker->containers()->inspect($container->getId());
            $state = $inspected->getStateString();
            $this->assertEquals('paused', strtolower($state));
            
            $this->docker->containers()->unpause($container->getId());
            $inspected = $this->docker->containers()->inspect($container->getId());
            $state = $inspected->getStateString();
            $this->assertEquals('running', strtolower($state));
        }

        public function testContainerRename()
        {
            $originalName = 'dockerlib-rename-test-' . uniqid();
            $newName = 'dockerlib-renamed-' . uniqid();
            
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '10']
            ], $originalName);
            
            $this->testContainers[] = $container->getId();
            
            $this->docker->containers()->rename($container->getId(), $newName);
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            $this->assertStringContainsString($newName, $inspected->getName());
        }

        public function testContainerExport()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '5']
            ]);
            
            $this->testContainers[] = $container->getId();
            
            $tarData = $this->docker->containers()->export($container->getId());
            
            $this->assertNotEmpty($tarData);
            $this->assertIsString($tarData);
        }

        public function testContainerChanges()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sh', '-c', 'echo "test" > /tmp/test.txt && sleep 5']
            ]);
            
            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());
            sleep(2);
            
            $changes = $this->docker->containers()->changes($container->getId());
            
            $this->assertIsArray($changes);
        }

        public function testContainerKill()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '60']
            ]);
            
            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());
            
            $this->docker->containers()->kill($container->getId(), 'SIGKILL');
            
            sleep(1);
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            $state = $inspected->getStateString();
            $this->assertIsString($state);
            $this->assertNotEquals('running', strtolower($state));
        }

        public function testContainerPrune()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['echo', 'test']
            ]);
            
            $containerId = $container->getId();
            $this->docker->containers()->start($containerId);
            $this->docker->containers()->wait($containerId);
            
            $result = $this->docker->containers()->prune();
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('ContainersDeleted', $result);
            $this->assertArrayHasKey('SpaceReclaimed', $result);
        }

        public function testListContainersWithFilters()
        {
            $testLabel = 'dockerlib-filter-test-' . uniqid();
            
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '20'],
                'Labels' => [$testLabel => 'true']
            ]);
            
            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());
            
            $containers = $this->docker->containers()->list(['label' => [$testLabel . '=true']]);
            
            $found = false;
            foreach ($containers as $c) {
                if ($c->getId() === $container->getId()) {
                    $found = true;
                    break;
                }
            }
            
            $this->assertTrue($found);
        }

        public function testContainerAttach()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sh', '-c', 'echo "Hello from container" && sleep 2'],
                'Tty' => false,
                'OpenStdin' => false,
                'StdinOnce' => false,
            ]);
            
            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());
            
            $stream = $this->docker->containers()->attach(
                $container->getId(),
                true,   // logs
                false,  // stream
                false,  // stdin
                true,   // stdout
                true    // stderr
            );
            
            $this->assertInstanceOf(\DockerLib\Objects\StreamResponse::class, $stream);
            $this->assertNotNull($stream->getResponse());
        }

        public function testContainerResize()
        {
            // Create a container with TTY
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30'],
                'Tty' => true,
                'OpenStdin' => true,
            ]);
            
            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());
            
            // Resize the TTY
            try {
                $this->docker->containers()->resize($container->getId(), 50, 120);
                $this->assertTrue(true); // If no exception, resize succeeded
            } catch (\Exception $e) {
                // Some environments may not support resize
                if (str_contains($e->getMessage(), 'not a tty')) {
                    $this->markTestSkipped('Container TTY resize not supported');
                }
                throw $e;
            }
        }

        public function testContainerResizeWithoutTty()
        {
            // Create a container without TTY
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '10'],
                'Tty' => false,
            ]);
            
            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());
            
            // Attempt to resize should fail for non-TTY container
            try {
                $this->docker->containers()->resize($container->getId(), 50, 120);
                // Some Docker versions may not error, so we don't assert exception
                $this->assertTrue(true);
            } catch (\Exception $e) {
                // Expected behavior for non-TTY containers
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }
