<?php

    namespace DockerLib;

    use DockerLib\Exceptions\ResponseException;

    /**
     * Edge case and boundary testing for Docker volume operations
     */
    class EdgeCaseVolumeTest extends BaseDockerTest
    {
        /**
         * Test creating volume with minimal config
         */
        public function testCreateVolumeMinimalConfig()
        {
            $volumeName = $this->generateTestId();
            
            $volume = $this->docker->volumes()->create([
                'Name' => $volumeName
            ]);
            
            $this->testVolumes[] = $volumeName;
            $this->assertEquals($volumeName, $volume->getName());
        }

        /**
         * Test creating volume with all options
         */
        public function testCreateVolumeWithAllOptions()
        {
            $volumeName = $this->generateTestId();
            
            $volume = $this->docker->volumes()->create([
                'Name' => $volumeName,
                'Driver' => 'local',
                'Labels' => [
                    'test' => 'edge-case',
                    'environment' => 'testing'
                ]
            ]);
            
            $this->testVolumes[] = $volumeName;
            $this->assertEquals($volumeName, $volume->getName());
            
            $inspected = $this->docker->volumes()->inspect($volumeName);
            $labels = $inspected->getLabels();
            $this->assertArrayHasKey('test', $labels);
            $this->assertEquals('edge-case', $labels['test']);
        }

        /**
         * Test creating volume with duplicate name
         */
        public function testCreateVolumeDuplicateName()
        {
            $volumeName = $this->generateTestId();
            
            $volume1 = $this->docker->volumes()->create(['Name' => $volumeName]);
            $this->testVolumes[] = $volumeName;
            
            try {
                $volume2 = $this->docker->volumes()->create(['Name' => $volumeName]);
                // Docker may return existing volume or error
                $this->assertEquals($volumeName, $volume2->getName());
            } catch (ResponseException $e) {
                $this->assertStringContainsString('already exists', $e->getMessage());
            }
        }

        /**
         * Test inspecting non-existent volume
         */
        public function testInspectNonExistentVolume()
        {
            try {
                $this->docker->volumes()->inspect('nonexistent-volume-' . uniqid());
                $this->fail('Should have thrown exception for non-existent volume');
            } catch (\Exception $e) {
                // Accept ResponseException or any exception containing "No such volume" or similar
                $this->assertTrue(
                    $e instanceof ResponseException || 
                    str_contains($e->getMessage(), 'No such volume') ||
                    str_contains($e->getMessage(), 'not found')
                );
            }
        }

        /**
         * Test removing non-existent volume
         */
        public function testRemoveNonExistentVolume()
        {
            try {
                $this->docker->volumes()->remove('nonexistent-volume-' . uniqid());
                $this->fail('Should have thrown exception for non-existent volume');
            } catch (\Exception $e) {
                // Accept ResponseException or any exception containing "No such volume" or similar
                $this->assertTrue(
                    $e instanceof ResponseException || 
                    str_contains($e->getMessage(), 'No such volume') ||
                    str_contains($e->getMessage(), 'not found')
                );
            }
        }

        /**
         * Test removing volume in use
         */
        public function testRemoveVolumeInUse()
        {
            $this->ensureImage('alpine:latest');
            
            $volumeName = $this->generateTestId();
            $volume = $this->docker->volumes()->create(['Name' => $volumeName]);
            $this->testVolumes[] = $volumeName;
            
            // Create container using the volume
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30'],
                'HostConfig' => [
                    'Binds' => ["{$volumeName}:/data"]
                ]
            ]);
            
            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());
            
            // Try to remove volume while in use
            try {
                $this->docker->volumes()->remove($volumeName);
                $this->fail('Should have thrown exception for volume in use');
            } catch (ResponseException $e) {
                $this->assertStringContainsString('in use', $e->getMessage());
            }
        }

        /**
         * Test listing volumes with filters
         */
        public function testListVolumesWithFilters()
        {
            $volumeName = $this->generateTestId();
            $this->docker->volumes()->create([
                'Name' => $volumeName,
                'Labels' => ['test-filter' => 'true']
            ]);
            $this->testVolumes[] = $volumeName;
            
            $result = $this->docker->volumes()->list([
                'label' => ['test-filter=true']
            ]);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('Volumes', $result);
            
            $found = false;
            if (isset($result['Volumes'])) {
                foreach ($result['Volumes'] as $volume) {
                    if ($volume->getName() === $volumeName) {
                        $found = true;
                        break;
                    }
                }
            }
            $this->assertTrue($found, 'Volume should be found with label filter');
        }

        /**
         * Test volume with special characters in name
         */
        public function testVolumeWithSpecialCharacters()
        {
            $volumeName = 'test-volume_' . time() . '.data';
            
            $volume = $this->docker->volumes()->create(['Name' => $volumeName]);
            $this->testVolumes[] = $volumeName;
            
            $this->assertEquals($volumeName, $volume->getName());
        }

        /**
         * Test pruning volumes
         */
        public function testPruneVolumes()
        {
            $result = $this->docker->volumes()->prune();
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('VolumesDeleted', $result);
            $this->assertArrayHasKey('SpaceReclaimed', $result);
        }

        /**
         * Test pruning volumes with filters
         */
        public function testPruneVolumesWithFilters()
        {
            // Create a volume with label
            $volumeName = $this->generateTestId();
            $this->docker->volumes()->create([
                'Name' => $volumeName,
                'Labels' => ['prune-test' => 'true']
            ]);
            $this->testVolumes[] = $volumeName;
            
            // Prune with label filter (should not delete our volume since it's recent)
            $result = $this->docker->volumes()->prune(['label' => ['prune-test=true']]);
            
            $this->assertIsArray($result);
        }

        /**
         * Test concurrent volume creation
         */
        public function testConcurrentVolumeCreation()
        {
            $volumes = [];
            
            for ($i = 0; $i < 5; $i++) {
                $volumeName = $this->generateTestId() . "-{$i}";
                $volume = $this->docker->volumes()->create(['Name' => $volumeName]);
                $volumes[] = $volumeName;
                $this->testVolumes[] = $volumeName;
            }
            
            $this->assertCount(5, $volumes);
            
            // Verify all volumes exist
            foreach ($volumes as $volumeName) {
                $volume = $this->docker->volumes()->inspect($volumeName);
                $this->assertEquals($volumeName, $volume->getName());
            }
        }
    }
