<?php

    namespace DockerLib\Objects;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents image distribution information from Docker registry
     * 
     * Contains descriptor metadata and platform information for multi-architecture images.
     * This object is typically returned when inspecting image distribution data.
     */
    class Distribution implements SerializableInterface
    {
        /**
         * Image descriptor containing media type, digest, and size information
         * 
         * @var array<string, mixed>
         */
        private array $descriptor;
        
        /**
         * List of platforms this image supports (architecture, OS, etc.)
         * 
         * @var array<array>
         */
        private array $platforms;

        /**
         * Create a new Distribution instance
         * 
         * @param array<string, mixed> $data Raw distribution data from Docker API
         */
        public function __construct(array $data = [])
        {
            $this->descriptor = $data['Descriptor'] ?? [];
            $this->platforms = $data['Platforms'] ?? [];
        }

        /**
         * Create a Distribution instance from an array
         * 
         * @param array<string, mixed> $data Raw distribution data
         * @return self New Distribution instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }

        /**
         * Convert the distribution to an array representation
         * 
         * @return array<string, mixed> Array containing descriptor and platforms
         */
        public function toArray(): array
        {
            return [
                'Descriptor' => $this->descriptor,
                'Platforms' => $this->platforms,
            ];
        }

        /**
         * Get the full image descriptor
         * 
         * @return array<string, mixed> Descriptor containing media type, digest, and size
         */
        public function getDescriptor(): array
        {
            return $this->descriptor;
        }

        /**
         * Get the list of supported platforms
         * 
         * @return array<array> Array of platform information including architecture and OS
         */
        public function getPlatforms(): array
        {
            return $this->platforms;
        }

        /**
         * Get the media type from the descriptor
         * 
         * @return string|null Media type (e.g., "application/vnd.docker.distribution.manifest.v2+json")
         */
        public function getMediaType(): ?string
        {
            return $this->descriptor['mediaType'] ?? $this->descriptor['MediaType'] ?? null;
        }

        /**
         * Get the content digest (SHA256 hash) of the image
         * 
         * @return string|null Content digest in format "sha256:..."
         */
        public function getDigest(): ?string
        {
            return $this->descriptor['digest'] ?? $this->descriptor['Digest'] ?? null;
        }

        /**
         * Get the size of the image in bytes
         * 
         * @return int|null Size in bytes
         */
        public function getSize(): ?int
        {
            return $this->descriptor['size'] ?? $this->descriptor['Size'] ?? null;
        }
    }
