<?php

    namespace DockerLib\Objects;

    use DockerLib\Objects\Volume;
    use PHPUnit\Framework\TestCase;

    class VolumeObjectTest extends TestCase
    {
        public function testVolumeCreation()
        {
            $data = [
                'Name' => 'test-volume',
                'Driver' => 'local',
                'Mountpoint' => '/var/lib/docker/volumes/test-volume/_data',
                'CreatedAt' => '2024-01-01T00:00:00Z',
                'Status' => ['test' => 'status'],
                'Labels' => ['app' => 'test'],
                'Scope' => 'local',
                'Options' => ['type' => 'tmpfs'],
                'UsageData' => [
                    'Size' => 12345,
                    'RefCount' => 1
                ]
            ];

            $volume = new Volume($data);

            $this->assertInstanceOf(Volume::class, $volume);
            $this->assertEquals('test-volume', $volume->getName());
            $this->assertEquals('local', $volume->getDriver());
            $this->assertEquals('/var/lib/docker/volumes/test-volume/_data', $volume->getMountpoint());
            $this->assertEquals('2024-01-01T00:00:00Z', $volume->getCreatedAt());
            $this->assertEquals('local', $volume->getScope());
        }

        public function testVolumeGetters()
        {
            $data = [
                'Name' => 'vol1',
                'Driver' => 'local',
                'Mountpoint' => '/data',
                'CreatedAt' => '2024-01-01T00:00:00Z',
                'Status' => null,
                'Labels' => ['test' => 'label'],
                'Scope' => 'local',
                'Options' => [],
                'UsageData' => null
            ];

            $volume = new Volume($data);

            $this->assertEquals('vol1', $volume->getName());
            $this->assertEquals('local', $volume->getDriver());
            $this->assertEquals('/data', $volume->getMountpoint());
        }

        public function testVolumeAdvancedGetters()
        {
            $data = [
                'Name' => 'myvolume',
                'Driver' => 'nfs',
                'Mountpoint' => '/mnt/nfs/myvolume',
                'CreatedAt' => '2024-06-15T12:30:00Z',
                'Status' => ['health' => 'ok'],
                'Labels' => ['env' => 'production', 'app' => 'web'],
                'Scope' => 'global',
                'Options' => ['device' => '/dev/sdb1', 'type' => 'ext4'],
                'UsageData' => [
                    'Size' => 1073741824,
                    'RefCount' => 5
                ]
            ];

            $volume = new Volume($data);

            $this->assertEquals('myvolume', $volume->getName());
            $this->assertEquals('nfs', $volume->getDriver());
            $this->assertEquals('/mnt/nfs/myvolume', $volume->getMountpoint());
            $this->assertEquals('2024-06-15T12:30:00Z', $volume->getCreatedAt());
            $this->assertIsArray($volume->getStatus());
            $this->assertEquals('ok', $volume->getStatus()['health']);
            $this->assertIsArray($volume->getLabels());
            $this->assertEquals('production', $volume->getLabels()['env']);
            $this->assertEquals('global', $volume->getScope());
            $this->assertIsArray($volume->getOptions());
            $this->assertIsArray($volume->getUsageData());
            $this->assertEquals(1073741824, $volume->getUsageData()['Size']);
        }

        public function testVolumeWithNullFields()
        {
            $data = [
                'Name' => 'simple-volume',
                'Driver' => 'local',
                'Mountpoint' => '/var/lib/docker/volumes/simple-volume/_data',
                'CreatedAt' => null,
                'Status' => null,
                'Labels' => [],
                'Scope' => 'local',
                'Options' => [],
                'UsageData' => null
            ];

            $volume = new Volume($data);

            $this->assertNull($volume->getCreatedAt());
            $this->assertNull($volume->getStatus());
            $this->assertNull($volume->getUsageData());
            $this->assertIsArray($volume->getLabels());
            $this->assertEmpty($volume->getLabels());
        }
    }
