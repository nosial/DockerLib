<?php

    namespace DockerLib;

    use DockerLib\Docker;
    use PHPUnit\Framework\TestCase;

    class PerformanceTest extends TestCase
    {
        private Docker $docker;
        private array $cleanup = [];

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }
        }

        protected function tearDown(): void
        {
            foreach ($this->cleanup as $item) {
                try {
                    switch ($item['type']) {
                        case 'container':
                            $this->docker->containers()->stop($item['id'], 1);
                            $this->docker->containers()->remove($item['id'], true);
                            break;
                        case 'network':
                            $this->docker->networks()->remove($item['id']);
                            break;
                        case 'volume':
                            $this->docker->volumes()->remove($item['id'], true);
                            break;
                    }
                } catch (\Exception $e) {
                    // Ignore cleanup errors
                }
            }
            
            $this->cleanup = [];
        }

        public function testMultipleContainerCreation()
        {
            $start = microtime(true);
            $count = 5;
            
            for ($i = 0; $i < $count; $i++) {
                $container = $this->docker->containers()->create([
                    'Image' => 'alpine:latest',
                    'Cmd' => ['sleep', '5']
                ], 'perf-test-' . $i . '-' . uniqid());
                
                $this->cleanup[] = ['type' => 'container', 'id' => $container->getId()];
            }
            
            $duration = microtime(true) - $start;
            
            $this->assertLessThan(30, $duration, "Creating $count containers took too long");
            $this->assertCount($count, $this->cleanup);
        }

        public function testMultipleNetworkCreation()
        {
            $start = microtime(true);
            $count = 5;
            
            for ($i = 0; $i < $count; $i++) {
                $network = $this->docker->networks()->create([
                    'Name' => 'perf-net-' . $i . '-' . uniqid(),
                    'Driver' => 'bridge'
                ]);
                
                $this->cleanup[] = ['type' => 'network', 'id' => $network->getId()];
            }
            
            $duration = microtime(true) - $start;
            
            $this->assertLessThan(20, $duration, "Creating $count networks took too long");
        }

        public function testMultipleVolumeCreation()
        {
            $start = microtime(true);
            $count = 5;
            
            for ($i = 0; $i < $count; $i++) {
                $volumeName = 'perf-vol-' . $i . '-' . uniqid();
                $volume = $this->docker->volumes()->create($volumeName);
                
                $this->cleanup[] = ['type' => 'volume', 'id' => $volume->getName()];
            }
            
            $duration = microtime(true) - $start;
            
            $this->assertLessThan(20, $duration, "Creating $count volumes took too long");
        }

        public function testRapidStartStop()
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '60']
            ]);
            
            $this->cleanup[] = ['type' => 'container', 'id' => $container->getId()];
            
            $start = microtime(true);
            $iterations = 3;
            
            for ($i = 0; $i < $iterations; $i++) {
                $this->docker->containers()->start($container->getId());
                sleep(1);
                $this->docker->containers()->stop($container->getId(), 1);
            }
            
            $duration = microtime(true) - $start;
            
            $this->assertLessThan($iterations * 5, $duration, "Start/stop cycle too slow");
        }

        public function testListLargeNumberOfImages()
        {
            $start = microtime(true);
            
            $images = $this->docker->images()->list([], true);
            
            $duration = microtime(true) - $start;
            
            $this->assertLessThan(5, $duration, "Listing images took too long");
            $this->assertIsArray($images);
        }

        public function testListLargeNumberOfContainers()
        {
            $start = microtime(true);
            
            $containers = $this->docker->containers()->list([], true);
            
            $duration = microtime(true) - $start;
            
            $this->assertLessThan(5, $duration, "Listing containers took too long");
            $this->assertIsArray($containers);
        }

        public function testConcurrentInspections()
        {
            // Create a few containers
            $containerIds = [];
            for ($i = 0; $i < 3; $i++) {
                $container = $this->docker->containers()->create([
                    'Image' => 'alpine:latest',
                    'Cmd' => ['sleep', '10']
                ]);
                
                $containerIds[] = $container->getId();
                $this->cleanup[] = ['type' => 'container', 'id' => $container->getId()];
            }
            
            $start = microtime(true);
            
            foreach ($containerIds as $id) {
                $this->docker->containers()->inspect($id);
            }
            
            $duration = microtime(true) - $start;
            
            $this->assertLessThan(10, $duration, "Concurrent inspections took too long");
        }

        public function testSystemInfoPerformance()
        {
            $iterations = 5;
            $start = microtime(true);
            
            for ($i = 0; $i < $iterations; $i++) {
                $this->docker->system()->info();
            }
            
            $duration = microtime(true) - $start;
            $avgTime = $duration / $iterations;
            
            $this->assertLessThan(1, $avgTime, "System info calls are too slow");
        }

        public function testImageSearchPerformance()
        {
            $start = microtime(true);
            
            $this->docker->images()->search('alpine', 10);
            
            $duration = microtime(true) - $start;
            
            $this->assertLessThan(10, $duration, "Image search took too long");
        }
    }
