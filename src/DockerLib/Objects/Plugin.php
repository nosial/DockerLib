<?php

    namespace DockerLib\Objects;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents a Docker plugin
     * 
     * Plugins extend Docker functionality with additional capabilities such as
     * volume drivers, network drivers, and authorization plugins.
     */
    class Plugin implements SerializableInterface
    {
        /**
         * Unique identifier of the plugin
         * 
         * @var string|null
         */
        private ?string $id;
        
        /**
         * Name of the plugin
         * 
         * @var string|null
         */
        private ?string $name;
        
        /**
         * Whether the plugin is currently enabled
         * 
         * @var bool
         */
        private bool $enabled;
        
        /**
         * Runtime settings for the plugin
         * 
         * @var array<string, mixed>
         */
        private array $settings;
        
        /**
         * Reference to the plugin image
         * 
         * @var string|null
         */
        private ?string $pluginReference;
        
        /**
         * Plugin configuration including description, interface, and environment
         * 
         * @var array<string, mixed>
         */
        private array $config;

        /**
         * Create a new Plugin instance
         * 
         * @param array<string, mixed> $data Raw plugin data from Docker API
         */
        public function __construct(array $data = [])
        {
            $this->id = $data['Id'] ?? null;
            $this->name = $data['Name'] ?? null;
            $this->enabled = $data['Enabled'] ?? false;
            $this->settings = $data['Settings'] ?? [];
            $this->pluginReference = $data['PluginReference'] ?? null;
            $this->config = $data['Config'] ?? [];
        }

        /**
         * Get the plugin ID
         * 
         * @return string|null Unique identifier
         */
        public function getId(): ?string
        {
            return $this->id;
        }

        /**
         * Get the plugin name
         * 
         * @return string|null Plugin name
         */
        public function getName(): ?string
        {
            return $this->name;
        }

        /**
         * Check if the plugin is enabled
         * 
         * @return bool True if enabled, false otherwise
         */
        public function getEnabled(): bool
        {
            return $this->enabled;
        }

        /**
         * Get the plugin runtime settings
         * 
         * @return array<string, mixed> Runtime configuration settings
         */
        public function getSettings(): array
        {
            return $this->settings;
        }

        /**
         * Get the plugin reference
         * 
         * @return string|null Reference to the plugin image
         */
        public function getPluginReference(): ?string
        {
            return $this->pluginReference;
        }

        /**
         * Get the full plugin configuration
         * 
         * @return array<string, mixed> Complete plugin config including description and interface
         */
        public function getConfig(): array
        {
            return $this->config;
        }

        /**
         * Get the plugin description
         * 
         * @return string|null Human-readable description
         */
        public function getDescription(): ?string
        {
            return $this->config['Description'] ?? null;
        }

        /**
         * Get the plugin documentation URL
         * 
         * @return string|null URL to plugin documentation
         */
        public function getDocumentation(): ?string
        {
            return $this->config['Documentation'] ?? null;
        }

        /**
         * Get the plugin interface specification
         * 
         * @return array<string, mixed>|null Interface types and capabilities
         */
        public function getInterface(): ?array
        {
            return $this->config['Interface'] ?? null;
        }

        /**
         * Get the plugin entrypoint
         * 
         * @return array<string> Command and arguments to start the plugin
         */
        public function getEntrypoint(): array
        {
            return $this->config['Entrypoint'] ?? [];
        }

        /**
         * Get the plugin working directory
         * 
         * @return string|null Working directory path
         */
        public function getWorkDir(): ?string
        {
            return $this->config['WorkDir'] ?? null;
        }

        /**
         * Get the plugin environment variables
         * 
         * @return array<string> List of environment variables
         */
        public function getEnv(): array
        {
            return $this->config['Env'] ?? [];
        }

        /**
         * Get the plugin arguments schema
         * 
         * @return array<string, mixed> Argument definitions and descriptions
         */
        public function getArgs(): array
        {
            return $this->config['Args'] ?? [];
        }

        /**
         * Get the plugin root filesystem configuration
         * 
         * @return array<string, mixed>|null Rootfs type and layers
         */
        public function getRootfs(): ?array
        {
            return $this->config['rootfs'] ?? null;
        }

        /**
         * Convert the plugin to an array representation
         * 
         * @return array<string, mixed> Array containing all plugin properties
         */
        public function toArray(): array
        {
            return [
                'Id' => $this->id,
                'Name' => $this->name,
                'Enabled' => $this->enabled,
                'Settings' => $this->settings,
                'PluginReference' => $this->pluginReference,
                'Config' => $this->config,
            ];
        }

        /**
         * Create a Plugin instance from an array
         * 
         * @param array<string, mixed> $data Raw plugin data
         * @return self New Plugin instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }

        /**
         * Get the raw plugin data
         * 
         * @deprecated Use toArray() instead
         * @return array<string, mixed> Array representation of the plugin
         */
        public function getRawData(): array
        {
            return $this->toArray();
        }
    }
