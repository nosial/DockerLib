<?php

    namespace DockerLib\Objects;

    use DockerLib\Interfaces\SerializableInterface;
    use DockerLib\Objects\Network\IPAM;

    /**
     * Represents a Docker network
     * 
     * This class encapsulates all information about a Docker network including
     * its driver, IPAM configuration, scope, and connected containers.
     */
    class Network implements SerializableInterface
    {
        /**
         * @var string|null The unique identifier of the network
         */
        private ?string $id;

        /**
         * @var string|null The name of the network
         */
        private ?string $name;

        /**
         * @var string|null Timestamp when the network was created (ISO 8601 format)
         */
        private ?string $created;

        /**
         * @var string|null The scope of the network (e.g., "local", "swarm", "global")
         */
        private ?string $scope;

        /**
         * @var string|null The network driver (e.g., "bridge", "overlay", "host")
         */
        private ?string $driver;

        /**
         * @var bool Whether IPv6 is enabled for this network
         */
        private bool $enableIPv6;

        /**
         * @var IPAM|null IP Address Management configuration
         */
        private ?IPAM $ipam;

        /**
         * @var bool Whether this is an internal network (no external connectivity)
         */
        private bool $internal;

        /**
         * @var bool Whether containers can attach to this network manually
         */
        private bool $attachable;

        /**
         * @var bool Whether this is the ingress network for swarm routing mesh
         */
        private bool $ingress;

        /**
         * @var array<string, mixed> Map of containers attached to this network
         */
        private array $containers;

        /**
         * @var array<string, string> Driver-specific options
         */
        private array $options;

        /**
         * @var array<string, string> User-defined metadata labels
         */
        private array $labels;

        /**
         * @var array<string, mixed>|null Configuration source for network config
         */
        private ?array $configFrom;

        /**
         * @var bool Whether this is a configuration-only network
         */
        private bool $configOnly;

        /**
         * Creates a new Network instance from Docker API data
         *
         * @param array<string, mixed> $data Raw network data from Docker API
         */
        public function __construct(array $data = [])
        {
            $ipam = null;
            if (isset($data['IPAM']) && is_array($data['IPAM'])) {
                $ipam = IPAM::fromArray($data['IPAM']);
            }

            $this->id = $data['Id'] ?? null;
            $this->name = $data['Name'] ?? null;
            $this->created = $data['Created'] ?? null;
            $this->scope = $data['Scope'] ?? null;
            $this->driver = $data['Driver'] ?? null;
            $this->enableIPv6 = $data['EnableIPv6'] ?? false;
            $this->ipam = $ipam;
            $this->internal = $data['Internal'] ?? false;
            $this->attachable = $data['Attachable'] ?? false;
            $this->ingress = $data['Ingress'] ?? false;
            $this->containers = $data['Containers'] ?? [];
            $this->options = $data['Options'] ?? [];
            $this->labels = $data['Labels'] ?? [];
            $this->configFrom = $data['ConfigFrom'] ?? null;
            $this->configOnly = $data['ConfigOnly'] ?? false;
        }

        /**
         * Gets the unique identifier of the network
         *
         * @return string|null The network ID
         */
        public function getId(): ?string
        {
            return $this->id;
        }

        /**
         * Gets the network name
         *
         * @return string|null The network name
         */
        public function getName(): ?string
        {
            return $this->name;
        }

        /**
         * Gets the creation timestamp
         *
         * @return string|null ISO 8601 formatted timestamp
         */
        public function getCreated(): ?string
        {
            return $this->created;
        }

        /**
         * Gets the network scope
         *
         * @return string|null Scope ("local", "swarm", "global")
         */
        public function getScope(): ?string
        {
            return $this->scope;
        }

        /**
         * Gets the network driver
         *
         * @return string|null Driver name (e.g., "bridge", "overlay", "host")
         */
        public function getDriver(): ?string
        {
            return $this->driver;
        }

        /**
         * Checks if IPv6 is enabled
         *
         * @return bool True if IPv6 is enabled
         */
        public function getEnableIPv6(): bool
        {
            return $this->enableIPv6;
        }

        /**
         * Gets the IPAM configuration
         *
         * @return IPAM|null IP Address Management configuration
         */
        public function getIpam(): ?IPAM
        {
            return $this->ipam;
        }

        /**
         * Checks if this is an internal network
         *
         * @return bool True if internal (no external connectivity)
         */
        public function getInternal(): bool
        {
            return $this->internal;
        }

        /**
         * Checks if the network is attachable
         *
         * @return bool True if containers can manually attach
         */
        public function getAttachable(): bool
        {
            return $this->attachable;
        }

        /**
         * Checks if this is the ingress network
         *
         * @return bool True if this is the swarm routing mesh ingress network
         */
        public function getIngress(): bool
        {
            return $this->ingress;
        }

        /**
         * Gets the attached containers
         *
         * @return array<string, mixed> Map of container IDs to their network info
         */
        public function getContainers(): array
        {
            return $this->containers;
        }

        /**
         * Gets the driver options
         *
         * @return array<string, string> Driver-specific configuration options
         */
        public function getOptions(): array
        {
            return $this->options;
        }

        /**
         * Gets the network labels
         *
         * @return array<string, string> User-defined metadata labels
         */
        public function getLabels(): array
        {
            return $this->labels;
        }

        /**
         * Gets the configuration source
         *
         * @return array<string, mixed>|null Network config source reference
         */
        public function getConfigFrom(): ?array
        {
            return $this->configFrom;
        }

        /**
         * Checks if this is a config-only network
         *
         * @return bool True if this is configuration-only
         */
        public function getConfigOnly(): bool
        {
            return $this->configOnly;
        }

        /**
         * Converts the network to an array representation
         *
         * @return array<string, mixed> Array containing all network properties
         */
        public function toArray(): array
        {
            return [
                'Id' => $this->id,
                'Name' => $this->name,
                'Created' => $this->created,
                'Scope' => $this->scope,
                'Driver' => $this->driver,
                'EnableIPv6' => $this->enableIPv6,
                'IPAM' => $this->ipam?->toArray(),
                'Internal' => $this->internal,
                'Attachable' => $this->attachable,
                'Ingress' => $this->ingress,
                'Containers' => $this->containers,
                'Options' => $this->options,
                'Labels' => $this->labels,
                'ConfigFrom' => $this->configFrom,
                'ConfigOnly' => $this->configOnly,
            ];
        }

        /**
         * Creates a Network instance from an array
         *
         * @param array<string, mixed> $data Network data from Docker API
         * @return self New Network instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }

        /**
         * Gets raw data as array (deprecated)
         *
         * @return array<string, mixed> Array containing all network properties
         * @deprecated Use toArray() instead
         */
        public function getRawData(): array
        {
            return $this->toArray();
        }
    }
