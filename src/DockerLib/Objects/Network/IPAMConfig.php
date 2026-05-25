<?php

    namespace DockerLib\Objects\Network;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents a single IPAM configuration block for a Docker network.
     *
     * This class defines the network configuration including subnet, gateway, and IP range
     * settings for IP address allocation within a Docker network.
     *
     * @package DockerLib\Objects\Network
     */
    class IPAMConfig implements SerializableInterface
    {
        /**
         * The subnet in CIDR notation (e.g., '192.168.1.0/24').
         *
         * @var string|null
         */
        private ?string $subnet;

        /**
         * The gateway IP address for the subnet.
         *
         * @var string|null
         */
        private ?string $gateway;

        /**
         * The IP range within the subnet to allocate container IPs from, in CIDR notation.
         *
         * @var string|null
         */
        private ?string $ipRange;

        /**
         * Constructs an IPAMConfig instance from an array of data.
         *
         * @param array $data Associative array containing configuration data with keys
         *                    'subnet'/'Subnet', 'gateway'/'Gateway', and 'ipRange'/'IPRange'
         */
        public function __construct(array $data = [])
        {
            $this->subnet = $data['subnet'] ?? $data['Subnet'] ?? null;
            $this->gateway = $data['gateway'] ?? $data['Gateway'] ?? null;
            $this->ipRange = $data['ipRange'] ?? $data['IPRange'] ?? null;
        }

        /**
         * Gets the subnet in CIDR notation.
         *
         * @return string|null The subnet or null if not set
         */
        public function getSubnet(): ?string
        {
            return $this->subnet;
        }

        /**
         * Gets the gateway IP address.
         *
         * @return string|null The gateway IP address or null if not set
         */
        public function getGateway(): ?string
        {
            return $this->gateway;
        }

        /**
         * Gets the IP range for container IP allocation.
         *
         * @return string|null The IP range in CIDR notation or null if not set
         */
        public function getIpRange(): ?string
        {
            return $this->ipRange;
        }

        /**
         * Converts the IPAMConfig object to an array representation.
         *
         * @return array Associative array with 'Subnet', 'Gateway', and 'IPRange' keys
         */
        public function toArray(): array
        {
            return [
                'Subnet' => $this->subnet,
                'Gateway' => $this->gateway,
                'IPRange' => $this->ipRange,
            ];
        }

        /**
         * Creates an IPAMConfig instance from an array of data.
         *
         * @param array $data Associative array containing configuration data
         * @return self New IPAMConfig instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }
    }
