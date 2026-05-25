<?php

    namespace DockerLib\Objects;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents a Docker exec instance
     * 
     * Contains information about a command execution inside a running container.
     * Exec instances allow running additional processes in existing containers.
     */
    class ExecInstance implements SerializableInterface
    {
        /**
         * Unique identifier of the exec instance
         * 
         * @var string|null
         */
        private ?string $id;
        
        /**
         * Whether the exec instance is currently running
         * 
         * @var bool
         */
        private bool $running;
        
        /**
         * Exit code of the exec command (null if still running)
         * 
         * @var int|null
         */
        private ?int $exitCode;
        
        /**
         * Process configuration including entrypoint and arguments
         * 
         * @var array<string, mixed>|null
         */
        private ?array $processConfig;
        
        /**
         * Whether stdin is attached
         * 
         * @var bool
         */
        private bool $openStdin;
        
        /**
         * Whether stderr is attached
         * 
         * @var bool
         */
        private bool $openStderr;
        
        /**
         * Whether stdout is attached
         * 
         * @var bool
         */
        private bool $openStdout;
        
        /**
         * Whether the exec instance can be removed
         * 
         * @var bool
         */
        private bool $canRemove;
        
        /**
         * ID of the container this exec instance is running in
         * 
         * @var string|null
         */
        private ?string $containerId;
        
        /**
         * Detach keys sequence to detach from exec session
         * 
         * @var string|null
         */
        private ?string $detachKeys;
        
        /**
         * Process ID of the exec command
         * 
         * @var int|null
         */
        private ?int $pid;

        /**
         * Create a new ExecInstance
         * 
         * @param array<string, mixed> $data Raw exec instance data from Docker API
         */
        public function __construct(array $data = [])
        {
            $id = $data['ID'] ?? ($data['Id'] ?? null);

            $this->id = $id;
            $this->running = $data['Running'] ?? false;
            $this->exitCode = $data['ExitCode'] ?? null;
            $this->processConfig = $data['ProcessConfig'] ?? null;
            $this->openStdin = $data['OpenStdin'] ?? false;
            $this->openStderr = $data['OpenStderr'] ?? false;
            $this->openStdout = $data['OpenStdout'] ?? false;
            $this->canRemove = $data['CanRemove'] ?? false;
            $this->containerId = $data['ContainerID'] ?? null;
            $this->detachKeys = $data['DetachKeys'] ?? null;
            $this->pid = $data['Pid'] ?? null;
        }

        /**
         * Get the exec instance ID
         * 
         * @return string|null Unique identifier
         */
        public function getId(): ?string
        {
            return $this->id;
        }

        /**
         * Get whether the exec instance is running
         * 
         * @return bool True if running, false otherwise
         */
        public function getRunning(): bool
        {
            return $this->running;
        }

        /**
         * Get the exit code of the exec command
         * 
         * @return int|null Exit code (0 for success, non-zero for error), null if still running
         */
        public function getExitCode(): ?int
        {
            return $this->exitCode;
        }

        /**
         * Get the process configuration
         * 
         * @return array<string, mixed>|null Configuration including entrypoint and arguments
         */
        public function getProcessConfig(): ?array
        {
            return $this->processConfig;
        }

        /**
         * Get whether stdin is attached
         * 
         * @return bool True if stdin is open
         */
        public function getOpenStdin(): bool
        {
            return $this->openStdin;
        }

        /**
         * Get whether stderr is attached
         * 
         * @return bool True if stderr is open
         */
        public function getOpenStderr(): bool
        {
            return $this->openStderr;
        }

        /**
         * Get whether stdout is attached
         * 
         * @return bool True if stdout is open
         */
        public function getOpenStdout(): bool
        {
            return $this->openStdout;
        }

        /**
         * Get whether the exec instance can be removed
         * 
         * @return bool True if removable
         */
        public function getCanRemove(): bool
        {
            return $this->canRemove;
        }

        /**
         * Get the container ID this exec instance is running in
         * 
         * @return string|null Container identifier
         */
        public function getContainerID(): ?string
        {
            return $this->containerId;
        }

        /**
         * Get the detach keys sequence
         * 
         * @return string|null Key sequence to detach from exec session
         */
        public function getDetachKeys(): ?string
        {
            return $this->detachKeys;
        }

        /**
         * Get the process ID
         * 
         * @return int|null PID of the exec command in the container
         */
        public function getPid(): ?int
        {
            return $this->pid;
        }

        /**
         * Check if the exec instance is currently running
         * 
         * @return bool True if running
         */
        public function isRunning(): bool
        {
            return $this->running;
        }

        /**
         * Check if stdin is attached
         * 
         * @return bool True if stdin is open
         */
        public function isOpenStdin(): bool
        {
            return $this->openStdin;
        }

        /**
         * Check if stderr is attached
         * 
         * @return bool True if stderr is open
         */
        public function isOpenStderr(): bool
        {
            return $this->openStderr;
        }

        /**
         * Check if stdout is attached
         * 
         * @return bool True if stdout is open
         */
        public function isOpenStdout(): bool
        {
            return $this->openStdout;
        }

        /**
         * Check if the exec instance can be removed
         * 
         * @return bool True if removable
         */
        public function isCanRemove(): bool
        {
            return $this->canRemove;
        }

        /**
         * Convert the exec instance to an array
         * 
         * @return array<string, mixed> Array representation of the exec instance
         */
        public function toArray(): array
        {
            return [
                'ID' => $this->id,
                'Running' => $this->running,
                'ExitCode' => $this->exitCode,
                'ProcessConfig' => $this->processConfig,
                'OpenStdin' => $this->openStdin,
                'OpenStderr' => $this->openStderr,
                'OpenStdout' => $this->openStdout,
                'CanRemove' => $this->canRemove,
                'ContainerID' => $this->containerId,
                'DetachKeys' => $this->detachKeys,
                'Pid' => $this->pid,
            ];
        }

        /**
         * Create an ExecInstance from an array
         * 
         * @param array<string, mixed> $data Raw exec data
         * @return self New ExecInstance instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }

        /**
         * @deprecated Use toArray() instead
         */
        public function getRawData(): array
        {
            return $this->toArray();
        }
    }
