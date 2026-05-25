<?php

    namespace DockerLib;

    use DockerLib\Classes\Logger;
    use DockerLib\Exceptions\ConnectionException;
    use DockerLib\Exceptions\DockerException;
    use DockerLib\Exceptions\ResponseException;
    use Exception;
    use stdClass;
    use Symfony\Component\Yaml\Yaml;

    /**
     * Docker Compose implementation for managing multi-container Docker applications
     * 
     * This class provides functionality similar to docker-compose, allowing you to define
     * and run multi-container Docker applications using a docker-compose.yml file. It handles
     * the creation and management of networks, volumes, and containers defined in the compose file.
     * 
     * @package DockerLib
     */
    class DockerCompose
    {
        private Docker $docker;

        private string $composeFile;
        private array $config;
        private string $projectName;
        private array $createdResources = [];

        /**
         * Constructs a new DockerCompose instance
         * 
         * Initializes the Docker Compose manager by parsing the specified compose file
         * and setting up the Docker client. The project name is automatically derived
         * from the directory containing the compose file.
         *
         * @param string $composeFilePath Path to the docker-compose.yml file
         * @param Docker|null $docker Optional Docker client instance, creates a new one if not provided
         * @throws DockerException If the compose file doesn't exist or cannot be parsed
         */
        public function __construct(string $composeFilePath, ?Docker $docker = null)
        {

            $this->composeFile = $composeFilePath;

            Logger::getLogger()->debug("Initializing DockerCompose with file: $composeFilePath");


            if (!file_exists($composeFilePath)) {
                throw new DockerException("Docker Compose file not found: $composeFilePath");
            }
            
            $this->config = Yaml::parseFile($composeFilePath);

            Logger::getLogger()->verbose("Parsed docker-compose.yml successfully");

            $this->docker = $docker ?? new Docker('/var/run/docker.sock');
            
            $dirName = basename(dirname(realpath($composeFilePath)));
            $this->projectName = preg_replace('/[^a-z0-9_-]/', '', strtolower($dirName));
            Logger::getLogger()->info("DockerCompose initialized with project: $this->projectName");

        }

        /**
         * Starts all services defined in the compose file
         * 
         * Creates and starts containers, networks, and volumes as defined in the compose file.
         * Optionally builds images before starting containers. Progress can be monitored through
         * the callback function.
         *
         * @param bool $build Whether to build images before starting containers
         * @param callable|null $callback Optional callback function to receive progress updates
         * @return array Returns an array with 'networks', 'volumes', and 'containers' keys containing created resource IDs
         * @throws Exception If an error occurs during the up process
         */
        public function up(bool $build = false, ?callable $callback = null): array
        {
            Logger::getLogger()->info("Starting docker-compose up for project: $this->projectName");

            $result = [
                'networks' => [],
                'volumes' => [],
                'containers' => []
            ];
            
            try {
                if (isset($this->config['networks'])) {
                    $result['networks'] = $this->createNetworks($callback);
                }
                
                if (isset($this->config['volumes'])) {
                    $result['volumes'] = $this->createVolumes($callback);
                }
                
                if ($build && isset($this->config['services'])) {
                    $this->buildServices($callback);
                }
                
                if (isset($this->config['services'])) {
                    $result['containers'] = $this->createAndStartContainers($callback);
                }
                Logger::getLogger()->info("Docker-compose up completed successfully");

                return $result;
                
            } catch (Exception $e) {
                Logger::getLogger()->debug("Error during up: " . $e->getMessage());

                throw $e;
            }
        }

        /**
         * Stops and removes all containers, networks, and optionally volumes and images
         * 
         * Tears down the entire application stack by stopping and removing containers,
         * removing networks, and optionally removing volumes and images. Progress can be
         * monitored through the callback function.
         *
         * @param bool $removeVolumes Whether to remove volumes defined in the compose file
         * @param bool $removeImages Whether to remove images used by services
         * @param callable|null $callback Optional callback function to receive progress updates
         * @return array Returns an array with 'containers', 'networks', 'volumes', and 'images' keys containing removed resources
         * @throws Exception If an error occurs during the down process
         */
        public function down(bool $removeVolumes = false, bool $removeImages = false, ?callable $callback = null): array
        {
            Logger::getLogger()->info("Starting docker-compose down for project: $this->projectName");
            $result = [
                'containers' => [],
                'networks' => [],
                'volumes' => [],
                'images' => []
            ];
            
            try {
                if (isset($this->config['services'])) {
                    $result['containers'] = $this->stopAndRemoveContainers($callback);
                }
                
                if (isset($this->config['networks'])) {
                    $result['networks'] = $this->removeNetworks($callback);
                }
                
                if ($removeVolumes && isset($this->config['volumes'])) {
                    $result['volumes'] = $this->removeVolumes($callback);
                }
                
                if ($removeImages && isset($this->config['services'])) {
                    $result['images'] = $this->removeImages($callback);
                }

                Logger::getLogger()->info("Docker-compose down completed successfully");

                return $result;
                
            } catch (Exception $e) {
                Logger::getLogger()->debug("Error during down: " . $e->getMessage());

                throw $e;
            }
        }

        /**
         * Builds images for services defined in the compose file
         * 
         * Builds Docker images for services that have a build configuration. Can build
         * specific services or all services if no services are specified.
         *
         * @param array $services Array of service names to build, or empty array to build all services
         * @param bool $noCache Whether to build without using cache
         * @param bool $pull Whether to always pull newer versions of base images
         * @param callable|null $callback Optional callback function to receive build progress updates
         * @return array Returns an array of service names that were successfully built
         * @throws Exception If an error occurs during the build process
         */
        public function build(array $services = [], bool $noCache = false, bool $pull = false, ?callable $callback = null): array
        {
            Logger::getLogger()->info("Building services for project: $this->projectName");
            $result = [];
            
            if (!isset($this->config['services'])) {
                return $result;
            }
            
            $servicesToBuild = empty($services) ? array_keys($this->config['services']) : $services;
            
            foreach ($servicesToBuild as $serviceName)
            {
                if (!isset($this->config['services'][$serviceName]))
                {
                    Logger::getLogger()->debug("Service not found: $serviceName");
                    continue;
                }
                
                $service = $this->config['services'][$serviceName];
                
                if (isset($service['build']))
                {
                    $result[$serviceName] = $this->buildService($serviceName, $service, $noCache, $pull, $callback);
                } else {
                    Logger::getLogger()->verbose("Service $serviceName has no build configuration, skipping");
                }
            }

            Logger::getLogger()->info("Build completed for " . count($result) . " service(s)");

            return $result;
        }

        /**
         * Removes stopped containers for the project
         * 
         * Stops and removes all containers associated with this compose project.
         *
         * @param bool $force Whether to force removal of running containers
         * @param bool $volumes Whether to remove anonymous volumes associated with containers
         * @param callable|null $callback Optional callback function to receive progress updates
         * @return array Returns an array of container names that were removed
         * @throws Exception If an error occurs during removal
         */
        public function remove(bool $force = false, bool $volumes = false, ?callable $callback = null): array
        {
            Logger::getLogger()->info("Removing containers for project: $this->projectName");

            return $this->stopAndRemoveContainers($callback, $force, $volumes);
        }

        private function createNetworks(?callable $callback = null): array
        {
            $networks = [];
            
            foreach ($this->config['networks'] as $networkName => $networkConfig) {
                $fullNetworkName = "{$this->projectName}_$networkName";
                Logger::getLogger()->verbose("Creating network: $fullNetworkName");

                if ($callback !== null) {
                    $callback(['step' => 'network_create', 'name' => $fullNetworkName]);
                }
                
                try {
                    $config = [
                        'Name' => $fullNetworkName,
                        'Driver' => $networkConfig['driver'] ?? 'bridge',
                        'Labels' => [
                            'com.docker.compose.project' => $this->projectName,
                            'com.docker.compose.network' => $networkName
                        ]
                    ];
                    
                    if (isset($networkConfig['driver_opts'])) {
                        $config['Options'] = $networkConfig['driver_opts'];
                    }
                    
                    $network = $this->docker->networks()->create($config);
                    $networks[$networkName] = $network->getId();
                    $this->createdResources['networks'][] = $fullNetworkName;
                    Logger::getLogger()->info("Network created: $fullNetworkName");

                } catch (Exception $e) {
                    Logger::getLogger()->debug("Failed to create network $fullNetworkName: " . $e->getMessage());
                }
            }
            
            return $networks;
        }

        private function createVolumes(?callable $callback = null): array
        {
            $volumes = [];
            
            foreach ($this->config['volumes'] as $volumeName => $volumeConfig) {
                $fullVolumeName = "{$this->projectName}_$volumeName";

                Logger::getLogger()->verbose("Creating volume: $fullVolumeName");

                if ($callback !== null) {
                    $callback(['step' => 'volume_create', 'name' => $fullVolumeName]);
                }
                
                try {
                    $config = [
                        'Labels' => [
                            'com.docker.compose.project' => $this->projectName,
                            'com.docker.compose.volume' => $volumeName
                        ]
                    ];
                    
                    if (isset($volumeConfig['driver'])) {
                        $config['Driver'] = $volumeConfig['driver'];
                    }
                    
                    if (isset($volumeConfig['driver_opts'])) {
                        $config['DriverOpts'] = $volumeConfig['driver_opts'];
                    }
                    
                    $volume = $this->docker->volumes()->create($fullVolumeName, $config);
                    $volumes[$volumeName] = $volume->getName();
                    $this->createdResources['volumes'][] = $fullVolumeName;

                    Logger::getLogger()->info("Volume created: $fullVolumeName");


                } catch (Exception $e) {
                    Logger::getLogger()->debug("Failed to create volume $fullVolumeName: " . $e->getMessage());

                }
            }
            
            return $volumes;
        }

        private function buildService(string $serviceName, array $service, bool $noCache, bool $pull, ?callable $callback = null): bool
        {
            $buildConfig = $service['build'];
            $context = is_string($buildConfig) ? $buildConfig : ($buildConfig['context'] ?? '.');

            $baseDir = dirname($this->composeFile);
            $contextPath = realpath($baseDir . '/' . $context);

            Logger::getLogger()->info("Building service: $serviceName from $contextPath");
            
            if ($callback !== null) {
                $callback(['step' => 'build_start', 'service' => $serviceName]);
            }
            
            $imageName = $service['image'] ?? "{$this->projectName}_$serviceName";
            $buildArgs = is_array($buildConfig) && isset($buildConfig['args']) ? $buildConfig['args'] : [];
            
            try {
                $this->docker->images()->build(
                    $buildArgs,
                    $imageName,
                    $noCache,
                    $pull,
                    true,
                    function ($data) use ($callback, $serviceName) {
                        if ($callback !== null) {
                            $callback(['step' => 'build_progress', 'service' => $serviceName, 'data' => $data]);
                        }
                    }
                );

                Logger::getLogger()->info("Build completed for service: $serviceName");

                if ($callback !== null) {
                    $callback(['step' => 'build_complete', 'service' => $serviceName]);
                }
                
                return true;
                
            } catch (Exception $e) {
                Logger::getLogger()->debug("Build failed for service $serviceName: " . $e->getMessage());
                return false;
            }
        }

        private function buildServices(?callable $callback = null): void
        {
            foreach ($this->config['services'] as $serviceName => $service) {
                if (isset($service['build'])) {
                    $this->buildService($serviceName, $service, false, false, $callback);
                }
            }
        }

        private function createAndStartContainers(?callable $callback = null): array
        {
            $containers = [];
            
            foreach ($this->config['services'] as $serviceName => $service) {
                $containerName = "{$this->projectName}_{$serviceName}_1";

                Logger::getLogger()->verbose("Creating container: $containerName");

                if ($callback !== null) {
                    $callback(['step' => 'container_create', 'service' => $serviceName, 'name' => $containerName]);
                }
                
                try {
                    $containerConfig = $this->buildContainerConfig($serviceName, $service);
                    $container = $this->docker->containers()->create($containerConfig, $containerName);

                    Logger::getLogger()->info("Starting container: $containerName");

                    if ($callback !== null) {
                        $callback(['step' => 'container_start', 'service' => $serviceName, 'name' => $containerName]);
                    }
                    
                    $this->docker->containers()->start($container->getId());
                    $containers[$serviceName] = $container->getId();
                    $this->createdResources['containers'][] = $container->getId();

                    Logger::getLogger()->info("Container started: $containerName");


                } catch (Exception $e) {
                    Logger::getLogger()->debug("Failed to create/start container $containerName: " . $e->getMessage());
                }
            }
            
            return $containers;
        }

        private function buildContainerConfig(string $serviceName, array $service): array
        {
            $config = [
                'Image' => $service['image'] ?? "{$this->projectName}_$serviceName",
                'Hostname' => $service['hostname'] ?? $serviceName,
                'Labels' => [
                    'com.docker.compose.project' => $this->projectName,
                    'com.docker.compose.service' => $serviceName,
                    'com.docker.compose.container-number' => '1'
                ],
                'HostConfig' => []
            ];
            
            if (isset($service['command'])) {
                $config['Cmd'] = is_array($service['command']) ? $service['command'] : explode(' ', $service['command']);
            }
            
            if (isset($service['environment'])) {
                $config['Env'] = $this->buildEnvironmentVariables($service['environment']);
            }
            
            if (isset($service['ports'])) {
                $this->configurePort($config, $service['ports']);
            }
            
            if (isset($service['volumes'])) {
                $this->configureVolumes($config, $service['volumes']);
            }
            
            if (isset($service['networks'])) {
                $this->configureNetworks($config, $service['networks']);
            }
            
            if (isset($service['restart'])) {
                $config['HostConfig']['RestartPolicy'] = $this->parseRestartPolicy($service['restart']);
            }
            
            if (isset($service['depends_on'])) {
                Logger::getLogger()->debug("Service $serviceName depends on: " . implode(', ', array_keys($service['depends_on'])));
            }
            
            if (empty($config['HostConfig'])) {
                $config['HostConfig'] = new stdClass();
            }
            
            return $config;
        }

        private function buildEnvironmentVariables(array $environment): array
        {
            $env = [];
            
            foreach ($environment as $key => $value) {
                if (is_int($key)) {
                    $env[] = $value;
                } else {
                    $env[] = "$key=$value";
                }
            }
            
            return $env;
        }

        private function configurePort(array &$config, array $ports): void
        {
            $exposedPorts = [];
            $portBindings = [];
            
            foreach ($ports as $port) {
                if (is_string($port) && str_contains($port, ':')) {
                    $parts = explode(':', $port);
                    $hostPort = $parts[0];
                    $containerPort = $parts[1];
                    
                    $exposedPorts["$containerPort/tcp"] = new stdClass();
                    $portBindings["$containerPort/tcp"] = [
                        ['HostPort' => $hostPort]
                    ];
                } else {
                    $exposedPorts["$port/tcp"] = new stdClass();
                }
            }
            
            $config['ExposedPorts'] = $exposedPorts;
            $config['HostConfig']['PortBindings'] = $portBindings;
        }

        private function configureVolumes(array &$config, array $volumes): void
        {
            $binds = [];
            
            foreach ($volumes as $volume) {
                if (is_string($volume)) {
                    if (str_contains($volume, ':')) {
                        $parts = explode(':', $volume);
                        $source = $parts[0];
                        $target = $parts[1];
                        
                        if (str_starts_with($source, '/') || str_starts_with($source, '.')) {
                            $baseDir = dirname($this->composeFile);
                            $source = realpath($baseDir . '/' . $source) ?: $source;
                        } else {
                            $source = "{$this->projectName}_$source";
                        }
                        
                        $binds[] = "$source:$target";
                    }
                }
            }
            
            $config['HostConfig']['Binds'] = $binds;
        }

        private function configureNetworks(array &$config, $networks): void
        {
            $networkConfig = [];
            
            if (is_array($networks)) {
                foreach ($networks as $networkName => $networkSettings) {
                    $fullNetworkName = "{$this->projectName}_$networkName";
                    $networkConfig[$fullNetworkName] = is_array($networkSettings) ? $networkSettings : new stdClass();
                }
            }
            
            $config['NetworkingConfig'] = ['EndpointsConfig' => $networkConfig];
        }

        private function parseRestartPolicy(string $restart): array
        {
            return match ($restart) {
                'always' => ['Name' => 'always'],
                'unless-stopped' => ['Name' => 'unless-stopped'],
                'on-failure' => ['Name' => 'on-failure', 'MaximumRetryCount' => 0],
                default => ['Name' => 'no'],
            };
        }

        /**
         * @throws ResponseException
         * @throws ConnectionException
         */
        private function stopAndRemoveContainers(?callable $callback = null, bool $force = false, bool $volumes = false): array
        {
            $removed = [];
            
            $filters = ['label' => ["com.docker.compose.project=$this->projectName"]];
            $containers = $this->docker->containers()->list($filters, true);
            
            foreach ($containers as $container) {
                $containerName = $container->getName();

                Logger::getLogger()->verbose("Stopping container: $containerName");
                if ($callback !== null) {
                    $callback(['step' => 'container_stop', 'name' => $containerName]);
                }
                
                try {
                    $this->docker->containers()->stop($container->getId(), 10);

                    Logger::getLogger()->verbose("Removing container: $containerName");


                    if ($callback !== null) {
                        $callback(['step' => 'container_remove', 'name' => $containerName]);
                    }
                    
                    $this->docker->containers()->remove($container->getId(), $force, $volumes);
                    $removed[] = $containerName;

                    Logger::getLogger()->info("Container removed: $containerName");


                } catch (Exception $e) {
                    Logger::getLogger()->debug("Failed to stop/remove container $containerName: " . $e->getMessage());

                }
            }
            
            return $removed;
        }

        private function removeNetworks(?callable $callback = null): array
        {
            $removed = [];
            
            foreach ($this->config['networks'] as $networkName => $networkConfig) {
                $fullNetworkName = "{$this->projectName}_$networkName";
                Logger::getLogger()->verbose("Removing network: $fullNetworkName");

                if ($callback !== null) {
                    $callback(['step' => 'network_remove', 'name' => $fullNetworkName]);
                }
                
                try {
                    $this->docker->networks()->remove($fullNetworkName);
                    $removed[] = $fullNetworkName;

                    Logger::getLogger()->info("Network removed: $fullNetworkName");
                } catch (Exception $e) {
                    Logger::getLogger()->debug("Failed to remove network $fullNetworkName: " . $e->getMessage());

                }
            }
            
            return $removed;
        }

        private function removeVolumes(?callable $callback = null): array
        {
            $removed = [];
            
            foreach ($this->config['volumes'] as $volumeName => $volumeConfig) {
                $fullVolumeName = "{$this->projectName}_$volumeName";
                Logger::getLogger()->verbose("Removing volume: $fullVolumeName");


                if ($callback !== null) {
                    $callback(['step' => 'volume_remove', 'name' => $fullVolumeName]);
                }
                
                try {
                    $this->docker->volumes()->remove($fullVolumeName, true);
                    $removed[] = $fullVolumeName;
                    Logger::getLogger()->info("Volume removed: $fullVolumeName");

                } catch (Exception $e) {
                    Logger::getLogger()->debug("Failed to remove volume $fullVolumeName: " . $e->getMessage());

                }
            }
            
            return $removed;
        }

        private function removeImages(?callable $callback = null): array
        {
            $removed = [];
            
            foreach ($this->config['services'] as $serviceName => $service) {
                $imageName = $service['image'] ?? "{$this->projectName}_$serviceName";

                Logger::getLogger()->verbose("Removing image: $imageName");


                if ($callback !== null) {
                    $callback(['step' => 'image_remove', 'name' => $imageName]);
                }
                
                try {
                    $this->docker->images()->remove($imageName, true);
                    $removed[] = $imageName;
                    Logger::getLogger()->info("Image removed: $imageName");
                } catch (Exception $e) {
                    Logger::getLogger()->debug("Failed to remove image $imageName: " . $e->getMessage());
                }
            }
            
            return $removed;
        }

        /**
         * Gets the project name for this compose instance
         * 
         * The project name is derived from the directory containing the compose file
         * and is used as a prefix for all created resources.
         *
         * @return string The project name
         */
        public function getProjectName(): string
        {
            return $this->projectName;
        }

        /**
         * Gets the parsed configuration from the compose file
         * 
         * Returns the complete parsed YAML configuration as an associative array.
         *
         * @return array The parsed compose file configuration
         */
        public function getConfig(): array
        {
            return $this->config;
        }

        /**
         * Gets the Docker client instance used by this compose manager
         * 
         * Provides access to the underlying Docker client for direct API access.
         *
         * @return Docker The Docker client instance
         */
        public function getDocker(): Docker
        {
            return $this->docker;
        }
    }
