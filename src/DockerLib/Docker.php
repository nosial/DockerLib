<?php

    namespace DockerLib;

    use DockerLib\Classes\SocketClient;
    use DockerLib\Exceptions\ConnectionException;
    use DockerLib\Exceptions\ResponseException;
    use DockerLib\Managers\ConfigManager;
    use DockerLib\Managers\ContainerManager;
    use DockerLib\Managers\DistributionManager;
    use DockerLib\Managers\ExecManager;
    use DockerLib\Managers\ImageManager;
    use DockerLib\Managers\NetworkManager;
    use DockerLib\Managers\NodeManager;
    use DockerLib\Managers\PluginManager;
    use DockerLib\Managers\SecretManager;
    use DockerLib\Managers\ServiceManager;
    use DockerLib\Managers\SwarmManager;
    use DockerLib\Managers\SystemManager;
    use DockerLib\Managers\TaskManager;
    use DockerLib\Managers\VolumeManager;

    /**
     * Main Docker client class for interacting with the Docker Engine API
     * 
     * This class provides a high-level interface to the Docker Engine API through
     * specialized manager classes for different Docker resources. It handles the
     * initialization of all managers and provides access to them through getter methods.
     * 
     * @package DockerLib
     */
    class Docker
    {
        private SocketClient $client;
        private ContainerManager $containers;
        private ImageManager $images;
        private NetworkManager $networks;
        private VolumeManager $volumes;
        private SystemManager $system;
        private ExecManager $exec;
        private SwarmManager $swarm;
        private ServiceManager $services;
        private NodeManager $nodes;
        private SecretManager $secrets;
        private ConfigManager $configs;
        private PluginManager $plugins;
        private TaskManager $tasks;
        private DistributionManager $distribution;

        /**
         * Constructs a new Docker client instance
         *
         * Initializes the Docker client with a connection to the Docker socket and
         * sets up all manager instances for working with containers, images, networks,
         * volumes, swarm, and other Docker resources.
         *
         * @param string $socketPath The path to the Docker socket, defaults to '/var/run/docker.sock'
         */
        public function __construct(string $socketPath = '/var/run/docker.sock')
        {
            $this->client = new SocketClient($socketPath);
            $this->containers = new ContainerManager($this->client);
            $this->images = new ImageManager($this->client);
            $this->networks = new NetworkManager($this->client);
            $this->volumes = new VolumeManager($this->client);
            $this->system = new SystemManager($this->client);
            $this->exec = new ExecManager($this->client);
            $this->swarm = new SwarmManager($this->client);
            $this->services = new ServiceManager($this->client);
            $this->nodes = new NodeManager($this->client);
            $this->secrets = new SecretManager($this->client);
            $this->configs = new ConfigManager($this->client);
            $this->plugins = new PluginManager($this->client);
            $this->tasks = new TaskManager($this->client);
            $this->distribution = new DistributionManager($this->client);
        }

        /**
         * Returns the ContainerManager for managing Docker containers
         * 
         * Provides access to operations such as creating, starting, stopping,
         * removing, and inspecting containers.
         *
         * @return ContainerManager The container manager instance
         */
        public function containers(): ContainerManager
        {
            return $this->containers;
        }

        /**
         * Returns the ImageManager for managing Docker images
         * 
         * Provides access to operations such as pulling, building, pushing,
         * removing, and inspecting images.
         *
         * @return ImageManager The image manager instance
         */
        public function images(): ImageManager
        {
            return $this->images;
        }

        /**
         * Returns the NetworkManager for managing Docker networks
         * 
         * Provides access to operations such as creating, connecting, disconnecting,
         * removing, and inspecting networks.
         *
         * @return NetworkManager The network manager instance
         */
        public function networks(): NetworkManager
        {
            return $this->networks;
        }

        /**
         * Returns the VolumeManager for managing Docker volumes
         * 
         * Provides access to operations such as creating, removing, and
         * inspecting volumes.
         *
         * @return VolumeManager The volume manager instance
         */
        public function volumes(): VolumeManager
        {
            return $this->volumes;
        }

        /**
         * Returns the SystemManager for Docker system operations
         * 
         * Provides access to system-wide operations such as getting Docker info,
         * monitoring events, managing data usage, and authentication.
         *
         * @return SystemManager The system manager instance
         */
        public function system(): SystemManager
        {
            return $this->system;
        }

        /**
         * Returns the ExecManager for executing commands in containers
         * 
         * Provides access to operations for creating and running exec instances
         * within running containers.
         *
         * @return ExecManager The exec manager instance
         */
        public function exec(): ExecManager
        {
            return $this->exec;
        }

        /**
         * Returns the SwarmManager for managing Docker Swarm
         * 
         * Provides access to operations for initializing, joining, leaving,
         * and managing Docker Swarm clusters.
         *
         * @return SwarmManager The swarm manager instance
         */
        public function swarm(): SwarmManager
        {
            return $this->swarm;
        }

        /**
         * Returns the ServiceManager for managing Docker Swarm services
         * 
         * Provides access to operations for creating, updating, removing,
         * and inspecting services in a Docker Swarm.
         *
         * @return ServiceManager The service manager instance
         */
        public function services(): ServiceManager
        {
            return $this->services;
        }

        /**
         * Returns the NodeManager for managing Docker Swarm nodes
         * 
         * Provides access to operations for listing, inspecting, updating,
         * and removing nodes in a Docker Swarm.
         *
         * @return NodeManager The node manager instance
         */
        public function nodes(): NodeManager
        {
            return $this->nodes;
        }

        /**
         * Returns the SecretManager for managing Docker secrets
         * 
         * Provides access to operations for creating, updating, removing,
         * and inspecting secrets in a Docker Swarm.
         *
         * @return SecretManager The secret manager instance
         */
        public function secrets(): SecretManager
        {
            return $this->secrets;
        }

        /**
         * Returns the ConfigManager for managing Docker configs
         * 
         * Provides access to operations for creating, updating, removing,
         * and inspecting configs in a Docker Swarm.
         *
         * @return ConfigManager The config manager instance
         */
        public function configs(): ConfigManager
        {
            return $this->configs;
        }

        /**
         * Returns the PluginManager for managing Docker plugins
         * 
         * Provides access to operations for installing, enabling, disabling,
         * removing, and inspecting Docker plugins.
         *
         * @return PluginManager The plugin manager instance
         */
        public function plugins(): PluginManager
        {
            return $this->plugins;
        }

        /**
         * Returns the TaskManager for managing Docker Swarm tasks
         * 
         * Provides access to operations for listing and inspecting tasks
         * running in a Docker Swarm.
         *
         * @return TaskManager The task manager instance
         */
        public function tasks(): TaskManager
        {
            return $this->tasks;
        }

        /**
         * Returns the DistributionManager for Docker image distribution
         * 
         * Provides access to operations for inspecting image distribution
         * metadata from registries.
         *
         * @return DistributionManager The distribution manager instance
         */
        public function distribution(): DistributionManager
        {
            return $this->distribution;
        }

        /**
         * Pings the Docker daemon to check if it's responsive
         *
         * Sends a ping request to the Docker daemon to verify connectivity
         * and that the daemon is running.
         *
         * @return bool Returns true if the daemon responds successfully
         * @throws ConnectionException If unable to connect to the Docker daemon
         * @throws ResponseException If the daemon returns an error response
         */
        public function ping(): bool
        {
            $this->client->request('GET', '/_ping');
            return true;
        }

        /**
         * Retrieves version information from the Docker daemon
         *
         * Returns detailed version information about the Docker daemon including
         * the version number, API version, Git commit, Go version, OS, and architecture.
         *
         * @return array An associative array containing Docker daemon version information
         * @throws ConnectionException
         * @throws ResponseException
         */
        public function version(): array
        {
            return $this->client->request('GET', '/version');
        }
    }