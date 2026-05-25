<?php

    namespace DockerLib\Objects\Container;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents Docker container network settings.
     * 
     * This class encapsulates network configuration and runtime network state
     * for a container, including IP addresses, gateways, network interfaces,
     * and port bindings.
     */
    class NetworkSettings implements SerializableInterface
    {
        /**
         * @var string|null The name of the bridge network
         */
        private ?string $bridge;
        
        /**
         * @var string|null The network sandbox ID
         */
        private ?string $sandboxId;
        
        /**
         * @var bool Whether hairpin NAT mode is enabled
         */
        private bool $hairpinMode;
        
        /**
         * @var string|null The link-local IPv6 address
         */
        private ?string $linkLocalIPv6Address;
        
        /**
         * @var int|null The link-local IPv6 address prefix length
         */
        private ?int $linkLocalIPv6PrefixLen;
        
        /**
         * @var string|null The network sandbox key
         */
        private ?string $sandboxKey;
        
        /**
         * @var string|null The IPv4 address assigned to the container
         */
        private ?string $ipAddress;
        
        /**
         * @var int|null The IPv4 address prefix length
         */
        private ?int $ipPrefixLen;
        
        /**
         * @var string|null The IPv4 gateway address
         */
        private ?string $gateway;
        
        /**
         * @var string|null The global IPv6 address assigned to the container
         */
        private ?string $globalIPv6Address;
        
        /**
         * @var int|null The global IPv6 address prefix length
         */
        private ?int $globalIPv6PrefixLen;
        
        /**
         * @var string|null The IPv6 gateway address
         */
        private ?string $ipv6Gateway;
        
        /**
         * @var string|null The MAC address assigned to the container
         */
        private ?string $macAddress;
        
        /**
         * @var array<string, mixed> Network interfaces configuration
         */
        private array $networks;
        
        /**
         * @var array<string, mixed> Port bindings configuration
         */
        private array $ports;

        /**
         * Constructs a new NetworkSettings instance from an array of data.
         * 
         * Accepts both camelCase and PascalCase keys for compatibility with
         * Docker API responses and internal data structures.
         *
         * @param array<string, mixed> $data Network settings data
         */
        public function __construct(array $data)
        {
            $this->bridge = $data['bridge'] ?? $data['Bridge'] ?? null;
            $this->sandboxId = $data['sandboxId'] ?? $data['SandboxID'] ?? null;
            $this->hairpinMode = $data['hairpinMode'] ?? $data['HairpinMode'] ?? false;
            $this->linkLocalIPv6Address = $data['linkLocalIPv6Address'] ?? $data['LinkLocalIPv6Address'] ?? null;
            $this->linkLocalIPv6PrefixLen = $data['linkLocalIPv6PrefixLen'] ?? $data['LinkLocalIPv6PrefixLen'] ?? null;
            $this->sandboxKey = $data['sandboxKey'] ?? $data['SandboxKey'] ?? null;
            $this->ipAddress = $data['ipAddress'] ?? $data['IPAddress'] ?? null;
            $this->ipPrefixLen = $data['ipPrefixLen'] ?? $data['IPPrefixLen'] ?? null;
            $this->gateway = $data['gateway'] ?? $data['Gateway'] ?? null;
            $this->globalIPv6Address = $data['globalIPv6Address'] ?? $data['GlobalIPv6Address'] ?? null;
            $this->globalIPv6PrefixLen = $data['globalIPv6PrefixLen'] ?? $data['GlobalIPv6PrefixLen'] ?? null;
            $this->ipv6Gateway = $data['ipv6Gateway'] ?? $data['IPv6Gateway'] ?? null;
            $this->macAddress = $data['macAddress'] ?? $data['MacAddress'] ?? null;
            $this->networks = $data['networks'] ?? $data['Networks'] ?? [];
            $this->ports = $data['ports'] ?? $data['Ports'] ?? [];
        }

        /**
         * Gets the bridge network name.
         *
         * @return string|null The bridge name or null if not set
         */
        public function getBridge(): ?string
        {
            return $this->bridge;
        }

        /**
         * Gets the network sandbox ID.
         *
         * @return string|null The sandbox ID or null if not set
         */
        public function getSandboxId(): ?string
        {
            return $this->sandboxId;
        }

        /**
         * Checks if hairpin NAT mode is enabled.
         *
         * @return bool True if hairpin mode is enabled, false otherwise
         */
        public function isHairpinMode(): bool
        {
            return $this->hairpinMode;
        }

        /**
         * Gets the link-local IPv6 address.
         *
         * @return string|null The link-local IPv6 address or null if not set
         */
        public function getLinkLocalIPv6Address(): ?string
        {
            return $this->linkLocalIPv6Address;
        }

        /**
         * Gets the link-local IPv6 address prefix length.
         *
         * @return int|null The prefix length or null if not set
         */
        public function getLinkLocalIPv6PrefixLen(): ?int
        {
            return $this->linkLocalIPv6PrefixLen;
        }

        /**
         * Gets the network sandbox key.
         *
         * @return string|null The sandbox key or null if not set
         */
        public function getSandboxKey(): ?string
        {
            return $this->sandboxKey;
        }

        /**
         * Gets the IPv4 address assigned to the container.
         *
         * @return string|null The IPv4 address or null if not set
         */
        public function getIpAddress(): ?string
        {
            return $this->ipAddress;
        }

        /**
         * Gets the IPv4 address prefix length.
         *
         * @return int|null The prefix length or null if not set
         */
        public function getIpPrefixLen(): ?int
        {
            return $this->ipPrefixLen;
        }

        /**
         * Gets the IPv4 gateway address.
         *
         * @return string|null The gateway address or null if not set
         */
        public function getGateway(): ?string
        {
            return $this->gateway;
        }

        /**
         * Gets the global IPv6 address assigned to the container.
         *
         * @return string|null The global IPv6 address or null if not set
         */
        public function getGlobalIPv6Address(): ?string
        {
            return $this->globalIPv6Address;
        }

        /**
         * Gets the global IPv6 address prefix length.
         *
         * @return int|null The prefix length or null if not set
         */
        public function getGlobalIPv6PrefixLen(): ?int
        {
            return $this->globalIPv6PrefixLen;
        }

        /**
         * Gets the IPv6 gateway address.
         *
         * @return string|null The IPv6 gateway address or null if not set
         */
        public function getIpv6Gateway(): ?string
        {
            return $this->ipv6Gateway;
        }

        /**
         * Gets the MAC address assigned to the container.
         *
         * @return string|null The MAC address or null if not set
         */
        public function getMacAddress(): ?string
        {
            return $this->macAddress;
        }

        /**
         * Gets the network interfaces configuration.
         *
         * @return array<string, mixed> The networks configuration
         */
        public function getNetworks(): array
        {
            return $this->networks;
        }

        /**
         * Gets the port bindings configuration.
         *
         * @return array<string, mixed> The ports configuration
         */
        public function getPorts(): array
        {
            return $this->ports;
        }

        /**
         * Converts the NetworkSettings to an array representation.
         * 
         * Returns data in PascalCase format compatible with Docker API.
         *
         * @return array<string, mixed> The network settings as an associative array
         */
        public function toArray(): array
        {
            return [
                'Bridge' => $this->bridge,
                'SandboxID' => $this->sandboxId,
                'HairpinMode' => $this->hairpinMode,
                'LinkLocalIPv6Address' => $this->linkLocalIPv6Address,
                'LinkLocalIPv6PrefixLen' => $this->linkLocalIPv6PrefixLen,
                'SandboxKey' => $this->sandboxKey,
                'IPAddress' => $this->ipAddress,
                'IPPrefixLen' => $this->ipPrefixLen,
                'Gateway' => $this->gateway,
                'GlobalIPv6Address' => $this->globalIPv6Address,
                'GlobalIPv6PrefixLen' => $this->globalIPv6PrefixLen,
                'IPv6Gateway' => $this->ipv6Gateway,
                'MacAddress' => $this->macAddress,
                'Networks' => $this->networks,
                'Ports' => $this->ports,
            ];
        }

        /**
         * Creates a NetworkSettings instance from an array of data.
         *
         * @param array<string, mixed> $data Network settings data
         * @return self A new NetworkSettings instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }
    }
