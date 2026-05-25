<?php

    namespace DockerLib\Objects;

    use DockerLib\Objects\ContainerStats;
    use PHPUnit\Framework\TestCase;

    class ContainerStatsObjectTest extends TestCase
    {
        public function testContainerStatsCalculations()
        {
            $data = [
                'id' => 'test123',
                'name' => '/test-container',
                'read' => '2024-01-01T00:00:00Z',
                'cpu_stats' => [
                    'cpu_usage' => [
                        'total_usage' => 2000000000,
                        'percpu_usage' => [1000000000, 1000000000]
                    ],
                    'system_cpu_usage' => 10000000000
                ],
                'precpu_stats' => [
                    'cpu_usage' => [
                        'total_usage' => 1000000000
                    ],
                    'system_cpu_usage' => 5000000000
                ],
                'memory_stats' => [
                    'usage' => 104857600, // 100MB
                    'limit' => 1073741824 // 1GB
                ]
            ];
            
            $stats = new ContainerStats($data);
            
            $this->assertEquals('test123', $stats->getId());
            $this->assertEquals('/test-container', $stats->getName());
            
            // Test CPU percentage calculation
            $cpuPercent = $stats->getCpuPercentage();
            $this->assertIsFloat($cpuPercent);
            $this->assertGreaterThanOrEqual(0, $cpuPercent);
            
            // Test memory calculations
            $this->assertEquals(104857600, $stats->getMemoryUsage());
            $this->assertEquals(1073741824, $stats->getMemoryLimit());
            
            $memoryPercent = $stats->getMemoryPercentage();
            $this->assertIsFloat($memoryPercent);
            $this->assertEqualsWithDelta(9.77, $memoryPercent, 0.1); // ~10%
        }

        public function testStatsWithZeroValues()
        {
            $data = [
                'cpu_stats' => ['cpu_usage' => ['total_usage' => 0]],
                'memory_stats' => ['usage' => 0, 'limit' => 0]
            ];
            
            $stats = new ContainerStats($data);
            
            $this->assertEquals(0.0, $stats->getCpuPercentage());
            $this->assertEquals(0.0, $stats->getMemoryPercentage());
        }
    }
