<?php

    namespace DockerLib\Objects\Container;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents Docker container host configuration settings.
     * 
     * This class encapsulates runtime configuration options for a container that affect
     * how it interacts with the host system, including network mode, resource limits,
     * restart policies, and privilege settings.
     */
    class HostConfig implements SerializableInterface
    {
        /**
         * @var string|null Network mode for the container (e.g., 'bridge', 'host', 'none')
         */
        private ?string $networkMode;
        
        /**
         * @var int|null CPU shares (relative weight) for the container
         */
        private ?int $cpuShares;
        
        /**
         * @var int|null Memory limit in bytes
         */
        private ?int $memory;
        
        /**
         * @var int|null Memory swap limit in bytes
         */
        private ?int $memorySwap;
        
        /**
         * @var int|null Memory soft reservation in bytes
         */
        private ?int $memoryReservation;
        
        /**
         * @var int|null Kernel memory limit in bytes
         */
        private ?int $kernelMemory;
        
        /**
         * @var string|null Restart policy name (e.g., 'no', 'always', 'on-failure', 'unless-stopped')
         */
        private ?string $restartPolicy;
        
        /**
         * @var int|null Maximum number of restart retries (used with 'on-failure' policy)
         */
        private ?int $maximumRetryCount;
        
        /**
         * @var bool Whether to automatically remove the container when it exits
         */
        private bool $autoRemove;
        
        /**
         * @var bool Whether to run the container in privileged mode
         */
        private bool $privileged;
        
        /**
         * @var bool Whether to publish all exposed ports to random ports on the host
         */
        private bool $publishAllPorts;

        /**
         * Constructs a new HostConfig instance from an array of data.
         * 
         * Accepts both camelCase and PascalCase keys for compatibility with
         * Docker API responses and internal data structures.
         *
         * @param array<string, mixed> $data Configuration data
         */
        public function __construct(array $data = [])
        {
            $restartPolicy = null;
            $maximumRetryCount = null;
            
            if (isset($data['RestartPolicy']) && is_array($data['RestartPolicy'])) {
                $restartPolicy = $data['RestartPolicy']['Name'] ?? null;
                $maximumRetryCount = $data['RestartPolicy']['MaximumRetryCount'] ?? null;
            } elseif (isset($data['restartPolicy'])) {
                $restartPolicy = $data['restartPolicy'];
            }
            
            if (isset($data['maximumRetryCount'])) {
                $maximumRetryCount = $data['maximumRetryCount'];
            }
            
            $this->networkMode = $data['networkMode'] ?? $data['NetworkMode'] ?? null;
            $this->cpuShares = $data['cpuShares'] ?? $data['CpuShares'] ?? null;
            $this->memory = $data['memory'] ?? $data['Memory'] ?? null;
            $this->memorySwap = $data['memorySwap'] ?? $data['MemorySwap'] ?? null;
            $this->memoryReservation = $data['memoryReservation'] ?? $data['MemoryReservation'] ?? null;
            $this->kernelMemory = $data['kernelMemory'] ?? $data['KernelMemory'] ?? null;
            $this->restartPolicy = $restartPolicy;
            $this->maximumRetryCount = $maximumRetryCount;
            $this->autoRemove = $data['autoRemove'] ?? $data['AutoRemove'] ?? false;
            $this->privileged = $data['privileged'] ?? $data['Privileged'] ?? false;
            $this->publishAllPorts = $data['publishAllPorts'] ?? $data['PublishAllPorts'] ?? false;
        }

        /**
         * Gets the network mode for the container.
         *
         * @return string|null The network mode or null if not set
         */
        public function getNetworkMode(): ?string
        {
            return $this->networkMode;
        }

        /**
         * Gets the CPU shares (relative weight) for the container.
         *
         * @return int|null The CPU shares or null if not set
         */
        public function getCpuShares(): ?int
        {
            return $this->cpuShares;
        }

        /**
         * Gets the memory limit in bytes.
         *
         * @return int|null The memory limit or null if not set
         */
        public function getMemory(): ?int
        {
            return $this->memory;
        }

        /**
         * Gets the memory swap limit in bytes.
         *
         * @return int|null The memory swap limit or null if not set
         */
        public function getMemorySwap(): ?int
        {
            return $this->memorySwap;
        }

        /**
         * Gets the memory soft reservation in bytes.
         *
         * @return int|null The memory reservation or null if not set
         */
        public function getMemoryReservation(): ?int
        {
            return $this->memoryReservation;
        }

        /**
         * Gets the kernel memory limit in bytes.
         *
         * @return int|null The kernel memory limit or null if not set
         */
        public function getKernelMemory(): ?int
        {
            return $this->kernelMemory;
        }

        /**
         * Gets the restart policy name.
         *
         * @return string|null The restart policy or null if not set
         */
        public function getRestartPolicy(): ?string
        {
            return $this->restartPolicy;
        }

        /**
         * Gets the maximum number of restart retries.
         *
         * @return int|null The maximum retry count or null if not set
         */
        public function getMaximumRetryCount(): ?int
        {
            return $this->maximumRetryCount;
        }

        /**
         * Checks if the container should be automatically removed when it exits.
         *
         * @return bool True if auto-remove is enabled, false otherwise
         */
        public function isAutoRemove(): bool
        {
            return $this->autoRemove;
        }

        /**
         * Checks if the container is running in privileged mode.
         *
         * @return bool True if privileged mode is enabled, false otherwise
         */
        public function isPrivileged(): bool
        {
            return $this->privileged;
        }

        /**
         * Checks if all exposed ports should be published to random ports on the host.
         *
         * @return bool True if publish all ports is enabled, false otherwise
         */
        public function isPublishAllPorts(): bool
        {
            return $this->publishAllPorts;
        }

        /**
         * Converts the HostConfig to an array representation.
         * 
         * Returns data in PascalCase format compatible with Docker API.
         *
         * @return array<string, mixed> The host configuration as an associative array
         */
        public function toArray(): array
        {
            $restartPolicy = null;
            if ($this->restartPolicy !== null) {
                $restartPolicy = [
                    'Name' => $this->restartPolicy,
                    'MaximumRetryCount' => $this->maximumRetryCount ?? 0
                ];
            }

            return [
                'NetworkMode' => $this->networkMode,
                'CpuShares' => $this->cpuShares,
                'Memory' => $this->memory,
                'MemorySwap' => $this->memorySwap,
                'MemoryReservation' => $this->memoryReservation,
                'KernelMemory' => $this->kernelMemory,
                'RestartPolicy' => $restartPolicy,
                'AutoRemove' => $this->autoRemove,
                'Privileged' => $this->privileged,
                'PublishAllPorts' => $this->publishAllPorts,
            ];
        }

        /**
         * Creates a HostConfig instance from an array of data.
         *
         * @param array<string, mixed> $data Configuration data
         * @return self A new HostConfig instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }
    }
