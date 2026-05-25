<?php

    namespace DockerLib\Managers;

    use DockerLib\Docker;
    use DockerLib\Objects\ExecInstance;
    use PHPUnit\Framework\TestCase;

    class ExecManagerTest extends TestCase
    {
        private Docker $docker;
        private array $testContainers = [];

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }
        }

        protected function tearDown(): void
        {
            // Clean up all test containers
            foreach ($this->testContainers as $containerId) {
                try {
                    $this->docker->containers()->stop($containerId, 1);
                    $this->docker->containers()->remove($containerId, true, true);
                } catch (\Exception $e) {
                    // Continue cleanup
                }
            }
            
            $this->testContainers = [];
        }

        private function createTestContainer(): string
        {
            $container = $this->docker->containers()->create([
                'Image' => 'alpine:latest',
                'Cmd' => ['sleep', '120'],
                'Tty' => true,
                'OpenStdin' => true
            ], 'dockerlib-exec-' . uniqid());
            
            $containerId = $container->getId();
            $this->testContainers[] = $containerId;
            
            $this->docker->containers()->start($containerId);
            sleep(1); // Wait for container to be ready
            
            return $containerId;
        }

        public function testCreateExec()
        {
            $containerId = $this->createTestContainer();
            
            $exec = $this->docker->exec()->create($containerId, [
                'Cmd' => ['echo', 'hello'],
                'AttachStdout' => true,
                'AttachStderr' => true
            ]);
            
            $this->assertInstanceOf(ExecInstance::class, $exec);
            $this->assertNotNull($exec->getId());
        }

        public function testExecCommand()
        {
            $containerId = $this->createTestContainer();
            
            $exec = $this->docker->exec()->create($containerId, [
                'Cmd' => ['echo', 'Hello from Docker'],
                'AttachStdout' => true,
                'AttachStderr' => true
            ]);
            
            $stream = $this->docker->exec()->start($exec->getId());
            $output = $stream->readAll();
            $stream->close();
            
            $this->assertStringContainsString('Hello', $output);
        }

        public function testExecInspect()
        {
            $containerId = $this->createTestContainer();
            
            $exec = $this->docker->exec()->create($containerId, [
                'Cmd' => ['sleep', '2'],
                'AttachStdout' => true
            ]);
            
            $inspected = $this->docker->exec()->inspect($exec->getId());
            
            $this->assertInstanceOf(ExecInstance::class, $inspected);
            $this->assertEquals($exec->getId(), $inspected->getId());
            $this->assertEquals($containerId, $inspected->getContainerID());
        }

        public function testExecMultipleCommands()
        {
            $containerId = $this->createTestContainer();
            
            // Execute first command
            $exec1 = $this->docker->exec()->create($containerId, [
                'Cmd' => ['echo', 'first'],
                'AttachStdout' => true
            ]);
            
            $stream1 = $this->docker->exec()->start($exec1->getId());
            $output1 = $stream1->readAll();
            $stream1->close();
            
            // Execute second command
            $exec2 = $this->docker->exec()->create($containerId, [
                'Cmd' => ['echo', 'second'],
                'AttachStdout' => true
            ]);
            
            $stream2 = $this->docker->exec()->start($exec2->getId());
            $output2 = $stream2->readAll();
            $stream2->close();
            
            $this->assertStringContainsString('first', $output1);
            $this->assertStringContainsString('second', $output2);
        }

        public function testExecWithWorkingDir()
        {
            $containerId = $this->createTestContainer();
            
            $exec = $this->docker->exec()->create($containerId, [
                'Cmd' => ['pwd'],
                'AttachStdout' => true,
                'WorkingDir' => '/tmp'
            ]);
            
            $stream = $this->docker->exec()->start($exec->getId());
            $output = $stream->readAll();
            $stream->close();
            
            $this->assertStringContainsString('tmp', $output);
        }

        public function testExecWithEnvironment()
        {
            $containerId = $this->createTestContainer();
            
            $exec = $this->docker->exec()->create($containerId, [
                'Cmd' => ['sh', '-c', 'echo $TEST_VAR'],
                'AttachStdout' => true,
                'Env' => ['TEST_VAR=dockerlib_test']
            ]);
            
            $stream = $this->docker->exec()->start($exec->getId());
            $output = $stream->readAll();
            $stream->close();
            
            $this->assertStringContainsString('dockerlib_test', $output);
        }

        public function testExecDetached()
        {
            $containerId = $this->createTestContainer();
            
            $exec = $this->docker->exec()->create($containerId, [
                'Cmd' => ['sleep', '5'],
                'AttachStdout' => false,
                'AttachStderr' => false,
                'Detach' => true
            ]);
            
            $stream = $this->docker->exec()->start($exec->getId(), true);
            $stream->close();
            
            // Verify exec was created
            $inspected = $this->docker->exec()->inspect($exec->getId());
            $this->assertNotNull($inspected->getId());
        }

        public function testExecMultipleCommandsSequential()
        {
            $containerId = $this->createTestContainer();
            
            // Execute multiple commands in sequence
            $commands = [
                ['echo', 'first'],
                ['echo', 'second'],
                ['echo', 'third']
            ];
            
            foreach ($commands as $cmd) {
                $exec = $this->docker->exec()->create($containerId, [
                    'Cmd' => $cmd,
                    'AttachStdout' => true
                ]);
                
                $stream = $this->docker->exec()->start($exec->getId(), false);
                $output = $stream->readAll();
                
                $this->assertStringContainsString($cmd[1], $output);
            }
        }

        public function testExecWithPrivilegedMode()
        {
            $containerId = $this->createTestContainer();
            
            $exec = $this->docker->exec()->create($containerId, [
                'Cmd' => ['ls', '-la', '/proc'],
                'AttachStdout' => true,
                'Privileged' => true
            ]);
            
            $stream = $this->docker->exec()->start($exec->getId(), false);
            $output = $stream->readAll();
            
            $this->assertNotEmpty($output);
        }
    }
