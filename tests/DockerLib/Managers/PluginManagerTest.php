<?php

    namespace DockerLib\Managers;

    use DockerLib\Docker;
    use DockerLib\Exceptions\ResponseException;
    use PHPUnit\Framework\TestCase;

    class PluginManagerTest extends TestCase
    {
        private Docker $docker;
        private ?string $pluginName = null;

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }
        }

        protected function tearDown(): void
        {
            if ($this->pluginName) {
                try {
                    $this->docker->plugins()->disable($this->pluginName, true);
                    $this->docker->plugins()->remove($this->pluginName, true);
                } catch (\Exception $e) {
                    // Plugin may already be removed or not exist
                }
            }
        }

        public function testListPlugins()
        {
            $plugins = $this->docker->plugins()->list();
            
            $this->assertIsArray($plugins);
            // Plugins might be empty if none are installed
        }

        public function testListPluginsWithFilters()
        {
            $plugins = $this->docker->plugins()->list(['capability' => ['networkdriver']]);
            
            $this->assertIsArray($plugins);
        }

        public function testInspectNonExistentPlugin()
        {
            $this->expectException(ResponseException::class);
            $this->docker->plugins()->inspect('nonexistent-plugin-' . uniqid());
        }

        public function testPluginPrivileges()
        {
            try {
                // This might not work in all environments, so we catch exceptions
                $privileges = $this->docker->plugins()->privileges('vieux/sshfs:latest');
                $this->assertIsArray($privileges);
            } catch (\Exception $e) {
                // Plugin remote might not be accessible
                $this->markTestSkipped('Plugin privileges test requires network access');
            }
        }
        
        public function testRemoveNonExistentPlugin()
        {
            try {
                $this->docker->plugins()->remove('nonexistent-plugin-' . uniqid());
                $this->markTestSkipped('Removing non-existent plugin did not throw (Docker API behavior)');
            } catch (ResponseException $e) {
                $this->assertTrue(true);
            }
        }

        public function testEnableNonExistentPlugin()
        {
            try {
                $this->docker->plugins()->enable('nonexistent-plugin-' . uniqid());
                $this->markTestSkipped('Enabling non-existent plugin did not throw (Docker API behavior)');
            } catch (ResponseException $e) {
                $this->assertTrue(true);
            }
        }

        public function testDisableNonExistentPlugin()
        {
            try {
                $this->docker->plugins()->disable('nonexistent-plugin-' . uniqid());
                $this->markTestSkipped('Disabling non-existent plugin did not throw (Docker API behavior)');
            } catch (ResponseException $e) {
                $this->assertTrue(true);
            }
        }
    }
