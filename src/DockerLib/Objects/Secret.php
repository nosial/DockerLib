<?php

    namespace DockerLib\Objects;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents a Docker Swarm secret
     * 
     * Secrets provide secure storage for sensitive data like passwords, certificates,
     * and API keys. They are encrypted at rest and in transit to containers.
     */
    class Secret implements SerializableInterface
    {
        /**
         * Unique identifier of the secret
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
         * Timestamp when the secret was created
         * 
         * @var string|null
         */
        private ?string $createdAt;
        
        /**
         * Timestamp when the secret was last updated
         * 
         * @var string|null
         */
        private ?string $updatedAt;
        
        /**
         * Secret specification including name, labels, and driver
         * 
         * @var array<string, mixed>
         */
        private array $spec;

        /**
         * Create a new Secret instance
         * 
         * @param array<string, mixed> $data Raw secret data from Docker API
         */
        public function __construct(array $data = [])
        {
            $this->id = $data['ID'] ?? null;
            $this->version = $data['Version'] ?? null;
            $this->createdAt = $data['CreatedAt'] ?? null;
            $this->updatedAt = $data['UpdatedAt'] ?? null;
            $this->spec = $data['Spec'] ?? [];
        }

        /**
         * Get the secret ID
         * 
         * @return string|null Unique identifier
         */
        public function getId(): ?string
        {
            return $this->id;
        }

        /**
         * Get the secret version
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
         * Get the full secret specification
         * 
         * @return array<string, mixed> Complete secret spec including name, labels, and driver
         */
        public function getSpec(): array
        {
            return $this->spec;
        }

        /**
         * Get the secret name
         * 
         * @return string|null Secret name from the spec
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
         * Get the secret data
         * 
         * @return string|null Base64-encoded secret data
         */
        public function getData(): ?string
        {
            return $this->spec['Data'] ?? null;
        }

        /**
         * Get the secret driver configuration
         * 
         * @return array<string, mixed>|null Driver information for external secret storage
         */
        public function getDriver(): ?array
        {
            return $this->spec['Driver'] ?? null;
        }

        /**
         * Get the templating configuration
         * 
         * @return array<string, mixed>|null Template driver information if applicable
         */
        public function getTemplating(): ?array
        {
            return $this->spec['Templating'] ?? null;
        }

        /**
         * Convert the secret to an array representation
         * 
         * @return array<string, mixed> Array containing all secret properties
         */
        public function toArray(): array
        {
            return [
                'ID' => $this->id,
                'Version' => $this->version,
                'CreatedAt' => $this->createdAt,
                'UpdatedAt' => $this->updatedAt,
                'Spec' => $this->spec,
            ];
        }

        /**
         * Create a Secret instance from an array
         * 
         * @param array<string, mixed> $data Raw secret data
         * @return self New Secret instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }
    }
