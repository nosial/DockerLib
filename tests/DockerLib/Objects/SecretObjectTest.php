<?php

    namespace DockerLib\Objects;

    use DockerLib\Objects\Secret;
    use PHPUnit\Framework\TestCase;

    class SecretObjectTest extends TestCase
    {
        public function testSecretCreation()
        {
            $data = [
                'ID' => 'secret-123abc',
                'Version' => ['Index' => 15],
                'CreatedAt' => '2024-01-01T12:00:00.000000000Z',
                'UpdatedAt' => '2024-01-01T12:00:00.000000000Z',
                'Spec' => [
                    'Name' => 'my-secret',
                    'Labels' => ['type' => 'password']
                ]
            ];

            $secret = Secret::fromArray($data);

            $this->assertEquals('secret-123abc', $secret->getId());
            $this->assertIsArray($secret->getVersion());
            $this->assertEquals(15, $secret->getVersion()['Index']);
            $this->assertEquals('2024-01-01T12:00:00.000000000Z', $secret->getCreatedAt());
            $this->assertEquals('2024-01-01T12:00:00.000000000Z', $secret->getUpdatedAt());
            $this->assertEquals('my-secret', $secret->getName());
        }

        public function testSecretWithLabels()
        {
            $data = [
                'ID' => 'secret-456',
                'Spec' => [
                    'Name' => 'db-password',
                    'Labels' => [
                        'service' => 'database',
                        'encrypted' => 'true'
                    ]
                ]
            ];

            $secret = Secret::fromArray($data);
            $spec = $secret->getSpec();

            $this->assertArrayHasKey('Labels', $spec);
            $this->assertEquals('database', $spec['Labels']['service']);
        }

        public function testSecretToArray()
        {
            $data = [
                'ID' => 'secret-789',
                'Version' => ['Index' => 20],
                'Spec' => ['Name' => 'api-key']
            ];

            $secret = Secret::fromArray($data);
            $array = $secret->toArray();

            $this->assertIsArray($array);
            $this->assertEquals('secret-789', $array['ID']);
            $this->assertEquals('api-key', $secret->getName());
        }
    }
