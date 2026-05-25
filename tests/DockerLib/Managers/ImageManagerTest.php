<?php

    namespace DockerLib\Managers;

    use DockerLib\Docker;
    use DockerLib\Objects\Image;
    use PHPUnit\Framework\TestCase;

    class ImageManagerTest extends TestCase
    {
        private Docker $docker;
        private array $testImages = [];
        private const TEST_IMAGE = 'alpine:latest';

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }
        }

        protected function tearDown(): void
        {
            // Clean up test images (but keep alpine:latest as it's commonly used)
            foreach ($this->testImages as $imageId) {
                try {
                    $this->docker->images()->remove($imageId, true, false);
                } catch (\Exception $e) {
                    // Image might be in use or already removed
                }
            }
            
            $this->testImages = [];
        }

        public function testListImages()
        {
            $images = $this->docker->images()->list();
            
            $this->assertIsArray($images);
            
            foreach ($images as $image) {
                $this->assertInstanceOf(Image::class, $image);
                $this->assertNotNull($image->getId());
            }
        }

        public function testInspectImage()
        {
            // Ensure alpine image exists
            $this->ensureImageExists(self::TEST_IMAGE);
            
            $image = $this->docker->images()->inspect(self::TEST_IMAGE);
            
            $this->assertInstanceOf(Image::class, $image);
            $this->assertNotNull($image->getId());
            $this->assertNotEmpty($image->getRepoTags());
            $this->assertGreaterThan(0, $image->getSize());
        }

        public function testPullImage()
        {
            // Try to remove the image first to test pull
            try {
                $this->docker->images()->remove(self::TEST_IMAGE, false, false);
            } catch (\Exception $e) {
                // Image might be in use
            }
            
            $stream = $this->docker->images()->pull('alpine', 'latest');
            
            $pullCompleted = false;
            while (($line = $stream->readLine()) !== null) {
                $data = json_decode($line, true);
                if (isset($data['status'])) {
                    if (strpos($data['status'], 'Downloaded') !== false || 
                        strpos($data['status'], 'Image is up to date') !== false ||
                        strpos($data['status'], 'Already exists') !== false) {
                        $pullCompleted = true;
                    }
                }
            }
            
            $stream->close();
            
            // Verify image exists
            $image = $this->docker->images()->inspect('alpine:latest');
            $this->assertNotNull($image->getId());
        }

        public function testTagImage()
        {
            $this->ensureImageExists(self::TEST_IMAGE);
            
            $newTag = 'dockerlib-test-tag-' . uniqid();
            
            $this->docker->images()->tag(self::TEST_IMAGE, 'alpine', $newTag);
            
            // Verify tag exists
            $image = $this->docker->images()->inspect("alpine:$newTag");
            $this->assertNotNull($image->getId());
            
            // Add to cleanup list
            $this->testImages[] = "alpine:$newTag";
        }

        public function testImageHistory()
        {
            $this->ensureImageExists(self::TEST_IMAGE);
            
            $history = $this->docker->images()->history(self::TEST_IMAGE);
            
            $this->assertIsArray($history);
            $this->assertNotEmpty($history);
            
            foreach ($history as $layer) {
                $this->assertArrayHasKey('Id', $layer);
                $this->assertArrayHasKey('Created', $layer);
            }
        }

        public function testSearchImages()
        {
            $results = $this->docker->images()->search('alpine', 5);
            
            $this->assertIsArray($results);
            $this->assertNotEmpty($results);
            $this->assertLessThanOrEqual(5, count($results));
            
            foreach ($results as $result) {
                $this->assertArrayHasKey('name', $result);
                $this->assertArrayHasKey('description', $result);
            }
        }

        public function testListAllImages()
        {
            $images = $this->docker->images()->list([], true);
            
            $this->assertIsArray($images);
        }

        public function testListImagesWithFilters()
        {
            $this->ensureImageExists(self::TEST_IMAGE);
            
            $images = $this->docker->images()->list([
                'reference' => ['alpine:latest']
            ]);
            
            $this->assertIsArray($images);
            
            $found = false;
            foreach ($images as $image) {
                $tags = $image->getRepoTags();
                if (in_array('alpine:latest', $tags)) {
                    $found = true;
                    break;
                }
            }
            
            $this->assertTrue($found, 'alpine:latest should be in filtered results');
        }

        public function testTagImageWithCustomRepo()
        {
            $this->ensureImageExists(self::TEST_IMAGE);
            
            $newTag = 'dockerlib-test-tag-' . uniqid();
            $this->docker->images()->tag('alpine:latest', $newTag, 'test');
            
            try {
                $image = $this->docker->images()->inspect($newTag . ':test');
                $this->assertInstanceOf(Image::class, $image);
                
                $tags = $image->getRepoTags();
                $this->assertContains($newTag . ':test', $tags);
                
            } finally {
                try {
                    $this->docker->images()->remove($newTag . ':test', true);
                } catch (\Exception $e) {
                    // Ignore
                }
            }
        }

        public function testSearchImagesLimit()
        {
            $results = $this->docker->images()->search('alpine', 5);
            
            $this->assertIsArray($results);
            $this->assertNotEmpty($results);
            $this->assertLessThanOrEqual(5, count($results));
            
            foreach ($results as $result) {
                $this->assertArrayHasKey('name', $result);
                $this->assertStringContainsString('alpine', strtolower($result['name']));
            }
        }

        public function testSearchImagesWithFilters()
        {
            $results = $this->docker->images()->search('alpine', 5, ['is-official' => ['true']]);
            
            $this->assertIsArray($results);
        }

        public function testPruneImages()
        {
            $result = $this->docker->images()->prune();
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('ImagesDeleted', $result);
            $this->assertArrayHasKey('SpaceReclaimed', $result);
        }

        public function testImageHistoryLayers()
        {
            $this->ensureImageExists(self::TEST_IMAGE);
            
            $history = $this->docker->images()->history('alpine:latest');
            
            $this->assertIsArray($history);
            $this->assertNotEmpty($history);
            
            foreach ($history as $layer) {
                $this->assertIsArray($layer);
                $this->assertArrayHasKey('Id', $layer);
            }
        }

        public function testRemoveNonExistentImage()
        {
            $this->expectException(\DockerLib\Exceptions\ResponseException::class);
            $this->docker->images()->remove('nonexistent-image-' . uniqid(), false);
        }

        public function testInspectNonExistentImage()
        {
            $this->expectException(\DockerLib\Exceptions\ResponseException::class);
            $this->docker->images()->inspect('nonexistent:tag');
        }

        public function testListAllImagesIncludingIntermediate()
        {
            $allImages = $this->docker->images()->list([], true);
            $normalImages = $this->docker->images()->list([], false);
            
            $this->assertGreaterThanOrEqual(count($normalImages), count($allImages));
        }

        public function testListImagesWithDigests()
        {
            $images = $this->docker->images()->list([], false, true);
            
            $this->assertIsArray($images);
            
            foreach ($images as $image) {
                $digests = $image->getRepoDigests();
                if (!empty($digests)) {
                    $this->assertIsArray($digests);
                }
            }
        }

        public function testExportSingleImage()
        {
            $this->ensureImageExists(self::TEST_IMAGE);

            $tarData = $this->docker->images()->export(self::TEST_IMAGE);

            $this->assertNotEmpty($tarData);
            $this->assertIsString($tarData);
        }

        public function testExportAllImages()
        {
            $this->ensureImageExists(self::TEST_IMAGE);

            $tarData = $this->docker->images()->exportAll([self::TEST_IMAGE]);

            $this->assertNotEmpty($tarData);
            $this->assertIsString($tarData);
        }

        public function testRemoveImageByTag()
        {
            $this->ensureImageExists(self::TEST_IMAGE);

            $tagName = 'dockerlib-remove-tag-' . uniqid();
            $this->docker->images()->tag(self::TEST_IMAGE, 'alpine', $tagName);

            $this->docker->images()->remove("alpine:$tagName", false, false);

            $this->expectException(\DockerLib\Exceptions\ResponseException::class);
            $this->docker->images()->inspect("alpine:$tagName");
        }

        private function ensureImageExists(string $image)
        {
            try {
                $this->docker->images()->inspect($image);
            } catch (\Exception $e) {
                // Image doesn't exist, pull it
                list($name, $tag) = explode(':', $image);
                $stream = $this->docker->images()->pull($name, $tag);
                while ($stream->readLine() !== null) {
                    // Consume stream
                }
                $stream->close();
            }
        }
    }
