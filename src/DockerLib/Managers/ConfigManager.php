<?php


    namespace DockerLib\Managers;

    use DockerLib\Classes\SocketClient;
    use DockerLib\Exceptions\ConnectionException;
    use DockerLib\Exceptions\ResponseException;
    use DockerLib\Objects\Config;

    /**
     * Manages Docker configs
     *
     * Provides operations for managing non-sensitive configuration data in Docker Swarm.
     * Configs are similar to secrets but are not encrypted, making them suitable for
     * configuration files, environment-specific settings, and other non-confidential data.
     */
    class ConfigManager
    {
        private SocketClient $client;

        /**
         * Creates a new ConfigManager instance
         *
         * @param SocketClient $client The socket client for Docker API communication
         */
        public function __construct(SocketClient $client)
        {
            $this->client = $client;
        }

        /**
         * Lists all configs in the swarm with optional filtering
         *
         * Returns a list of configs available in the swarm.
         * Can be filtered by name, label, or other criteria.
         *
         * @param array $filters Filters to apply (e.g., ['name' => ['my-config'], 'label' => ['key=value']])
         * @return array<Config> Array of Config objects matching the specified criteria
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

            $response = $this->client->request('GET', '/configs', null, $query);
            $configs = [];

            foreach ($response['data'] as $data)
            {
                $configs[] = Config::fromArray($data);
            }

            return $configs;
        }

        /**
         * Inspects a config and retrieves its metadata
         *
         * Returns information about a config including its creation date,
         * labels, and version. The actual config data is also returned.
         *
         * @param string $id Config ID or name to inspect
         * @return Config Config object containing metadata and data
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function inspect(string $id): Config
        {
            return Config::fromArray($this->client->request('GET', "/configs/$id")['data']);
        }

        /**
         * Creates a new config
         *
         * Creates a config with the specified data and metadata.
         * The config data should be base64-encoded in the configuration.
         *
         * @param array $config Config configuration including 'Name', 'Data' (base64-encoded), 'Labels', etc.
         * @return Config The created Config object with metadata
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function create(array $config): Config
        {
            $response = $this->client->request('POST', '/configs/create', $config);
            return $this->inspect($response['data']['ID']);
        }

        /**
         * Updates a config's metadata
         *
         * Modifies a config's labels or other metadata. The actual config data
         * cannot be updated; create a new config instead. Requires the current version.
         *
         * @param string $id Config ID or name to update
         * @param int $version The current version of the config (from inspect())
         * @param array $config Updated config specification with 'Labels', etc.
         * @return void
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function update(string $id, int $version, array $config): void
        {
            $this->client->request('POST', "/configs/$id/update", $config, ['version' => $version]);
        }

        /**
         * Removes a config from the swarm
         *
         * Deletes a config. The config must not be in use by any services.
         * Once deleted, the config data is permanently lost.
         *
         * @param string $id Config ID or name to remove
         * @return void
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function remove(string $id): void
        {
            $this->client->request('DELETE', "/configs/$id");
        }
    }
