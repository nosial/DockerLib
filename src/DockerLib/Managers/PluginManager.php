<?php


    namespace DockerLib\Managers;

    use DockerLib\Classes\SocketClient;
    use DockerLib\Exceptions\ConnectionException;
    use DockerLib\Exceptions\ResponseException;
    use DockerLib\Objects\Plugin;

    /**
     * Manages Docker plugins
     *
     * Provides operations for managing Docker plugins which extend Docker's functionality.
     * Plugins can provide volume drivers, network drivers, authorization, logging, and other
     * capabilities. Supports installing, enabling, disabling, and configuring plugins.
     */
    class PluginManager
    {
        private SocketClient $client;

        /**
         * Creates a new PluginManager instance
         *
         * @param SocketClient $client The socket client for Docker API communication
         */
        public function __construct(SocketClient $client)
        {
            $this->client = $client;
        }

        /**
         * Lists all Docker plugins with optional filtering
         *
         * Returns a list of plugins installed on the Docker host.
         * Can be filtered by capability or enabled status.
         *
         * @param array $filters Filters to apply (e.g., ['capability' => ['volumedriver'], 'enabled' => ['true']])
         * @return array<Plugin> Array of Plugin objects matching the specified criteria
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function list(array $filters = []): array
        {
            $query = [];
            if (!empty($filters))
            {
                $query['filters'] = json_encode($filters);
            }

            $response = $this->client->request('GET', '/plugins', null, $query);
            $plugins = [];

            foreach ($response['data'] as $data)
            {
                $plugins[] = Plugin::fromArray($data);
            }

            return $plugins;
        }

        /**
         * Inspects a plugin and retrieves detailed information
         * Returns comprehensive information about a plugin including its
         * configuration, capabilities, settings, and enabled status.
         *
         * @param string $name Plugin name or ID to inspect
         * @return Plugin Plugin object containing detailed configuration and metadata
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function inspect(string $name): Plugin
        {
            return Plugin::fromArray($this->client->request('GET', "/plugins/$name/json")['data']);
        }

        /**
         * Installs a plugin from a remote location
         *
         * Downloads and installs a plugin from a registry or remote URL.
         * May require accepting privileges for the plugin's capabilities.
         *
         * @param string $remote Remote reference to the plugin (e.g., "vieux/sshfs:latest")
         * @param string|null $name Local name for the plugin; uses remote name if not specified
         * @param array $privileges Array of privilege descriptions that must be accepted
         * @return void
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function install(string $remote, ?string $name = null, array $privileges = []): void
        {
            $query = ['remote' => $remote];
            if ($name !== null)
            {
                $query['name'] = $name;
            }

            $this->client->request('POST', '/plugins/pull', $privileges, $query);
        }

        /**
         * Enables a plugin
         *
         * Activates a disabled plugin, making its functionality available.
         * The plugin must be installed before it can be enabled.
         *
         * @param string $name Plugin name or ID to enable
         * @param int $timeout Timeout in seconds to wait for the plugin to start
         * @return void
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function enable(string $name, int $timeout = 0): void
        {
            $this->client->request('POST', "/plugins/$name/enable", null, ['timeout' => $timeout]);
        }

        /**
         * Disables a plugin
         *
         * Deactivates an enabled plugin, stopping its functionality.
         * The plugin remains installed but inactive.
         *
         * @param string $name Plugin name or ID to disable
         * @param bool $force If true, forcefully disables the plugin even if it's in use
         * @return void
         * @throws ConnectionException Thrown on a socket connection error
          * @throws ResponseException Thrown on a response error
         */
        public function disable(string $name, bool $force = false): void
        {
            $this->client->request('POST', "/plugins/$name/disable", null, ['force' => $force ? 1 : 0]);
        }

        /**
         * Removes a plugin
         *
         * Uninstalls a plugin from the Docker host.
         * The plugin must be disabled before removal unless force is used.
         *
         * @param string $name Plugin name or ID to remove
         * @param bool $force If true, forcefully removes the plugin even if it's enabled
         * @return void
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function remove(string $name, bool $force = false): void
        {
            $this->client->request('DELETE', "/plugins/$name", null, ['force' => $force ? 1 : 0]);
        }

        /**
         * Upgrades a plugin to a newer version
         *
         * Updates an installed plugin to a newer version from a remote location.
         * May require accepting new privileges.
         *
         * @param string $name Plugin name or ID to upgrade
         * @param string $remote Remote reference to the new plugin version
         * @param array $privileges Array of privilege descriptions that must be accepted
         * @return void
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function upgrade(string $name, string $remote, array $privileges = []): void
        {
            $this->client->request('POST', "/plugins/$name/upgrade", $privileges, ['remote' => $remote]);
        }

        /**
         * Pushes a plugin to a registry
         *
         * Uploads a local plugin to a Docker registry, making it available
         * for others to install.
         *
         * @param string $name Plugin name to push
         * @return void
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function push(string $name): void
        {
            $this->client->request('POST', "/plugins/$name/push");
        }

        /**
         * Configures a plugin's settings
         *
         * Modifies a plugin's configuration parameters.
         * The plugin must be disabled before configuration changes.
         *
         * @param string $name Plugin name or ID to configure
         * @param array $config Configuration settings as key-value pairs
         * @return void
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function configure(string $name, array $config): void
        {
            $this->client->request('POST', "/plugins/$name/set", $config);
        }

        /**
         * Retrieves privilege requirements for a plugin
         *
         * Returns the list of privileges that will be required when installing
         * a plugin. Used to inform users before installation.
         *
         * @param string $remote Remote reference to the plugin
         * @return array Array of privilege descriptions required by the plugin
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function privileges(string $remote): array
        {
            $query = ['remote' => $remote];
            $response = $this->client->request('GET', '/plugins/privileges', null, $query);
            return $response['data'];
        }

        /**
         * Create a plugin from a rootfs and configuration
         *
         * @param string $name The name of the plugin (e.g., "my-plugin:latest")
         * @param string $tarContext Path to tar file containing plugin rootfs and manifest
         * @return void
         * @throws ResponseException Thrown on a response error (e.g., invalid tar format, missing manifest)
         */
        public function create(string $name, string $tarContext): void
        {
            $query = ['name' => $name];
            $headers = ['Content-Type' => 'application/x-tar'];
            $tarData = file_get_contents($tarContext);

            $this->client->requestRaw('POST', '/plugins/create', $tarData, $query, $headers);
        }
    }
