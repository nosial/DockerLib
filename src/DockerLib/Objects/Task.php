<?php

    namespace DockerLib\Objects;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents a Docker Swarm task
     * 
     * Tasks are the atomic unit of work in a swarm service. Each task represents a single
     * instance of a container running as part of a service.
     */
    class Task implements SerializableInterface
    {
        /**
         * Unique identifier of the task
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
         * Timestamp when the task was created
         * 
         * @var string|null
         */
        private ?string $createdAt;
        
        /**
         * Timestamp when the task was last updated
         * 
         * @var string|null
         */
        private ?string $updatedAt;
        
        /**
         * Name of the task
         * 
         * @var string|null
         */
        private ?string $name;
        
        /**
         * User-defined metadata labels
         * 
         * @var array<string, string>
         */
        private array $labels;
        
        /**
         * Task specification defining container and resource requirements
         * 
         * @var array<string, mixed>
         */
        private array $spec;
        
        /**
         * ID of the service this task belongs to
         * 
         * @var string|null
         */
        private ?string $serviceID;
        
        /**
         * Slot number for replicated services
         * 
         * @var int|null
         */
        private ?int $slot;
        
        /**
         * ID of the node where this task is running
         * 
         * @var string|null
         */
        private ?string $nodeID;
        
        /**
         * Current status of the task including state and container info
         * 
         * @var array<string, mixed>|null
         */
        private ?array $status;
        
        /**
         * Desired state for this task (running, shutdown, etc.)
         * 
         * @var string|null
         */
        private ?string $desiredState;
        
        /**
         * Network attachments for this task
         * 
         * @var array<array>
         */
        private array $networksAttachments;
        
        /**
         * Generic resource allocations for this task
         * 
         * @var array<array>
         */
        private array $genericResources;

        /**
         * Create a new Task instance
         * 
         * @param array<string, mixed> $data Raw task data from Docker API
         */
        public function __construct(array $data=[])
        {
            $this->id = $data['ID'] ?? null;
            $this->version = $data['Version'] ?? null;
            $this->createdAt = $data['CreatedAt'] ?? null;
            $this->updatedAt = $data['UpdatedAt'] ?? null;
            $this->name = $data['Name'] ?? null;
            $this->labels = $data['Labels'] ?? [];
            $this->spec = $data['Spec'] ?? [];
            $this->serviceID = $data['ServiceID'] ?? null;
            $this->slot = $data['Slot'] ?? null;
            $this->nodeID = $data['NodeID'] ?? null;
            $this->status = $data['Status'] ?? null;
            $this->desiredState = $data['DesiredState'] ?? null;
            $this->networksAttachments = $data['NetworksAttachments'] ?? [];
            $this->genericResources = $data['GenericResources'] ?? [];
        }

        /**
         * Get the task ID
         * 
         * @return string|null Unique identifier
         */
        public function getId(): ?string
        {
            return $this->id;
        }

        /**
         * Get the task version
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
         * Get the task name
         * 
         * @return string|null Task name
         */
        public function getName(): ?string
        {
            return $this->name;
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
         * Get the task specification
         * 
         * @return array<string, mixed> Task spec including container and resource requirements
         */
        public function getSpec(): array
        {
            return $this->spec;
        }

        /**
         * Get the service ID
         * 
         * @return string|null ID of the service this task belongs to
         */
        public function getServiceID(): ?string
        {
            return $this->serviceID;
        }

        /**
         * Get the slot number
         * 
         * @return int|null Slot number for replicated services
         */
        public function getSlot(): ?int
        {
            return $this->slot;
        }

        /**
         * Get the node ID
         * 
         * @return string|null ID of the node where this task is running
         */
        public function getNodeID(): ?string
        {
            return $this->nodeID;
        }

        /**
         * Get the task status
         * 
         * @return array<string, mixed>|null Current status including state and timestamps
         */
        public function getStatus(): ?array
        {
            return $this->status;
        }

        /**
         * Get the desired state
         * 
         * @return string|null Desired state (e.g., "running", "shutdown")
         */
        public function getDesiredState(): ?string
        {
            return $this->desiredState;
        }

        /**
         * Get network attachments
         * 
         * @return array<array> List of networks attached to this task
         */
        public function getNetworksAttachments(): array
        {
            return $this->networksAttachments;
        }

        /**
         * Get generic resources
         * 
         * @return array<array> List of generic resource allocations
         */
        public function getGenericResources(): array
        {
            return $this->genericResources;
        }

        /**
         * Get the current state
         * 
         * @return string|null Current state from status (e.g., "running", "failed")
         */
        public function getState(): ?string
        {
            return $this->status['State'] ?? null;
        }

        /**
         * Get the status message
         * 
         * @return string|null Human-readable status message
         */
        public function getMessage(): ?string
        {
            return $this->status['Message'] ?? null;
        }

        /**
         * Get the container status
         * 
         * @return array<string, mixed>|null Container-specific status information
         */
        public function getContainerStatus(): ?array
        {
            return $this->status['ContainerStatus'] ?? null;
        }

        /**
         * Convert the task to an array representation
         * 
         * @return array<string, mixed> Array containing all task properties
         */
        public function toArray(): array
        {
            return [
                'ID' => $this->id,
                'Version' => $this->version,
                'CreatedAt' => $this->createdAt,
                'UpdatedAt' => $this->updatedAt,
                'Name' => $this->name,
                'Labels' => $this->labels,
                'Spec' => $this->spec,
                'ServiceID' => $this->serviceID,
                'Slot' => $this->slot,
                'NodeID' => $this->nodeID,
                'Status' => $this->status,
                'DesiredState' => $this->desiredState,
                'NetworksAttachments' => $this->networksAttachments,
                'GenericResources' => $this->genericResources,
            ];
        }

        /**
         * Create a Task instance from an array
         * 
         * @param array<string, mixed> $data Raw task data
         * @return self New Task instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }
    }
