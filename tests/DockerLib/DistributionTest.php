<?php

    namespace DockerLib;

    use DockerLib\Docker;
    use DockerLib\Objects\Distribution;
    use PHPUnit\Framework\TestCase;

    class DistributionTest extends TestCase
    {
        private Docker $docker;

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

        public function testInspectDistribution(): void
        {
            $dist = $this->docker->distribution()->inspect('alpine:latest');

            $this->assertInstanceOf(Distribution::class, $dist);

            $descriptor = $dist->getDescriptor();
            $this->assertIsArray($descriptor);
            $this->assertNotEmpty($descriptor);

            $platforms = $dist->getPlatforms();
            $this->assertIsArray($platforms);
        }

        public function testDistributionDescriptorFields(): void
        {
            $dist = $this->docker->distribution()->inspect('alpine:latest');

            $mediaType = $dist->getMediaType();
            $this->assertNotNull($mediaType);
            $this->assertNotEmpty($mediaType);

            $digest = $dist->getDigest();
            $this->assertNotNull($digest);
            $this->assertStringStartsWith('sha256:', $digest);

            $size = $dist->getSize();
            $this->assertNotNull($size);
            $this->assertGreaterThan(0, $size);
        }

        public function testDistributionPlatforms(): void
        {
            $dist = $this->docker->distribution()->inspect('alpine:latest');

            $platforms = $dist->getPlatforms();
            $this->assertIsArray($platforms);

            if (!empty($platforms)) {
                foreach ($platforms as $platform) {
                    $this->assertArrayHasKey('architecture', $platform);
                    $this->assertArrayHasKey('os', $platform);
                }
            }
        }

        public function testDistributionToArray(): void
        {
            $dist = $this->docker->distribution()->inspect('alpine:latest');

            $array = $dist->toArray();

            $this->assertIsArray($array);
            $this->assertArrayHasKey('Descriptor', $array);
            $this->assertArrayHasKey('Platforms', $array);
        }

        public function testDistributionFromArray(): void
        {
            $data = [
                'Descriptor' => [
                    'MediaType' => 'application/test',
                    'Digest' => 'sha256:test',
                    'Size' => 1234
                ],
                'Platforms' => [
                    ['Architecture' => 'amd64', 'OS' => 'linux']
                ]
            ];

            $dist = Distribution::fromArray($data);

            $this->assertEquals('application/test', $dist->getMediaType());
            $this->assertEquals('sha256:test', $dist->getDigest());
            $this->assertEquals(1234, $dist->getSize());
            $this->assertCount(1, $dist->getPlatforms());
        }

        public function testDistributionInspectNonExistentImage(): void
        {
            $this->expectException(\DockerLib\Exceptions\ResponseException::class);
            $this->docker->distribution()->inspect('nonexistent-image-' . uniqid() . ':latest');
        }
    }
