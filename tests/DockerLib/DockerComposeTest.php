<?php

    namespace DockerLib;

    use DockerLib\DockerCompose;
    use DockerLib\Docker;
    use PHPUnit\Framework\TestCase;
    use LogLib2\Logger;

    class DockerComposeTest extends TestCase
    {
        private string $testDir;
        private string $composeFile;
        private Docker $docker;

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }

            $this->testDir = sys_get_temp_dir() . '/dockerlib-test-' . uniqid();
            mkdir($this->testDir);
            $this->composeFile = $this->testDir . '/docker-compose.yml';
        }

        protected function tearDown(): void
        {
            if (file_exists($this->composeFile)) {
                unlink($this->composeFile);
            }
            if (is_dir($this->testDir)) {
                rmdir($this->testDir);
            }
        }

        public function testConstructorParsesYaml()
        {
            $yaml = <<<YAML
version: '3.8'
services:
  test:
    image: alpine:latest
networks:
  testnet:
    driver: bridge
volumes:
  testvolume:
YAML;
            file_put_contents($this->composeFile, $yaml);

            $compose = new DockerCompose($this->composeFile);
            
            $this->assertInstanceOf(DockerCompose::class, $compose);
            $this->assertNotEmpty($compose->getProjectName());
            
            $config = $compose->getConfig();
            $this->assertArrayHasKey('services', $config);
            $this->assertArrayHasKey('networks', $config);
            $this->assertArrayHasKey('volumes', $config);
        }

        public function testConstructorWithLogger()
        {
            $yaml = <<<YAML
version: '3.8'
services:
  test:
    image: alpine:latest
YAML;
            file_put_contents($this->composeFile, $yaml);

            $logger = new Logger('test');
            $compose = new DockerCompose($this->composeFile, null, $logger);
            
            $this->assertInstanceOf(DockerCompose::class, $compose);
        }

        public function testConstructorThrowsExceptionForMissingFile()
        {
            $this->expectException(\DockerLib\Exceptions\DockerException::class);
            new DockerCompose('/nonexistent/docker-compose.yml');
        }

        public function testGetProjectName()
        {
            $yaml = "version: '3.8'\nservices:\n  test:\n    image: alpine:latest";
            file_put_contents($this->composeFile, $yaml);

            $compose = new DockerCompose($this->composeFile);
            $projectName = $compose->getProjectName();
            
            $this->assertNotEmpty($projectName);
            $this->assertMatchesRegularExpression('/^[a-z0-9_-]+$/', $projectName);
        }

        public function testGetConfig()
        {
            $yaml = <<<YAML
version: '3.8'
services:
  web:
    image: nginx:alpine
    ports:
      - "8080:80"
YAML;
            file_put_contents($this->composeFile, $yaml);

            $compose = new DockerCompose($this->composeFile);
            $config = $compose->getConfig();
            
            $this->assertIsArray($config);
            $this->assertArrayHasKey('services', $config);
            $this->assertArrayHasKey('web', $config['services']);
        }

        public function testGetDocker()
        {
            $yaml = "version: '3.8'\nservices:\n  test:\n    image: alpine:latest";
            file_put_contents($this->composeFile, $yaml);

            $compose = new DockerCompose($this->composeFile);
            $docker = $compose->getDocker();
            
            $this->assertInstanceOf(Docker::class, $docker);
        }

        public function testUpAndDown()
        {
            $yaml = <<<YAML
version: '3.8'
services:
  test:
    image: alpine:latest
    command: sleep 30
networks:
  testnet:
    driver: bridge
YAML;
            file_put_contents($this->composeFile, $yaml);

            $compose = new DockerCompose($this->composeFile);
            
            try {
                $result = $compose->up(false);
                
                $this->assertIsArray($result);
                $this->assertArrayHasKey('networks', $result);
                $this->assertArrayHasKey('containers', $result);
                
                sleep(2);
                
                $downResult = $compose->down(false, false);
                
                $this->assertIsArray($downResult);
                $this->assertArrayHasKey('containers', $downResult);
                $this->assertArrayHasKey('networks', $downResult);
                
            } catch (\Exception $e) {
                try {
                    $compose->down(true, false);
                } catch (\Exception $cleanupException) {
                    // Ignore cleanup errors
                }
                throw $e;
            }
        }

        public function testUpWithCallback()
        {
            $yaml = <<<YAML
version: '3.8'
services:
  test:
    image: alpine:latest
    command: sleep 10
YAML;
            file_put_contents($this->composeFile, $yaml);

            $compose = new DockerCompose($this->composeFile);
            $callbackInvoked = false;
            
            try {
                $compose->up(false, function ($event) use (&$callbackInvoked) {
                    $callbackInvoked = true;
                    $this->assertIsArray($event);
                    $this->assertArrayHasKey('step', $event);
                });
                
                $this->assertTrue($callbackInvoked);
                
                $compose->down(false, false);
                
            } catch (\Exception $e) {
                try {
                    $compose->down(true, false);
                } catch (\Exception $cleanupException) {
                }
                throw $e;
            }
        }

        public function testRemove()
        {
            $yaml = <<<YAML
version: '3.8'
services:
  test:
    image: alpine:latest
    command: sleep 5
YAML;
            file_put_contents($this->composeFile, $yaml);

            $compose = new DockerCompose($this->composeFile);
            
            try {
                $compose->up(false);
                sleep(2);
                
                $result = $compose->remove(true, false);
                
                $this->assertIsArray($result);
                
            } catch (\Exception $e) {
                try {
                    $compose->down(true, false);
                } catch (\Exception $cleanupException) {
                }
                throw $e;
            }
        }

        public function testBuildWithoutBuildConfig()
        {
            $yaml = <<<YAML
version: '3.8'
services:
  test:
    image: alpine:latest
YAML;
            file_put_contents($this->composeFile, $yaml);

            $compose = new DockerCompose($this->composeFile);
            $result = $compose->build();
            
            $this->assertIsArray($result);
            $this->assertEmpty($result);
        }

        public function testComposeWithMultipleServices()
        {
            $yaml = <<<YAML
version: '3.8'
services:
  web:
    image: nginx:alpine
    ports:
      - "8081:80"
  redis:
    image: redis:alpine
  db:
    image: postgres:alpine
    environment:
      POSTGRES_PASSWORD: test
networks:
  app:
    driver: bridge
YAML;
            file_put_contents($this->composeFile, $yaml);

            $compose = new DockerCompose($this->composeFile);
            
            try {
                $result = $compose->up(false);
                
                $this->assertArrayHasKey('containers', $result);
                $this->assertArrayHasKey('networks', $result);
                $this->assertIsArray($result['containers']);
                $this->assertIsArray($result['networks']);
                
                sleep(2);
                $compose->down(false, false);
                
            } catch (\Exception $e) {
                try {
                    $compose->down(true, false);
                } catch (\Exception $cleanupException) {
                }
                throw $e;
            }
        }

        public function testComposeWithVolumes()
        {
            $yaml = <<<YAML
version: '3.8'
services:
  app:
    image: alpine:latest
    command: sleep 10
    volumes:
      - app-data:/data
volumes:
  app-data:
    driver: local
YAML;
            file_put_contents($this->composeFile, $yaml);

            $compose = new DockerCompose($this->composeFile);
            
            try {
                $result = $compose->up(false);
                
                $this->assertArrayHasKey('volumes', $result);
                $this->assertNotEmpty($result['volumes']);
                
                sleep(2);
                $compose->down(true, false);
                
            } catch (\Exception $e) {
                try {
                    $compose->down(true, false);
                } catch (\Exception $cleanupException) {
                }
                throw $e;
            }
        }

        public function testComposeWithEnvironmentVariables()
        {
            $yaml = <<<YAML
version: '3.8'
services:
  app:
    image: alpine:latest
    command: sh -c 'sleep 5'
    environment:
      - TEST_VAR=test_value
      - ANOTHER_VAR=another_value
YAML;
            file_put_contents($this->composeFile, $yaml);

            $compose = new DockerCompose($this->composeFile);
            
            try {
                $result = $compose->up(false);
                
                $this->assertArrayHasKey('containers', $result);
                $this->assertIsArray($result['containers']);
                
                sleep(2);
                $compose->down(false, false);
                
            } catch (\Exception $e) {
                try {
                    $compose->down(true, false);
                } catch (\Exception $cleanupException) {
                }
                throw $e;
            }
        }

        public function testComposeWithRestartPolicy()
        {
            $yaml = <<<YAML
version: '3.8'
services:
  app:
    image: alpine:latest
    command: sleep 10
    restart: unless-stopped
YAML;
            file_put_contents($this->composeFile, $yaml);

            $compose = new DockerCompose($this->composeFile);
            
            try {
                $result = $compose->up(false);
                
                $this->assertNotEmpty($result['containers']);
                
                sleep(2);
                $compose->down(false, false);
                
            } catch (\Exception $e) {
                try {
                    $compose->down(true, false);
                } catch (\Exception $cleanupException) {
                }
                throw $e;
            }
        }

        public function testComposeDownWithVolumeRemoval()
        {
            $yaml = <<<YAML
version: '3.8'
services:
  app:
    image: alpine:latest
    command: sleep 5
    volumes:
      - test-vol:/data
volumes:
  test-vol:
YAML;
            file_put_contents($this->composeFile, $yaml);

            $compose = new DockerCompose($this->composeFile);
            
            try {
                $compose->up(false);
                sleep(2);
                
                $result = $compose->down(true, false);
                
                $this->assertArrayHasKey('volumes', $result);
                
            } catch (\Exception $e) {
                try {
                    $compose->down(true, false);
                } catch (\Exception $cleanupException) {
                }
                throw $e;
            }
        }

        public function testComposeWithPortMapping()
        {
            $yaml = <<<YAML
version: '3.8'
services:
  web:
    image: nginx:alpine
    ports:
      - "8082:80"
      - "8443:443"
YAML;
            file_put_contents($this->composeFile, $yaml);

            $compose = new DockerCompose($this->composeFile);
            
            try {
                $result = $compose->up(false);
                
                $this->assertIsArray($result['containers']);
                
                sleep(2);
                $compose->down(false, false);
                
            } catch (\Exception $e) {
                try {
                    $compose->down(true, false);
                } catch (\Exception $cleanupException) {
                }
                throw $e;
            }
        }

        public function testComposeWithHostname()
        {
            $yaml = <<<YAML
version: '3.8'
services:
  app:
    image: alpine:latest
    command: sleep 10
    hostname: test-hostname
YAML;
            file_put_contents($this->composeFile, $yaml);

            $compose = new DockerCompose($this->composeFile);
            
            try {
                $result = $compose->up(false);
                
                $this->assertIsArray($result['containers']);
                
                sleep(2);
                $compose->down(false, false);
                
            } catch (\Exception $e) {
                try {
                    $compose->down(true, false);
                } catch (\Exception $cleanupException) {
                }
                throw $e;
            }
        }

        public function testComposeProjectNameGeneration()
        {
            $yaml = "version: '3.8'\nservices:\n  test:\n    image: alpine:latest";
            file_put_contents($this->composeFile, $yaml);

            $compose = new DockerCompose($this->composeFile);
            $projectName = $compose->getProjectName();
            
            $this->assertNotEmpty($projectName);
            $this->assertMatchesRegularExpression('/^[a-z0-9_-]+$/', $projectName);
            $this->assertStringStartsWith('dockerlib-test-', $projectName);
        }
    }
