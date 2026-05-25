<?php


    namespace DockerLib\Managers;

    use DockerLib\Classes\SocketClient;
    use DockerLib\Exceptions\ConnectionException;
    use DockerLib\Exceptions\ResponseException;
    use DockerLib\Objects\Swarm;

    /**
     * Manages Docker Swarm mode operations
     *
     * Provides operations for managing Docker Swarm clusters including initialization,
     * joining nodes, leaving the swarm, and updating swarm configuration.
     * Swarm mode enables native clustering and orchestration of Docker engines.
     */
    class SwarmManager
    {
        private SocketClient $client;

        /**
         * Creates a new SwarmManager instance
         *
         * @param SocketClient $client The socket client for Docker API communication
         */
        public function __construct(SocketClient $client)
        {
            $this->client = $client;
        }

        /**
         * Inspects the swarm configuration
         *
         * Returns comprehensive information about the current swarm including
         * its configuration, TLS information, and raft consensus settings.
         *
         * @return Swarm Swarm object containing swarm configuration and metadata
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function inspect(): Swarm
        {
            return Swarm::fromArray($this->client->request('GET', '/swarm')['data']);
        }

        /**
         * Initializes a new swarm
         *
         * Turns the Docker engine into a swarm manager node and creates a new swarm.
         * This is the first step in creating a swarm cluster.
         *
         * @param array $config Swarm initialization configuration including 'ListenAddr', 'AdvertiseAddr', 'Spec', etc.
         * @return string The swarm node ID
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function init(array $config): string
        {
            return $this->client->request('POST', '/swarm/init', $config)['data'];
        }

        /**
         * Joins an existing swarm
         *
         * Makes the current Docker engine join an existing swarm as either
         * a worker node or a manager node.
         *
         * @param array $config Join configuration including 'ListenAddr', 'AdvertiseAddr', 'RemoteAddrs', 'JoinToken', etc.
         * @return void
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function join(array $config): void
        {
            $this->client->request('POST', '/swarm/join', $config);
        }

        /**
         * Leaves the swarm
         *
         * Removes the current node from the swarm. If this is the last manager node,
         * the force option must be used.
         *
         * @param bool $force If true, forcefully leaves the swarm even if this is the last manager
         * @return void
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function leave(bool $force = false): void
        {
            $this->client->request('POST', '/swarm/leave', null, ['force' => $force ? 1 : 0]);
        }

        /**
         * Updates the swarm configuration
         *
         * Modifies the swarm configuration such as certificate settings, dispatcher
         * configuration, or raft settings. Requires the current swarm version.
         *
         * @param int $version The current version of the swarm (from inspect())
         * @param array $config Updated swarm specification with 'Name', 'Orchestration', 'Raft', 'Dispatcher', etc.
         * @param bool $rotateWorkerToken If true, generates a new worker join token
         * @param bool $rotateManagerToken If true, generates a new manager join token
         * @param bool $rotateManagerUnlockKey If true, generates a new unlock key for locked managers
         * @return void
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function update(int $version, array $config, bool $rotateWorkerToken = false, bool $rotateManagerToken = false, bool $rotateManagerUnlockKey = false): void
        {
            $this->client->request('POST', '/swarm/update', $config, [
                'version' => $version,
                'rotateWorkerToken' => $rotateWorkerToken ? 1 : 0,
                'rotateManagerToken' => $rotateManagerToken ? 1 : 0,
                'rotateManagerUnlockKey' => $rotateManagerUnlockKey ? 1 : 0,
            ]);
        }

        /**
         * Retrieves the swarm unlock key
         *
         * Gets the unlock key required to unlock a manager node that was
         * locked after restarting. Only applicable if auto-lock is enabled.
         *
         * @return array Array containing 'UnlockKey' field
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function unlockKey(): array
        {
            return $this->client->request('GET', '/swarm/unlockkey')['data'];
        }

        /**
         * Unlocks a locked manager node
         *
         * Unlocks a swarm manager using the unlock key. Required when a manager
         * with auto-lock enabled restarts.
         *
         * @param string $unlockKey The unlock key to use for unlocking the manager
         * @return void
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function unlock(string $unlockKey): void
        {
            $this->client->request('POST', '/swarm/unlock', ['UnlockKey' => $unlockKey]);
        }
    }
