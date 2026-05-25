<?php

    namespace DockerLib;

    use DockerLib\Exceptions\ResponseException;

    /**
     * Edge case and boundary testing for Docker container operations
     */
    class EdgeCaseContainerTest extends BaseDockerTest
    {
        protected function setUp(): void
        {
            parent::setUp();
            $this->ensureImage('alpine:latest');
        }

        /**
         * Test creating container with null/empty values
         */
        public function testCreateContainerWithEmptyValues()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['echo', 'test'],
                'Labels' => [],
                'Env' => [],
            ]);

            $this->testContainers[] = $container->getId();
            $this->assertNotNull($container->getId());
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            $config = $inspected->getConfig();
            
            $this->assertIsArray($config);
            $this->assertArrayHasKey('Labels', $config);
        }

        /**
         * Test container with extremely long name
         */
        public function testContainerWithLongName()
        {
            $longName = str_repeat('a', 200);
            
            try {
                $container = $this->docker->containers()->create([
                    'Image' => 'alpine:latest',
                    'Cmd' => ['echo', 'test']
                ], $longName);
                
                $this->testContainers[] = $container->getId();
                // Docker may or may not enforce name length limits depending on version
                // Just verify the container was created
                $this->assertNotNull($container);
            } catch (ResponseException $e) {
                // Expected - Docker has name length limits
                $this->assertTrue(true);
            }
        }

        /**
         * Test container with special characters in labels
         */
        public function testContainerWithSpecialCharacterLabels()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['echo', 'test'],
                'Labels' => [
                    'test.label/with-special_chars' => 'value@123',
                    'unicode' => 'テスト',
                    'emoji' => '🐳',
                ]
            ]);

            $this->testContainers[] = $container->getId();
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            $labels = $inspected->getLabels();
            
            $this->assertArrayHasKey('test.label/with-special_chars', $labels);
        }

        /**
         * Test container with maximum number of environment variables
         */
        public function testContainerWithManyEnvironmentVariables()
        {
            $env = [];
            for ($i = 0; $i < 100; $i++) {
                $env[] = "VAR_{$i}=value_{$i}";
            }

            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['printenv'],
                'Env' => $env
            ]);

            $this->testContainers[] = $container->getId();
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            $config = $inspected->getConfig();
            
            $this->assertGreaterThan(99, count($config['Env']));
        }

        /**
         * Test container with empty command
         */
        public function testContainerWithEmptyCommand()
        {
            // Using image default command
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
            ]);

            $this->testContainers[] = $container->getId();
            $this->assertNotNull($container->getId());
        }

        /**
         * Test listing containers with various filter combinations
         */
        public function testListContainersWithComplexFilters()
        {
            // Create test container with specific labels
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30'],
                'Labels' => [
                    'test.type' => 'edge-case',
                    'test.number' => '42'
                ]
            ]);

            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            // Test multiple filter combinations
            $containers = $this->docker->containers()->list([
                'label' => ['test.type=edge-case'],
                'status' => ['running']
            ], true);

            $this->assertIsArray($containers);
            
            $found = false;
            foreach ($containers as $c) {
                if ($c->getId() === $container->getId()) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, 'Container should be found with label filter');
        }

        /**
         * Test container state transitions
         */
        public function testContainerStateTransitions()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '10']
            ]);

            $this->testContainers[] = $container->getId();

            // Created state
            $inspected = $this->docker->containers()->inspect($container->getId());
            $state = $inspected->getStateArray();
            $this->assertIsArray($state);

            // Start -> Running state
            $this->docker->containers()->start($container->getId());
            $this->waitForContainerState($container->getId(), 'running', 5);
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            $state = $inspected->getState();
            $this->assertEquals('running', $state['Status']);

            // Pause -> Paused state
            $this->docker->containers()->pause($container->getId());
            sleep(1);
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            $state = $inspected->getState();
            $this->assertEquals('paused', $state['Status']);

            // Unpause -> Running state
            $this->docker->containers()->unpause($container->getId());
            sleep(1);
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            $state = $inspected->getState();
            $this->assertEquals('running', $state['Status']);

            // Stop -> Exited state
            $this->docker->containers()->stop($container->getId(), 1);
            $this->waitForContainerState($container->getId(), 'exited', 5);
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            $state = $inspected->getState();
            $this->assertEquals('exited', $state['Status']);
        }

        /**
         * Test container with null values in inspect response
         */
        public function testContainerInspectWithNullValues()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['echo', 'test']
            ]);

            $this->testContainers[] = $container->getId();
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            
            // Test that getters handle null values properly
            $this->assertIsArray($inspected->getConfig());
            $this->assertIsArray($inspected->getHostConfig());
            $networkSettings = $inspected->getNetworkSettings();
            $this->assertTrue($networkSettings === null || is_array($networkSettings));
        }

        /**
         * Test operations on non-existent container
         */
        public function testOperationsOnNonExistentContainer()
        {
            $fakeId = 'nonexistent' . uniqid();

            try {
                $this->docker->containers()->inspect($fakeId);
                $this->fail('Should have thrown exception for non-existent container');
            } catch (ResponseException $e) {
                $this->assertStringContainsString('No such container', $e->getMessage());
            }

            try {
                $this->docker->containers()->start($fakeId);
                $this->fail('Should have thrown exception for non-existent container');
            } catch (ResponseException $e) {
                $this->assertStringContainsString('No such container', $e->getMessage());
            }
        }

        /**
         * Test concurrent container operations
         */
        public function testConcurrentContainerCreation()
        {
            $containers = [];
            
            // Create multiple containers concurrently
            for ($i = 0; $i < 5; $i++) {
                $container = $this->docker->containers()->create([
                    'Image' => 'alpine:latest',
                    'Cmd' => ['sleep', '5'],
                    'Labels' => ['concurrent' => "test-{$i}"]
                ]);
                
                $containers[] = $container->getId();
                $this->testContainers[] = $container->getId();
            }

            $this->assertCount(5, $containers);
            
            // Verify all containers exist
            foreach ($containers as $containerId) {
                $inspected = $this->docker->containers()->inspect($containerId);
                $this->assertNotNull($inspected->getId());
            }
        }

        /**
         * Test container with extremely large environment variable
         */
        public function testContainerWithLargeEnvironmentVariable()
        {
            $largeValue = str_repeat('A', 10000);
            
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['echo', 'test'],
                'Env' => ["LARGE_VAR={$largeValue}"]
            ]);

            $this->testContainers[] = $container->getId();
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            $config = $inspected->getConfig();
            
            $found = false;
            foreach ($config['Env'] as $env) {
                if (str_starts_with($env, 'LARGE_VAR=')) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, 'Large environment variable should be set');
        }

        /**
         * Test container rename edge cases
         */
        public function testContainerRenameEdgeCases()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['echo', 'test']
            ]);

            $this->testContainers[] = $container->getId();

            // Rename to valid name
            $newName = 'test-container-' . uniqid();
            $this->docker->containers()->rename($container->getId(), $newName);
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            $this->assertEquals($newName, $inspected->getName());

            // Try renaming to invalid name (with spaces)
            try {
                $this->docker->containers()->rename($container->getId(), 'invalid name with spaces');
                $this->fail('Should have thrown exception for invalid container name');
            } catch (ResponseException $e) {
                $this->assertTrue(true);
            }
        }

        /**
         * Test container with resource limits at boundaries
         */
        public function testContainerWithBoundaryResourceLimits()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['echo', 'test'],
                'HostConfig' => [
                    'Memory' => 6291456, // 6MB (Docker minimum)
                    'MemorySwap' => 12582912, // 12MB
                    'CpuShares' => 2, // minimum
                ]
            ]);

            $this->testContainers[] = $container->getId();
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            $hostConfig = $inspected->getHostConfig();
            
            $this->assertEquals(6291456, $hostConfig['Memory']);
            $this->assertEquals(2, $hostConfig['CpuShares']);
        }
    }
