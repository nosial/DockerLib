<?php

    namespace DockerLib\Objects;

    use DockerLib\Objects\Network;
    use DockerLib\Objects\Network\IPAM;
    use PHPUnit\Framework\TestCase;

    class NetworkObjectTest extends TestCase
    {
        public function testNetworkFromArray()
        {
            $data = [
                'Id' => 'test-network-id',
                'Name' => 'test-network',
                'Created' => '2024-01-01T00:00:00Z',
                'Scope' => 'local',
                'Driver' => 'bridge',
                'EnableIPv6' => false,
                'IPAM' => [
                    'Driver' => 'default',
                    'Config' => [
                        ['Subnet' => '172.17.0.0/16']
                    ],
                    'Options' => []
                ],
                'Internal' => false,
                'Attachable' => false,
                'Ingress' => false,
                'Containers' => [],
                'Options' => ['com.docker.network.bridge.name' => 'docker0'],
                'Labels' => ['test' => 'label'],
                'ConfigFrom' => null,
                'ConfigOnly' => false
            ];

            $network = new Network($data);

            $this->assertInstanceOf(Network::class, $network);
            $this->assertEquals('test-network-id', $network->getId());
            $this->assertEquals('test-network', $network->getName());
            $this->assertEquals('bridge', $network->getDriver());
            $this->assertEquals('local', $network->getScope());
            $this->assertFalse($network->getEnableIPv6());
            $this->assertFalse($network->getInternal());
            $this->assertFalse($network->getAttachable());
            $this->assertFalse($network->getIngress());
            $this->assertInstanceOf(\DockerLib\Objects\Network\IPAM::class, $network->getIpam());
        }

        public function testNetworkGettersBasic()
        {
            $data = [
                'Id' => 'test-id',
                'Name' => 'test-name',
                'Created' => '2024-01-01T00:00:00Z',
                'Scope' => 'local',
                'Driver' => 'bridge',
                'EnableIPv6' => false,
                'IPAM' => [
                    'Driver' => 'default',
                    'Config' => [],
                    'Options' => []
                ],
                'Internal' => false,
                'Attachable' => false,
                'Ingress' => false,
                'Containers' => [],
                'Options' => [],
                'Labels' => [],
                'ConfigFrom' => null,
                'ConfigOnly' => false
            ];

            $network = new Network($data);

            $this->assertEquals('test-id', $network->getId());
            $this->assertEquals('test-name', $network->getName());
            $this->assertEquals('bridge', $network->getDriver());
        }

        public function testNetworkGetters()
        {
            $data = [
                'Id' => 'id123',
                'Name' => 'mynetwork',
                'Created' => '2024-01-01T00:00:00Z',
                'Scope' => 'swarm',
                'Driver' => 'overlay',
                'EnableIPv6' => true,
                'IPAM' => [
                    'Driver' => 'default',
                    'Config' => [],
                    'Options' => []
                ],
                'Internal' => true,
                'Attachable' => true,
                'Ingress' => true,
                'Containers' => ['container1' => ['Name' => 'test']],
                'Options' => ['opt1' => 'val1'],
                'Labels' => ['label1' => 'value1'],
                'ConfigFrom' => ['Network' => 'parent'],
                'ConfigOnly' => true
            ];

            $network = new Network($data);

            $this->assertEquals('id123', $network->getId());
            $this->assertEquals('mynetwork', $network->getName());
            $this->assertEquals('2024-01-01T00:00:00Z', $network->getCreated());
            $this->assertEquals('swarm', $network->getScope());
            $this->assertEquals('overlay', $network->getDriver());
            $this->assertTrue($network->getEnableIPv6());
            $this->assertTrue($network->getInternal());
            $this->assertTrue($network->getAttachable());
            $this->assertTrue($network->getIngress());
            $this->assertTrue($network->getConfigOnly());
            $this->assertIsArray($network->getContainers());
            $this->assertIsArray($network->getOptions());
            $this->assertIsArray($network->getLabels());
            $this->assertIsArray($network->getConfigFrom());
        }
    }
