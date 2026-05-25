<?php

    namespace DockerLib\Objects;

    use DockerLib\Objects\Plugin;
    use PHPUnit\Framework\TestCase;

    class PluginObjectTest extends TestCase
    {
        public function testPluginCreation()
        {
            $data = [
                'Id' => 'plugin-123',
                'Name' => 'test-plugin:latest',
                'Enabled' => true,
                'Settings' => [
                    'Mounts' => [],
                    'Env' => ['DEBUG=1'],
                    'Args' => [],
                    'Devices' => []
                ],
                'PluginReference' => 'test/plugin:latest',
                'Config' => [
                    'Description' => 'A test plugin',
                    'Documentation' => 'https://docs.example.com',
                    'Interface' => [
                        'Types' => ['docker.volumedriver/1.0']
                    ],
                    'Entrypoint' => ['/usr/bin/plugin'],
                    'WorkDir' => '/app',
                    'User' => ['uid' => '1000', 'gid' => '1000'],
                    'Network' => ['Type' => 'host'],
                    'Linux' => [
                        'Capabilities' => ['CAP_SYS_ADMIN'],
                        'AllowAllDevices' => false,
                        'Devices' => []
                    ]
                ]
            ];

            $plugin = Plugin::fromArray($data);

            $this->assertEquals('plugin-123', $plugin->getId());
            $this->assertEquals('test-plugin:latest', $plugin->getName());
            $this->assertTrue($plugin->getEnabled());
            $this->assertEquals('test/plugin:latest', $plugin->getPluginReference());
            $this->assertIsArray($plugin->getSettings());
            $this->assertIsArray($plugin->getConfig());
        }

        public function testPluginWithMinimalData()
        {
            $data = [
                'Id' => 'plugin-456',
                'Name' => 'simple-plugin',
                'Enabled' => false
            ];

            $plugin = Plugin::fromArray($data);

            $this->assertEquals('plugin-456', $plugin->getId());
            $this->assertEquals('simple-plugin', $plugin->getName());
            $this->assertFalse($plugin->getEnabled());
        }

        public function testPluginToArray()
        {
            $data = [
                'Id' => 'plugin-789',
                'Name' => 'network-plugin',
                'Enabled' => true,
                'Settings' => ['Env' => ['MODE=production']],
                'Config' => ['Description' => 'Network driver']
            ];

            $plugin = Plugin::fromArray($data);
            $array = $plugin->toArray();

            $this->assertIsArray($array);
            $this->assertEquals('plugin-789', $array['Id']);
            $this->assertEquals('network-plugin', $array['Name']);
            $this->assertTrue($array['Enabled']);
        }
    }
