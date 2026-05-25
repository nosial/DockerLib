<?php

    namespace DockerLib\Objects;

    use DockerLib\Objects\SystemInfo;
    use PHPUnit\Framework\TestCase;

    class SystemInfoObjectTest extends TestCase
    {
        public function testSystemInfoObjectCreation()
        {
            $data = [
                'ID' => 'TEST:123',
                'Containers' => 10,
                'ContainersRunning' => 5,
                'ContainersPaused' => 1,
                'ContainersStopped' => 4,
                'Images' => 20,
                'Driver' => 'overlay2',
                'OperatingSystem' => 'Ubuntu 22.04',
                'Architecture' => 'x86_64',
                'NCPU' => 8,
                'MemTotal' => 16777216000,
                'ServerVersion' => '24.0.0',
                'KernelVersion' => '5.15.0',
                'OSType' => 'linux'
            ];
            
            $info = new SystemInfo($data);
            
            $this->assertEquals('TEST:123', $info->getId());
            $this->assertEquals(10, $info->getContainers());
            $this->assertEquals(5, $info->getContainersRunning());
            $this->assertEquals(1, $info->getContainersPaused());
            $this->assertEquals(4, $info->getContainersStopped());
            $this->assertEquals(20, $info->getImages());
            $this->assertEquals('overlay2', $info->getDriver());
            $this->assertEquals('Ubuntu 22.04', $info->getOperatingSystem());
            $this->assertEquals('x86_64', $info->getArchitecture());
            $this->assertEquals(8, $info->getNCPU());
            $this->assertEquals(16777216000, $info->getMemTotal());
            $this->assertEquals('24.0.0', $info->getServerVersion());
        }

        public function testSystemInfoBooleanProperties()
        {
            $data = [
                'MemoryLimit' => true,
                'SwapLimit' => false,
                'CpuCfsPeriod' => true,
                'Debug' => false,
                'ExperimentalBuild' => true
            ];
            
            $info = new SystemInfo($data);
            
            $this->assertTrue($info->getMemoryLimit());
            $this->assertFalse($info->getSwapLimit());
            $this->assertTrue($info->getCpuCfsPeriod());
            $this->assertFalse($info->getDebug());
            $this->assertTrue($info->getExperimentalBuild());
        }
    }
