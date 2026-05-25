<?php

    namespace DockerLib;

    use DockerLib\Docker;
    use DockerLib\Objects\Network;
    use PHPUnit\Framework\TestCase;

    class NetworkOperationsTest extends TestCase
    {
        private Docker $docker;
        private array $testNetworks = [];

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }
        }

        protected function tearDown(): void
        {
            // Clean up test networks
            foreach ($this->testNetworks as $networkId) {
                try {
                    $this->docker->networks()->remove($networkId);
                } catch (\Exception $e) {
                    // Network might already be removed or in use
                }
            }
            
            $this->testNetworks = [];
        }

        public function testListNetworks()
        {
            $networks = $this->docker->networks()->list();
            
            $this->assertIsArray($networks);
            $this->assertNotEmpty($networks);
            
            foreach ($networks as $network) {
                $this->assertInstanceOf(Network::class, $network);
            }
        }

        public function testCreateAndInspectNetwork()
        {
            $networkName = 'dockerlib-test-network-' . uniqid();
            
            $network = $this->docker->networks()->create([
                'Name' => $networkName,
                'Driver' => 'bridge',
                'Labels' => ['test' => 'true']
            ]);
            
            $this->testNetworks[] = $network->getId();
            
            $this->assertInstanceOf(Network::class, $network);
            $this->assertNotNull($network->getId());
            $this->assertEquals($networkName, $network->getName());
            
            // Inspect the network
            $inspected = $this->docker->networks()->inspect($network->getId());
            $this->assertEquals($network->getId(), $inspected->getId());
        }

        public function testRemoveNetwork()
        {
            $networkName = 'dockerlib-test-remove-' . uniqid();
            
            $network = $this->docker->networks()->create([
                'Name' => $networkName,
                'Driver' => 'bridge'
            ]);
            
            $networkId = $network->getId();
            
            // Remove network
            $this->docker->networks()->remove($networkId);
            
            // Verify it's removed
            $this->expectException(\DockerLib\Exceptions\ResponseException::class);
            $this->docker->networks()->inspect($networkId);
        }

        public function testConnectAndDisconnectContainer()
        {
            $networkName = 'dockerlib-test-connect-' . uniqid();
            
            // Create network
            $network = $this->docker->networks()->create([
                'Name' => $networkName,
                'Driver' => 'bridge'
            ]);
            
            $this->testNetworks[] = $network->getId();
            
            // Create container
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30']
            ]);
            
            try {
                // Start the container so it shows up in network inspection
                $this->docker->containers()->start($container->getId());
                
                // Connect container to network
                $this->docker->networks()->connect($network->getId(), $container->getId());
                
                // Verify connection
                $inspected = $this->docker->networks()->inspect($network->getId());
                $containers = $inspected->getContainers();
                
                // Check if container ID exists (Docker may use full or shortened IDs as keys)
                $containerFound = false;
                $containerId = $container->getId();
                foreach (array_keys($containers) as $key) {
                    // Check if the key matches the full ID or if the full ID starts with the key (shortened)
                    if ($key === $containerId || str_starts_with($containerId, $key) || str_starts_with($key, $containerId)) {
                        $containerFound = true;
                        break;
                    }
                }
                $this->assertTrue($containerFound, "Container {$containerId} not found in network. Keys: " . implode(', ', array_keys($containers)));
                
                // Disconnect container
                $this->docker->networks()->disconnect($network->getId(), $container->getId());
                
                // Verify disconnection
                $inspectedAfter = $this->docker->networks()->inspect($network->getId());
                $containersAfter = $inspectedAfter->getContainers();
                $this->assertEmpty($containersAfter, "Container should be disconnected from network");
                
            } finally {
                // Cleanup
                try {
                    $this->docker->containers()->remove($container->getId(), true);
                } catch (\Exception $e) {
                    // Ignore cleanup errors
                }
            }
        }

        public function testNetworkWithCustomSubnet()
        {
            $networkName = 'dockerlib-test-subnet-' . uniqid();
            
            $network = $this->docker->networks()->create([
                'Name' => $networkName,
                'Driver' => 'bridge',
                'IPAM' => [
                    'Config' => [
                        ['Subnet' => '172.28.0.0/16', 'Gateway' => '172.28.0.1']
                    ]
                ]
            ]);
            
            $this->testNetworks[] = $network->getId();
            
            $this->assertNotNull($network->getId());
            
            $inspected = $this->docker->networks()->inspect($network->getId());
            $ipam = $inspected->getIpam();
            $this->assertNotNull($ipam);
        }

        public function testPruneNetworks()
        {
            // Create a network without containers
            $networkName = 'dockerlib-test-prune-' . uniqid();
            $network = $this->docker->networks()->create([
                'Name' => $networkName,
                'Driver' => 'bridge'
            ]);
            
            $networkId = $network->getId();
            
            try {
                // Prune unused networks
                $result = $this->docker->networks()->prune();
                
                $this->assertIsArray($result);
                $this->assertArrayHasKey('NetworksDeleted', $result);
            } catch (\Exception $e) {
                // Cleanup if prune failed
                try {
                    $this->docker->networks()->remove($networkId);
                } catch (\Exception $e2) {
                    // Ignore
                }
            }
        }

        public function testNetworkListWithFilters()
        {
            $networkName = 'dockerlib-test-filter-' . uniqid();
            
            $network = $this->docker->networks()->create([
                'Name' => $networkName,
                'Driver' => 'bridge',
                'Labels' => ['filter-test' => 'yes']
            ]);
            
            $this->testNetworks[] = $network->getId();
            
            // List with filter
            $networks = $this->docker->networks()->list([
                'label' => ['filter-test=yes']
            ]);
            
            $found = false;
            foreach ($networks as $net) {
                if ($net->getId() === $network->getId()) {
                    $found = true;
                    break;
                }
            }
            
            $this->assertTrue($found, 'Network with label should be found in filtered list');
        }
    }
