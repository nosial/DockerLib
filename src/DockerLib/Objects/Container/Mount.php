<?php

    namespace DockerLib\Objects\Container;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents a Docker container mount point.
     * 
     * This class encapsulates mount configuration for volumes, bind mounts, and tmpfs
     * mounts attached to a container, including source, destination, and mount options.
     */
    class Mount implements SerializableInterface
    {
        /**
         * @var string|null The mount type (e.g., 'bind', 'volume', 'tmpfs')
         */
        private ?string $type;
        
        /**
         * @var string|null The name of the volume (for named volumes)
         */
        private ?string $name;
        
        /**
         * @var string|null The source path on the host or volume name
         */
        private ?string $source;
        
        /**
         * @var string|null The destination path inside the container
         */
        private ?string $destination;
        
        /**
         * @var string|null The volume driver name (for volumes)
         */
        private ?string $driver;
        
        /**
         * @var string|null The mount mode (e.g., 'rw', 'ro')
         */
        private ?string $mode;
        
        /**
         * @var bool Whether the mount is read-write (true) or read-only (false)
         */
        private bool $rw;
        
        /**
         * @var string|null The bind propagation mode (e.g., 'rprivate', 'shared', 'rshared')
         */
        private ?string $propagation;

        /**
         * Constructs a new Mount instance from an array of data.
         * 
         * Accepts both camelCase and PascalCase keys for compatibility with
         * Docker API responses and internal data structures.
         *
         * @param array<string, mixed> $data Mount configuration data
         */
        public function __construct(array $data = [])
        {
            $this->type = $data['type'] ?? $data['Type'] ?? null;
            $this->name = $data['name'] ?? $data['Name'] ?? null;
            $this->source = $data['source'] ?? $data['Source'] ?? null;
            $this->destination = $data['destination'] ?? $data['Destination'] ?? null;
            $this->driver = $data['driver'] ?? $data['Driver'] ?? null;
            $this->mode = $data['mode'] ?? $data['Mode'] ?? null;
            $this->rw = $data['rw'] ?? $data['RW'] ?? false;
            $this->propagation = $data['propagation'] ?? $data['Propagation'] ?? null;
        }

        /**
         * Gets the mount type.
         *
         * @return string|null The mount type or null if not set
         */
        public function getType(): ?string
        {
            return $this->type;
        }

        /**
         * Gets the volume name.
         *
         * @return string|null The volume name or null if not set
         */
        public function getName(): ?string
        {
            return $this->name;
        }

        /**
         * Gets the source path on the host or volume name.
         *
         * @return string|null The source path or null if not set
         */
        public function getSource(): ?string
        {
            return $this->source;
        }

        /**
         * Gets the destination path inside the container.
         *
         * @return string|null The destination path or null if not set
         */
        public function getDestination(): ?string
        {
            return $this->destination;
        }

        /**
         * Gets the volume driver name.
         *
         * @return string|null The driver name or null if not set
         */
        public function getDriver(): ?string
        {
            return $this->driver;
        }

        /**
         * Gets the mount mode.
         *
         * @return string|null The mount mode or null if not set
         */
        public function getMode(): ?string
        {
            return $this->mode;
        }

        /**
         * Checks if the mount is read-write.
         *
         * @return bool True if read-write, false if read-only
         */
        public function isRw(): bool
        {
            return $this->rw;
        }

        /**
         * Gets the bind propagation mode.
         *
         * @return string|null The propagation mode or null if not set
         */
        public function getPropagation(): ?string
        {
            return $this->propagation;
        }

        /**
         * Converts the Mount to an array representation.
         * 
         * Returns data in PascalCase format compatible with Docker API.
         *
         * @return array<string, mixed> The mount configuration as an associative array
         */
        public function toArray(): array
        {
            return [
                'Type' => $this->type,
                'Name' => $this->name,
                'Source' => $this->source,
                'Destination' => $this->destination,
                'Driver' => $this->driver,
                'Mode' => $this->mode,
                'RW' => $this->rw,
                'Propagation' => $this->propagation,
            ];
        }

        /**
         * Creates a Mount instance from an array of data.
         *
         * @param array<string, mixed> $data Mount configuration data
         * @return self A new Mount instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }
    }
