<?php

    namespace DockerLib;

    use DockerLib\Docker;
    use DockerLib\Objects\Volume;
    use PHPUnit\Framework\TestCase;

    class VolumeOperationsTest extends TestCase
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
            
            if (!empty($result['Volumes'])) {
                foreach ($result['Volumes'] as $volume) {
                    $this->assertInstanceOf(Volume::class, $volume);
                }
            }
        }

        public function testCreateAndInspectVolume()
        {
            $volumeName = 'dockerlib-test-volume-' . uniqid();
            
            $volume = $this->docker->volumes()->create([
                'Name' => $volumeName,
                'Driver' => 'local',
                'Labels' => ['test' => 'true']
            ]);
            
            $this->testVolumes[] = $volumeName;
            
            $this->assertInstanceOf(Volume::class, $volume);
            $this->assertEquals($volumeName, $volume->getName());
            
            // Inspect the volume
            $inspected = $this->docker->volumes()->inspect($volumeName);
            $this->assertEquals($volumeName, $inspected->getName());
        }

        public function testRemoveVolume()
        {
            $volumeName = 'dockerlib-test-remove-' . uniqid();
            
            $volume = $this->docker->volumes()->create([
                'Name' => $volumeName,
                'Driver' => 'local'
            ]);
            
            // Remove volume
            $this->docker->volumes()->remove($volumeName);
            
            // Verify it's removed
            $this->expectException(\DockerLib\Exceptions\ResponseException::class);
            $this->docker->volumes()->inspect($volumeName);
        }

        public function testVolumeWithLabels()
        {
            $volumeName = 'dockerlib-test-labels-' . uniqid();
            
            $volume = $this->docker->volumes()->create([
                'Name' => $volumeName,
                'Driver' => 'local',
                'Labels' => [
                    'app' => 'dockerlib',
                    'env' => 'test'
                ]
            ]);
            
            $this->testVolumes[] = $volumeName;
            
            $inspected = $this->docker->volumes()->inspect($volumeName);
            $labels = $inspected->getLabels();
            
            $this->assertEquals('dockerlib', $labels['app']);
            $this->assertEquals('test', $labels['env']);
        }

        public function testPruneVolumes()
        {
            // Create a volume not in use
            $volumeName = 'dockerlib-test-prune-' . uniqid();
            $this->docker->volumes()->create([
                'Name' => $volumeName,
                'Driver' => 'local'
            ]);
            
            try {
                // Prune unused volumes
                $result = $this->docker->volumes()->prune();
                
                $this->assertIsArray($result);
                $this->assertArrayHasKey('VolumesDeleted', $result);
                $this->assertArrayHasKey('SpaceReclaimed', $result);
            } catch (\Exception $e) {
                // Cleanup if prune failed
                try {
                    $this->docker->volumes()->remove($volumeName);
                } catch (\Exception $e2) {
                    // Ignore
                }
            }
        }

        public function testVolumeInContainer()
        {
            $volumeName = 'dockerlib-test-mount-' . uniqid();
            
            // Create volume
            $volume = $this->docker->volumes()->create([
                'Name' => $volumeName,
                'Driver' => 'local'
            ]);
            
            $this->testVolumes[] = $volumeName;
            
            // Create container with volume
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '10'],
                'HostConfig' => [
                    'Binds' => [$volumeName . ':/data']
                ]
            ]);
            
            try {
                $inspected = $this->docker->containers()->inspect($container->getId());
                $mounts = $inspected->getMounts();
                
                $found = false;
                foreach ($mounts as $mount) {
                    if ($mount->getName() === $volumeName) {
                        $found = true;
                        break;
                    }
                }
                
                $this->assertTrue($found, 'Volume should be mounted in container');
                
            } finally {
                // Cleanup
                try {
                    $this->docker->containers()->remove($container->getId(), true);
                } catch (\Exception $e) {
                    // Ignore cleanup errors
                }
            }
        }

        public function testVolumeListWithFilters()
        {
            $volumeName = 'dockerlib-test-filter-' . uniqid();
            
            $this->docker->volumes()->create([
                'Name' => $volumeName,
                'Driver' => 'local',
                'Labels' => ['filter-test' => 'yes']
            ]);
            
            $this->testVolumes[] = $volumeName;
            
            // List with filter
            $result = $this->docker->volumes()->list([
                'label' => ['filter-test=yes']
            ]);
            
            $found = false;
            if (!empty($result['Volumes'])) {
                foreach ($result['Volumes'] as $vol) {
                    if ($vol->getName() === $volumeName) {
                        $found = true;
                        break;
                    }
                }
            }
            
            $this->assertTrue($found, 'Volume with label should be found in filtered list');
        }
    }
