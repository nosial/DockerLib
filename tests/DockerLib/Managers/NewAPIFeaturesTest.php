<?php

    namespace DockerLib\Managers;

    use DockerLib\Docker;
    use DockerLib\Exceptions\ResponseException;
    use PHPUnit\Framework\TestCase;

    /**
     * Tests for new Docker API v1.52 endpoints
     */
    class NewAPIFeaturesTest extends TestCase
    {
        private Docker $docker;
        private array $testContainers = [];
        private array $testImages = [];

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }

            // Ensure we have alpine image for testing
            try {
                $this->docker->images()->inspect('alpine:latest');
            } catch (ResponseException $e) {
                $this->docker->images()->pull('alpine', 'latest');
            }
        }

        protected function tearDown(): void
        {
            // Clean up test containers
            foreach ($this->testContainers as $containerId) {
                try {
                    $this->docker->containers()->stop($containerId, 1);
                    $this->docker->containers()->remove($containerId, true, true);
                } catch (\Exception $e) {
                    // Ignore cleanup errors
                }
            }

            // Clean up test images
            foreach ($this->testImages as $imageId) {
                try {
                    $this->docker->images()->remove($imageId, true, false);
                } catch (\Exception $e) {
                    // Ignore cleanup errors
                }
            }

            $this->testContainers = [];
            $this->testImages = [];
        }

        // ========================================================================
        // Container Archive Operations Tests
        // ========================================================================

        public function testGetArchiveFromContainer()
        {
            // Create a container
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '60']
            ], 'test-archive-' . uniqid());
            
            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            // Get archive of /etc directory
            $tarData = $this->docker->containers()->getArchive($container->getId(), '/etc');

            // Verify we got tar data
            $this->assertNotEmpty($tarData);
            $this->assertIsString($tarData);
            // Tar files start with specific magic bytes
            $this->assertTrue(strlen($tarData) > 512, 'Tar archive should be larger than 512 bytes');
        }

        public function testPutArchiveToContainer()
        {
            // Create a container
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '60']
            ], 'test-put-archive-' . uniqid());
            
            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            // Create a simple tar archive in memory
            $tarPath = '/tmp/test-' . uniqid() . '.tar';
            $testFile = '/tmp/testfile-' . uniqid() . '.txt';
            file_put_contents($testFile, 'test content');
            
            // Create tar using system tar command
            exec("tar -cf {$tarPath} -C " . dirname($testFile) . " " . basename($testFile));
            $tarData = file_get_contents($tarPath);
            
            // Upload to container
            $this->docker->containers()->putArchive($container->getId(), '/tmp', $tarData);

            // Verify by getting the file back
            $archiveData = $this->docker->containers()->getArchive($container->getId(), '/tmp/' . basename($testFile));
            $this->assertNotEmpty($archiveData);

            // Cleanup temp files
            @unlink($testFile);
            @unlink($tarPath);
        }

        public function testStatArchive()
        {
            // Create a container
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '60']
            ], 'test-stat-' . uniqid());
            
            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            // Get stat info for /etc directory
            $stat = $this->docker->containers()->statArchive($container->getId(), '/etc');

            // Verify stat information is returned
            $this->assertIsArray($stat);
            // The stat format varies, but should not be empty if the path exists
            $this->assertNotEmpty($stat);
        }

        public function testAttachWebSocket()
        {
            // Create a container
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '60']
            ], 'test-ws-' . uniqid());
            
            $this->testContainers[] = $container->getId();

            // Get WebSocket URL
            $wsUrl = $this->docker->containers()->attachWebSocket(
                $container->getId(),
                logs: true,
                stream: true,
                stdout: true
            );

            // Verify URL format
            $this->assertStringStartsWith('ws://', $wsUrl);
            $this->assertStringContainsString($container->getId(), $wsUrl);
            $this->assertStringContainsString('attach/ws', $wsUrl);
            $this->assertStringContainsString('logs=1', $wsUrl);
            $this->assertStringContainsString('stdout=1', $wsUrl);
        }

        // ========================================================================
        // Image Commit Tests
        // ========================================================================

        public function testCommitContainer()
        {
            // Create and start a container
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sh', '-c', 'echo "test" > /testfile.txt && sleep 60']
            ], 'test-commit-' . uniqid());
            
            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            // Wait a moment for the file to be created
            sleep(2);

            // Commit the container to a new image
            $imageName = 'test-committed-' . uniqid();
            $result = $this->docker->images()->commit(
                $container->getId(),
                repo: $imageName,
                tag: 'latest',
                comment: 'Test commit',
                author: 'DockerLib Test <test@example.com>',
                pause: true
            );

            // Verify result
            $this->assertIsArray($result);
            $this->assertArrayHasKey('Id', $result);
            $this->testImages[] = $result['Id'];

            // Verify the image exists
            $image = $this->docker->images()->inspect($imageName . ':latest');
            $this->assertNotNull($image->getId());
        }

        public function testCommitWithChanges()
        {
            // Create a container
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '60']
            ], 'test-commit-changes-' . uniqid());
            
            $this->testContainers[] = $container->getId();

            // Commit with changes (Dockerfile instructions as string)
            $imageName = 'test-commit-changes-' . uniqid();
            $result = $this->docker->images()->commit(
                $container->getId(),
                repo: $imageName,
                tag: 'latest',
                changes: "ENV TEST=value\nLABEL test=label"
            );

            $this->assertIsArray($result);
            $this->assertArrayHasKey('Id', $result);
            $this->testImages[] = $result['Id'];
        }

        // ========================================================================
        // Build Cache Prune Tests
        // ========================================================================

        public function testPruneBuildCache()
        {
            // Prune build cache
            $result = $this->docker->images()->pruneBuildCache(all: false);

            // Verify result structure
            $this->assertIsArray($result);
            $this->assertArrayHasKey('SpaceReclaimed', $result);
            $this->assertIsNumeric($result['SpaceReclaimed']);
        }

        public function testPruneBuildCacheAll()
        {
            // Prune all build cache
            $result = $this->docker->images()->pruneBuildCache(all: true);

            // Verify result
            $this->assertIsArray($result);
            $this->assertArrayHasKey('SpaceReclaimed', $result);
            $this->assertGreaterThanOrEqual(0, $result['SpaceReclaimed']);
        }

        public function testPruneBuildCacheWithFilters()
        {
            // Prune with filters
            $result = $this->docker->images()->pruneBuildCache(
                filters: ['until' => ['24h']]
            );

            $this->assertIsArray($result);
            $this->assertArrayHasKey('SpaceReclaimed', $result);
        }

        // ========================================================================
        // Distribution Tests
        // ========================================================================

        public function testDistributionInspect()
        {
            try {
                // Inspect a public image distribution
                $dist = $this->docker->distribution()->inspect('alpine:latest');

                // Verify Distribution object
                $this->assertInstanceOf(\DockerLib\Objects\Distribution::class, $dist);
                
                // Verify descriptor exists and is an array
                $descriptor = $dist->getDescriptor();
                $this->assertIsArray($descriptor);
                
                // Descriptor should have some content
                $this->assertNotEmpty($descriptor);
                
                // Verify platforms
                $platforms = $dist->getPlatforms();
                $this->assertIsArray($platforms);
            } catch (ResponseException $e) {
                // If we get 401, it might be registry auth required
                if ($e->getCode() === 401) {
                    $this->markTestSkipped('Registry authentication required for distribution inspect');
                } else {
                    throw $e;
                }
            }
        }

        // ========================================================================
        // System Session Tests
        // ========================================================================

        public function testCreateSession()
        {
            try {
                // Create session (this is experimental and may not be supported)
                $stream = $this->docker->system()->createSession();

                // Verify it returns a StreamResponse
                $this->assertInstanceOf(\DockerLib\Objects\StreamResponse::class, $stream);
            } catch (ResponseException $e) {
                // Session endpoint is experimental and may not be available
                if (strpos($e->getMessage(), 'not found') !== false || 
                    strpos($e->getMessage(), 'not supported') !== false) {
                    $this->markTestSkipped('Session endpoint not available on this Docker version');
                } else {
                    throw $e;
                }
            }
        }

        // ========================================================================
        // Plugin Create Tests (if plugin support is available)
        // ========================================================================

        public function testPluginCreate()
        {
            // Plugin creation requires a valid plugin tarball
            // This is a placeholder test that verifies the method exists and is callable
            $this->assertTrue(
                method_exists($this->docker->plugins(), 'create'),
                'PluginManager should have create() method'
            );
        }

        // ========================================================================
        // Integration Tests
        // ========================================================================

        public function testContainerArchiveWorkflow()
        {
            // Complete workflow: create container, add file, commit, verify
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '60']
            ], 'test-workflow-' . uniqid());
            
            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            // Create a test file
            $testFile = '/tmp/workflow-test-' . uniqid() . '.txt';
            file_put_contents($testFile, 'workflow test content');
            
            // Create tar
            $tarPath = '/tmp/workflow-' . uniqid() . '.tar';
            exec("tar -cf {$tarPath} -C " . dirname($testFile) . " " . basename($testFile));
            $tarData = file_get_contents($tarPath);

            // Upload to container
            $this->docker->containers()->putArchive($container->getId(), '/tmp', $tarData);

            // Stat the file - just verify we get a response
            $stat = $this->docker->containers()->statArchive($container->getId(), '/tmp/' . basename($testFile));
            $this->assertIsArray($stat);
            $this->assertNotEmpty($stat);

            // Commit container
            $imageName = 'test-workflow-img-' . uniqid();
            $result = $this->docker->images()->commit(
                $container->getId(),
                repo: $imageName,
                comment: 'Workflow test'
            );
            
            $this->testImages[] = $result['Id'];

            // Verify image exists
            $image = $this->docker->images()->inspect($imageName . ':latest');
            $this->assertNotNull($image);

            // Cleanup
            @unlink($testFile);
            @unlink($tarPath);
        }
    }
