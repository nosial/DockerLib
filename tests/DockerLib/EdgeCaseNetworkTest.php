<?php

    namespace DockerLib;

    use DockerLib\Exceptions\ResponseException;

    /**
     * Edge case and boundary testing for Docker network operations
     */
    class EdgeCaseNetworkTest extends BaseDockerTest
    {
        /**
         * Test creating network with minimal config
         */
        public function testCreateNetworkMinimalConfig()
        {
            $networkName = $this->generateTestId();
            
            $network = $this->docker->networks()->create([
                'Name' => $networkName
            ]);
            
            $this->testNetworks[] = $network->getId();
            $this->assertNotNull($network->getId());
        }

        /**
         * Test creating network with all options
         */
        public function testCreateNetworkWithAllOptions()
        {
            $networkName = $this->generateTestId();
            
            $network = $this->docker->networks()->create([
                'Name' => $networkName,
                'Driver' => 'bridge',
                'Internal' => false,
                'Attachable' => true,
                'EnableIPv6' => false,
                'Labels' => [
                    'test' => 'edge-case',
                    'type' => 'test-network'
                ],
                'IPAM' => [
                    'Driver' => 'default',
                    'Config' => []
                ]
            ]);
            
            $this->testNetworks[] = $network->getId();
            $this->assertNotNull($network->getId());
            
            $inspected = $this->docker->networks()->inspect($network->getId());
            $this->assertEquals($networkName, $inspected->getName());
        }

        /**
         * Test creating network with duplicate name
         */
        public function testCreateNetworkDuplicateName()
        {
            $networkName = $this->generateTestId();
            
            $network1 = $this->docker->networks()->create(['Name' => $networkName]);
            $this->testNetworks[] = $network1->getId();
            
            try {
                $network2 = $this->docker->networks()->create(['Name' => $networkName]);
                $this->fail('Should have thrown exception for duplicate network name');
            } catch (ResponseException $e) {
                $this->assertStringContainsString('already exists', $e->getMessage());
            }
        }

        /**
         * Test inspecting non-existent network
         */
        public function testInspectNonExistentNetwork()
        {
            try {
                $this->docker->networks()->inspect('nonexistent-network-' . uniqid());
                $this->fail('Should have thrown exception for non-existent network');
            } catch (ResponseException $e) {
                $this->assertTrue(true);
            }
        }

        /**
         * Test removing non-existent network
         */
        public function testRemoveNonExistentNetwork()
        {
            try {
                $this->docker->networks()->remove('nonexistent-network-' . uniqid());
                $this->fail('Should have thrown exception for non-existent network');
            } catch (ResponseException $e) {
                $this->assertTrue(true);
            }
        }

        /**
         * Test removing network in use
         */
        public function testRemoveNetworkInUse()
        {
            $this->ensureImage('alpine:latest');
            
            $networkName = $this->generateTestId();
            $network = $this->docker->networks()->create(['Name' => $networkName]);
            $this->testNetworks[] = $network->getId();
            
            // Create container using the network
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30'],
                'NetworkingConfig' => [
                    'EndpointsConfig' => [
                        $networkName => []
                    ]
                ]
            ]);
            
            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());
            
            // Try to remove network while in use
            try {
                $this->docker->networks()->remove($network->getId());
                $this->fail('Should have thrown exception for network in use');
            } catch (ResponseException $e) {
                $this->assertTrue(true);
            }
        }

        /**
         * Test listing networks with filters
         */
        public function testListNetworksWithFilters()
        {
            $networkName = $this->generateTestId();
            $network = $this->docker->networks()->create([
                'Name' => $networkName,
                'Labels' => ['test-filter' => 'true']
            ]);
            $this->testNetworks[] = $network->getId();
            
            $networks = $this->docker->networks()->list([
                'label' => ['test-filter=true']
            ]);
            
            $this->assertIsArray($networks);
            
            $found = false;
            foreach ($networks as $net) {
                if ($net->getId() === $network->getId()) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, 'Network should be found with label filter');
        }

        /**
         * Test network with special characters in name
         */
        public function testNetworkWithSpecialCharacters()
        {
            $networkName = 'test-network_' . time();
            
            $network = $this->docker->networks()->create(['Name' => $networkName]);
            $this->testNetworks[] = $network->getId();
            
            $inspected = $this->docker->networks()->inspect($network->getId());
            $this->assertEquals($networkName, $inspected->getName());
        }

        /**
         * Test connecting container to network
         */
        public function testConnectContainerToNetwork()
        {
            $this->ensureImage('alpine:latest');
            
            $networkName = $this->generateTestId();
            $network = $this->docker->networks()->create(['Name' => $networkName]);
            $this->testNetworks[] = $network->getId();
            
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30']
            ]);
            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());
            
            // Connect container to network
            $this->docker->networks()->connect($network->getId(), $container->getId());
            
            // Verify connection
            $inspected = $this->docker->networks()->inspect($network->getId());
            $containers = $inspected->getContainers();
            $this->assertArrayHasKey($container->getId(), $containers);
        }

        /**
         * Test disconnecting container from network
         */
        public function testDisconnectContainerFromNetwork()
        {
            $this->ensureImage('alpine:latest');
            
            $networkName = $this->generateTestId();
            $network = $this->docker->networks()->create(['Name' => $networkName]);
            $this->testNetworks[] = $network->getId();
            
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30']
            ]);
            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());
            
            // Connect and then disconnect
            $this->docker->networks()->connect($network->getId(), $container->getId());
            $this->docker->networks()->disconnect($network->getId(), $container->getId());
            
            // Verify disconnection
            $inspected = $this->docker->networks()->inspect($network->getId());
            $containers = $inspected->getContainers();
            $this->assertArrayNotHasKey($container->getId(), $containers);
        }

        /**
         * Test pruning networks
         */
        public function testPruneNetworks()
        {
            $result = $this->docker->networks()->prune();
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('NetworksDeleted', $result);
        }

        /**
         * Test concurrent network creation
         */
        public function testConcurrentNetworkCreation()
        {
            $networks = [];
            
            for ($i = 0; $i < 3; $i++) {
                $networkName = $this->generateTestId() . "-{$i}";
                $network = $this->docker->networks()->create(['Name' => $networkName]);
                $networks[] = $network->getId();
                $this->testNetworks[] = $network->getId();
            }
            
            $this->assertCount(3, $networks);
            
            // Verify all networks exist
            foreach ($networks as $networkId) {
                $network = $this->docker->networks()->inspect($networkId);
                $this->assertNotNull($network->getId());
            }
        }

        /**
         * Test default bridge network cannot be removed
         */
        public function testCannotRemoveDefaultNetworks()
        {
            try {
                $this->docker->networks()->remove('bridge');
                $this->fail('Should not be able to remove default bridge network');
            } catch (ResponseException $e) {
                $this->assertTrue(true);
            }
        }
    }
