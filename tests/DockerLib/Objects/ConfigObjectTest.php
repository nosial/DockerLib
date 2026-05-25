<?php

    namespace DockerLib\Objects;

    use DockerLib\Objects\Config;
    use PHPUnit\Framework\TestCase;

    class ConfigObjectTest extends TestCase
    {
        public function testConfigCreation()
        {
            $data = [
                'ID' => 'config-123abc',
                'Version' => ['Index' => 10],
                'CreatedAt' => '2024-01-01T00:00:00.000000000Z',
                'UpdatedAt' => '2024-01-01T00:00:00.000000000Z',
                'Spec' => [
                    'Name' => 'my-config',
                    'Labels' => ['env' => 'production'],
                    'Data' => base64_encode('config data here')
                ]
            ];

            $config = Config::fromArray($data);

            $this->assertEquals('config-123abc', $config->getId());
            $this->assertIsArray($config->getVersion());
            $this->assertEquals(10, $config->getVersion()['Index']);
            $this->assertEquals('2024-01-01T00:00:00.000000000Z', $config->getCreatedAt());
            $this->assertEquals('2024-01-01T00:00:00.000000000Z', $config->getUpdatedAt());
            $this->assertIsArray($config->getSpec());
            $this->assertEquals('my-config', $config->getName());
        }

        public function testConfigWithLabels()
        {
            $data = [
                'ID' => 'config-456',
                'Spec' => [
                    'Name' => 'labeled-config',
                    'Labels' => [
                        'env' => 'staging',
                        'team' => 'devops',
                        'version' => '1.0'
                    ]
                ]
            ];

            $config = Config::fromArray($data);
            $spec = $config->getSpec();

            $this->assertArrayHasKey('Labels', $spec);
            $this->assertEquals('staging', $spec['Labels']['env']);
            $this->assertEquals('devops', $spec['Labels']['team']);
        }

        public function testConfigToArray()
        {
            $data = [
                'ID' => 'config-789',
                'Version' => ['Index' => 5],
                'Spec' => ['Name' => 'test-config']
            ];

            $config = Config::fromArray($data);
            $array = $config->toArray();

            $this->assertIsArray($array);
            $this->assertEquals('config-789', $array['ID']);
            $this->assertArrayHasKey('Version', $array);
        }
    }
