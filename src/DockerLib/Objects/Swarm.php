<?php

    namespace DockerLib\Objects;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents a Docker Swarm cluster
     * 
     * Contains information about the swarm's configuration, TLS settings, join tokens,
     * and networking configuration for the cluster.
     */
    class Swarm implements SerializableInterface
    {
        /**
         * Unique identifier of the swarm
         * 
         * @var string|null
         */
        private ?string $id;
        
        /**
         * Version information for optimistic concurrency control
         * 
         * @var array<string, mixed>|null
         */
        private ?array $version;
        
        /**
         * Timestamp when the swarm was created
         * 
         * @var string|null
         */
        private ?string $createdAt;
        
        /**
         * Timestamp when the swarm was last updated
         * 
         * @var string|null
         */
        private ?string $updatedAt;
        
        /**
         * Swarm specification including name and orchestration settings
         * 
         * @var array<string, mixed>
         */
        private array $spec;
        
        /**
         * TLS certificate and trust information for the swarm
         * 
         * @var array<string, mixed>|null
         */
        private ?array $tlsInfo;
        
        /**
         * Whether root certificate rotation is currently in progress
         * 
         * @var bool
         */
        private bool $rootRotationInProgress;
        
        /**
         * Default address pools for node networks
         * 
         * @var array<string>
         */
        private array $defaultAddrPool;
        
        /**
         * Subnet size for each node's network
         * 
         * @var int|null
         */
        private ?int $subnetSize;
        
        /**
         * Port number for the data path (VXLAN)
         * 
         * @var int|null
         */
        private ?int $dataPathPort;
        
        /**
         * Join tokens for worker and manager nodes
         * 
         * @var array<string, mixed>|null
         */
        private ?array $joinTokens;

        /**
         * Create a new Swarm instance
         * 
         * @param array<string, mixed> $data Raw swarm data from Docker API
         */
        public function __construct(array $data = [])
        {
            $this->id = $data['ID'] ?? null;
            $this->version = $data['Version'] ?? null;
            $this->createdAt = $data['CreatedAt'] ?? null;
            $this->updatedAt = $data['UpdatedAt'] ?? null;
            $this->spec = $data['Spec'] ?? [];
            $this->tlsInfo = $data['TLSInfo'] ?? null;
            $this->rootRotationInProgress = $data['RootRotationInProgress'] ?? false;
            $this->defaultAddrPool = $data['DefaultAddrPool'] ?? [];
            $this->subnetSize = $data['SubnetSize'] ?? null;
            $this->dataPathPort = $data['DataPathPort'] ?? null;
            $this->joinTokens = $data['JoinTokens'] ?? null;
        }

        /**
         * Get the swarm ID
         * 
         * @return string|null Unique identifier
         */
        public function getId(): ?string
        {
            return $this->id;
        }

        /**
         * Get the swarm version
         * 
         * @return array<string, mixed>|null Version object with index
         */
        public function getVersion(): ?array
        {
            return $this->version;
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
         * Get the last update timestamp
         * 
         * @return string|null ISO 8601 timestamp
         */
        public function getUpdatedAt(): ?string
        {
            return $this->updatedAt;
        }

        /**
         * Get the swarm specification
         * 
         * @return array<string, mixed> Complete swarm spec including name and orchestration settings
         */
        public function getSpec(): array
        {
            return $this->spec;
        }

        /**
         * Get the TLS information
         * 
         * @return array<string, mixed>|null TLS certificate and trust configuration
         */
        public function getTLSInfo(): ?array
        {
            return $this->tlsInfo;
        }

        /**
         * Get whether root rotation is in progress
         * 
         * @return bool True if root certificate rotation is ongoing
         */
        public function getRootRotationInProgress(): bool
        {
            return $this->rootRotationInProgress;
        }

        /**
         * Get the default address pools
         * 
         * @return array<string> List of CIDR blocks for node networks
         */
        public function getDefaultAddrPool(): array
        {
            return $this->defaultAddrPool;
        }

        /**
         * Get the subnet size
         * 
         * @return int|null Size of each node's subnet in bits
         */
        public function getSubnetSize(): ?int
        {
            return $this->subnetSize;
        }

        /**
         * Get the data path port
         * 
         * @return int|null Port number for VXLAN traffic
         */
        public function getDataPathPort(): ?int
        {
            return $this->dataPathPort;
        }

        /**
         * Get the join tokens
         * 
         * @return array<string, mixed>|null Tokens for worker and manager nodes to join the swarm
         */
        public function getJoinTokens(): ?array
        {
            return $this->joinTokens;
        }

        /**
         * Convert the swarm to an array representation
         * 
         * @return array<string, mixed> Array containing all swarm properties
         */
        public function toArray(): array
        {
            return [
                'ID' => $this->id,
                'Version' => $this->version,
                'CreatedAt' => $this->createdAt,
                'UpdatedAt' => $this->updatedAt,
                'Spec' => $this->spec,
                'TLSInfo' => $this->tlsInfo,
                'RootRotationInProgress' => $this->rootRotationInProgress,
                'DefaultAddrPool' => $this->defaultAddrPool,
                'SubnetSize' => $this->subnetSize,
                'DataPathPort' => $this->dataPathPort,
                'JoinTokens' => $this->joinTokens,
            ];
        }

        /**
         * Create a Swarm instance from an array
         * 
         * @param array<string, mixed> $data Raw swarm data
         * @return self New Swarm instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }
    }
