<?php

    namespace DockerLib\Objects;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents a Docker Swarm node
     * 
     * This class encapsulates information about a node in a Docker Swarm cluster,
     * including its role (manager/worker), status, resource availability, and
     * platform information.
     */
    class Node implements SerializableInterface
    {
        /**
         * @var string|null The unique identifier of the node
         */
        private ?string $id;

        /**
         * @var array<string, mixed>|null Version information for optimistic concurrency control
         */
        private ?array $version;

        /**
         * @var string|null Timestamp when the node was created (ISO 8601 format)
         */
        private ?string $createdAt;

        /**
         * @var string|null Timestamp when the node was last updated (ISO 8601 format)
         */
        private ?string $updatedAt;

        /**
         * @var array<string, mixed> Node specification including name, labels, role, and availability
         */
        private array $spec;

        /**
         * @var array<string, mixed>|null Node description including hostname, platform, resources, and engine info
         */
        private ?array $description;

        /**
         * @var array<string, mixed>|null Current status of the node
         */
        private ?array $status;

        /**
         * @var array<string, mixed>|null Manager-specific status information (null for worker nodes)
         */
        private ?array $managerStatus;

        /**
         * Creates a new Node instance from Docker API data
         *
         * @param array<string, mixed> $data Raw node data from Docker API
         */
        public function __construct(array $data = [])
        {
            $this->id = $data['ID'] ?? null;
            $this->version = $data['Version'] ?? null;
            $this->createdAt = $data['CreatedAt'] ?? null;
            $this->updatedAt = $data['UpdatedAt'] ?? null;
            $this->spec = $data['Spec'] ?? [];
            $this->description = $data['Description'] ?? null;
            $this->status = $data['Status'] ?? null;
            $this->managerStatus = $data['ManagerStatus'] ?? null;
        }

        /**
         * Gets the unique identifier of the node
         *
         * @return string|null The node ID
         */
        public function getId(): ?string
        {
            return $this->id;
        }

        /**
         * Gets the version information
         *
         * @return array<string, mixed>|null Version for optimistic concurrency control
         */
        public function getVersion(): ?array
        {
            return $this->version;
        }

        /**
         * Gets the creation timestamp
         *
         * @return string|null ISO 8601 formatted timestamp
         */
        public function getCreatedAt(): ?string
        {
            return $this->createdAt;
        }

        /**
         * Gets the last update timestamp
         *
         * @return string|null ISO 8601 formatted timestamp
         */
        public function getUpdatedAt(): ?string
        {
            return $this->updatedAt;
        }

        /**
         * Gets the node specification
         *
         * @return array<string, mixed> Node spec including name, labels, role, and availability
         */
        public function getSpec(): array
        {
            return $this->spec;
        }

        /**
         * Gets the node description
         *
         * @return array<string, mixed>|null Description including hardware and platform info
         */
        public function getDescription(): ?array
        {
            return $this->description;
        }

        /**
         * Gets the node status
         *
         * @return array<string, mixed>|null Current status of the node
         */
        public function getStatus(): ?array
        {
            return $this->status;
        }

        /**
         * Gets the manager status
         *
         * @return array<string, mixed>|null Manager status (null for worker nodes)
         */
        public function getManagerStatus(): ?array
        {
            return $this->managerStatus;
        }

        /**
         * Gets the node name
         *
         * @return string|null The node name from spec
         */
        public function getName(): ?string
        {
            return $this->spec['Name'] ?? null;
        }

        /**
         * Gets the node labels
         *
         * @return array<string, string> User-defined labels
         */
        public function getLabels(): array
        {
            return $this->spec['Labels'] ?? [];
        }

        /**
         * Gets the node role
         *
         * @return string|null Node role ("manager" or "worker")
         */
        public function getRole(): ?string
        {
            return $this->spec['Role'] ?? null;
        }

        /**
         * Gets the node availability
         *
         * @return string|null Availability status ("active", "pause", "drain")
         */
        public function getAvailability(): ?string
        {
            return $this->spec['Availability'] ?? null;
        }

        /**
         * Gets the hostname
         *
         * @return string|null The node's hostname
         */
        public function getHostname(): ?string
        {
            return $this->description['Hostname'] ?? null;
        }

        /**
         * Gets the platform information
         *
         * @return array<string, mixed>|null Platform details (architecture, OS)
         */
        public function getPlatform(): ?array
        {
            return $this->description['Platform'] ?? null;
        }

        /**
         * Gets the resource information
         *
         * @return array<string, mixed>|null Available resources (CPU, memory)
         */
        public function getResources(): ?array
        {
            return $this->description['Resources'] ?? null;
        }

        /**
         * Gets the Docker Engine information
         *
         * @return array<string, mixed>|null Engine version and plugins
         */
        public function getEngine(): ?array
        {
            return $this->description['Engine'] ?? null;
        }

        /**
         * Gets the TLS information
         *
         * @return array<string, mixed>|null TLS certificate details
         */
        public function getTLSInfo(): ?array
        {
            return $this->description['TLSInfo'] ?? null;
        }

        /**
         * Checks if this node is a manager
         *
         * @return bool True if the node is a manager, false otherwise
         */
        public function isManager(): bool
        {
            return $this->managerStatus !== null;
        }

        /**
         * Converts the node to an array representation
         *
         * @return array<string, mixed> Array containing all node properties
         */
        public function toArray(): array
        {
            return [
                'ID' => $this->id,
                'Version' => $this->version,
                'CreatedAt' => $this->createdAt,
                'UpdatedAt' => $this->updatedAt,
                'Spec' => $this->spec,
                'Description' => $this->description,
                'Status' => $this->status,
                'ManagerStatus' => $this->managerStatus,
            ];
        }

        /**
         * Creates a Node instance from an array
         *
         * @param array<string, mixed> $data Node data from Docker API
         * @return self New Node instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }

        /**
         * Gets raw data as array (deprecated)
         *
         * @return array<string, mixed> Array containing all node properties
         * @deprecated Use toArray() instead
         */
        public function getRawData(): array
        {
            return $this->toArray();
        }
    }
