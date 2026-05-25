<?php

    namespace DockerLib\Objects\Network;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents the IP Address Management (IPAM) configuration for a Docker network.
     *
     * This class encapsulates the IPAM settings including the driver, network configurations,
     * and driver-specific options used to manage IP address allocation within a Docker network.
     *
     * @package DockerLib\Objects\Network
     */
    class IPAM implements SerializableInterface
    {
        /**
         * The IPAM driver name (e.g., 'default', 'bridge').
         *
         * @var string
         */
        private string $driver;

        /**
         * Array of IPAM configuration blocks defining subnets, gateways, and IP ranges.
         *
         * @var array<IPAMConfig>
         */
        private array $config;

        /**
         * Driver-specific options for IPAM configuration.
         *
         * @var array<string, string>
         */
        private array $options;

        /**
         * Constructs an IPAM instance from an array of data.
         *
         * @param array $data Associative array containing IPAM data with keys 'driver'/'Driver',
         *                    'config'/'Config', and 'options'/'Options'
         */
        public function __construct(array $data = [])
        {
            $this->driver = $data['driver'] ?? $data['Driver'] ?? 'default';
            
            $config = [];
            foreach ($data['config'] ?? $data['Config'] ?? [] as $configData) {
                if ($configData instanceof IPAMConfig) {
                    $config[] = $configData;
                } elseif (is_array($configData)) {
                    $config[] = IPAMConfig::fromArray($configData);
                }
            }
            $this->config = $config;
            
            $this->options = $data['options'] ?? $data['Options'] ?? [];
        }

        /**
         * Gets the IPAM driver name.
         *
         * @return string The IPAM driver name
         */
        public function getDriver(): string
        {
            return $this->driver;
        }

        /**
         * Gets the array of IPAM configuration blocks.
         *
         * @return array<IPAMConfig> Array of IPAMConfig objects
         */
        public function getConfig(): array
        {
            return $this->config;
        }

        /**
         * Gets the driver-specific options.
         *
         * @return array<string, string> Associative array of option key-value pairs
         */
        public function getOptions(): array
        {
            return $this->options;
        }

        /**
         * Converts the IPAM object to an array representation.
         *
         * @return array Associative array with 'Driver', 'Config', and 'Options' keys
         */
        public function toArray(): array
        {
            return [
                'Driver' => $this->driver,
                'Config' => array_map(fn(IPAMConfig $c) => $c->toArray(), $this->config),
                'Options' => $this->options,
            ];
        }

        /**
         * Creates an IPAM instance from an array of data.
         *
         * @param array $data Associative array containing IPAM data
         * @return self New IPAM instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }
    }
