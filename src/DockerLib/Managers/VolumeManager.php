<?php


    namespace DockerLib\Managers;

    use DockerLib\Classes\SocketClient;
    use DockerLib\Exceptions\ConnectionException;
    use DockerLib\Exceptions\ResponseException;
    use DockerLib\Objects\Volume;

    /**
     * Manages Docker volumes
     *
     * Provides operations for managing Docker volumes including creation, inspection,
     * and removal. Volumes are the preferred mechanism for persisting data generated
     * by and used by Docker containers.
     */
    class VolumeManager
    {
        private SocketClient $client;

        /**
         * Creates a new VolumeManager instance
         *
         * @param SocketClient $client The socket client for Docker API communication
         */
        public function __construct(SocketClient $client)
        {
            $this->client = $client;
        }

        /**
         * Lists all Docker volumes with optional filtering
         *
         * Returns a list of volumes available on the Docker host.
         * Can be filtered by driver, name, labels, or other criteria.
         *
         * @param array $filters Filters to apply (e.g., ['driver' => ['local'], 'name' => ['my-volume']])
         * @return array Array containing 'Volumes' (array of Volume objects) and 'Warnings' (array of warning messages)
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function list(array $filters = []): array
        {
            $query = [];
            if (!empty($filters))
            {
                $query['filters'] = json_encode($filters);
            }

            $response = $this->client->request('GET', '/volumes', null, $query);
            $volumes = [];

            if (isset($response['data']['Volumes']))
            {
                foreach ($response['data']['Volumes'] as $data)
                {
                    $volumes[] = Volume::fromArray($data);
                }
            }

            return [
                'Volumes' => $volumes,
                'Warnings' => $response['data']['Warnings'] ?? []
            ];
        }

        /**
         * Inspects a Docker volume and retrieves detailed information
         *
         * Returns comprehensive information about a volume including its driver,
         * mount point, labels, and other metadata.
         *
         * @param string $name Volume name to inspect
         * @return Volume Volume object containing detailed configuration and metadata
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function inspect(string $name): Volume
        {
            return Volume::fromArray($this->client->request('GET', "/volumes/$name")['data']);
        }

        /**
         * Creates a new Docker volume
         *
         * Creates a volume with the specified name and configuration.
         * Supports both old API (single array parameter) and new API (name + config).
         *
         * @param array|string $name Volume name as string, or full configuration array for backward compatibility
         * @param array $config Volume configuration including 'Driver', 'DriverOpts', 'Labels', etc.
         * @return Volume The created Volume object with full configuration details
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function create(array|string $name, array $config = []): Volume
        {
            if (is_array($name))
            {
                $config = $name;
            }
            else
            {
                $config['Name'] = $name;
            }

            $response = $this->client->request('POST', '/volumes/create', $config);
            return Volume::fromArray($response['data']);
        }

        /**
         * Removes a Docker volume
         *
         * Deletes a volume from the Docker host. The volume must not be in use
         * by any containers unless the force option is used.
         *
         * @param string $name Volume name to remove
         * @param bool $force If true, forcefully removes the volume even if it's in use
         * @return void
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function remove(string $name, bool $force = false): void
        {
            $this->client->request('DELETE', "/volumes/$name", null, ['force' => $force ? 1 : 0]);
        }

        /**
         * Updates a volume (valid only for Swarm cluster volumes)
         *
         * Updates the specification of a cluster volume. Currently, only
         * Availability may change. The version number is required to avoid
         * conflicting writes and can be found in the volume's ClusterVolume field.
         *
         * @param string $name Volume name or ID to update
         * @param int $version The version number of the volume being updated (required to avoid conflicting writes)
         * @param array $spec The new ClusterVolumeSpec for the volume
         * @return array Response data from the API
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function update(string $name, int $version, array $spec = []): array
        {
            $query = ['version' => $version];
            $body = !empty($spec) ? ['Spec' => $spec] : [];
            $response = $this->client->request('PUT', "/volumes/$name", $body, $query);
            return $response['data'];
        }

        /**
         * Removes unused volumes
         *
         * Deletes volumes that are not being used by any containers.
         * This helps reclaim disk space by removing orphaned volumes.
         *
         * @param array $filters Filters to apply (e.g., ['label' => ['key=value']])
         * @return array Pruning results with 'VolumesDeleted' and 'SpaceReclaimed' keys
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function prune(array $filters = []): array
        {
            $query = [];
            if (!empty($filters))
            {
                $query['filters'] = json_encode($filters);
            }

            $response = $this->client->request('POST', '/volumes/prune', null, $query);
            return $response['data'];
        }
    }
