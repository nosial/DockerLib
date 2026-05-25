<?php

    namespace DockerLib\Objects;

    use DockerLib\Objects\Task;
    use PHPUnit\Framework\TestCase;

    class TaskObjectTest extends TestCase
    {
        public function testTaskCreation()
        {
            $data = [
                'ID' => 'task-123',
                'Version' => ['Index' => 100],
                'CreatedAt' => '2024-01-01T10:00:00.000000000Z',
                'UpdatedAt' => '2024-01-01T10:05:00.000000000Z',
                'Name' => 'my-service.1.task123',
                'Labels' => ['com.docker.swarm.service.name' => 'my-service'],
                'Spec' => [
                    'ContainerSpec' => [
                        'Image' => 'nginx:latest',
                        'Labels' => ['app' => 'web']
                    ]
                ],
                'ServiceID' => 'service-456',
                'Slot' => 1,
                'NodeID' => 'node-789',
                'Status' => [
                    'Timestamp' => '2024-01-01T10:05:00.000000000Z',
                    'State' => 'running',
                    'Message' => 'started',
                    'ContainerStatus' => [
                        'ContainerID' => 'container-abc',
                        'PID' => 1234
                    ]
                ],
                'DesiredState' => 'running'
            ];

            $task = Task::fromArray($data);

            $this->assertEquals('task-123', $task->getId());
            $this->assertEquals('my-service.1.task123', $task->getName());
            $this->assertEquals('service-456', $task->getServiceId());
            $this->assertEquals(1, $task->getSlot());
            $this->assertEquals('node-789', $task->getNodeId());
            $this->assertEquals('running', $task->getDesiredState());
            $this->assertIsArray($task->getStatus());
            $this->assertEquals('running', $task->getStatus()['State']);
        }

        public function testTaskWithMinimalData()
        {
            $data = [
                'ID' => 'task-simple',
                'Name' => 'simple-task',
                'DesiredState' => 'shutdown'
            ];

            $task = Task::fromArray($data);

            $this->assertEquals('task-simple', $task->getId());
            $this->assertEquals('simple-task', $task->getName());
            $this->assertEquals('shutdown', $task->getDesiredState());
        }

        public function testTaskToArray()
        {
            $data = [
                'ID' => 'task-999',
                'Name' => 'test-task',
                'ServiceID' => 'svc-1',
                'DesiredState' => 'running',
                'Status' => ['State' => 'preparing']
            ];

            $task = Task::fromArray($data);
            $array = $task->toArray();

            $this->assertIsArray($array);
            $this->assertEquals('task-999', $array['ID']);
            $this->assertEquals('test-task', $array['Name']);
            $this->assertEquals('running', $array['DesiredState']);
        }
    }
