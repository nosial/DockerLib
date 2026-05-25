<?php


    namespace DockerLib\Managers;

    use DockerLib\Classes\SocketClient;
    use DockerLib\Exceptions\ConnectionException;
    use DockerLib\Exceptions\ResponseException;
    use DockerLib\Objects\Secret;

    /**
     * Manages Docker secrets
     *
     * Provides operations for managing sensitive data in Docker Swarm.
     * Secrets are encrypted during transit and at rest, and are only accessible
     * to services that have been granted explicit access. Used for passwords,
     * SSH keys, TLS certificates, and other confidential data.
     */
    class SecretManager
    {
        private SocketClient $client;

        /**
         * Creates a new SecretManager instance
         *
         * @param SocketClient $client The socket client for Docker API communication
         */
        public function __construct(SocketClient $client)
        {
            $this->client = $client;
        }

        /**
         * Lists all secrets in the swarm with optional filtering
         *
         * Returns a list of secrets available in the swarm.
         * Can be filtered by name, label, or other criteria.
         *
         * @param array $filters Filters to apply (e.g., ['name' => ['my-secret'], 'label' => ['key=value']])
         * @return array<Secret> Array of Secret objects matching the specified criteria
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

            $response = $this->client->request('GET', '/secrets', null, $query);
            $secrets = [];

            foreach ($response['data'] as $data)
            {
                $secrets[] = Secret::fromArray($data);
            }

            return $secrets;
        }

        /**
         * Inspects a secret and retrieves its metadata
         *
         * Returns information about a secret including its creation date,
         * labels, and version. The actual secret data is never returned.
         *
         * @param string $id Secret ID or name to inspect
         * @return Secret Secret object containing metadata (not the actual secret value)
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function inspect(string $id): Secret
        {
            return Secret::fromArray($this->client->request('GET', "/secrets/$id")['data']);
        }

        /**
         * Creates a new secret
         *
         * Creates a secret with the specified data and metadata.
         * The secret data should be base64-encoded in the configuration.
         *
         * @param array $config Secret configuration including 'Name', 'Data' (base64-encoded), 'Labels', etc.
         * @return Secret The created Secret object with metadata
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function create(array $config): Secret
        {
            $response = $this->client->request('POST', '/secrets/create', $config);
            return $this->inspect($response['data']['ID']);
        }

        /**
         * Updates a secret's metadata
         *
         * Modifies a secret's labels or other metadata. The actual secret data
         * cannot be updated; create a new secret instead. Requires the current version.
         *
         * @param string $id Secret ID or name to update
         * @param int $version The current version of the secret (from inspect())
         * @param array $config Updated secret specification with 'Labels', etc.
         * @return void
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function update(string $id, int $version, array $config): void
        {
            $this->client->request('POST', "/secrets/$id/update", $config, ['version' => $version]);
        }

        /**
         * Removes a secret from the swarm
         *
         * Deletes a secret. The secret must not be in use by any services.
         * Once deleted, the secret data is permanently lost.
         *
         * @param string $id Secret ID or name to remove
         * @return void
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function remove(string $id): void
        {
            $this->client->request('DELETE', "/secrets/$id");
        }
    }
