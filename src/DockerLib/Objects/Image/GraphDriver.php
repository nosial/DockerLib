<?php

    namespace DockerLib\Objects\Image;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents the graph driver used by a Docker image.
     *
     * Graph drivers manage the storage of image layers and container filesystems.
     * This class encapsulates the driver name and its configuration data.
     *
     * @package DockerLib\Objects\Image
     */
    class GraphDriver implements SerializableInterface
    {
        /**
         * The name of the graph driver (e.g., overlay2, aufs, devicemapper)
         *
         * @var string
         */
        private string $name;

        /**
         * Configuration data for the graph driver
         *
         * @var array<string, mixed>
         */
        private array $data;

        /**
         * GraphDriver constructor.
         *
         * @param string $name The name of the graph driver
         * @param array<string, mixed> $data Configuration data for the graph driver
         */
        public function __construct(string $name, array $data)
        {
            $this->name = $name;
            $this->data = $data;
        }

        /**
         * Get the name of the graph driver.
         *
         * @return string The graph driver name
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Get the configuration data for the graph driver.
         *
         * @return array<string, mixed> The graph driver configuration data
         */
        public function getData(): array
        {
            return $this->data;
        }

        /**
         * Convert the graph driver to an array representation.
         *
         * @return array<string, mixed> Array representation with 'Name' and 'Data' keys
         */
        public function toArray(): array
        {
            return [
                'Name' => $this->name,
                'Data' => $this->data,
            ];
        }

        /**
         * Create a GraphDriver instance from an array.
         *
         * @param array<string, mixed> $data Array containing 'Name' and 'Data' keys
         * @return self New GraphDriver instance
         */
        public static function fromArray(array $data): self
        {
            return new self(
                $data['Name'] ?? '',
                $data['Data'] ?? []
            );
        }
    }
