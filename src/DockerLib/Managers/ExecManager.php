<?php


    namespace DockerLib\Managers;

    use DockerLib\Classes\SocketClient;
    use DockerLib\Exceptions\ConnectionException;
    use DockerLib\Exceptions\ResponseException;
    use DockerLib\Objects\ExecInstance;
    use DockerLib\Objects\StreamResponse;

    /**
     * Manages Docker exec instances
     *
     * Provides operations for running commands inside running containers.
     * Exec instances allow executing processes in an already running container
     * without starting a new container or modifying the container's main process.
     */
    class ExecManager
    {
        private SocketClient $client;

        /**
         * Creates a new ExecManager instance
         *
         * @param SocketClient $client The socket client for Docker API communication
         */
        public function __construct(SocketClient $client)
        {
            $this->client = $client;
        }

        /**
         * Creates an exec instance in a running container
         *
         * Sets up a command to be executed inside the specified container.
         * The exec instance must be started with the start() method.
         *
         * @param string $containerId Container ID or name where the command will be executed
         * @param array $config Exec configuration including 'Cmd' (command array), 'AttachStdin', 'AttachStdout', 'AttachStderr', 'Tty', 'Env', 'User', etc.
         * @return ExecInstance The created exec instance object
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function create(string $containerId, array $config): ExecInstance
        {
            $response = $this->client->request('POST', "/containers/$containerId/exec", $config);
            return $this->inspect($response['data']['Id']);
        }

        /**
         * Starts a previously created exec instance
         *
         * Executes the command configured in the exec instance and returns
         * a stream for interacting with the process.
         *
         * @param string $execId Exec instance ID to start
         * @param bool $detach If true, starts the exec instance in detached mode (does not attach to output)
         * @param string|null $detachKeys Override the key sequence for detaching from the exec instance
         * @return StreamResponse Stream object for reading output and sending input to the exec process
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function start(string $execId, bool $detach = false, ?string $detachKeys = null): StreamResponse
        {
            $data = ['Detach' => $detach];
            if ($detachKeys !== null)
            {
                $data['DetachKeys'] = $detachKeys;
            }

            return $this->client->stream('POST', "/exec/$execId/start", $data);
        }

        /**
         * Inspects an exec instance
         *
         * Returns detailed information about an exec instance including its
         * configuration, running status, exit code, and process ID.
         *
         * @param string $execId Exec instance ID to inspect
         * @return ExecInstance Exec instance object with configuration and state information
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function inspect(string $execId): ExecInstance
        {
            return ExecInstance::fromArray($this->client->request('GET', "/exec/$execId/json")['data']);
        }

        /**
         * Resizes the TTY for an exec instance
         *
         * Changes the size of the TTY (terminal) for a running exec instance.
         * Used to adjust terminal dimensions when console window size changes.
         *
         * @param string $execId Exec instance ID to resize
         * @param int $height New height in characters
         * @param int $width New width in characters
         * @return void
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function resize(string $execId, int $height, int $width): void
        {
            $this->client->request('POST', "/exec/$execId/resize", null, ['h' => $height, 'w' => $width]);
        }
    }
