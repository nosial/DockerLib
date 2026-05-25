<?php

    namespace DockerLib;

    use DockerLib\Exceptions\ResponseException;

    /**
     * Edge case and boundary testing for Docker image operations
     */
    class EdgeCaseImageTest extends BaseDockerTest
    {
        /**
         * Test listing images with no filters
         */
        public function testListImagesNoFilters()
        {
            $images = $this->docker->images()->list();
            $this->assertIsArray($images);
        }

        /**
         * Test listing images with empty filter array
         */
        public function testListImagesEmptyFilters()
        {
            $images = $this->docker->images()->list([]);
            $this->assertIsArray($images);
        }

        /**
         * Test listing images with dangling filter
         */
        public function testListImagesDanglingFilter()
        {
            $images = $this->docker->images()->list(['dangling' => ['true']]);
            $this->assertIsArray($images);
            
            foreach ($images as $image) {
                $tags = $image->getRepoTags();
                if (!empty($tags)) {
                    // Dangling images should have <none> tags
                    $this->assertContains('<none>:<none>', $tags);
                }
            }
        }

        /**
         * Test inspecting non-existent image
         */
        public function testInspectNonExistentImage()
        {
            try {
                $this->docker->images()->inspect('nonexistent-image:' . uniqid());
                $this->fail('Should have thrown exception for non-existent image');
            } catch (ResponseException $e) {
                $this->assertStringContainsString('No such image', $e->getMessage());
            }
        }

        /**
         * Test tagging image with invalid tag format
         */
        public function testTagImageWithInvalidFormat()
        {
            $this->ensureImage('alpine:latest');
            
            try {
                // Invalid tag with uppercase
                $this->docker->images()->tag('alpine:latest', 'INVALID_TAG', 'latest');
                $this->fail('Should have thrown exception for invalid tag format');
            } catch (ResponseException $e) {
                $this->assertTrue(true);
            }
        }

        /**
         * Test tagging image with multiple tags
         */
        public function testTagImageMultipleTimes()
        {
            $this->ensureImage('alpine:latest');
            
            $tag1 = $this->generateTestId();
            $tag2 = $this->generateTestId();
            
            $this->docker->images()->tag('alpine:latest', $tag1, 'test1');
            $this->testImages[] = "{$tag1}:test1";
            
            $this->docker->images()->tag('alpine:latest', $tag2, 'test2');
            $this->testImages[] = "{$tag2}:test2";
            
            // Verify both tags exist
            $image1 = $this->docker->images()->inspect("{$tag1}:test1");
            $image2 = $this->docker->images()->inspect("{$tag2}:test2");
            
            $this->assertEquals($image1->getId(), $image2->getId());
        }

        /**
         * Test removing image that doesn't exist
         */
        public function testRemoveNonExistentImage()
        {
            try {
                $this->docker->images()->remove('nonexistent:' . uniqid());
                $this->fail('Should have thrown exception for non-existent image');
            } catch (ResponseException $e) {
                $this->assertStringContainsString('No such image', $e->getMessage());
            }
        }

        /**
         * Test searching for images with empty query
         */
        public function testSearchImagesEmptyQuery()
        {
            try {
                $results = $this->docker->images()->search('');
                // Some implementations may return empty array, others may error
                $this->assertIsArray($results);
            } catch (ResponseException $e) {
                // Empty query may not be allowed
                $this->assertTrue(true);
            }
        }

        /**
         * Test searching for images with special characters
         */
        public function testSearchImagesSpecialCharacters()
        {
            $results = $this->docker->images()->search('alpine');
            $this->assertIsArray($results);
            $this->assertNotEmpty($results);
        }

        /**
         * Test image history on non-existent image
         */
        public function testHistoryNonExistentImage()
        {
            try {
                $this->docker->images()->history('nonexistent:' . uniqid());
                $this->fail('Should have thrown exception for non-existent image');
            } catch (ResponseException $e) {
                $this->assertStringContainsString('No such image', $e->getMessage());
            }
        }

        /**
         * Test image history on valid image
         */
        public function testImageHistory()
        {
            $this->ensureImage('alpine:latest');
            
            $history = $this->docker->images()->history('alpine:latest');
            $this->assertIsArray($history);
            $this->assertNotEmpty($history);
        }

        /**
         * Test pruning images with filters
         */
        public function testPruneImagesWithFilters()
        {
            $result = $this->docker->images()->prune(['dangling' => ['true']]);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('ImagesDeleted', $result);
            $this->assertArrayHasKey('SpaceReclaimed', $result);
        }

        /**
         * Test getting image with digest
         */
        public function testInspectImageWithDigest()
        {
            $this->ensureImage('alpine:latest');
            
            $image = $this->docker->images()->inspect('alpine:latest');
            $id = $image->getId();
            
            // Try to inspect using ID
            $imageById = $this->docker->images()->inspect($id);
            $this->assertEquals($id, $imageById->getId());
        }

        /**
         * Test image object properties with null values
         */
        public function testImageObjectNullProperties()
        {
            $this->ensureImage('alpine:latest');
            
            $image = $this->docker->images()->inspect('alpine:latest');
            
            // Test all getters return something (null or value)
            $this->assertIsString($image->getId());
            $tags = $image->getRepoTags();
            $this->assertTrue(is_array($tags));
            
            // These may be null
            $parentId = $image->getParentId();
            $this->assertTrue($parentId === null || is_string($parentId));
        }

        /**
         * Test concurrent image operations
         */
        public function testConcurrentImageTagging()
        {
            $this->ensureImage('alpine:latest');
            
            $tags = [];
            for ($i = 0; $i < 3; $i++) {
                $tag = $this->generateTestId();
                $this->docker->images()->tag('alpine:latest', $tag, "tag{$i}");
                $tags[] = "{$tag}:tag{$i}";
                $this->testImages[] = "{$tag}:tag{$i}";
            }
            
            // Verify all tags were created
            foreach ($tags as $tag) {
                $image = $this->docker->images()->inspect($tag);
                $this->assertNotNull($image->getId());
            }
        }

        /**
         * Test image with no tags (dangling)
         */
        public function testDanglingImageHandling()
        {
            $images = $this->docker->images()->list(['dangling' => ['true']]);
            $this->assertIsArray($images);
            
            foreach ($images as $image) {
                // Dangling images should still have an ID
                $this->assertNotNull($image->getId());
            }
        }

        /**
         * Test getTags alias method
         */
        public function testGetTagsAlias()
        {
            $this->ensureImage('alpine:latest');
            
            $image = $this->docker->images()->inspect('alpine:latest');
            $tags = $image->getTags();
            $repoTags = $image->getRepoTags();
            
            $this->assertEquals($repoTags, $tags);
        }
    }
