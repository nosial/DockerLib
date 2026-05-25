<?php

    namespace DockerLib\Managers;

    use DockerLib\Classes\Logger;
    use DockerLib\Classes\SocketClient;
    use DockerLib\Exceptions\ConnectionException;
    use DockerLib\Exceptions\ResponseException;
    use DockerLib\Objects\Container;
    use DockerLib\Objects\ContainerStats;
    use DockerLib\Objects\StreamResponse;

    /**
     * Manages Docker containers
     *
     * Provides comprehensive operations for managing Docker containers including creation,
     * starting, stopping, inspecting, and monitoring containers. Handles container lifecycle
     * management, resource allocation, logging, and file system operations.
     */
    class ContainerManager
    {
        private SocketClient $client;

        /**
         * Creates a new ContainerManager instance
         *
         * @param SocketClient $client The socket client for Docker API communication
         */
        public function __construct(SocketClient $client)
        {
            $this->client = $client;
        }

        /**
         * Lists all Docker containers with optional filtering
         *
         * Returns a list of containers based on the provided filters and options.
         * Supports both current and legacy parameter formats for backward compatibility.
         *
         * @param array $filters Filters to apply as associative array (e.g., ['status' => ['running']])
         *                            or legacy boolean parameter (deprecated)
         * @param bool|int $all When true, shows all containers including stopped ones; when false, shows only running containers
         * @param int $limit Maximum number of containers to return; -1 for unlimited
         * @param bool|array $size When true, includes container size information in the response
         * @return array<Container> Array of Container objects matching the specified criteria
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function list(array $filters=[], int|false $all=false, int $limit=-1, array|false $size=false): array
        {
            // Handle legacy boolean/mixed parameter usage
            if (is_bool($filters) || is_int($filters))
            {
                $actualAll = is_bool($filters) ? $filters : false;
                $actualLimit = is_int($all) ? $all : -1;
                $actualSize = is_bool($limit) ? $limit : false;
                $actualFilters = is_array($size) ? $size : [];
                
                $filters = $actualFilters;
                $all = $actualAll;
                $limit = $actualLimit;
                $size = $actualSize;
            }
            elseif (!is_array($filters))
            {
                $filters = [];
            }

            $query = [
                'all' => $all ? 1 : 0,
                'size' => $size ? 1 : 0,
            ];

            if (!empty($filters))
            {
                $query['filters'] = json_encode($filters);
            }

            if ($limit > 0)
            {
                $query['limit'] = $limit;
            }

            $response = $this->client->request('GET', '/containers/json', null, $query);
            $containers = [];

            foreach ($response['data'] as $data)
            {
                $containers[] = Container::fromArray($data);
            }

            return $containers;
        }

        /**
         * Creates a new Docker container
         *
         * Creates a container from the provided configuration without starting it.
         * The container can be started later using the start() method.
         *
         * @param array $config Container configuration including image, command, environment, volumes, etc.
         * @param string|null $name Optional custom name for the container; auto-generated if not provided
         * @return Container The created Container object with full configuration details
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function create(array $config, ?string $name = null): Container
        {
            $query = [];
            if ($name !== null)
            {
                $query['name'] = $name;
            }

            $response = $this->client->request('POST', '/containers/create', $config, $query);
            return $this->inspect($response['data']['Id']);
        }

        /**
         * Inspects a Docker container and retrieves detailed information
         *
         * Returns comprehensive information about a container including its configuration,
         * state, network settings, mounts, and other metadata.
         *
         * @param string $id Container ID or name to inspect
         * @return Container Container object containing detailed configuration and state information
         * @throws ConnectionException
         * @throws ResponseException
         */
        public function inspect(string $id): Container
        {
            $response = $this->client->request('GET', "/containers/$id/json");
            return Container::fromArray($response['data']);
        }

        /**
         * Starts a previously created container
         *
         * Starts a container that is in "created" or "stopped" state.
         * This operation is idempotent; starting an already running container has no effect.
         *
         * @param string $id Container ID or name to start
         * @param string|null $detachKeys Override the key sequence for detaching from the container (e.g., "ctrl-p,ctrl-q")
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function start(string $id, ?string $detachKeys = null): void
        {
            Logger::getLogger()->info("Starting container: $id");
            
            $query = [];
            if ($detachKeys !== null)
            {
                $query['detachKeys'] = $detachKeys;
            }

            $this->client->request('POST', "/containers/$id/start", null, $query);
            
            Logger::getLogger()->debug("Container started successfully: $id");
        }

        /**
         * Stops a running container gracefully
         *
         * Sends SIGTERM to the container's main process, then waits for the specified timeout
         * before sending SIGKILL. Default timeout is 10 seconds.
         *
         * @param string $id Container ID or name to stop
         * @param int|null $timeout Number of seconds to wait before forcefully killing the container
         * @return void
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function stop(string $id, ?int $timeout = null): void
        {
            $query = [];
            if ($timeout !== null)
            {
                $query['t'] = $timeout;
            }

            $this->client->request('POST', "/containers/$id/stop", null, $query);
        }

        /**
         * Restarts a container
         *
         * Stops and then starts the container. If the container is already stopped,
         * it will just be started.
         *
         * @param string $id Container ID or name to restart
         * @param int|null $timeout Number of seconds to wait before forcefully killing during stop phase
         * @return void
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function restart(string $id, ?int $timeout = null): void
        {
            $query = [];
            if ($timeout !== null)
            {
                $query['t'] = $timeout;
            }

            $this->client->request('POST', "/containers/$id/restart", null, $query);
        }

        /**
         * Kills a running container by sending a signal
         *
         * Sends a Unix signal to the container's main process. Default is SIGKILL
         * which immediately terminates the container without cleanup.
         *
         * @param string $id Container ID or name to kill
         * @param string $signal Signal to send (e.g., "SIGKILL", "SIGTERM", "SIGHUP")
         * @return void
         * @throws ConnectionException
         * @throws ResponseException
         */
        public function kill(string $id, string $signal = 'SIGKILL'): void
        {
            $query = ['signal' => $signal];
            $this->client->request('POST', "/containers/$id/kill", null, $query);
        }

        /**
         * Removes a container
         *
         * Deletes a container from the Docker host. The container must be stopped first
         * unless the force option is used.
         *
         * @param string $id Container ID or name to remove
         * @param bool $force If true, forcefully removes a running container (uses SIGKILL)
         * @param bool $volumes If true, removes anonymous volumes associated with the container
         * @param bool $link If true, removes the specified link
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a respons eerror
         */
        public function remove(string $id, bool $force=false, bool $volumes=false, bool $link=false): void
        {
            $query = [
                'force' => $force ? 1 : 0,
                'v' => $volumes ? 1 : 0,
                'link' => $link ? 1 : 0,
            ];

            $this->client->request('DELETE', "/containers/$id", null, $query);
        }

        /**
         * Pauses all processes within a container
         *
         * Suspends all processes in the container using cgroups freezer.
         * The container remains running but all processes are paused.
         *
         * @param string $id Container ID or name to pause
         * @return void
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function pause(string $id): void
        {
            $this->client->request('POST', "/containers/$id/pause");
        }

        /**
         * Unpauses all processes within a paused container
         *
         * Resumes all processes that were previously paused in the container.
         *
         * @param string $id Container ID or name to unpause
         * @return void
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function unpause(string $id): void
        {
            $this->client->request('POST', "/containers/$id/unpause");
        }

        /**
         * Renames a container
         *
         * Changes the name of an existing container to the specified new name.
         * The new name must not already be in use.
         *
         * @param string $id Container ID or current name
         * m string $name New name for the container
         * @param string $name New name for the container; must be unique among all containers
         * @return void
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error (e.g., name already in use)
         */
        public function rename(string $id, string $name): void
        {
            $query = ['name' => $name];
            $this->client->request('POST', "/containers/$id/rename", null, $query);
        }

        /**
         * Retrieves logs from a container
         *
         * Fetches stdout and/or stderr logs from the container. Logs can be filtered
         * by time range and limited to a specific number of recent lines.
         *
         * @param string $id Container ID or name
         * @param bool $stdout If true, includes stdout logs
         * @param bool $stderr If true, includes stderr logs
         * @param bool $timestamps If true, prepends timestamps to each log line
         * @param string $tail Number of lines to show from the end of the logs; use 'all' for all logs
         * @param int|null $since Only return logs since this Unix timestamp
         * @param int|null $until Only return logs before this Unix timestamp
         * @return string Raw log output as a string
         * @throws ResponseException Thrown on a response error (e.g., container not found)
         */
        public function logs(string $id, bool $stdout = true, bool $stderr = true, bool $timestamps = false, string $tail = 'all', ?int $since = null, ?int $until = null): string
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

            if ($until !== null)
            {
                $query['until'] = $until;
            }

            return $this->client->requestRaw('GET', "/containers/$id/logs", null, $query);
        }

        /**
         * Retrieves resource usage statistics for a container
         *
         * Returns a snapshot of container resource usage including CPU, memory,
         * network I/O, and block I/O statistics.
         *
         * @param string $id Container ID or name
         * @param bool $stream If true, streams stats continuously; if false, returns a single snapshot
         * @return ContainerStats Object containing resource usage statistics
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error (e.g., container not found)
         */
        public function stats(string $id, bool $stream = false): ContainerStats
        {
            $query = ['stream' => $stream ? 1 : 0];
            $response = $this->client->request('GET', "/containers/$id/stats", null, $query);
            return ContainerStats::fromArray($response['data']);
        }

        /**
         * Lists running processes inside a container
         *
         * Returns information about processes running inside the container,
         * similar to the Unix 'ps' command.
         *
         * @param string $id Container ID or name
         * @param string $psArgs Arguments to pass to 'ps' command (default: '-ef' for full format listing)
         * @return array Array containing process information with 'Titles' and 'Processes' keys
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error (e.g., container not found)
         */
        public function top(string $id, string $psArgs = '-ef'): array
        {
            $query = ['ps_args' => $psArgs];
            $response = $this->client->request('GET', "/containers/$id/top", null, $query);
            return $response['data'];
        }

        /**
         * Inspects changes to files or directories on a container's filesystem
         *
         * Returns a list of files and directories that have been added, modified,
         * or deleted since the container was created.
         *
         * @param string $id Container ID or name
         * @return array Array of changes with 'Path' and 'Kind' (0=modified, 1=added, 2=deleted)
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error (e.g., container not found)
         */
        public function changes(string $id): array
        {
            $response = $this->client->request('GET', "/containers/$id/changes");
            return $response['data'];
        }

        /**
         * Exports a container's filesystem as a tar archive
         *
         * Exports the entire filesystem of the container as a tar archive.
         * This includes the full filesystem, not just changes.
         *
         * @param string $id Container ID or name
         * @return string Raw tar archive data as a binary string
         * @throws ResponseException Thrown on a response error (e.g., container not found)
         */
        public function export(string $id): string
        {
            return $this->client->requestRaw('GET', "/containers/$id/export");
        }

        /**
         * Attaches to a running container
         *
         * Attaches to a container to read its output or send input to it.
         * Returns a bidirectional stream for interaction with the container.
         *
         * @param string $id Container ID or name
         * @param bool $logs If true, returns previous log output
         * @param bool $stream If true, streams output; if false, returns current output and closes
         * @param bool $stdin If true, attaches to stdin for sending input
         * @param bool $stdout If true, attaches to stdout
         * @param bool $stderr If true, attaches to stderr
         * @return StreamResponse Stream object for reading/writing container I/O
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error (e.g., container not found)
         */
        public function attach(string $id, bool $logs = false, bool $stream = true, bool $stdin = false, bool $stdout = true, bool $stderr = true): StreamResponse
        {
            $query = [
                'logs' => $logs ? 1 : 0,
                'stream' => $stream ? 1 : 0,
                'stdin' => $stdin ? 1 : 0,
                'stdout' => $stdout ? 1 : 0,
                'stderr' => $stderr ? 1 : 0,
            ];

            return $this->client->stream('POST', "/containers/$id/attach", null, $query);
        }

        /**
         * Waits for a container to meet a specific condition
         *
         * Blocks until the container reaches the specified state condition.
         * Returns when the condition is met with the container's exit status.
         *
         * @param string $id Container ID or name
         * @param string $condition Condition to wait for: "not-running", "next-exit", "removed"
         * @return array Array containing 'StatusCode' and optional 'Error' information
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error (e.g., container not found)
         */
        public function wait(string $id, string $condition = 'not-running'): array
        {
            $query = ['condition' => $condition];
            $response = $this->client->request('POST', "/containers/$id/wait", null, $query);
            return $response['data'];
        }

        /**
         * Removes stopped containers
         *
         * Deletes all stopped containers matching the specified filters.
         * This helps reclaim disk space by removing unused containers.
         *
         * @param array $filters Filters to apply (e.g., ['until' => ['<timestamp>']])
         * @return array Pruning results with 'ContainersDeleted' and 'SpaceReclaimed' keys
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

            $response = $this->client->request('POST', '/containers/prune', null, $query);
            return $response['data'];
        }

        /**
         * Updates a container's resource configuration
         *
         * Modifies resource constraints for a running container such as CPU shares,
         * memory limits, and restart policy.
         *
         * @param string $id Container ID or name
         * @param array $config Configuration updates (e.g., ['Memory' => 536870912, 'CpuShares' => 512])
         * @return void
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error (e.g., container not found, invalid configuration)
         */
        public function update(string $id, array $config): void
        {
            $this->client->request('POST', "/containers/$id/update", $config);
        }

        /**
         * Resizes the TTY for a container
         *
         * Changes the size of the TTY (terminal) for exec instances or attach sessions.
         * Used to adjust terminal dimensions when console window size changes.
         *
         * @param string $id Container ID or name
         * @param int $height New height in characters
         * @param int $width New width in characters
         * @return void
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error (e.g., container not found, no TTY attached)
         */
        public function resize(string $id, int $height, int $width): void
        {
            $query = ['h' => $height, 'w' => $width];
            $this->client->request('POST', "/containers/$id/resize", null, $query);
        }

        /**
         * Get an archive of a filesystem resource in a container
         *
         * @param string $id Container ID or name
         * @param string $path Resource in the container's filesystem to archive
         * @return string The tar archive as raw binary data
         * @throws ResponseException Thrown on a response error (e.g., container not found, path not found)
         */
        public function getArchive(string $id, string $path): string
        {
            $query = ['path' => $path];
            return $this->client->requestRaw('GET', "/containers/$id/archive", null, $query);
        }

        /**
         * Extract an archive of files or folders to a directory in a container
         *
         * @param string $id Container ID or name
         * @param string $path Path to a directory in the container to extract the archive's contents into
         * @param string $tarData The tar archive data
         * @param bool $noOverwriteDirNonDir If true, do not overwrite an existing directory with a non-directory and vice versa
         * @param string|null $copyUIDGID If set, change ownership of extracted files to this UID:GID
         * @return void
         * @throws ResponseException Thrown on a response error (e.g., container not found, path not found, invalid archive data)
         */
        public function putArchive(string $id, string $path, string $tarData, bool $noOverwriteDirNonDir = false, ?string $copyUIDGID = null): void
        {
            $query = ['path' => $path];
            
            if ($noOverwriteDirNonDir)
            {
                $query['noOverwriteDirNonDir'] = 'true';
            }
            
            if ($copyUIDGID !== null)
            {
                $query['copyUIDGID'] = $copyUIDGID;
            }

            $headers = ['Content-Type' => 'application/x-tar'];
            $this->client->requestRaw('PUT', "/containers/$id/archive", $tarData, $query, $headers);
        }

        /**
         * Get information about files in a container
         *
         * @param string $id Container ID or name
         * @param string $path Resource in the container's filesystem to get info about
         * @return array Decoded stat information from X-Docker-Container-Path-Stat header
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error (e.g., container not found, path not found)
         */
        public function statArchive(string $id, string $path): array
        {
            $query = ['path' => $path];
            $response = $this->client->request('HEAD', "/containers/$id/archive", null, $query);
            
            // The stat info is returned in the X-Docker-Container-Path-Stat header as base64-encoded JSON
            // Headers are lowercase in response
            if (isset($response['headers']['x-docker-container-path-stat']))
            {
                $decoded = base64_decode($response['headers']['x-docker-container-path-stat']);
                return json_decode($decoded, true) ?? [];
            }
            
            return [];
        }

        /**
         * Attach to a container via WebSocket
         *
         * @param string $id Container ID or name
         * @param bool $logs Return logs
         * @param bool $stream Stream attach (multiplex stdout/stderr)
         * @param bool $stdin Attach to stdin
         * @param bool $stdout Attach to stdout
         * @param bool $stderr Attach to stderr
         * @param string|null $detachKeys Override the key sequence for detaching a container
         * @return string WebSocket URL to connect to
         */
        public function attachWebSocket(string $id, bool $logs = false, bool $stream = true, bool $stdin = false, bool $stdout = true, bool $stderr = true, ?string $detachKeys = null): string
        {
            $query = [
                'logs' => $logs ? 1 : 0,
                'stream' => $stream ? 1 : 0,
                'stdin' => $stdin ? 1 : 0,
                'stdout' => $stdout ? 1 : 0,
                'stderr' => $stderr ? 1 : 0,
            ];
            
            if ($detachKeys !== null)
            {
                $query['detachKeys'] = $detachKeys;
            }

            // Build WebSocket URL
            $queryString = http_build_query($query);
            return "ws://localhost/containers/$id/attach/ws?$queryString";
        }
    }
