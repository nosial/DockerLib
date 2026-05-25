<?php

    namespace DockerLib;

    use DockerLib\Docker;
    use PHPUnit\Framework\TestCase;

    /**
     * Base test class for Docker tests with improved cleanup and CI support
     */
    abstract class BaseDockerTest extends TestCase
    {
        protected Docker $docker;
        protected array $testContainers = [];
        protected array $testImages = [];
        protected array $testVolumes = [];
        protected array $testNetworks = [];
        protected array $testFiles = [];

        /**
         * Default timeout for operations in CI (seconds)
         */
        protected int $operationTimeout = 30;

        /**
         * Whether we're running in CI environment
         */
        protected bool $isCI = false;

        protected function setUp(): void
        {
            // Detect CI environment
            $this->isCI = getenv('CI') !== false || getenv('GITHUB_ACTIONS') !== false;
            
            // Adjust timeouts for CI
            if ($this->isCI) {
                $this->operationTimeout = 60;
            }

            try {
                $this->docker = new Docker();
            } catch (\Exception $e) {
                $this->markTestSkipped('Docker client initialization failed: ' . $e->getMessage());
            }
            
            // Verify Docker is running
            if (!$this->pingWithRetry()) {
                $this->markTestSkipped('Docker daemon is not available');
            }
        }

        protected function tearDown(): void
        {
            $this->cleanupResources();
        }

        /**
         * Cleanup all test resources with proper error handling
         */
        protected function cleanupResources(): void
        {
            // Stop and remove containers first
            foreach ($this->testContainers as $containerId) {
                try {
                    $this->docker->containers()->stop($containerId, 1);
                } catch (\Exception $e) {
                    // Container might already be stopped or removed
                }
                
                try {
                    $this->docker->containers()->remove($containerId, true, true);
                } catch (\Exception $e) {
                    // Container might already be removed
                    error_log("Warning: Failed to remove container {$containerId}: " . $e->getMessage());
                }
            }
            $this->testContainers = [];

            // Remove test images
            foreach ($this->testImages as $imageId) {
                try {
                    // Only remove if it's a tagged test image
                    if (str_contains($imageId, 'dockerlib-test')) {
                        $this->docker->images()->remove($imageId, true, false);
                    }
                } catch (\Exception $e) {
                    // Image might already be removed or in use
                    error_log("Warning: Failed to remove image {$imageId}: " . $e->getMessage());
                }
            }
            $this->testImages = [];

            // Remove test networks
            foreach ($this->testNetworks as $networkId) {
                try {
                    $this->docker->networks()->remove($networkId);
                } catch (\Exception $e) {
                    // Network might already be removed or in use
                    error_log("Warning: Failed to remove network {$networkId}: " . $e->getMessage());
                }
            }
            $this->testNetworks = [];

            // Remove test volumes
            foreach ($this->testVolumes as $volumeName) {
                try {
                    $this->docker->volumes()->remove($volumeName, true);
                } catch (\Exception $e) {
                    // Volume might already be removed or in use
                    error_log("Warning: Failed to remove volume {$volumeName}: " . $e->getMessage());
                }
            }
            $this->testVolumes = [];

            // Clean up test files
            foreach ($this->testFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            $this->testFiles = [];
        }

        /**
         * Ping Docker daemon with retry logic
         */
        protected function pingWithRetry(int $maxAttempts = 3, int $delay = 1): bool
        {
            for ($i = 0; $i < $maxAttempts; $i++) {
                try {
                    if ($this->docker->ping()) {
                        return true;
                    }
                } catch (\Exception $e) {
                    if ($i === $maxAttempts - 1) {
                        return false;
                    }
                    sleep($delay);
                }
            }
            return false;
        }

        /**
         * Ensure an image is available, pulling if necessary
         */
        protected function ensureImage(string $image, int $maxAttempts = 2): void
        {
            for ($i = 0; $i < $maxAttempts; $i++) {
                try {
                    $this->docker->images()->inspect($image);
                    return;
                } catch (\Exception $e) {
                    // Image not found, try to pull
                    try {
                        $this->docker->images()->pull($image);
                        return;
                    } catch (\Exception $pullError) {
                        if ($i === $maxAttempts - 1) {
                            $this->markTestSkipped("Could not pull image {$image}: " . $pullError->getMessage());
                        }
                        sleep(2);
                    }
                }
            }
        }

        /**
         * Wait for container to reach a specific state
         */
        protected function waitForContainerState(string $containerId, string $expectedState, int $timeout = 30): bool
        {
            $start = time();
            while (time() - $start < $timeout) {
                try {
                    $container = $this->docker->containers()->inspect($containerId);
                    $state = $container->getState();
                    
                    if (is_array($state) && isset($state['Status']) && $state['Status'] === $expectedState) {
                        return true;
                    } elseif (is_string($state) && $state === $expectedState) {
                        return true;
                    }
                } catch (\Exception $e) {
                    // Container might not exist yet or be in transition
                }
                usleep(100000); // 100ms
            }
            return false;
        }

        /**
         * Generate unique test identifier
         */
        protected function generateTestId(): string
        {
            return 'dockerlib-test-' . uniqid();
        }

        /**
         * Create a temporary test file
         */
        protected function createTestFile(string $content, string $prefix = 'dockerlib-test-'): string
        {
            $file = sys_get_temp_dir() . '/' . $prefix . uniqid() . '.tmp';
            file_put_contents($file, $content);
            $this->testFiles[] = $file;
            return $file;
        }

        /**
         * Assert that cleanup was successful
         */
        protected function assertCleanupSuccessful(): void
        {
            $this->assertEmpty($this->testContainers, 'Test containers were not cleaned up');
            $this->assertEmpty($this->testImages, 'Test images were not cleaned up');
            $this->assertEmpty($this->testNetworks, 'Test networks were not cleaned up');
            $this->assertEmpty($this->testVolumes, 'Test volumes were not cleaned up');
            $this->assertEmpty($this->testFiles, 'Test files were not cleaned up');
        }

        /**
         * Run operation with timeout
         */
        protected function withTimeout(callable $operation, int $timeout = null)
        {
            $timeout = $timeout ?? $this->operationTimeout;
            $start = time();
            
            try {
                $result = $operation();
                $elapsed = time() - $start;
                
                if ($elapsed > $timeout) {
                    $this->fail("Operation exceeded timeout of {$timeout} seconds (took {$elapsed}s)");
                }
                
                return $result;
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }
