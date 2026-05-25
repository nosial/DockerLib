<?php

    namespace DockerLib\Objects;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents a Docker volume
     * 
     * Volumes are persistent data storage mechanisms that can be mounted into containers.
     * They exist independently of containers and can be shared across multiple containers.
     */
    class Volume implements SerializableInterface
    {
        /**
         * Unique name of the volume
         * 
         * @var string|null
         */
        private ?string $name;
        
        /**
         * Volume driver used (e.g., "local", "nfs")
         * 
         * @var string|null
         */
        private ?string $driver;
        
        /**
         * Mount point on the host filesystem
         * 
         * @var string|null
         */
        private ?string $mountpoint;
        
        /**
         * Timestamp when the volume was created
         * 
         * @var string|null
         */
        private ?string $createdAt;
        
        /**
         * Additional status information about the volume
         * 
         * @var array<string, mixed>|null
         */
        private ?array $status;
        
        /**
         * User-defined metadata labels
         * 
         * @var array<string, string>
         */
        private array $labels;
        
        /**
         * Scope of the volume (e.g., "local", "global")
         * 
         * @var string|null
         */
        private ?string $scope;
        
        /**
         * Driver-specific options
         * 
         * @var array<string, string>
         */
        private array $options;
        
        /**
         * Volume usage statistics
         * 
         * @var array<string, mixed>|null
         */
        private ?array $usageData;

        /**
         * Create a new Volume instance
         * 
         * @param array<string, mixed> $data Raw volume data from Docker API
         */
        public function __construct(array $data = [])
        {
            $this->name = $data['Name'] ?? null;
            $this->driver = $data['Driver'] ?? null;
            $this->mountpoint = $data['Mountpoint'] ?? null;
            $this->createdAt = $data['CreatedAt'] ?? null;
            $this->status = $data['Status'] ?? null;
            $this->labels = $data['Labels'] ?? [];
            $this->scope = $data['Scope'] ?? null;
            $this->options = $data['Options'] ?? [];
            $this->usageData = $data['UsageData'] ?? null;
        }

        /**
         * Get the volume name
         * 
         * @return string|null Unique volume name
         */
        public function getName(): ?string
        {
            return $this->name;
        }

        /**
         * Get the volume driver
         * 
         * @return string|null Driver name (e.g., "local", "nfs")
         */
        public function getDriver(): ?string
        {
            return $this->driver;
        }

        /**
         * Get the mount point path
         * 
         * @return string|null Path on the host filesystem
         */
        public function getMountpoint(): ?string
        {
            return $this->mountpoint;
        }

        /**
         * Get the creation timestamp
         * 
         * @return string|null ISO 8601 timestamp
         */
        public function getCreatedAt(): ?string
        {
            return $this->createdAt;
        }

        /**
         * Get the volume status
         * 
         * @return array<string, mixed>|null Additional status information
         */
        public function getStatus(): ?array
        {
            return $this->status;
        }

        /**
         * Get user-defined labels
         * 
         * @return array<string, string> Key-value pairs of metadata labels
         */
        public function getLabels(): array
        {
            return $this->labels;
        }

        /**
         * Get the volume scope
         * 
         * @return string|null Scope (e.g., "local", "global")
         */
        public function getScope(): ?string
        {
            return $this->scope;
        }

        /**
         * Get driver-specific options
         * 
         * @return array<string, string> Driver configuration options
         */
        public function getOptions(): array
        {
            return $this->options;
        }

        /**
         * Get volume usage data
         * 
         * @return array<string, mixed>|null Usage statistics including size and reference count
         */
        public function getUsageData(): ?array
        {
            return $this->usageData;
        }

        /**
         * Convert the volume to an array representation
         * 
         * @return array<string, mixed> Array containing all volume properties
         */
        public function toArray(): array
        {
            return [
                'Name' => $this->name,
                'Driver' => $this->driver,
                'Mountpoint' => $this->mountpoint,
                'CreatedAt' => $this->createdAt,
                'Status' => $this->status,
                'Labels' => $this->labels,
                'Scope' => $this->scope,
                'Options' => $this->options,
                'UsageData' => $this->usageData,
            ];
        }

        /**
         * Create a Volume instance from an array
         * 
         * @param array<string, mixed> $data Raw volume data
         * @return self New Volume instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }
    }
