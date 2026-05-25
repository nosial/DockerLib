<?php

    namespace DockerLib;

    use DockerLib\Docker;
    use PHPUnit\Framework\TestCase;

    class ContainerArchiveTest extends TestCase
    {
        private Docker $docker;
        private array $testContainers = [];
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
                $this->markTestSkipped('Could not pull alpine image: ' . $e->getMessage());
            }
        }

        protected function tearDown(): void
        {
            foreach ($this->testContainers as $containerId) {
                try {
                    $this->docker->containers()->remove($containerId, true, true);
                } catch (\Exception $e) {
                }
            }

            foreach ($this->testFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            $this->testContainers = [];
            $this->testFiles = [];
        }

        public function testGetArchive(): void
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30']
            ]);

            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            $tarData = $this->docker->containers()->getArchive($container->getId(), '/etc');

            $this->assertNotEmpty($tarData);
            $this->assertIsString($tarData);

            $tarFile = sys_get_temp_dir() . '/dockerlib-archive-' . uniqid() . '.tar';
            $this->testFiles[] = $tarFile;
            file_put_contents($tarFile, $tarData);

            $this->assertFileExists($tarFile);
            $this->assertGreaterThan(50, filesize($tarFile));
        }

        public function testGetArchiveSpecificFile(): void
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30']
            ]);

            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            $tarData = $this->docker->containers()->getArchive($container->getId(), '/etc/hostname');

            $this->assertNotEmpty($tarData);
            $this->assertIsString($tarData);
        }

        public function testGetArchiveNonExistentPath(): void
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30']
            ]);

            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            $result = $this->docker->containers()->getArchive($container->getId(), '/nonexistent/path');
            $this->assertIsString($result);
        }

        public function testStatArchive(): void
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30']
            ]);

            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            $stat = $this->docker->containers()->statArchive($container->getId(), '/etc');

            $this->assertIsArray($stat);
            $this->assertNotEmpty($stat);
        }

        public function testStatArchiveSpecificFile(): void
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30']
            ]);

            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            $stat = $this->docker->containers()->statArchive($container->getId(), '/etc/hostname');

            $this->assertIsArray($stat);
            $this->assertNotEmpty($stat);
        }

        public function testStatArchiveNonExistentPath(): void
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30']
            ]);

            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            try {
                $stat = $this->docker->containers()->statArchive($container->getId(), '/nonexistent');
                $this->assertEmpty($stat);
            } catch (\Exception $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }

        public function testPutArchive(): void
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30']
            ]);

            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            $tarPath = sys_get_temp_dir() . '/dockerlib-put-test-' . uniqid() . '.tar';
            $this->testFiles[] = $tarPath;

            $testContent = 'hello dockerlib';
            $testFile = sys_get_temp_dir() . '/put-test-' . uniqid() . '.txt';
            $this->testFiles[] = $testFile;
            file_put_contents($testFile, $testContent);

            $phar = new \PharData($tarPath);
            $phar->addFile($testFile, 'test-file.txt');

            $tarData = file_get_contents($tarPath);

            $this->docker->containers()->putArchive($container->getId(), '/tmp', $tarData);

            $verifyTar = $this->docker->containers()->getArchive($container->getId(), '/tmp/test-file.txt');
            $this->assertNotEmpty($verifyTar);
        }

        public function testPutArchiveOverwriteDirNonDir(): void
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30']
            ]);

            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            $tarPath = sys_get_temp_dir() . '/dockerlib-put-overwrite-' . uniqid() . '.tar';
            $this->testFiles[] = $tarPath;

            $testContent = 'overwrite test';
            $testFile = sys_get_temp_dir() . '/overwrite-test-' . uniqid() . '.txt';
            $this->testFiles[] = $testFile;
            file_put_contents($testFile, $testContent);

            $phar = new \PharData($tarPath);
            $phar->addFile($testFile, 'overwrite-test.txt');

            $tarData = file_get_contents($tarPath);

            try {
                $this->docker->containers()->putArchive($container->getId(), '/tmp', $tarData, true);
                $this->assertTrue(true);
            } catch (\Exception $e) {
                $this->fail('putArchive with noOverwriteDirNonDir flag failed: ' . $e->getMessage());
            }
        }

        public function testGetArchiveWholeRoot(): void
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30']
            ]);

            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            $tarData = $this->docker->containers()->getArchive($container->getId(), '/');

            $this->assertNotEmpty($tarData);
            $this->assertIsString($tarData);
        }
    }
