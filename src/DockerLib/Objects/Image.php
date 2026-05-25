<?php

    namespace DockerLib\Objects;

    use DockerLib\Interfaces\SerializableInterface;
    use DockerLib\Objects\Image\RootFS;
    use DockerLib\Objects\Image\GraphDriver;
    use DockerLib\Objects\Image\ImageConfig;

    /**
     * Represents a Docker image with all its metadata and configuration
     * 
     * This class encapsulates all information about a Docker image including
     * its layers, configuration, size, tags, and other properties returned
     * by Docker's image inspect and list operations.
     */
    class Image implements SerializableInterface
    {
        /**
         * @var string|null The unique identifier of the image
         */
        private ?string $id;

        /**
         * @var string|null The ID of the parent image this image was built from
         */
        private ?string $parentId;

        /**
         * @var array<string> List of repository tags for this image (e.g., ["ubuntu:latest", "ubuntu:22.04"])
         */
        private array $repoTags;

        /**
         * @var array<string> List of content-addressable digests for this image
         */
        private array $repoDigests;

        /**
         * @var int|null Unix timestamp of when the image was created
         */
        private ?int $created;

        /**
         * @var int|null Size of the image in bytes
         */
        private ?int $size;

        /**
         * @var int|null Virtual size of the image in bytes (including shared layers)
         */
        private ?int $virtualSize;

        /**
         * @var int|null Size of shared data in bytes
         */
        private ?int $sharedSize;

        /**
         * @var array<string, string> User-defined metadata labels
         */
        private array $labels;

        /**
         * @var int|null Number of containers using this image
         */
        private ?int $containers;

        /**
         * @var string|null CPU architecture the image is built for (e.g., "amd64", "arm64")
         */
        private ?string $architecture;

        /**
         * @var string|null Operating system the image is built for (e.g., "linux", "windows")
         */
        private ?string $os;

        /**
         * @var string|null Specific version of the operating system
         */
        private ?string $osVersion;

        /**
         * @var string|null Version of Docker that created this image
         */
        private ?string $dockerVersion;

        /**
         * @var string|null Author of the image
         */
        private ?string $author;

        /**
         * @var ImageConfig|null Configuration for running containers from this image
         */
        private ?ImageConfig $config;

        /**
         * @var string|null Container ID used to create this image
         */
        private ?string $container;

        /**
         * @var ImageConfig|null Container configuration used when building this image
         */
        private ?ImageConfig $containerConfig;

        /**
         * @var RootFS|null Information about the image's root filesystem and layers
         */
        private ?RootFS $rootFS;

        /**
         * @var GraphDriver|null Information about the storage driver used for this image
         */
        private ?GraphDriver $graphDriver;

        /**
         * @var string|null Comment or description for this image
         */
        private ?string $comment;

        /**
         * Creates a new Image instance from Docker API data
         *
         * @param array<string, mixed> $data Raw image data from Docker API
         */
        public function __construct(array $data = [])
        {
            $config = null;
            if (isset($data['Config']) && is_array($data['Config'])) {
                $config = ImageConfig::fromArray($data['Config']);
            }

            $containerConfig = null;
            if (isset($data['ContainerConfig']) && is_array($data['ContainerConfig'])) {
                $containerConfig = ImageConfig::fromArray($data['ContainerConfig']);
            }

            $rootFS = null;
            if (isset($data['RootFS']) && is_array($data['RootFS'])) {
                $rootFS = RootFS::fromArray($data['RootFS']);
            }

            $graphDriver = null;
            if (isset($data['GraphDriver']) && is_array($data['GraphDriver'])) {
                $graphDriver = GraphDriver::fromArray($data['GraphDriver']);
            }

            $this->id = $data['Id'] ?? null;
            $this->parentId = $data['ParentId'] ?? ($data['Parent'] ?? null);
            $this->repoTags = $data['RepoTags'] ?? [];
            $this->repoDigests = $data['RepoDigests'] ?? [];
            $this->created = isset($data['Created']) ? (int)$data['Created'] : null;
            $this->size = isset($data['Size']) ? (int)$data['Size'] : null;
            $this->virtualSize = isset($data['VirtualSize']) ? (int)$data['VirtualSize'] : null;
            $this->sharedSize = isset($data['SharedSize']) ? (int)$data['SharedSize'] : null;
            $this->labels = $data['Labels'] ?? [];
            $this->containers = isset($data['Containers']) ? (int)$data['Containers'] : null;
            $this->architecture = $data['Architecture'] ?? null;
            $this->os = $data['Os'] ?? null;
            $this->osVersion = $data['OsVersion'] ?? null;
            $this->dockerVersion = $data['DockerVersion'] ?? null;
            $this->author = $data['Author'] ?? null;
            $this->config = $config;
            $this->container = $data['Container'] ?? null;
            $this->containerConfig = $containerConfig;
            $this->rootFS = $rootFS;
            $this->graphDriver = $graphDriver;
            $this->comment = $data['Comment'] ?? null;
        }

        /**
         * Gets the unique identifier of the image
         *
         * @return string|null The image ID (typically a SHA256 hash)
         */
        public function getId(): ?string
        {
            return $this->id;
        }

        /**
         * Gets the parent image ID
         *
         * @return string|null The ID of the parent image, or null if this is a base image
         */
        public function getParentId(): ?string
        {
            return $this->parentId;
        }

        /**
         * Gets the repository tags for this image
         *
         * @return array<string> List of tags (e.g., ["ubuntu:latest", "ubuntu:22.04"])
         */
        public function getRepoTags(): array
        {
            return $this->repoTags;
        }

        /**
         * Gets the content-addressable digests for this image
         *
         * @return array<string> List of repository digests
         */
        public function getRepoDigests(): array
        {
            return $this->repoDigests;
        }

        /**
         * Alias for getRepoTags() for backward compatibility
         * @return array<string>
         */
        public function getTags(): array
        {
            return $this->repoTags;
        }

        /**
         * Gets the creation timestamp
         *
         * @return int|null Unix timestamp of when the image was created
         */
        public function getCreated(): ?int
        {
            return $this->created;
        }

        /**
         * Gets the size of the image
         *
         * @return int|null Size in bytes
         */
        public function getSize(): ?int
        {
            return $this->size;
        }

        /**
         * Gets the virtual size of the image
         *
         * @return int|null Virtual size in bytes (including shared layers)
         */
        public function getVirtualSize(): ?int
        {
            return $this->virtualSize;
        }

        /**
         * Gets the shared size
         *
         * @return int|null Size of shared data in bytes
         */
        public function getSharedSize(): ?int
        {
            return $this->sharedSize;
        }

        /**
         * Gets the image labels
         *
         * @return array<string, string> User-defined metadata labels
         */
        public function getLabels(): array
        {
            return $this->labels;
        }

        /**
         * Gets the number of containers using this image
         *
         * @return int|null Container count
         */
        public function getContainers(): ?int
        {
            return $this->containers;
        }

        /**
         * Gets the CPU architecture
         *
         * @return string|null Architecture (e.g., "amd64", "arm64")
         */
        public function getArchitecture(): ?string
        {
            return $this->architecture;
        }

        /**
         * Gets the operating system
         *
         * @return string|null OS name (e.g., "linux", "windows")
         */
        public function getOs(): ?string
        {
            return $this->os;
        }

        /**
         * Gets the operating system version
         *
         * @return string|null OS version string
         */
        public function getOsVersion(): ?string
        {
            return $this->osVersion;
        }

        /**
         * Gets the Docker version used to create this image
         *
         * @return string|null Docker version string
         */
        public function getDockerVersion(): ?string
        {
            return $this->dockerVersion;
        }

        /**
         * Gets the image author
         *
         * @return string|null Author name or email
         */
        public function getAuthor(): ?string
        {
            return $this->author;
        }

        /**
         * Gets the image configuration
         *
         * @return ImageConfig|null Configuration for running containers from this image
         */
        public function getConfig(): ?ImageConfig
        {
            return $this->config;
        }

        /**
         * Gets the container ID used to create this image
         *
         * @return string|null Container ID
         */
        public function getContainer(): ?string
        {
            return $this->container;
        }

        /**
         * Gets the container configuration used when building
         *
         * @return ImageConfig|null Container configuration from build time
         */
        public function getContainerConfig(): ?ImageConfig
        {
            return $this->containerConfig;
        }

        /**
         * Gets the root filesystem information
         *
         * @return RootFS|null Information about layers and filesystem type
         */
        public function getRootFS(): ?RootFS
        {
            return $this->rootFS;
        }

        /**
         * Gets the graph driver information
         *
         * @return GraphDriver|null Storage driver details
         */
        public function getGraphDriver(): ?GraphDriver
        {
            return $this->graphDriver;
        }

        /**
         * Gets the image comment
         *
         * @return string|null Comment or description
         */
        public function getComment(): ?string
        {
            return $this->comment;
        }

        /**
         * Converts the image to an array representation
         *
         * @return array<string, mixed> Array containing all image properties
         */
        public function toArray(): array
        {
            return [
                'Id' => $this->id,
                'ParentId' => $this->parentId,
                'RepoTags' => $this->repoTags,
                'RepoDigests' => $this->repoDigests,
                'Created' => $this->created,
                'Size' => $this->size,
                'VirtualSize' => $this->virtualSize,
                'SharedSize' => $this->sharedSize,
                'Labels' => $this->labels,
                'Containers' => $this->containers,
                'Architecture' => $this->architecture,
                'Os' => $this->os,
                'OsVersion' => $this->osVersion,
                'DockerVersion' => $this->dockerVersion,
                'Author' => $this->author,
                'Config' => $this->config?->toArray(),
                'Container' => $this->container,
                'ContainerConfig' => $this->containerConfig?->toArray(),
                'RootFS' => $this->rootFS?->toArray(),
                'GraphDriver' => $this->graphDriver?->toArray(),
                'Comment' => $this->comment,
            ];
        }

        /**
         * Creates an Image instance from an array
         *
         * @param array<string, mixed> $data Image data from Docker API
         * @return self New Image instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }

        /**
         * Gets raw data as array (deprecated)
         *
         * @return array<string, mixed> Array containing all image properties
         * @deprecated Use toArray() instead
         */
        public function getRawData(): array
        {
            return $this->toArray();
        }
    }
