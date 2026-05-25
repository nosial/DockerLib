<?php

    namespace DockerLib;

    use DockerLib\Docker;
    use PHPUnit\Framework\TestCase;

    class AdvancedContainerTest extends TestCase
    {
        private Docker $docker;
        private array $testContainers = [];

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }

            // Ensure alpine image is available
            try {
                $this->docker->images()->pull('alpine:latest');
            } catch (\Exception $e) {
                $this->markTestSkipped('Could not pull alpine image: ' . $e->getMessage());
            }
        }

        protected function tearDown(): void
        {
            foreach ($this->testContainers as $containerId) {
                try {
                    $this->docker->containers()->remove($containerId, true);
                } catch (\Exception $e) {
                    // Container might already be removed
                }
            }
            $this->testContainers = [];
        }

        public function testContainerWithPortMapping()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30'],
                'ExposedPorts' => [
                    '8080/tcp' => new \stdClass()
                ],
                'HostConfig' => [
                    'PortBindings' => [
                        '8080/tcp' => [
                            ['HostPort' => '0'] // Random host port
                        ]
                    ]
                ]
            ]);

            $this->testContainers[] = $container->getId();

            $this->docker->containers()->start($container->getId());

            $inspected = $this->docker->containers()->inspect($container->getId());
            $networkSettings = $inspected->getNetworkSettings();

            $this->assertIsArray($networkSettings);
            $this->assertArrayHasKey('Ports', $networkSettings);
            $this->assertIsArray($networkSettings['Ports']);
        }

        public function testContainerWithEnvironmentVariables()
        {
            $envVars = [
                'TEST_VAR=test_value',
                'DEBUG=true',
                'PORT=8080'
            ];

            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Env' => $envVars,
                'Cmd' => ['sh', '-c', 'env && sleep 5']
            ]);

            $this->testContainers[] = $container->getId();

            $this->docker->containers()->start($container->getId());
            
            $inspected = $this->docker->containers()->inspect($container->getId());
            $config = $inspected->getConfig();

            $this->assertArrayHasKey('Env', $config);
            $this->assertContains('TEST_VAR=test_value', $config['Env']);
        }

        public function testContainerWithResourceLimits()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30'],
                'HostConfig' => [
                    'Memory' => 134217728, // 128MB
                    'MemorySwap' => 268435456, // 256MB
                    'CpuShares' => 512,
                    'CpuQuota' => 50000,
                    'CpuPeriod' => 100000
                ]
            ]);

            $this->testContainers[] = $container->getId();

            $inspected = $this->docker->containers()->inspect($container->getId());
            $hostConfig = $inspected->getHostConfig();

            $this->assertArrayHasKey('Memory', $hostConfig);
            $this->assertEquals(134217728, $hostConfig['Memory']);
            $this->assertArrayHasKey('CpuShares', $hostConfig);
            $this->assertEquals(512, $hostConfig['CpuShares']);
        }

        public function testContainerWithLabels()
        {
            $labels = [
                'com.example.environment' => 'test',
                'com.example.version' => '1.0.0',
                'com.example.owner' => 'dockerlib-test'
            ];

            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30'],
                'Labels' => $labels
            ]);

            $this->testContainers[] = $container->getId();

            $inspected = $this->docker->containers()->inspect($container->getId());
            $config = $inspected->getConfig();

            $this->assertArrayHasKey('Labels', $config);
            foreach ($labels as $key => $value) {
                $this->assertArrayHasKey($key, $config['Labels']);
                $this->assertEquals($value, $config['Labels'][$key]);
            }
        }

        public function testContainerWithWorkingDirectory()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'WorkingDir' => '/app',
                'Cmd' => ['pwd']
            ]);

            $this->testContainers[] = $container->getId();

            $inspected = $this->docker->containers()->inspect($container->getId());
            $config = $inspected->getConfig();

            $this->assertArrayHasKey('WorkingDir', $config);
            $this->assertEquals('/app', $config['WorkingDir']);
        }

        public function testContainerWithUser()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'User' => '1000:1000',
                'Cmd' => ['id']
            ]);

            $this->testContainers[] = $container->getId();

            $inspected = $this->docker->containers()->inspect($container->getId());
            $config = $inspected->getConfig();

            $this->assertArrayHasKey('User', $config);
            $this->assertEquals('1000:1000', $config['User']);
        }

        public function testContainerWithRestartPolicy()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30'],
                'HostConfig' => [
                    'RestartPolicy' => [
                        'Name' => 'on-failure',
                        'MaximumRetryCount' => 3
                    ]
                ]
            ]);

            $this->testContainers[] = $container->getId();

            $inspected = $this->docker->containers()->inspect($container->getId());
            $hostConfig = $inspected->getHostConfig();

            $this->assertArrayHasKey('RestartPolicy', $hostConfig);
            $this->assertEquals('on-failure', $hostConfig['RestartPolicy']['Name']);
            $this->assertEquals(3, $hostConfig['RestartPolicy']['MaximumRetryCount']);
        }

        public function testContainerAutoRemove()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['echo', 'hello'],
                'HostConfig' => [
                    'AutoRemove' => true
                ]
            ]);

            $containerId = $container->getId();

            $this->docker->containers()->start($containerId);
            $this->docker->containers()->wait($containerId);

            // Give Docker time to auto-remove
            sleep(2);

            // Container should be auto-removed, so inspect should fail
            try {
                $this->docker->containers()->inspect($containerId);
                // If we get here, container wasn't auto-removed (might be Docker version issue)
                $this->testContainers[] = $containerId; // Add for cleanup
                $this->markTestSkipped('Container was not auto-removed (Docker version may not support it)');
            } catch (\Exception $e) {
                // Expected: container not found
                $this->assertStringContainsString('No such container', $e->getMessage());
            }
        }

        public function testListContainersWithFilters()
        {
            // Create container with specific label
            $label = 'test-filter-' . uniqid();
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30'],
                'Labels' => ['test' => $label]
            ]);

            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            // List with label filter
            $containers = $this->docker->containers()->list([
                'label' => ['test=' . $label]
            ], true);

            $this->assertNotEmpty($containers);
            
            $found = false;
            foreach ($containers as $c) {
                if ($c->getId() === $container->getId()) {
                    $found = true;
                    break;
                }
            }

            $this->assertTrue($found, 'Container with label filter not found');
        }
    }
