<?php

    namespace DockerLib\Objects;

    use DockerLib\Objects\Image;
    use PHPUnit\Framework\TestCase;

    class ImageObjectTest extends TestCase
    {
        public function testImageObjectCreation()
        {
            $data = [
                'Id' => 'sha256:abc123def456',
                'RepoTags' => ['alpine:latest', 'alpine:3.18'],
                'RepoDigests' => ['alpine@sha256:xyz789'],
                'Size' => 7340032,
                'VirtualSize' => 7340032,
                'Created' => 1234567890,
                'Labels' => ['maintainer' => 'test'],
                'Architecture' => 'amd64',
                'Os' => 'linux'
            ];
            
            $image = new Image($data);
            
            $this->assertEquals('sha256:abc123def456', $image->getId());
            $this->assertIsArray($image->getRepoTags());
            $this->assertCount(2, $image->getRepoTags());
            $this->assertContains('alpine:latest', $image->getRepoTags());
            $this->assertEquals(7340032, $image->getSize());
            $this->assertEquals(1234567890, $image->getCreated());
            $this->assertEquals('amd64', $image->getArchitecture());
            $this->assertEquals('linux', $image->getOs());
        }

        public function testImageWithoutTags()
        {
            $data = [
                'Id' => 'sha256:untagged',
                'RepoTags' => null
            ];
            
            $image = new Image($data);
            
            $this->assertIsArray($image->getRepoTags());
            $this->assertEmpty($image->getRepoTags());
        }
    }
