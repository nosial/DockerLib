<?php

    namespace DockerLib\Objects;

    use DockerLib\Interfaces\SerializableInterface;
    use DockerLib\Objects\Container\Port;
    use DockerLib\Objects\Container\Mount;
    use DockerLib\Objects\Container\HostConfig;
    use DockerLib\Objects\Container\NetworkSettings;
    use DockerLib\Objects\Container\ContainerConfig;

    /**
     * Represents a Docker container with all its configuration and state information
     * 
     * This class encapsulates all information about a Docker container including
     * its state, network settings, mounts, ports, labels, and runtime configuration.
     * It can represent both list and inspect API responses.
     */
    class Container implements SerializableInterface
    {
        /**
         * @var string|null The unique identifier of the container
         */
        private ?string $id;

        /**
         * @var array<string> List of container names (with leading slashes from Docker API)
         */
        private array $names;

        /**
         * @var string|null The primary name of the container (without leading slash)
         */
        private ?string $name;

        /**
         * @var string|null The image name or ID used to create this container
         */
        private ?string $image;

        /**
         * @var string|null The full image ID (SHA256 hash)
         */
        private ?string $imageId;

        /**
         * @var string|null The command being run in the container
         */
        private ?string $command;

        /**
         * @var int|null Unix timestamp when the container was created
         */
        private ?int $created;

        /**
         * @var string|null The current state of the container (e.g., "running", "exited", "paused")
         */
        private ?string $state;

        /**
         * @var array<string, mixed>|null Full state object from container inspect
         */
        private ?array $stateArray;

        /**
         * @var string|null Human-readable status string
         */
        private ?string $status;

        /**
         * @var array<Port> List of port mappings for the container
         */
        private array $ports;

        /**
         * @var array<string, string> User-defined metadata labels
         */
        private array $labels;

        /**
         * @var int|null Size of files that have been created or changed in the container
         */
        private ?int $sizeRw;

        /**
         * @var int|null Total size of all the files in the container
         */
        private ?int $sizeRootFs;

        /**
         * @var HostConfig|null Container host configuration (resource limits, restart policy, etc.)
         */
        private ?HostConfig $hostConfig;

        /**
         * @var NetworkSettings|null Container network configuration and current network state
         */
        private ?NetworkSettings $networkSettings;

        /**
         * @var array<Mount> List of volume and bind mounts attached to the container
         */
        private array $mounts;

        /**
         * @var ContainerConfig|null Runtime configuration for the container
         */
        private ?ContainerConfig $config;

        /**
         * @var string|null Path to the command being executed
         */
        private ?string $path;

        /**
         * @var array<string> Command-line arguments
         */
        private array $args;

        /**
         * @var int Number of times the container has been restarted
         */
        private int $restartCount;

        /**
         * @var string|null Platform the container is running on
         */
        private ?string $platform;

        /**
         * Creates a new Container instance from Docker API data
         *
         * @param array<string, mixed> $data Raw container data from Docker API (list or inspect response)
         */
        public function __construct(array $data = [])
        {
            $names = $data['Names'] ?? [];
            
            $name = null;
            if (isset($data['Name']) && is_string($data['Name'])) {
                $name = ltrim($data['Name'], '/');
            } elseif (!empty($names)) {
                $name = ltrim($names[0], '/');
            }

            $state = null;
            $stateArray = null;
            if (isset($data['State'])) {
                if (is_array($data['State'])) {
                    $stateArray = $data['State'];
                    $state = $data['State']['Status'] ?? null;
                } else {
                    $state = $data['State'];
                }
            }
            if ($state === null) {
                $state = $data['Status'] ?? null;
            }

            $ports = [];
            foreach ($data['Ports'] ?? [] as $portData) {
                $ports[] = Port::fromArray($portData);
            }

            $mounts = [];
            foreach ($data['Mounts'] ?? [] as $mountData) {
                $mounts[] = Mount::fromArray($mountData);
            }

            $hostConfig = null;
            if (isset($data['HostConfig']) && is_array($data['HostConfig'])) {
                $hostConfig = HostConfig::fromArray($data['HostConfig']);
            }

            $networkSettings = null;
            if (isset($data['NetworkSettings']) && is_array($data['NetworkSettings'])) {
                $networkSettings = NetworkSettings::fromArray($data['NetworkSettings']);
            }

            $config = null;
            if (isset($data['Config']) && is_array($data['Config'])) {
                $config = ContainerConfig::fromArray($data['Config']);
            }

            $this->id = $data['Id'] ?? null;
            $this->names = $names;
            $this->name = $name;
            $this->image = $data['Image'] ?? null;
            $this->imageId = $data['ImageID'] ?? null;
            $this->command = $data['Command'] ?? null;
            $this->created = isset($data['Created']) ? (int)$data['Created'] : null;
            $this->state = $state;
            $this->stateArray = $stateArray;
            $this->status = $data['Status'] ?? null;
            $this->ports = $ports;
            $this->labels = $data['Labels'] ?? [];
            $this->sizeRw = $data['SizeRw'] ?? null;
            $this->sizeRootFs = $data['SizeRootFs'] ?? null;
            $this->hostConfig = $hostConfig;
            $this->networkSettings = $networkSettings;
            $this->mounts = $mounts;
            $this->config = $config;
            $this->path = $data['Path'] ?? null;
            $this->args = $data['Args'] ?? [];
            $this->restartCount = $data['RestartCount'] ?? 0;
            $this->platform = $data['Platform'] ?? null;
        }

        /**
         * Gets the unique identifier of the container
         *
         * @return string|null The container ID
         */
        public function getId(): ?string
        {
            return $this->id;
        }

        /**
         * Gets all container names
         *
         * @return array<string> List of names (as returned by Docker API with leading slashes)
         */
        public function getNames(): array
        {
            return $this->names;
        }

        /**
         * Gets the primary container name
         *
         * @return string|null The container name (without leading slash)
         */
        public function getName(): ?string
        {
            return $this->name;
        }

        /**
         * Gets the image name or ID
         *
         * @return string|null The image used to create this container
         */
        public function getImage(): ?string
        {
            return $this->image;
        }

        /**
         * Gets the full image ID
         *
         * @return string|null The image ID (SHA256 hash)
         */
        public function getImageID(): ?string
        {
            return $this->imageId;
        }

        /**
         * Gets the command being run
         *
         * @return string|null The command string
         */
        public function getCommand(): ?string
        {
            return $this->command;
        }

        /**
         * Gets the creation timestamp
         *
         * @return int|null Unix timestamp when container was created
         */
        public function getCreated(): ?int
        {
            return $this->created;
        }

        /**
         * Returns the container state
         * If full state data is available (from inspect), returns the state array
         * Otherwise returns the state string
         * 
         * @return string|array<string, mixed>|null State string or full state object
         */
        public function getState(): array|string|null
        {
            return $this->stateArray ?? $this->state;
        }

        /**
         * Returns the state as a string only
         * 
         * @return string|null Returns the state string (e.g., 'running', 'paused', 'exited')
         */
        public function getStateString(): ?string
        {
            return $this->state;
        }

        /**
         * Returns the full state array if available
         * 
         * @return array<string, mixed>|null Returns the full state object from Docker
         */
        public function getStateArray(): ?array
        {
            return $this->stateArray;
        }

        /**
         * Gets the human-readable status string
         *
         * @return string|null Status description (e.g., "Up 5 minutes", "Exited (0) 2 hours ago")
         */
        public function getStatus(): ?string
        {
            return $this->status;
        }

        /**
         * Gets the port mappings
         *
         * @return array<Port> List of port binding objects
         */
        public function getPorts(): array
        {
            return $this->ports;
        }

        /**
         * Gets the container labels
         *
         * @return array<string, string> User-defined metadata labels
         */
        public function getLabels(): array
        {
            // If we have labels directly on the container, use those (from list)
            if (!empty($this->labels)) {
                return $this->labels;
            }
            // Otherwise try to get from config (from inspect)
            return $this->config?->getLabels() ?? [];
        }

        /**
         * Gets the writable layer size
         *
         * @return int|null Size of files created or changed in bytes
         */
        public function getSizeRw(): ?int
        {
            return $this->sizeRw;
        }

        /**
         * Gets the total size
         *
         * @return int|null Total size of all files in bytes
         */
        public function getSizeRootFs(): ?int
        {
            return $this->sizeRootFs;
        }

        /**
         * Returns the HostConfig as an array
         * 
         * @return array<string, mixed>|null Returns the HostConfig array if object exists, otherwise null
         */
        public function getHostConfig(): ?array
        {
            return $this->hostConfig?->toArray();
        }

        /**
         * Returns the HostConfig object
         * 
         * @return HostConfig|null Returns the HostConfig object if available
         */
        public function getHostConfigObject(): ?HostConfig
        {
            return $this->hostConfig;
        }

        /**
         * Returns the NetworkSettings as an array
         * 
         * @return array<string, mixed>|null Returns the NetworkSettings array if object exists, otherwise null
         */
        public function getNetworkSettings(): ?array
        {
            return $this->networkSettings?->toArray();
        }

        /**
         * Returns the NetworkSettings object
         * 
         * @return NetworkSettings|null Returns the NetworkSettings object if available
         */
        public function getNetworkSettingsObject(): ?NetworkSettings
        {
            return $this->networkSettings;
        }

        /**
         * Gets the volume and bind mounts
         *
         * @return array<Mount> List of mount objects
         */
        public function getMounts(): array
        {
            return $this->mounts;
        }

        /**
         * Returns the ContainerConfig as an array
         * 
         * @return array<string, mixed>|null Returns the ContainerConfig array if object exists, otherwise null
         */
        public function getConfig(): ?array
        {
            return $this->config?->toArray();
        }

        /**
         * Returns the ContainerConfig object
         * 
         * @return ContainerConfig|null Returns the ContainerConfig object if available
         */
        public function getConfigObject(): ?ContainerConfig
        {
            return $this->config;
        }

        /**
         * Gets the path to the command being executed
         *
         * @return string|null Command path
         */
        public function getPath(): ?string
        {
            return $this->path;
        }

        /**
         * Gets the command-line arguments
         *
         * @return array<string> List of arguments
         */
        public function getArgs(): array
        {
            return $this->args;
        }

        /**
         * Gets the restart count
         *
         * @return int Number of times container has been restarted
         */
        public function getRestartCount(): int
        {
            return $this->restartCount;
        }

        /**
         * Gets the platform
         *
         * @return string|null Platform identifier
         */
        public function getPlatform(): ?string
        {
            return $this->platform;
        }

        /**
         * Checks if the container is currently running
         *
         * @return bool True if running, false otherwise
         */
        public function isRunning(): bool
        {
            return $this->state === 'running';
        }

        /**
         * Checks if the container is paused
         *
         * @return bool True if paused, false otherwise
         */
        public function isPaused(): bool
        {
            return $this->state === 'paused';
        }

        /**
         * Gets the environment variables
         *
         * @return array<string> List of environment variables in KEY=value format
         */
        public function getEnvironment(): array
        {
            return $this->config?->getEnv() ?? [];
        }

        /**
         * Converts the container to an array representation
         *
         * @return array<string, mixed> Array containing all container properties
         */
        public function toArray(): array
        {
            return [
                'Id' => $this->id,
                'Names' => $this->names,
                'Name' => $this->name,
                'Image' => $this->image,
                'ImageID' => $this->imageId,
                'Command' => $this->command,
                'Created' => $this->created,
                'State' => $this->state,
                'Status' => $this->status,
                'Ports' => array_map(fn(Port $port) => $port->toArray(), $this->ports),
                'Labels' => $this->labels,
                'SizeRw' => $this->sizeRw,
                'SizeRootFs' => $this->sizeRootFs,
                'HostConfig' => $this->hostConfig?->toArray(),
                'NetworkSettings' => $this->networkSettings?->toArray(),
                'Mounts' => array_map(fn(Mount $mount) => $mount->toArray(), $this->mounts),
                'Config' => $this->config?->toArray(),
                'Path' => $this->path,
                'Args' => $this->args,
                'RestartCount' => $this->restartCount,
                'Platform' => $this->platform,
            ];
        }

        /**
         * Gets raw data as array (deprecated)
         *
         * @return array<string, mixed> Array containing all container properties
         * @deprecated Use toArray() instead
         */
        public function getRawData(): array
        {
            return $this->toArray();
        }

        /**
         * Creates a Container instance from an array
         *
         * @param array<string, mixed> $data Container data from Docker API
         * @return self New Container instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }
    }
