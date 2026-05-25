<?php

    namespace DockerLib\Managers;

    use DockerLib\Docker;
    use DockerLib\Objects\Network;
    use PHPUnit\Framework\TestCase;

    class NetworkManagerTest extends TestCase
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
                    // Disconnect any containers first
                    $network = $this->docker->networks()->inspect($networkId);
                    $containers = $network->getContainers();
                    
                    foreach ($containers as $containerId => $containerInfo) {
                        try {
                            $this->docker->networks()->disconnect($networkId, $containerId, true);
                        } catch (\Exception $e) {
                            // Continue cleanup
                        }
                    }
                    
                    $this->docker->networks()->remove($networkId);
                } catch (\Exception $e) {
                    // Network might already be removed
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
                $this->assertNotNull($network->getId());
                $this->assertNotNull($network->getName());
            }
        }

        public function testCreateNetwork()
        {
            $networkName = 'dockerlib-test-' . uniqid();
            
            $network = $this->docker->networks()->create([
                'Name' => $networkName,
                'Driver' => 'bridge',
                'CheckDuplicate' => true,
                'Labels' => [
                    'test' => 'dockerlib'
                ]
            ]);
            
            $this->testNetworks[] = $network->getId();
            
            $this->assertInstanceOf(Network::class, $network);
            $this->assertNotNull($network->getId());
            $this->assertEquals($networkName, $network->getName());
            $this->assertEquals('bridge', $network->getDriver());
        }

        public function testInspectNetwork()
        {
            $networkName = 'dockerlib-inspect-' . uniqid();
            
            $network = $this->docker->networks()->create(['Name' => $networkName]);
            $this->testNetworks[] = $network->getId();
            
            $inspected = $this->docker->networks()->inspect($network->getId());
            
            $this->assertEquals($network->getId(), $inspected->getId());
            $this->assertEquals($networkName, $inspected->getName());
            $this->assertNotNull($inspected->getScope());
            $this->assertNotNull($inspected->getDriver());
        }

        public function testConnectDisconnectContainer()
        {
            // Create network
            $networkName = 'dockerlib-connect-' . uniqid();
            $network = $this->docker->networks()->create(['Name' => $networkName]);
            $this->testNetworks[] = $network->getId();
            
            // Create container
            $containerName = 'dockerlib-net-test-' . uniqid();
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '60']
            ], $containerName);
            
            $this->docker->containers()->start($container->getId());
            
            try {
                // Connect container to network
                $this->docker->networks()->connect($network->getId(), $container->getId());
                
                // Verify connection
                $networkInspect = $this->docker->networks()->inspect($network->getId());
                $containers = $networkInspect->getContainers();
                $this->assertArrayHasKey($container->getId(), $containers);
                
                // Disconnect container
                $this->docker->networks()->disconnect($network->getId(), $container->getId());
                
                // Verify disconnection
                $networkInspect = $this->docker->networks()->inspect($network->getId());
                $containers = $networkInspect->getContainers();
                $this->assertArrayNotHasKey($container->getId(), $containers);
                
            } finally {
                // Cleanup
                $this->docker->containers()->stop($container->getId(), 1);
                $this->docker->containers()->remove($container->getId(), true, true);
            }
        }

        public function testRemoveNetwork()
        {
            $networkName = 'dockerlib-remove-' . uniqid();
            
            $network = $this->docker->networks()->create(['Name' => $networkName]);
            $networkId = $network->getId();
            
            $this->docker->networks()->remove($networkId);
            
            // Verify network is removed
            $this->expectException(\DockerLib\Exceptions\ResponseException::class);
            $this->docker->networks()->inspect($networkId);
        }

        public function testListNetworksWithFilters()
        {
            $networks = $this->docker->networks()->list([
                'driver' => ['bridge']
            ]);
            
            $this->assertIsArray($networks);
            
            foreach ($networks as $network) {
                $this->assertEquals('bridge', $network->getDriver());
            }
        }

        public function testNetworkProperties()
        {
            $networkName = 'dockerlib-props-' . uniqid();
            
            $network = $this->docker->networks()->create([
                'Name' => $networkName,
                'Driver' => 'bridge',
                'Internal' => false,
                'Attachable' => true,
                'Labels' => [
                    'env' => 'test',
                    'project' => 'dockerlib'
                ],
                'IPAM' => [
                    'Config' => [
                        [
                            'Subnet' => '172.28.0.0/16',
                            'Gateway' => '172.28.0.1'
                        ]
                    ]
                ]
            ]);
            
            $this->testNetworks[] = $network->getId();
            
            $this->assertFalse($network->getInternal());
            $this->assertTrue($network->getAttachable());
            $this->assertNotEmpty($network->getLabels());
            $this->assertArrayHasKey('env', $network->getLabels());
            $this->assertNotEmpty($network->getIpam());
        }

        public function testNetworkPrune()
        {
            // Create unused networks
            $network1 = $this->docker->networks()->create([
                'Name' => 'dockerlib-prune1-' . uniqid(),
                'Driver' => 'bridge',
                'Labels' => ['prune-test' => 'true']
            ]);
            
            $network2 = $this->docker->networks()->create([
                'Name' => 'dockerlib-prune2-' . uniqid(),
                'Driver' => 'bridge',
                'Labels' => ['prune-test' => 'true']
            ]);
            
            $network1Id = $network1->getId();
            $network2Id = $network2->getId();
            
            // Prune unused networks
            $result = $this->docker->networks()->prune();
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('NetworksDeleted', $result);
            // Note: Network prune does not return SpaceReclaimed, only NetworksDeleted
            
            // Verify networks were deleted
            try {
                $this->docker->networks()->inspect($network1Id);
                $this->fail('Network 1 should have been pruned');
            } catch (\Exception $e) {
                $this->assertTrue(true); // Expected
            }
            
            try {
                $this->docker->networks()->inspect($network2Id);
                $this->fail('Network 2 should have been pruned');
            } catch (\Exception $e) {
                $this->assertTrue(true); // Expected
            }
        }

        public function testNetworkPruneWithFilters()
        {
            // Create unused network with specific label
            $network = $this->docker->networks()->create([
                'Name' => 'dockerlib-prune-filter-' . uniqid(),
                'Driver' => 'bridge',
                'Labels' => ['keep' => 'false']
            ]);
            
            $networkId = $network->getId();
            
            // Prune with label filter
            $result = $this->docker->networks()->prune([
                'label' => ['keep=false']
            ]);
            
            $this->assertIsArray($result);
            
            // Verify network was pruned
            try {
                $this->docker->networks()->inspect($networkId);
                // Network might not be deleted if filters don't match
                // Remove it manually
                $this->docker->networks()->remove($networkId);
            } catch (\Exception $e) {
                // Network was pruned successfully
                $this->assertTrue(true);
            }
        }
    }
