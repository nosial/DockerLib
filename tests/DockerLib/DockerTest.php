<?php

    namespace DockerLib;

    use DockerLib\Docker;
    use DockerLib\Managers\ConfigManager;
    use DockerLib\Managers\ContainerManager;
    use DockerLib\Managers\ExecManager;
    use DockerLib\Managers\ImageManager;
    use DockerLib\Managers\NetworkManager;
    use DockerLib\Managers\NodeManager;
    use DockerLib\Managers\PluginManager;
    use DockerLib\Managers\SecretManager;
    use DockerLib\Managers\ServiceManager;
    use DockerLib\Managers\SwarmManager;
    use DockerLib\Managers\SystemManager;
    use DockerLib\Managers\TaskManager;
    use DockerLib\Managers\VolumeManager;
    use PHPUnit\Framework\TestCase;

    class DockerTest extends TestCase
    {
        private Docker $docker;

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }
        }

        public function testDockerInstantiation()
        {
            $this->assertInstanceOf(Docker::class, $this->docker);
        }

        public function testContainersManager()
        {
            $manager = $this->docker->containers();
            $this->assertInstanceOf(ContainerManager::class, $manager);
        }

        public function testImagesManager()
        {
            $manager = $this->docker->images();
            $this->assertInstanceOf(ImageManager::class, $manager);
        }

        public function testNetworksManager()
        {
            $manager = $this->docker->networks();
            $this->assertInstanceOf(NetworkManager::class, $manager);
        }

        public function testVolumesManager()
        {
            $manager = $this->docker->volumes();
            $this->assertInstanceOf(VolumeManager::class, $manager);
        }

        public function testSystemManager()
        {
            $manager = $this->docker->system();
            $this->assertInstanceOf(SystemManager::class, $manager);
        }

        public function testExecManager()
        {
            $manager = $this->docker->exec();
            $this->assertInstanceOf(ExecManager::class, $manager);
        }

        public function testSwarmManager()
        {
            $manager = $this->docker->swarm();
            $this->assertInstanceOf(SwarmManager::class, $manager);
        }

        public function testServicesManager()
        {
            $manager = $this->docker->services();
            $this->assertInstanceOf(ServiceManager::class, $manager);
        }

        public function testNodesManager()
        {
            $manager = $this->docker->nodes();
            $this->assertInstanceOf(NodeManager::class, $manager);
        }

        public function testSecretsManager()
        {
            $manager = $this->docker->secrets();
            $this->assertInstanceOf(SecretManager::class, $manager);
        }

        public function testConfigsManager()
        {
            $manager = $this->docker->configs();
            $this->assertInstanceOf(ConfigManager::class, $manager);
        }

        public function testPluginsManager()
        {
            $manager = $this->docker->plugins();
            $this->assertInstanceOf(PluginManager::class, $manager);
        }

        public function testTasksManager()
        {
            $manager = $this->docker->tasks();
            $this->assertInstanceOf(TaskManager::class, $manager);
        }

        public function testPing()
        {
            $result = $this->docker->ping();
            $this->assertTrue($result);
        }

        public function testVersion()
        {
            $version = $this->docker->version();
            
            $this->assertIsArray($version);
            $this->assertArrayHasKey('data', $version);
            $this->assertArrayHasKey('Version', $version['data']);
            $this->assertArrayHasKey('ApiVersion', $version['data']);
        }

        public function testCustomSocketPath()
        {
            $docker = new Docker('/var/run/docker.sock');
            $this->assertTrue($docker->ping());
        }

        public function testManagersSingleton()
        {
            // Managers should return the same instance
            $this->assertSame($this->docker->containers(), $this->docker->containers());
            $this->assertSame($this->docker->images(), $this->docker->images());
            $this->assertSame($this->docker->networks(), $this->docker->networks());
        }
    }
