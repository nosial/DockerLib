<?php


    namespace DockerLib\Managers;

    use DockerLib\Classes\SocketClient;
    use DockerLib\Exceptions\ConnectionException;
    use DockerLib\Exceptions\ResponseException;
    use DockerLib\Objects\StreamResponse;
    use DockerLib\Objects\SystemDataUsage;
    use DockerLib\Objects\SystemInfo;

    /**
     * Manages Docker system-level operations
     *
     * Provides system-wide operations including retrieving Docker engine information,
     * version details, monitoring system events, checking disk usage, and managing
     * authentication. Used for system diagnostics and monitoring.
     */
    class SystemManager
    {
        private SocketClient $client;

        /**
         * Creates a new SystemManager instance
         *
         * @param SocketClient $client The socket client for Docker API communication
         */
        public function __construct(SocketClient $client)
        {
            $this->client = $client;
        }

        /**
         * Retrieves Docker system information
         *
         * Returns comprehensive information about the Docker engine including
         * container/image counts, system resources, storage drivers, and configuration.
         *
         * @return SystemInfo System information object containing Docker engine details
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function info(): SystemInfo
        {
            return SystemInfo::fromArray($this->client->request('GET', '/info')['data']);
        }

        /**
         * Retrieves Docker version information
         *
         * Returns version details of the Docker engine including version number,
         * API version, Git commit, build time, and platform information.
         *
         * @return array Version information with keys like 'Version', 'ApiVersion', 'GitCommit', 'GoVersion', etc.
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function version(): array
        {
            return $this->client->request('GET', '/version')['data'];
        }

        /**
         * Pings the Docker daemon
         *
         * Simple health check endpoint to verify the Docker daemon is running
         * and responsive. Returns "OK" on success.
         *
         * @return string Response from the Docker daemon (typically "OK")
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function ping(): string
        {
            return $this->client->request('GET', '/_ping')['data'] ?? 'OK';
        }

        /**
         * Pings the Docker daemon using HEAD method
         *
         * Lightweight alternative to ping() that uses HEAD instead of GET.
         * Returns the same response headers but with an empty body.
         * Useful for quick health checks with minimal overhead.
         *
         * @return array Response headers from the Docker daemon
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function pingHead(): array
        {
            $response = $this->client->request('HEAD', '/_ping');
            return $response['data'] ?? (isset($response['headers']) ? $response['headers'] : []);
        }

        /**
         * Streams real-time events from the Docker daemon
         *
         * Returns a stream of events occurring in the Docker daemon such as
         * container start/stop, image pull/push, network create/destroy, etc.
         *
         * @param array $filters Filters to apply (e.g., ['type' => ['container'], 'event' => ['start', 'stop']])
         * @param int|null $since Only return events created since this Unix timestamp
         * @param int|null $until Only return events created before this Unix timestamp
         * @return StreamResponse Stream object for reading real-time Docker events
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function events(array $filters = [], ?int $since = null, ?int $until = null): StreamResponse
        {
            $query = [];
            if (!empty($filters))
            {
                $query['filters'] = json_encode($filters);
            }

            if ($since !== null)
            {
                $query['since'] = $since;
            }

            if ($until !== null)
            {
                $query['until'] = $until;
            }

            return $this->client->stream('GET', '/events', null, $query);
        }

        /**
         * Retrieves Docker disk usage information
         *
         * Returns information about disk space used by images, containers, volumes,
         * and build cache. Useful for monitoring and cleanup operations.
         *
         * @return SystemDataUsage Data usage object with breakdowns by resource type
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function dataUsage(): SystemDataUsage
        {
            return SystemDataUsage::fromArray($this->client->request('GET', '/system/df')['data']);
        }

        /**
         * Validates registry authentication credentials
         *
         * Checks credentials against a registry and returns the authentication status.
         * Used to verify registry credentials before push/pull operations.
         *
         * @param array $authConfig Authentication configuration with 'username', 'password', 'serveraddress', etc.
         * @return array Authentication response with 'Status' and 'IdentityToken' keys
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function auth(array $authConfig): array
        {
            return $this->client->request('POST', '/auth', $authConfig)['data'];
        }

        /**
         * Initialize interactive session
         *
         * This endpoint is experimental and used for BuildKit session support.
         * It hijacks the HTTP connection to HTTP2 transport for advanced gRPC capabilities.
         *
         * @return StreamResponse The hijacked session stream
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function createSession(): StreamResponse
        {
            return $this->client->stream('POST', '/session');
        }
    }
