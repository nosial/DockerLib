<?php

    namespace DockerLib\Objects\Container;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents a Docker container port mapping.
     * 
     * This class encapsulates port binding configuration for a container,
     * including the private (container) port, public (host) port, IP address,
     * and protocol type.
     */
    class Port implements SerializableInterface
    {
        /**
         * @var string|null The host IP address to bind to
         */
        private ?string $ip;
        
        /**
         * @var int The port number inside the container
         */
        private int $privatePort;
        
        /**
         * @var int|null The port number on the host machine
         */
        private ?int $publicPort;
        
        /**
         * @var string The protocol type (e.g., 'tcp', 'udp', 'sctp')
         */
        private string $type;

        /**
         * Constructs a new Port instance from an array of data.
         * 
         * Accepts both camelCase and PascalCase keys for compatibility with
         * Docker API responses and internal data structures.
         *
         * @param array<string, mixed> $data Port configuration data
         */
        public function __construct(array $data = [])
        {
            $this->ip = $data['ip'] ?? $data['IP'] ?? null;
            $this->privatePort = $data['privatePort'] ?? $data['PrivatePort'] ?? 0;
            $this->publicPort = $data['publicPort'] ?? $data['PublicPort'] ?? null;
            $this->type = $data['type'] ?? $data['Type'] ?? 'tcp';
        }

        /**
         * Gets the host IP address to bind to.
         *
         * @return string|null The IP address or null if not set
         */
        public function getIp(): ?string
        {
            return $this->ip;
        }

        /**
         * Gets the port number inside the container.
         *
         * @return int The private port number
         */
        public function getPrivatePort(): int
        {
            return $this->privatePort;
        }

        /**
         * Gets the port number on the host machine.
         *
         * @return int|null The public port number or null if not published
         */
        public function getPublicPort(): ?int
        {
            return $this->publicPort;
        }

        /**
         * Gets the protocol type.
         *
         * @return string The protocol type (e.g., 'tcp', 'udp')
         */
        public function getType(): string
        {
            return $this->type;
        }

        /**
         * Converts the Port to an array representation.
         * 
         * Returns data in PascalCase format compatible with Docker API.
         *
         * @return array<string, mixed> The port configuration as an associative array
         */
        public function toArray(): array
        {
            return [
                'IP' => $this->ip,
                'PrivatePort' => $this->privatePort,
                'PublicPort' => $this->publicPort,
                'Type' => $this->type,
            ];
        }

        /**
         * Creates a Port instance from an array of data.
         *
         * @param array<string, mixed> $data Port configuration data
         * @return self A new Port instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }
    }
