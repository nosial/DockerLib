<?php

    namespace DockerLib\Objects;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents a Docker Swarm service
     * 
     * Services define how containers should be deployed and managed in a swarm cluster.
     * They include specifications for tasks, replicas, networks, and update strategies.
     */
    class Service implements SerializableInterface
    {
        /**
         * Unique identifier of the service
         * 
         * @var string|null
         */
        private ?string $id;
        
        /**
         * Version information for optimistic concurrency control
         * 
         * @var array<string, int>|null
         */
        private ?array $version;
        
        /**
         * Timestamp when the service was created
         * 
         * @var string|null
         */
        private ?string $createdAt;
        
        /**
         * Timestamp when the service was last updated
         * 
         * @var string|null
         */
        private ?string $updatedAt;
        
        /**
         * Service specification including name, labels, and task template
         * 
         * @var array<string, mixed>
         */
        private array $spec;
        
        /**
         * Network endpoint configuration for the service
         * 
         * @var array<string, mixed>|null
         */
        private ?array $endpoint;
        
        /**
         * Status of the last service update operation
         * 
         * @var array<string, mixed>|null
         */
        private ?array $updateStatus;
        
        /**
         * Current status of the service
         * 
         * @var array<string, mixed>|null
         */
        private ?array $serviceStatus;
        
        /**
         * Status for job-mode services
         * 
         * @var array<string, mixed>|null
         */
        private ?array $jobStatus;

        /**
         * Create a new Service instance
         * 
         * @param array<string, mixed> $data Raw service data from Docker API
         */
        public function __construct(array $data = [])
        {
            $this->id = $data['ID'] ?? null;
            $this->version = $data['Version'] ?? null;
            $this->createdAt = $data['CreatedAt'] ?? null;
            $this->updatedAt = $data['UpdatedAt'] ?? null;
            $this->spec = $data['Spec'] ?? [];
            $this->endpoint = $data['Endpoint'] ?? null;
            $this->updateStatus = $data['UpdateStatus'] ?? null;
            $this->serviceStatus = $data['ServiceStatus'] ?? null;
            $this->jobStatus = $data['JobStatus'] ?? null;
        }

        /**
         * Get the service ID
         * 
         * @return string|null Unique identifier
         */
        public function getId(): ?string
        {
            return $this->id;
        }

        /**
         * Get the service version
         * 
         * @return array<string, int>|null Version object with index
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
         * Get the full service specification
         * 
         * @return array<string, mixed> Complete service spec including name, labels, and task template
         */
        public function getSpec(): array
        {
            return $this->spec;
        }

        /**
         * Get the service endpoint configuration
         * 
         * @return array<string, mixed>|null Endpoint settings including ports and virtual IPs
         */
        public function getEndpoint(): ?array
        {
            return $this->endpoint;
        }

        /**
         * Get the update status
         * 
         * @return array<string, mixed>|null Status of the last update operation
         */
        public function getUpdateStatus(): ?array
        {
            return $this->updateStatus;
        }

        /**
         * Get the service status
         * 
         * @return array<string, mixed>|null Current service status information
         */
        public function getServiceStatus(): ?array
        {
            return $this->serviceStatus;
        }

        /**
         * Get the job status for job-mode services
         * 
         * @return array<string, mixed>|null Job execution status
         */
        public function getJobStatus(): ?array
        {
            return $this->jobStatus;
        }

        /**
         * Get the service name
         * 
         * @return string|null Service name from the spec
         */
        public function getName(): ?string
        {
            return $this->spec['Name'] ?? null;
        }

        /**
         * Get user-defined labels
         * 
         * @return array<string, string> Key-value pairs of metadata labels
         */
        public function getLabels(): array
        {
            return $this->spec['Labels'] ?? [];
        }

        /**
         * Get the service mode configuration
         * 
         * @return array<string, mixed>|null Mode settings (replicated or global)
         */
        public function getMode(): ?array
        {
            return $this->spec['Mode'] ?? null;
        }

        /**
         * Get the task template
         * 
         * @return array<string, mixed>|null Template defining how tasks should be created
         */
        public function getTaskTemplate(): ?array
        {
            return $this->spec['TaskTemplate'] ?? null;
        }

        /**
         * Convert the service to an array representation
         * 
         * @return array<string, mixed> Array containing all service properties
         */
        public function toArray(): array
        {
            return [
                'ID' => $this->id,
                'Version' => $this->version,
                'CreatedAt' => $this->createdAt,
                'UpdatedAt' => $this->updatedAt,
                'Spec' => $this->spec,
                'Endpoint' => $this->endpoint,
                'UpdateStatus' => $this->updateStatus,
                'ServiceStatus' => $this->serviceStatus,
                'JobStatus' => $this->jobStatus,
            ];
        }

        /**
         * Create a Service instance from an array
         * 
         * @param array<string, mixed> $data Raw service data
         * @return self New Service instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }
    }
