<?php


    namespace DockerLib\Managers;

    use DockerLib\Classes\SocketClient;
    use DockerLib\Exceptions\ConnectionException;
    use DockerLib\Exceptions\ResponseException;
    use DockerLib\Objects\Service;

    /**
     * Manages Docker Swarm services
     *
     * Provides operations for managing services in Docker Swarm mode.
     * Services are the definition of tasks to execute on worker or manager nodes.
     * They define container images, replica counts, update strategies, and resource constraints.
     */
    class ServiceManager
    {
        private SocketClient $client;

        /**
         * Creates a new ServiceManager instance
         *
         * @param SocketClient $client The socket client for Docker API communication
         */
        public function __construct(SocketClient $client)
        {
            $this->client = $client;
        }

        /**
         * Lists all services in the swarm with optional filtering
         *
         * Returns a list of services defined in the swarm.
         * Can be filtered by name, label, or other criteria.
         *
         * @param array $filters Filters to apply (e.g., ['name' => ['my-service'], 'label' => ['key=value']])
         * @return array<Service> Array of Service objects matching the specified criteria
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

            $response = $this->client->request('GET', '/services', null, $query);
            $services = [];

            foreach ($response['data'] as $data)
            {
                $services[] = Service::fromArray($data);
            }

            return $services;
        }

        /**
         * Inspects a service and retrieves detailed information
         *
         * Returns comprehensive information about a service including its specification,
         * endpoint configuration, update configuration, and current state.
         *
         * @param string $id Service ID or name to inspect
         * @param bool $insertDefaults If true, includes default values in the service spec
         * @return Service Service object containing detailed configuration and state information
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function inspect(string $id, bool $insertDefaults = false): Service
        {
            $query = ['insertDefaults' => $insertDefaults ? 1 : 0];
            $response = $this->client->request('GET', "/services/$id", null, $query);
            return Service::fromArray($response['data']);
        }

        /**
         * Creates a new service in the swarm
         *
         * Deploys a new service with the specified configuration.
         * The service will be scheduled on available nodes according to placement constraints.
         *
         * @param array $config Service specification including 'Name', 'TaskTemplate', 'Mode', 'UpdateConfig', 'Networks', etc.
         * @param string|null $authConfig Base64-encoded authentication credentials for private registries
         * @return Service The created Service object with full configuration details
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function create(array $config, ?string $authConfig = null): Service
        {
            $headers = [];
            if ($authConfig !== null)
            {
                $headers['X-Registry-Auth'] = base64_encode($authConfig);
            }

            $response = $this->client->request('POST', '/services/create', $config, [], $headers);
            return $this->inspect($response['data']['ID']);
        }

        /**
         * Updates an existing service
         *
         * Modifies a service's configuration such as image version, replica count,
         * or resource limits. Triggers a rolling update based on the update strategy.
         *
         * @param string $id Service ID or name to update
         * @param int $version The current version of the service (from inspect())
         * @param array $config Updated service specification
         * @param string|null $authConfig Base64-encoded authentication credentials for private registries
         * @param string|null $registryAuthFrom Source of registry authentication: "spec" or "previous-spec"
         * @return Service The updated Service object
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function update(string $id, int $version, array $config, ?string $authConfig = null, ?string $registryAuthFrom = null): Service
        {
            $query = ['version' => $version];
            if ($registryAuthFrom !== null)
            {
                $query['registryAuthFrom'] = $registryAuthFrom;
            }

            $headers = [];
            if ($authConfig !== null)
            {
                $headers['X-Registry-Auth'] = base64_encode($authConfig);
            }

            $this->client->request('POST', "/services/$id/update", $config, $query, $headers);
            return $this->inspect($id);
        }

        /**
         * Removes a service from the swarm
         *
         * Deletes a service and stops all its tasks. Running containers
         * associated with the service will be stopped and removed.
         *
         * @param string $id Service ID or name to remove
         * @return void
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function remove(string $id): void
        {
            $this->client->request('DELETE', "/services/$id");
        }

        /**
         * Retrieves logs from a service
         *
         * Fetches aggregated logs from all tasks of the service.
         * Logs can be filtered by time range and limited to recent lines.
         *
         * @param string $id Service ID or name
         * @param bool $stdout If true, includes stdout logs
         * @param bool $stderr If true, includes stderr logs
         * @param bool $timestamps If true, prepends timestamps to each log line
         * @param string $tail Number of lines to show from the end of the logs; use 'all' for all logs
         * @param int|null $since Only return logs since this Unix timestamp
         * @return string Raw log output as a string
         * @throws ResponseException Thrown on a response error
         */
        public function logs(string $id, bool $stdout = true, bool $stderr = true, bool $timestamps = false, string $tail = 'all', ?int $since = null): string
        {
            $query = [
                'stdout' => $stdout ? 1 : 0,
                'stderr' => $stderr ? 1 : 0,
                'timestamps' => $timestamps ? 1 : 0,
                'tail' => $tail,
            ];

            if ($since !== null)
            {
                $query['since'] = $since;
            }

            return $this->client->requestRaw('GET', "/services/$id/logs", null, $query);
        }
    }
