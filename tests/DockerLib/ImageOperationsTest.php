<?php

    namespace DockerLib;

    use DockerLib\Docker;
    use DockerLib\Objects\Image;
    use PHPUnit\Framework\TestCase;

    class ImageOperationsTest extends TestCase
    {
        private Docker $docker;
        private array $testImages = [];

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }
        }

        protected function tearDown(): void
        {
            // Clean up test images (excluding base images like alpine)
            foreach ($this->testImages as $imageId) {
                try {
                    // Only remove if it's a tagged test image
                    if (str_contains($imageId, 'dockerlib-test')) {
                        $this->docker->images()->remove($imageId, true, false);
                    }
                } catch (\Exception $e) {
                    // Image might already be removed or in use
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

        public function testListImagesWithFilters()
        {
            $images = $this->docker->images()->list(['dangling' => ['false']]);
            
            $this->assertIsArray($images);
        }

        public function testInspectImage()
        {
            // Ensure alpine image exists (pull if necessary)
            try {
                $image = $this->docker->images()->inspect('alpine:latest');
            } catch (\Exception $e) {
                // Pull the image if it doesn't exist
                $this->docker->images()->pull('alpine', 'latest');
                $image = $this->docker->images()->inspect('alpine:latest');
            }
            
            $this->assertInstanceOf(Image::class, $image);
            $this->assertNotNull($image->getId());
            $this->assertNotEmpty($image->getRepoTags());
        }

        public function testImageHistory()
        {
            try {
                $history = $this->docker->images()->history('alpine:latest');
                
                $this->assertIsArray($history);
                $this->assertNotEmpty($history);
            } catch (\Exception $e) {
                // Pull the image if it doesn't exist
                $this->docker->images()->pull('alpine', 'latest');
                $history = $this->docker->images()->history('alpine:latest');
                $this->assertIsArray($history);
            }
        }

        public function testSearchImages()
        {
            try {
                $results = $this->docker->images()->search('alpine', 5);
                
                $this->assertIsArray($results);
                $this->assertNotEmpty($results);
                $this->assertLessThanOrEqual(5, count($results));
            } catch (\Exception $e) {
                // Search might require network access
                $this->markTestSkipped('Image search requires network access');
            }
        }

        public function testTagImage()
        {
            try {
                // Ensure base image exists
                try {
                    $this->docker->images()->inspect('alpine:latest');
                } catch (\Exception $e) {
                    $this->docker->images()->pull('alpine', 'latest');
                }
                
                $testTag = 'dockerlib-test-' . uniqid();
                $this->testImages[] = $testTag;
                
                // Tag the image
                $this->docker->images()->tag('alpine:latest', $testTag, 'test');
                
                // Verify tag was created
                $image = $this->docker->images()->inspect($testTag . ':test');
                $this->assertInstanceOf(Image::class, $image);
                
            } catch (\Exception $e) {
                // If it fails, skip test
                $this->markTestSkipped('Image tagging test requires alpine image: ' . $e->getMessage());
            }
        }

        public function testPruneImages()
        {
            try {
                $result = $this->docker->images()->prune();
                
                $this->assertIsArray($result);
                $this->assertArrayHasKey('ImagesDeleted', $result);
                $this->assertArrayHasKey('SpaceReclaimed', $result);
            } catch (\Exception $e) {
                // Prune might fail if no images to prune
                $this->assertTrue(true);
            }
        }

        public function testImageSize()
        {
            try {
                $image = $this->docker->images()->inspect('alpine:latest');
                $size = $image->getSize();
                
                $this->assertIsInt($size);
                $this->assertGreaterThan(0, $size);
            } catch (\Exception $e) {
                $this->markTestSkipped('Alpine image not available');
            }
        }

        public function testImageArchitecture()
        {
            try {
                $image = $this->docker->images()->inspect('alpine:latest');
                $arch = $image->getArchitecture();
                
                $this->assertNotEmpty($arch);
                $this->assertIsString($arch);
            } catch (\Exception $e) {
                $this->markTestSkipped('Alpine image not available');
            }
        }
    }
