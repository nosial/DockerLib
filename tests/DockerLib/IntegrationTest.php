<?php

    namespace DockerLib;

    use DockerLib\Docker;
    use PHPUnit\Framework\TestCase;

    /**
     * Integration tests that test complete workflows
     * These tests create real containers and clean them up
     */
    class IntegrationTest extends TestCase
    {
        private Docker $docker;
        private array $cleanup = [];

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }
            
            // Ensure alpine image exists
            $this->ensureAlpineImage();
        }

        protected function tearDown(): void
        {
            // Comprehensive cleanup
            foreach ($this->cleanup as $item) {
                try {
                    switch ($item['type']) {
                        case 'container':
                            $this->docker->containers()->stop($item['id'], 1);
                            $this->docker->containers()->remove($item['id'], true, true);
                            break;
                        case 'network':
                            $this->docker->networks()->remove($item['id']);
                            break;
                        case 'volume':
                            $this->docker->volumes()->remove($item['id'], true);
                            break;
                    }
                } catch (\Exception $e) {
                    // Continue cleanup
                }
            }
            
            $this->cleanup = [];
        }

        private function ensureAlpineImage()
        {
            try {
                $this->docker->images()->inspect('alpine:latest');
            } catch (\Exception $e) {
                $stream = $this->docker->images()->pull('alpine', 'latest');
                while ($stream->readLine() !== null) {}
                $stream->close();
            }
        }

        public function testCompleteContainerWorkflow()
        {
            $testId = uniqid();
            
            // 1. Create container
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sh', '-c', 'echo "test-' . $testId . '" && sleep 30'],
                'Labels' => ['integration-test' => 'true']
            ], "integration-test-$testId");
            
            $this->cleanup[] = ['type' => 'container', 'id' => $container->getId()];
            
            $this->assertNotNull($container->getId());
            
            // 2. Start container
            $this->docker->containers()->start($container->getId());
            sleep(2);
            
            // 3. Verify it's running
            $inspected = $this->docker->containers()->inspect($container->getId());
            $this->assertTrue($inspected->isRunning());
            
            // 4. Get logs
            $logs = $this->docker->containers()->logs($container->getId());
            $this->assertStringContainsString("test-$testId", $logs);
            
            // 5. Get stats
            $stats = $this->docker->containers()->stats($container->getId());
            $this->assertNotNull($stats->getId());
            $this->assertGreaterThanOrEqual(0, $stats->getCpuPercentage());
            
            // 6. Execute command
            $exec = $this->docker->exec()->create($container->getId(), [
                'Cmd' => ['echo', 'exec-test'],
                'AttachStdout' => true
            ]);
            
            $stream = $this->docker->exec()->start($exec->getId());
            $output = $stream->readAll();
            $stream->close();
            
            $this->assertStringContainsString('exec-test', $output);
            
            // 7. Stop container
            $this->docker->containers()->stop($container->getId(), 5);
            
            // 8. Verify it's stopped
            $stopped = $this->docker->containers()->inspect($container->getId());
            $this->assertFalse($stopped->isRunning());
        }

        public function testNetworkContainerIntegration()
        {
            $testId = uniqid();
            
            // Create custom network with unique subnet
            $subnetId = rand(30, 250);
            $network = $this->docker->networks()->create([
                'Name' => "integration-net-$testId",
                'Driver' => 'bridge',
                'IPAM' => [
                    'Config' => [
                        ['Subnet' => "172.$subnetId.0.0/16"]
                    ]
                ]
            ]);
            $this->cleanup[] = ['type' => 'network', 'id' => $network->getId()];
            
            // Create container in network
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '60'],
                'HostConfig' => [
                    'NetworkMode' => "integration-net-$testId"
                ]
            ], "integration-container-$testId");
            
            $this->cleanup[] = ['type' => 'container', 'id' => $container->getId()];
            
            $this->docker->containers()->start($container->getId());
            sleep(1);
            
            // Verify container is in network
            $networkInspect = $this->docker->networks()->inspect($network->getId());
            $containers = $networkInspect->getContainers();
            $this->assertArrayHasKey($container->getId(), $containers);
        }

        public function testVolumeContainerIntegration()
        {
            $testId = uniqid();
            
            // Create volume
            $volume = $this->docker->volumes()->create("integration-vol-$testId", [
                'Labels' => ['test' => 'integration']
            ]);
            $this->cleanup[] = ['type' => 'volume', 'id' => $volume->getName()];
            
            // Create container with volume
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sh', '-c', 'echo "data" > /data/test.txt && cat /data/test.txt'],
                'HostConfig' => [
                    'Binds' => ["integration-vol-$testId:/data"]
                ]
            ], "integration-vol-container-$testId");
            
            $this->cleanup[] = ['type' => 'container', 'id' => $container->getId()];
            
            // Start and wait for completion
            $this->docker->containers()->start($container->getId());
            $this->docker->containers()->wait($container->getId());
            
            // Check logs to verify volume worked
            $logs = $this->docker->containers()->logs($container->getId());
            $this->assertStringContainsString('data', $logs);
            
            // Verify volume still exists
            $volumeInspect = $this->docker->volumes()->inspect($volume->getName());
            $this->assertEquals($volume->getName(), $volumeInspect->getName());
        }

        public function testMultipleContainersNetwork()
        {
            $testId = uniqid();
            
            // Create network
            $network = $this->docker->networks()->create(['Name' => "multi-net-$testId"]);
            $this->cleanup[] = ['type' => 'network', 'id' => $network->getId()];
            
            // Create first container
            $container1 = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '60'],
                'Hostname' => 'container1',
                'HostConfig' => [
                    'NetworkMode' => "multi-net-$testId"
                ]
            ], "multi-container1-$testId");
            $this->cleanup[] = ['type' => 'container', 'id' => $container1->getId()];
            
            // Create second container
            $container2 = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '60'],
                'Hostname' => 'container2',
                'HostConfig' => [
                    'NetworkMode' => "multi-net-$testId"
                ]
            ], "multi-container2-$testId");
            $this->cleanup[] = ['type' => 'container', 'id' => $container2->getId()];
            
            // Start both
            $this->docker->containers()->start($container1->getId());
            $this->docker->containers()->start($container2->getId());
            sleep(2);
            
            // Verify both in network
            $networkInspect = $this->docker->networks()->inspect($network->getId());
            $containers = $networkInspect->getContainers();
            
            $this->assertArrayHasKey($container1->getId(), $containers);
            $this->assertArrayHasKey($container2->getId(), $containers);
            $this->assertCount(2, $containers);
        }

        public function testSystemDataUsageAfterOperations()
        {
            $testId = uniqid();
            
            // Get initial usage
            $usageBefore = $this->docker->system()->dataUsage();
            $containersBefore = count($usageBefore->getContainers());
            
            // Create and start a container
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '10']
            ], "usage-test-$testId");
            
            $this->cleanup[] = ['type' => 'container', 'id' => $container->getId()];
            
            $this->docker->containers()->start($container->getId());
            sleep(1);
            
            // Get usage after
            $usageAfter = $this->docker->system()->dataUsage();
            $containersAfter = count($usageAfter->getContainers());
            
            // Should have at least one more container
            $this->assertGreaterThanOrEqual($containersBefore + 1, $containersAfter);
        }
    }
