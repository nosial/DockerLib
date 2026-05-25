<?php

    namespace DockerLib\Objects;

    use DockerLib\Objects\ExecInstance;
    use PHPUnit\Framework\TestCase;

    class ExecInstanceObjectTest extends TestCase
    {
        public function testExecInstanceFromArray()
        {
            $data = [
                'ID' => 'exec-123',
                'Running' => true,
                'ExitCode' => 0,
                'ProcessConfig' => [
                    'tty' => true,
                    'entrypoint' => 'sh',
                    'arguments' => ['-c', 'echo test'],
                    'privileged' => false
                ],
                'OpenStdin' => true,
                'OpenStderr' => true,
                'OpenStdout' => true,
                'CanRemove' => false,
                'ContainerID' => 'container-456',
                'DetachKeys' => '',
                'Pid' => 12345
            ];

            $exec = new ExecInstance($data);

            $this->assertInstanceOf(ExecInstance::class, $exec);
            $this->assertEquals('exec-123', $exec->getId());
            $this->assertTrue($exec->isRunning());
            $this->assertEquals(0, $exec->getExitCode());
            $this->assertEquals('container-456', $exec->getContainerId());
            $this->assertEquals(12345, $exec->getPid());
        }

        public function testExecInstanceToArray()
        {
            $data = [
                'ID' => 'exec-789',
                'Running' => false,
                'ExitCode' => 1,
                'ProcessConfig' => [
                    'tty' => false,
                    'entrypoint' => 'bash',
                    'arguments' => ['-c', 'ls'],
                    'privileged' => true
                ],
                'OpenStdin' => false,
                'OpenStderr' => true,
                'OpenStdout' => true,
                'CanRemove' => true,
                'ContainerID' => 'container-999',
                'DetachKeys' => 'ctrl-p,ctrl-q',
                'Pid' => 54321
            ];

            $exec = new ExecInstance($data);

            $this->assertEquals('exec-789', $exec->getId());
            $this->assertFalse($exec->isRunning());
        }

        public function testExecInstanceGetters()
        {
            $data = [
                'ID' => 'test-exec',
                'Running' => true,
                'ExitCode' => null,
                'ProcessConfig' => [
                    'tty' => true,
                    'entrypoint' => '/bin/sh',
                    'arguments' => [],
                    'privileged' => false
                ],
                'OpenStdin' => true,
                'OpenStderr' => true,
                'OpenStdout' => true,
                'CanRemove' => false,
                'ContainerID' => 'my-container',
                'DetachKeys' => '',
                'Pid' => 9876
            ];

            $exec = new ExecInstance($data);

            $this->assertEquals('test-exec', $exec->getId());
            $this->assertTrue($exec->isRunning());
            $this->assertNull($exec->getExitCode());
            $this->assertIsArray($exec->getProcessConfig());
            $this->assertTrue($exec->isOpenStdin());
            $this->assertTrue($exec->isOpenStderr());
            $this->assertTrue($exec->isOpenStdout());
            $this->assertFalse($exec->isCanRemove());
            $this->assertEquals('my-container', $exec->getContainerId());
            $this->assertEquals('', $exec->getDetachKeys());
            $this->assertEquals(9876, $exec->getPid());
        }
    }
