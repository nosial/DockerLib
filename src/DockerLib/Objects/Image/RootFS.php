<?php

    namespace DockerLib\Objects\Image;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents the root filesystem configuration of a Docker image.
     *
     * Contains information about the filesystem type and the layers that make up
     * the image's root filesystem.
     *
     * @package DockerLib\Objects\Image
     */
    class RootFS implements SerializableInterface
    {
        /**
         * The type of the root filesystem (typically 'layers')
         *
         * @var string
         */
        private string $type;

        /**
         * Array of layer identifiers that comprise the root filesystem
         *
         * @var array<string>
         */
        private array $layers;

        /**
         * RootFS constructor.
         *
         * @param string $type The filesystem type
         * @param array<string> $layers Array of layer identifiers
         */
        public function __construct(string $type, array $layers)
        {
            $this->type = $type;
            $this->layers = $layers;
        }

        /**
         * Get the root filesystem type.
         *
         * @return string The filesystem type
         */
        public function getType(): string
        {
            return $this->type;
        }

        /**
         * Get the array of layer identifiers.
         *
         * @return array<string> Array of layer identifiers
         */
        public function getLayers(): array
        {
            return $this->layers;
        }

        /**
         * Convert the RootFS to an array representation.
         *
         * @return array<string, mixed> Array representation with 'Type' and 'Layers' keys
         */
        public function toArray(): array
        {
            return [
                'Type' => $this->type,
                'Layers' => $this->layers,
            ];
        }

        /**
         * Create a RootFS instance from an array.
         *
         * @param array<string, mixed> $data Array containing 'Type' and 'Layers' keys
         * @return self New RootFS instance
         */
        public static function fromArray(array $data): self
        {
            return new self(
                $data['Type'] ?? 'layers',
                $data['Layers'] ?? []
            );
        }
    }
