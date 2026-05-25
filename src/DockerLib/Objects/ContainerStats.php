<?php

    namespace DockerLib\Objects;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents real-time resource usage statistics for a Docker container
     *
     * This class encapsulates CPU, memory, network, block I/O, and process
     * statistics for a running container. It provides methods to calculate
     * derived metrics like CPU and memory percentages.
     */
    class ContainerStats implements SerializableInterface
    {
        /**
         * Timestamp when the stats were read
         *
         * @var string|null
         */
        private ?string $read;

        /**
         * Timestamp when the stats were previously read
         *
         * @var string|null
         */
        private ?string $preread;

        /**
         * Process ID statistics
         *
         * @var array<string, mixed>
         */
        private array $pidsStats;

        /**
         * Block I/O statistics
         *
         * @var array<string, mixed>
         */
        private array $blkioStats;

        /**
         * Number of processes running in the container
         *
         * @var int|null
         */
        private ?int $numProcs;

        /**
         * Storage statistics
         *
         * @var array<string, mixed>
         */
        private array $storageStats;

        /**
         * Current CPU usage statistics
         *
         * @var array<string, mixed>
         */
        private array $cpuStats;

        /**
         * Previous CPU usage statistics for calculating deltas
         *
         * @var array<string, mixed>
         */
        private array $precpuStats;

        /**
         * Memory usage statistics
         *
         * @var array<string, mixed>
         */
        private array $memoryStats;

        /**
         * Network interface statistics
         *
         * @var array<string, array<string, mixed>>
         */
        private array $networkStats;

        /**
         * Container name
         *
         * @var string|null
         */
        private ?string $name;

        /**
         * Container ID
         *
         * @var string|null
         */
        private ?string $id;

        /**
         * Creates a new ContainerStats instance
         *
         * @param array<string, mixed> $data Raw statistics data from Docker API
         */
        public function __construct(array $data = [])
        {
            $this->read = $data['read'] ?? null;
            $this->preread = $data['preread'] ?? null;
            $this->pidsStats = $data['pids_stats'] ?? [];
            $this->blkioStats = $data['blkio_stats'] ?? [];
            $this->numProcs = $data['num_procs'] ?? null;
            $this->storageStats = $data['storage_stats'] ?? [];
            $this->cpuStats = $data['cpu_stats'] ?? [];
            $this->precpuStats = $data['precpu_stats'] ?? [];
            $this->memoryStats = $data['memory_stats'] ?? [];
            $this->networkStats = $data['networks'] ?? [];
            $this->name = $data['name'] ?? null;
            $this->id = $data['id'] ?? null;
        }

        /**
         * Gets the timestamp when stats were read
         *
         * @return string|null
         */
        public function getRead(): ?string
        {
            return $this->read;
        }

        /**
         * Gets the timestamp when stats were previously read
         *
         * @return string|null
         */
        public function getPreread(): ?string
        {
            return $this->preread;
        }

        /**
         * Gets the process ID statistics
         *
         * @return array<string, mixed>
         */
        public function getPidsStats(): array
        {
            return $this->pidsStats;
        }

        /**
         * Gets the block I/O statistics
         *
         * @return array<string, mixed>
         */
        public function getBlkioStats(): array
        {
            return $this->blkioStats;
        }

        /**
         * Gets the number of processes
         *
         * @return int|null
         */
        public function getNumProcs(): ?int
        {
            return $this->numProcs;
        }

        /**
         * Gets the storage statistics
         *
         * @return array<string, mixed>
         */
        public function getStorageStats(): array
        {
            return $this->storageStats;
        }

        /**
         * Gets the current CPU statistics
         *
         * @return array<string, mixed>
         */
        public function getCpuStats(): array
        {
            return $this->cpuStats;
        }

        /**
         * Gets the previous CPU statistics
         *
         * @return array<string, mixed>
         */
        public function getPrecpuStats(): array
        {
            return $this->precpuStats;
        }

        /**
         * Gets the memory statistics
         *
         * @return array<string, mixed>
         */
        public function getMemoryStats(): array
        {
            return $this->memoryStats;
        }

        /**
         * Gets the network statistics
         *
         * @return array<string, array<string, mixed>>
         */
        public function getNetworkStats(): array
        {
            return $this->networkStats;
        }

        /**
         * Gets the container name
         *
         * @return string|null
         */
        public function getName(): ?string
        {
            return $this->name;
        }

        /**
         * Gets the container ID
         *
         * @return string|null
         */
        public function getId(): ?string
        {
            return $this->id;
        }

        /**
         * Calculates the CPU usage percentage
         *
         * Computes the percentage of CPU used by comparing current and previous
         * CPU statistics, accounting for the number of available CPUs.
         *
         * @return float CPU usage percentage (0.0 - 100.0 * number of CPUs)
         */
        public function getCpuPercentage(): float
        {
            $cpuDelta = ($this->cpuStats['cpu_usage']['total_usage'] ?? 0) - ($this->precpuStats['cpu_usage']['total_usage'] ?? 0);
            $systemDelta = ($this->cpuStats['system_cpu_usage'] ?? 0) - ($this->precpuStats['system_cpu_usage'] ?? 0);

            if ($systemDelta > 0 && $cpuDelta > 0) {
                $numCpus = count($this->cpuStats['cpu_usage']['percpu_usage'] ?? [1]);
                return ($cpuDelta / $systemDelta) * $numCpus * 100.0;
            }

            return 0.0;
        }

        /**
         * Gets the current memory usage in bytes
         *
         * @return int Memory usage in bytes
         */
        public function getMemoryUsage(): int
        {
            return $this->memoryStats['usage'] ?? 0;
        }

        /**
         * Gets the memory limit in bytes
         *
         * @return int Memory limit in bytes
         */
        public function getMemoryLimit(): int
        {
            return $this->memoryStats['limit'] ?? 0;
        }

        /**
         * Calculates the memory usage percentage
         *
         * Computes the percentage of memory used relative to the memory limit.
         *
         * @return float Memory usage percentage (0.0 - 100.0)
         */
        public function getMemoryPercentage(): float
        {
            $usage = $this->getMemoryUsage();
            $limit = $this->getMemoryLimit();

            if ($limit > 0) {
                return ($usage / $limit) * 100.0;
            }

            return 0.0;
        }

        /**
         * Converts the statistics to an array
         *
         * @return array<string, mixed>
         */
        public function toArray(): array
        {
            return [
                'read' => $this->read,
                'preread' => $this->preread,
                'pids_stats' => $this->pidsStats,
                'blkio_stats' => $this->blkioStats,
                'num_procs' => $this->numProcs,
                'storage_stats' => $this->storageStats,
                'cpu_stats' => $this->cpuStats,
                'precpu_stats' => $this->precpuStats,
                'memory_stats' => $this->memoryStats,
                'networks' => $this->networkStats,
                'name' => $this->name,
                'id' => $this->id,
            ];
        }

        /**
         * Creates a ContainerStats instance from an array
         *
         * @param array<string, mixed> $data Statistics data array
         * @return self
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }

        /**
         * Gets the raw data array (deprecated)
         *
         * @deprecated Use toArray() instead
         * @return array<string, mixed>
         */
        public function getRawData(): array
        {
            return $this->toArray();
        }
    }
