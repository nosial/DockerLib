<?php

    namespace DockerLib;

    use DockerLib\DockerCompose;
    use DockerLib\Docker;
    use PHPUnit\Framework\TestCase;

    class DockerComposeExtendedTest extends TestCase
    {
        private Docker $docker;

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }
        }

        public function testDockerComposeWithNonExistentFile()
        {
            $this->expectException(\DockerLib\Exceptions\DockerException::class);
            new DockerCompose('/nonexistent/docker-compose.yml');
        }

        public function testDockerComposeGetProjectName()
        {
            // Create a temporary compose file
            $tmpDir = sys_get_temp_dir() . '/dockerlib-test-' . uniqid();
            mkdir($tmpDir);
            $composeFile = $tmpDir . '/docker-compose.yml';
            
            file_put_contents($composeFile, <<<YAML
version: '3'
services:
  test:
    image: alpine:latest
    command: sleep 10
YAML
            );
            
            try {
                $compose = new DockerCompose($composeFile);
                $projectName = $compose->getProjectName();
                
                $this->assertNotEmpty($projectName);
                $this->assertMatchesRegularExpression('/^[a-z0-9_-]+$/', $projectName);
            } finally {
                unlink($composeFile);
                rmdir($tmpDir);
            }
        }

        public function testDockerComposeGetConfig()
        {
            // Create a temporary compose file
            $tmpDir = sys_get_temp_dir() . '/dockerlib-test-' . uniqid();
            mkdir($tmpDir);
            $composeFile = $tmpDir . '/docker-compose.yml';
            
            file_put_contents($composeFile, <<<YAML
version: '3'
services:
  web:
    image: nginx:latest
networks:
  frontend:
    driver: bridge
volumes:
  data:
    driver: local
YAML
            );
            
            try {
                $compose = new DockerCompose($composeFile);
                $config = $compose->getConfig();
                
                $this->assertIsArray($config);
                $this->assertArrayHasKey('services', $config);
                $this->assertArrayHasKey('networks', $config);
                $this->assertArrayHasKey('volumes', $config);
                $this->assertArrayHasKey('web', $config['services']);
            } finally {
                unlink($composeFile);
                rmdir($tmpDir);
            }
        }

        public function testDockerComposeInvalidYaml()
        {
            $tmpDir = sys_get_temp_dir() . '/dockerlib-test-' . uniqid();
            mkdir($tmpDir);
            $composeFile = $tmpDir . '/docker-compose.yml';
            
            // Write invalid YAML
            file_put_contents($composeFile, "invalid: yaml: content:\n  - broken");
            
            try {
                $this->expectException(\Exception::class);
                new DockerCompose($composeFile);
            } finally {
                unlink($composeFile);
                rmdir($tmpDir);
            }
        }

        public function testDockerComposeCustomDocker()
        {
            $tmpDir = sys_get_temp_dir() . '/dockerlib-test-' . uniqid();
            mkdir($tmpDir);
            $composeFile = $tmpDir . '/docker-compose.yml';
            
            file_put_contents($composeFile, <<<YAML
version: '3'
services:
  test:
    image: alpine:latest
YAML
            );
            
            try {
                $customDocker = new Docker();
                $compose = new DockerCompose($composeFile, $customDocker);
                
                $this->assertInstanceOf(DockerCompose::class, $compose);
            } finally {
                unlink($composeFile);
                rmdir($tmpDir);
            }
        }
    }
