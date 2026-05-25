<?php

    namespace DockerLib\Managers;

    use DockerLib\Docker;
    use DockerLib\Objects\Volume;
    use PHPUnit\Framework\TestCase;

    class VolumeManagerTest extends TestCase
    {
        private Docker $docker;
        private array $testVolumes = [];

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }
        }

        protected function tearDown(): void
        {
            // Clean up test volumes
            foreach ($this->testVolumes as $volumeName) {
                try {
                    $this->docker->volumes()->remove($volumeName, true);
                } catch (\Exception $e) {
                    // Volume might already be removed or in use
                }
            }
            
            $this->testVolumes = [];
        }

        public function testListVolumes()
        {
            $result = $this->docker->volumes()->list();
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('Volumes', $result);
            
            foreach ($result['Volumes'] as $volume) {
                $this->assertInstanceOf(Volume::class, $volume);
                $this->assertNotNull($volume->getName());
                $this->assertNotNull($volume->getDriver());
            }
        }

        public function testCreateVolume()
        {
            $volumeName = 'dockerlib-test-' . uniqid();
            
            $volume = $this->docker->volumes()->create($volumeName, [
                'Driver' => 'local',
                'Labels' => [
                    'test' => 'dockerlib',
                    'cleanup' => 'true'
                ]
            ]);
            
            $this->testVolumes[] = $volumeName;
            
            $this->assertInstanceOf(Volume::class, $volume);
            $this->assertEquals($volumeName, $volume->getName());
            $this->assertEquals('local', $volume->getDriver());
            $this->assertNotEmpty($volume->getMountpoint());
        }

        public function testInspectVolume()
        {
            $volumeName = 'dockerlib-inspect-' . uniqid();
            
            $volume = $this->docker->volumes()->create($volumeName);
            $this->testVolumes[] = $volumeName;
            
            $inspected = $this->docker->volumes()->inspect($volumeName);
            
            $this->assertEquals($volumeName, $inspected->getName());
            $this->assertNotNull($inspected->getDriver());
            $this->assertNotNull($inspected->getMountpoint());
            $this->assertNotNull($inspected->getScope());
        }

        public function testVolumeWithLabels()
        {
            $volumeName = 'dockerlib-labels-' . uniqid();
            
            $volume = $this->docker->volumes()->create($volumeName, [
                'Labels' => [
                    'env' => 'test',
                    'project' => 'dockerlib',
                    'version' => '1.0'
                ]
            ]);
            
            $this->testVolumes[] = $volumeName;
            
            $labels = $volume->getLabels();
            $this->assertNotEmpty($labels);
            $this->assertArrayHasKey('env', $labels);
            $this->assertEquals('test', $labels['env']);
        }

        public function testRemoveVolume()
        {
            $volumeName = 'dockerlib-remove-' . uniqid();
            
            $volume = $this->docker->volumes()->create($volumeName);
            
            $this->docker->volumes()->remove($volumeName);
            
            // Verify volume is removed
            $this->expectException(\DockerLib\Exceptions\ResponseException::class);
            $this->docker->volumes()->inspect($volumeName);
        }

        public function testVolumeWithContainer()
        {
            $volumeName = 'dockerlib-container-' . uniqid();
            $containerName = 'dockerlib-vol-test-' . uniqid();
            
            // Create volume
            $volume = $this->docker->volumes()->create($volumeName);
            $this->testVolumes[] = $volumeName;
            
            // Create container with volume
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sh', '-c', 'echo "test" > /data/test.txt && sleep 10'],
                'HostConfig' => [
                    'Binds' => [
                        "$volumeName:/data"
                    ]
                ]
            ], $containerName);
            
            try {
                $this->docker->containers()->start($container->getId());
                $this->docker->containers()->wait($container->getId());
                
                // Volume should still exist after container use
                $volumeInspect = $this->docker->volumes()->inspect($volumeName);
                $this->assertEquals($volumeName, $volumeInspect->getName());
                
            } finally {
                // Cleanup container
                try {
                    $this->docker->containers()->remove($container->getId(), true, false);
                } catch (\Exception $e) {
                    // Continue
                }
            }
        }

        public function testListVolumesWithFilters()
        {
            $volumeName = 'dockerlib-filter-' . uniqid();
            
            $volume = $this->docker->volumes()->create($volumeName, [
                'Labels' => [
                    'filter-test' => 'true'
                ]
            ]);
            $this->testVolumes[] = $volumeName;
            
            $volumes = $this->docker->volumes()->list([
                'label' => ['filter-test=true']
            ]);
            
            $this->assertIsArray($volumes);
            $this->assertArrayHasKey('Volumes', $volumes);
            
            $found = false;
            if (!empty($volumes['Volumes'])) {
                foreach ($volumes['Volumes'] as $vol) {
                    if ($vol->getName() === $volumeName) {
                        $found = true;
                        break;
                    }
                }
            }
            
            $this->assertTrue($found, 'Volume with filter label should be found');
        }

        public function testPruneVolumes()
        {
            // Create and remove a container with an anonymous volume
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '5'],
                'HostConfig' => [
                    'Binds' => ['/tmp/test']
                ]
            ]);
            
            $this->docker->containers()->remove($container->getId(), true, false);
            
            $result = $this->docker->volumes()->prune();
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('VolumesDeleted', $result);
            $this->assertArrayHasKey('SpaceReclaimed', $result);
        }

        public function testVolumeWithDriverOpts()
        {
            $volumeName = 'dockerlib-driver-opts-' . uniqid();
            
            $volume = $this->docker->volumes()->create($volumeName, [
                'Driver' => 'local',
                'DriverOpts' => [
                    'type' => 'tmpfs',
                    'device' => 'tmpfs',
                    'o' => 'size=100m'
                ]
            ]);
            
            $this->testVolumes[] = $volumeName;
            
            $this->assertInstanceOf(Volume::class, $volume);
            $this->assertEquals($volumeName, $volume->getName());
        }

        public function testRemoveNonExistentVolume()
        {
            $this->expectException(\DockerLib\Exceptions\ResponseException::class);
            $this->docker->volumes()->remove('nonexistent-volume-' . uniqid(), false);
        }

        public function testInspectNonExistentVolume()
        {
            $this->expectException(\DockerLib\Exceptions\ResponseException::class);
            $this->docker->volumes()->inspect('nonexistent-volume-' . uniqid());
        }

        public function testVolumeScope()
        {
            $volumeName = 'dockerlib-scope-' . uniqid();
            
            $volume = $this->docker->volumes()->create($volumeName);
            $this->testVolumes[] = $volumeName;
            
            $this->assertNotNull($volume->getScope());
            $this->assertEquals('local', $volume->getScope());
        }

        public function testListVolumesEmpty()
        {
            // Try to list volumes with a filter that matches nothing
            $volumes = $this->docker->volumes()->list([
                'label' => ['nonexistent-label-' . uniqid() . '=true']
            ]);
            
            $this->assertIsArray($volumes);
            // May or may not be empty depending on system state
        }

        public function testVolumeWithMultipleLabels()
        {
            $volumeName = 'dockerlib-multi-labels-' . uniqid();
            
            $volume = $this->docker->volumes()->create($volumeName, [
                'Labels' => [
                    'label1' => 'value1',
                    'label2' => 'value2',
                    'label3' => 'value3'
                ]
            ]);
            
            $this->testVolumes[] = $volumeName;
            
            $labels = $volume->getLabels();
            $this->assertCount(3, $labels);
            $this->assertEquals('value1', $labels['label1']);
            $this->assertEquals('value2', $labels['label2']);
            $this->assertEquals('value3', $labels['label3']);
        }

        public function testUpdateVolumeMethodExists()
        {
            $this->assertTrue(
                method_exists($this->docker->volumes(), 'update'),
                'VolumeManager should have update() method'
            );
        }

        public function testCreateAnonymousVolume()
        {
            $volume = $this->docker->volumes()->create([]);
            $this->testVolumes[] = $volume->getName();

            $this->assertInstanceOf(Volume::class, $volume);
            $this->assertNotEmpty($volume->getName());
            $this->assertNotEmpty($volume->getMountpoint());
        }

        public function testListVolumesWithNameFilter()
        {
            $volumeName = 'dockerlib-name-filter-' . uniqid();
            $this->docker->volumes()->create($volumeName);
            $this->testVolumes[] = $volumeName;

            $volumes = $this->docker->volumes()->list([
                'name' => [$volumeName]
            ]);

            $this->assertIsArray($volumes);
            $this->assertArrayHasKey('Volumes', $volumes);
            $this->assertCount(1, $volumes['Volumes']);
            $this->assertEquals($volumeName, $volumes['Volumes'][0]->getName());
        }

        public function testVolumeInspectReturnsAllFields()
        {
            $volumeName = 'dockerlib-fields-' . uniqid();
            $volume = $this->docker->volumes()->create($volumeName, [
                'Driver' => 'local',
                'Labels' => ['env' => 'test']
            ]);
            $this->testVolumes[] = $volumeName;

            $this->assertEquals($volumeName, $volume->getName());
            $this->assertEquals('local', $volume->getDriver());
            $this->assertNotEmpty($volume->getMountpoint());
            $this->assertEquals('local', $volume->getScope());
            $this->assertIsArray($volume->getLabels());
            $this->assertArrayHasKey('env', $volume->getLabels());
            $this->assertIsArray($volume->getOptions());
        }
    }
