<?php

    namespace DockerLib;

    use DockerLib\Docker;
    use DockerLib\Objects\Image;
    use PHPUnit\Framework\TestCase;

    class ImageCommitTest extends TestCase
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

            foreach ($this->testImages as $imageTag) {
                try {
                    $this->docker->images()->remove($imageTag, true, false);
                } catch (\Exception $e) {
                }
            }

            $this->testContainers = [];
            $this->testImages = [];
        }

        public function testCommitContainer(): void
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '10']
            ]);

            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            sleep(1);

            $result = $this->docker->images()->commit($container->getId());

            $this->assertIsArray($result);
            $this->assertArrayHasKey('Id', $result);
            $this->assertNotEmpty($result['Id']);
        }

        public function testCommitWithRepoAndTag(): void
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '10']
            ]);

            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            sleep(1);

            $tag = 'dockerlib-commit-test-' . uniqid();
            $this->testImages[] = 'dockerlib-commit:' . $tag;

            $result = $this->docker->images()->commit(
                $container->getId(),
                'dockerlib-commit',
                $tag
            );

            $this->assertIsArray($result);
            $this->assertArrayHasKey('Id', $result);

            $image = $this->docker->images()->inspect('dockerlib-commit:' . $tag);
            $this->assertInstanceOf(Image::class, $image);
            $this->assertNotNull($image->getId());
        }

        public function testCommitWithMessage(): void
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '10']
            ]);

            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            sleep(1);

            $tag = 'dockerlib-commit-msg-' . uniqid();
            $this->testImages[] = 'dockerlib-commit-msg:' . $tag;

            $result = $this->docker->images()->commit(
                $container->getId(),
                'dockerlib-commit-msg',
                $tag,
                'Test commit message',
                'DockerLib Test'
            );

            $this->assertIsArray($result);
            $this->assertArrayHasKey('Id', $result);
        }

        public function testCommitWithChanges(): void
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '10']
            ]);

            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            sleep(1);

            $tag = 'dockerlib-commit-chg-' . uniqid();
            $this->testImages[] = 'dockerlib-commit-chg:' . $tag;

            $result = $this->docker->images()->commit(
                $container->getId(),
                'dockerlib-commit-chg',
                $tag,
                'test with changes',
                null,
                true,
                'CMD ["echo", "hello"]'
            );

            $this->assertIsArray($result);
            $this->assertArrayHasKey('Id', $result);
        }

        public function testCommitPausedContainer(): void
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '30']
            ]);

            $this->testContainers[] = $container->getId();
            $this->docker->containers()->start($container->getId());

            sleep(1);

            $this->docker->containers()->pause($container->getId());
            sleep(1);

            $tag = 'dockerlib-commit-pause-' . uniqid();
            $this->testImages[] = 'dockerlib-commit-pause:' . $tag;

            try {
                $result = $this->docker->images()->commit(
                    $container->getId(),
                    'dockerlib-commit-pause',
                    $tag,
                    null,
                    null,
                    false
                );

                $this->assertIsArray($result);
                $this->assertArrayHasKey('Id', $result);
            } finally {
                try {
                    $this->docker->containers()->unpause($container->getId());
                } catch (\Exception $e) {
                }
            }
        }
    }
