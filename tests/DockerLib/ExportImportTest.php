<?php

    namespace DockerLib;

    use DockerLib\Docker;
    use PHPUnit\Framework\TestCase;

    class ExportImportTest extends TestCase
    {
        private Docker $docker;
        private array $testContainers = [];
        private array $testImages = [];
        private array $testFiles = [];

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }

            try {
                $this->docker->images()->pull('alpine:latest');
            } catch (\Exception $e) {
                $this->markTestSkipped('Could not pull alpine image');
            }
        }

        protected function tearDown(): void
        {
            foreach ($this->testContainers as $containerId) {
                try {
                    $this->docker->containers()->remove($containerId, true);
                } catch (\Exception $e) {
                    // Container might already be removed
                }
            }

            foreach ($this->testImages as $imageTag) {
                try {
                    $this->docker->images()->remove($imageTag, true);
                } catch (\Exception $e) {
                    // Image might already be removed
                }
            }

            foreach ($this->testFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            $this->testContainers = [];
            $this->testImages = [];
            $this->testFiles = [];
        }

        public function testContainerExport()
        {
            // Create and start a container
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sh', '-c', 'echo "test data" > /test.txt && sleep 30']
            ]);

            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            // Give container time to write file
            sleep(2);

            // Export container
            $tarData = $this->docker->containers()->export($container->getId());

            $this->assertNotEmpty($tarData);
            $this->assertIsString($tarData);

            // Save to file and verify it's a valid tar
            $tarFile = sys_get_temp_dir() . '/container-export-' . uniqid() . '.tar';
            $this->testFiles[] = $tarFile;
            file_put_contents($tarFile, $tarData);

            $this->assertFileExists($tarFile);
            $this->assertGreaterThan(1000, filesize($tarFile));
        }

        public function testImageExport()
        {
            // Export alpine image
            $tarData = $this->docker->images()->export('alpine:latest');

            $this->assertNotEmpty($tarData);
            $this->assertIsString($tarData);

            // Save to file
            $tarFile = sys_get_temp_dir() . '/image-export-' . uniqid() . '.tar';
            $this->testFiles[] = $tarFile;
            file_put_contents($tarFile, $tarData);

            $this->assertFileExists($tarFile);
            $this->assertGreaterThan(1000, filesize($tarFile));
        }

        public function testImageExportMultiple()
        {
            // Export multiple images
            $tarData = $this->docker->images()->exportAll(['alpine:latest']);

            $this->assertNotEmpty($tarData);
            $this->assertIsString($tarData);

            $tarFile = sys_get_temp_dir() . '/images-export-' . uniqid() . '.tar';
            $this->testFiles[] = $tarFile;
            file_put_contents($tarFile, $tarData);

            $this->assertFileExists($tarFile);
            $this->assertGreaterThan(1000, filesize($tarFile));
        }

        public function testImageHistory()
        {
            $history = $this->docker->images()->history('alpine:latest');

            $this->assertIsArray($history);
            $this->assertNotEmpty($history);

            // Each history entry should have expected fields
            foreach ($history as $entry) {
                $this->assertArrayHasKey('Id', $entry);
                $this->assertArrayHasKey('Created', $entry);
                $this->assertArrayHasKey('CreatedBy', $entry);
                $this->assertArrayHasKey('Size', $entry);
            }
        }
    }
