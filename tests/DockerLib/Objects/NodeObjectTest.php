<?php

    namespace DockerLib\Objects;

    use DockerLib\Objects\Node;
    use PHPUnit\Framework\TestCase;

    class NodeObjectTest extends TestCase
    {
        public function testNodeCreation()
        {
            $data = [
                'ID' => 'node-123abc',
                'Version' => ['Index' => 50],
                'CreatedAt' => '2024-01-01T00:00:00.000000000Z',
                'UpdatedAt' => '2024-01-01T00:00:00.000000000Z',
                'Spec' => [
                    'Name' => 'worker-node-1',
                    'Labels' => ['region' => 'us-west'],
                    'Role' => 'worker',
                    'Availability' => 'active'
                ],
                'Description' => [
                    'Hostname' => 'worker1.example.com',
                    'Platform' => [
                        'Architecture' => 'x86_64',
                        'OS' => 'linux'
                    ],
                    'Resources' => [
                        'NanoCPUs' => 4000000000,
                        'MemoryBytes' => 8589934592
                    ],
                    'Engine' => [
                        'EngineVersion' => '20.10.0'
                    ]
                ],
                'Status' => [
                    'State' => 'ready',
                    'Addr' => '192.168.1.100'
                ],
                'ManagerStatus' => null
            ];

            $node = Node::fromArray($data);

            $this->assertEquals('node-123abc', $node->getId());
            $this->assertIsArray($node->getVersion());
            $this->assertEquals('2024-01-01T00:00:00.000000000Z', $node->getCreatedAt());
            $this->assertIsArray($node->getSpec());
            $this->assertIsArray($node->getDescription());
            $this->assertIsArray($node->getStatus());
            $this->assertEquals('ready', $node->getStatus()['State']);
        }

        public function testManagerNode()
        {
            $data = [
                'ID' => 'node-manager',
                'Spec' => [
                    'Name' => 'manager-1',
                    'Role' => 'manager',
                    'Availability' => 'active'
                ],
                'ManagerStatus' => [
                    'Leader' => true,
                    'Reachability' => 'reachable',
                    'Addr' => '192.168.1.10:2377'
                ],
                'Status' => ['State' => 'ready']
            ];

            $node = Node::fromArray($data);

            $this->assertEquals('node-manager', $node->getId());
            $this->assertIsArray($node->getManagerStatus());
            $this->assertTrue($node->getManagerStatus()['Leader']);
        }

        public function testNodeToArray()
        {
            $data = [
                'ID' => 'node-789',
                'Spec' => ['Name' => 'test-node'],
                'Status' => ['State' => 'down']
            ];

            $node = Node::fromArray($data);
            $array = $node->toArray();

            $this->assertIsArray($array);
            $this->assertEquals('node-789', $array['ID']);
            $this->assertEquals('down', $array['Status']['State']);
        }
    }
