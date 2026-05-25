<?php

    namespace DockerLib\Objects;

    use DockerLib\Objects\Service;
    use PHPUnit\Framework\TestCase;

    class ServiceObjectTest extends TestCase
    {
        public function testServiceFromArray()
        {
            $data = [
                'ID' => 'service-123',
                'Version' => ['Index' => 100],
                'CreatedAt' => '2024-01-01T00:00:00Z',
                'UpdatedAt' => '2024-01-02T00:00:00Z',
                'Spec' => [
                    'Name' => 'web-service',
                    'Labels' => ['app' => 'web'],
                    'TaskTemplate' => [
                        'ContainerSpec' => [
                            'Image' => 'nginx:latest'
                        ]
                    ],
                    'Mode' => ['Replicated' => ['Replicas' => 3]],
                    'UpdateConfig' => ['Parallelism' => 1],
                    'RollbackConfig' => null,
                    'Networks' => [],
                    'EndpointSpec' => ['Ports' => []]
                ],
                'PreviousSpec' => null,
                'Endpoint' => [
                    'Spec' => [],
                    'Ports' => [],
                    'VirtualIPs' => []
                ],
                'UpdateStatus' => null
            ];

            $service = new Service($data);

            $this->assertInstanceOf(Service::class, $service);
            $this->assertEquals('service-123', $service->getId());
            $this->assertEquals('2024-01-01T00:00:00Z', $service->getCreatedAt());
            $this->assertEquals('2024-01-02T00:00:00Z', $service->getUpdatedAt());
            $this->assertIsArray($service->getSpec());
            $this->assertEquals('web-service', $service->getSpec()['Name']);
        }

        public function testServiceToArray()
        {
            $data = [
                'ID' => 'svc-456',
                'Version' => ['Index' => 50],
                'CreatedAt' => '2024-01-01T00:00:00Z',
                'UpdatedAt' => '2024-01-01T00:00:00Z',
                'Spec' => [
                    'Name' => 'api-service',
                    'Labels' => [],
                    'TaskTemplate' => ['ContainerSpec' => ['Image' => 'api:v1']],
                    'Mode' => ['Global' => []],
                    'UpdateConfig' => null,
                    'RollbackConfig' => null,
                    'Networks' => [],
                    'EndpointSpec' => null
                ],
                'PreviousSpec' => null,
                'Endpoint' => ['Spec' => [], 'Ports' => [], 'VirtualIPs' => []],
                'UpdateStatus' => null
            ];

            $service = new Service($data);

            $this->assertEquals('svc-456', $service->getId());
            $this->assertIsArray($service->getSpec());
        }

        public function testServiceGetters()
        {
            $data = [
                'ID' => 'my-service-id',
                'Version' => ['Index' => 200],
                'CreatedAt' => '2024-06-15T10:00:00Z',
                'UpdatedAt' => '2024-06-15T11:00:00Z',
                'Spec' => [
                    'Name' => 'database-service',
                    'Labels' => ['tier' => 'backend'],
                    'TaskTemplate' => [
                        'ContainerSpec' => ['Image' => 'postgres:14']
                    ],
                    'Mode' => ['Replicated' => ['Replicas' => 1]],
                    'UpdateConfig' => ['Parallelism' => 1, 'Delay' => 10000000000],
                    'RollbackConfig' => ['Parallelism' => 1],
                    'Networks' => [['Target' => 'network1']],
                    'EndpointSpec' => ['Ports' => [['TargetPort' => 5432]]]
                ],
                'PreviousSpec' => null,
                'Endpoint' => [
                    'Spec' => ['Ports' => []],
                    'Ports' => [['TargetPort' => 5432]],
                    'VirtualIPs' => [['NetworkID' => 'net1', 'Addr' => '10.0.0.5/24']]
                ],
                'UpdateStatus' => ['State' => 'completed']
            ];

            $service = new Service($data);

            $this->assertEquals('my-service-id', $service->getId());
            $this->assertIsArray($service->getVersion());
            $this->assertEquals(200, $service->getVersion()['Index']);
            $this->assertEquals('2024-06-15T10:00:00Z', $service->getCreatedAt());
            $this->assertEquals('2024-06-15T11:00:00Z', $service->getUpdatedAt());
            $this->assertIsArray($service->getSpec());
            $this->assertIsArray($service->getEndpoint());
            $this->assertIsArray($service->getUpdateStatus());
        }
    }
