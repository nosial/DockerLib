<?php

    namespace DockerLib\Objects;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents Docker system disk usage information
     * 
     * Provides detailed statistics about disk space used by images, containers,
     * volumes, and build cache. This is returned by the system df command.
     */
    class SystemDataUsage implements SerializableInterface
    {
        /**
         * Total size of all image layers in bytes
         * 
         * @var int|null
         */
        private ?int $layersSize;
        
        /**
         * List of images with their disk usage information
         * 
         * @var array<array<string, mixed>>
         */
        private array $images;
        
        /**
         * List of containers with their disk usage information
         * 
         * @var array<array<string, mixed>>
         */
        private array $containers;
        
        /**
         * List of volumes with their disk usage information
         * 
         * @var array<array<string, mixed>>
         */
        private array $volumes;
        
        /**
         * List of build cache entries with their disk usage information
         * 
         * @var array<array<string, mixed>>
         */
        private array $buildCache;

        /**
         * Create a new SystemDataUsage instance
         * 
         * @param array<string, mixed> $data Raw system data usage from Docker API
         */
        public function __construct(array $data)
        {
            $this->layersSize = $data['layersSize'] ?? $data['LayersSize'] ?? null;
            $this->images = $data['images'] ?? $data['Images'] ?? [];
            $this->containers = $data['containers'] ?? $data['Containers'] ?? [];
            $this->volumes = $data['volumes'] ?? $data['Volumes'] ?? [];
            $this->buildCache = $data['buildCache'] ?? $data['BuildCache'] ?? [];
        }

        /**
         * Get the total size of all image layers
         * 
         * @return int|null Size in bytes
         */
        public function getLayersSize(): ?int
        {
            return $this->layersSize;
        }

        /**
         * Get image disk usage information
         * 
         * @return array<array<string, mixed>> List of images with size and metadata
         */
        public function getImages(): array
        {
            return $this->images;
        }

        /**
         * Get container disk usage information
         * 
         * @return array<array<string, mixed>> List of containers with size and metadata
         */
        public function getContainers(): array
        {
            return $this->containers;
        }

        /**
         * Get volume disk usage information
         * 
         * @return array<array<string, mixed>> List of volumes with size and metadata
         */
        public function getVolumes(): array
        {
            return $this->volumes;
        }

        /**
         * Get build cache disk usage information
         * 
         * @return array<array<string, mixed>> List of build cache entries with size and metadata
         */
        public function getBuildCache(): array
        {
            return $this->buildCache;
        }

        /**
         * Convert the system data usage to an array representation
         * 
         * @return array<string, mixed> Array containing all disk usage properties
         */
        public function toArray(): array
        {
            return [
                'LayersSize' => $this->layersSize,
                'Images' => $this->images,
                'Containers' => $this->containers,
                'Volumes' => $this->volumes,
                'BuildCache' => $this->buildCache,
            ];
        }

        /**
         * Create a SystemDataUsage instance from an array
         * 
         * @param array<string, mixed> $data Raw system data usage
         * @return self New SystemDataUsage instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }
    }
