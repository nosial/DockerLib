<?php

    namespace DockerLib\Objects;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents a Docker Swarm config
     * 
     * Configs allow storing non-sensitive configuration data that can be mounted
     * into service containers. Unlike secrets, configs are not encrypted at rest.
     */
    class Config implements SerializableInterface
    {
        /**
         * Unique identifier of the config
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
         * Timestamp when the config was created
         * 
         * @var string|null
         */
        private ?string $createdAt;
        
        /**
         * Timestamp when the config was last updated
         * 
         * @var string|null
         */
        private ?string $updatedAt;
        
        /**
         * Config specification including name, labels, and data
         * 
         * @var array<string, mixed>
         */
        private array $spec;

        /**
         * Create a new Config instance
         * 
         * @param array<string, mixed> $data Raw config data from Docker API
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
         * Get the config ID
         * 
         * @return string|null Unique identifier
         */
        public function getId(): ?string
        {
            return $this->id;
        }

        /**
         * Get the config version
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
         * Get the full config specification
         * 
         * @return array<string, mixed> Complete config spec including name, labels, and data
         */
        public function getSpec(): array
        {
            return $this->spec;
        }

        /**
         * Get the config name
         * 
         * @return string|null Config name from the spec
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
         * Get the config data
         * 
         * @return string|null Base64-encoded configuration data
         */
        public function getData(): ?string
        {
            return $this->spec['Data'] ?? null;
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
         * Convert the config to an array representation
         * 
         * @return array<string, mixed> Array containing all config properties
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
         * Create a Config instance from an array
         * 
         * @param array<string, mixed> $data Raw config data
         * @return self New Config instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }

        /**
         * Get the raw config data
         * 
         * @deprecated Use toArray() instead
         * @return array<string, mixed> Array representation of the config
         */
        public function getRawData(): array
        {
            return $this->toArray();
        }
    }
