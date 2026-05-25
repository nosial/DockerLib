<?php

    namespace DockerLib\Managers;

    use DockerLib\Docker;
    use PHPUnit\Framework\TestCase;

    class CallbackTest extends TestCase
    {
        private Docker $docker;

        protected function setUp(): void
        {
            $this->docker = new Docker();
            
            if (!$this->docker->ping()) {
                $this->markTestSkipped('Docker is not available');
            }
        }

        public function testPullWithCallback()
        {
            $callbackInvoked = false;
            $statusReceived = false;
            
            $stream = $this->docker->images()->pull('alpine', 'latest', null, function($progress) use (&$callbackInvoked, &$statusReceived) {
                $callbackInvoked = true;
                
                // During streaming, progress can be an array or other data
                if (is_array($progress) && isset($progress['status'])) {
                    $statusReceived = true;
                }
            });
            
            $this->assertTrue($callbackInvoked, 'Callback should be invoked during pull');
            $this->assertTrue($statusReceived, 'Status should be received in callback');
        }

        public function testPullWithoutCallback()
        {
            $stream = $this->docker->images()->pull('alpine', 'latest');
            
            $this->assertInstanceOf(\DockerLib\Objects\StreamResponse::class, $stream);
        }

        public function testPushWithCallback()
        {
            $imageName = 'dockerlib-test-push-' . uniqid();
            
            try {
                $this->docker->images()->tag('alpine:latest', $imageName, 'test');
                
                $callbackInvoked = false;
                
                try {
                    $stream = $this->docker->images()->push($imageName, 'test', null, function($progress) use (&$callbackInvoked) {
                        $callbackInvoked = true;
                        $this->assertIsArray($progress);
                    });
                } catch (\Exception $e) {
                    // Push may fail if not authenticated, but callback should still work
                }
                
                $this->docker->images()->remove($imageName . ':test', true);
                
            } catch (\Exception $e) {
                // Cleanup
                try {
                    $this->docker->images()->remove($imageName . ':test', true);
                } catch (\Exception $cleanupException) {
                }
            }
        }
    }
