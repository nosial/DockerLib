<?php

    namespace DockerLib\Objects;

    use DockerLib\Objects\Container;
    use PHPUnit\Framework\TestCase;

    class ContainerObjectTest extends TestCase
    {
        public function testContainerObjectCreation()
        {
            $data = [
                'Id' => 'abc123',
                'Names' => ['/test-container'],
                'Image' => 'alpine:latest',
                'State' => 'running',
                'Status' => 'Up 2 hours',
                'Created' => 1234567890,
                'Labels' => ['env' => 'test'],
                'Ports' => [
                    ['PublicPort' => 8080, 'PrivatePort' => 80, 'Type' => 'tcp']
                ]
            ];
            
            $container = new Container($data);
            
            $this->assertEquals('abc123', $container->getId());
            $this->assertEquals('test-container', $container->getName());
            $this->assertEquals('alpine:latest', $container->getImage());
            $this->assertEquals('running', $container->getState());
            $this->assertEquals('Up 2 hours', $container->getStatus());
            $this->assertEquals(1234567890, $container->getCreated());
            $this->assertIsArray($container->getLabels());
            $this->assertIsArray($container->getPorts());
            $this->assertTrue($container->isRunning());
        }

        public function testContainerNotRunning()
        {
            $data = [
                'Id' => 'def456',
                'State' => 'exited'
            ];
            
            $container = new Container($data);
            
            $this->assertFalse($container->isRunning());
        }

        public function testContainerRawData()
        {
            $data = ['Id' => 'test123', 'Names' => ['/test'], 'Image' => 'alpine'];
            $container = new Container($data);
            
            $rawData = $container->getRawData();
            $this->assertIsArray($rawData);
            $this->assertEquals('test123', $rawData['Id']);
        }

        public function testContainerNullValues()
        {
            $container = new Container([]);
            
            $this->assertNull($container->getId());
            $this->assertNull($container->getName());
            $this->assertEmpty($container->getLabels());
            $this->assertEmpty($container->getPorts());
        }
    }
