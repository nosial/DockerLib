<?php


    namespace DockerLib\Managers;

    use DockerLib\Classes\SocketClient;
    use DockerLib\Exceptions\ConnectionException;
    use DockerLib\Exceptions\ResponseException;
    use DockerLib\Objects\Task;

    /**
     * Manages Docker Swarm tasks
     *
     * Provides operations for inspecting and monitoring tasks in Docker Swarm.
     * Tasks represent the execution of a service's containers on swarm nodes.
     * Each task corresponds to one container and includes its scheduling,
     * execution state, and resource allocation.
     */
    class TaskManager
    {
        private SocketClient $client;

        /**
         * Creates a new TaskManager instance
         *
         * @param SocketClient $client The socket client for Docker API communication
         */
        public function __construct(SocketClient $client)
        {
            $this->client = $client;
        }

        /**
         * Lists all tasks in the swarm with optional filtering
         *
         * Returns a list of tasks from all services in the swarm.
         * Can be filtered by service, node, desired state, or other criteria.
         *
         * @param array $filters Filters to apply (e.g., ['service' => ['my-service'], 'desired-state' => ['running']])
         * @return array<Task> Array of Task objects matching the specified criteria
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

            $response = $this->client->request('GET', '/tasks', null, $query);
            $tasks = [];

            foreach ($response['data'] as $data)
            {
                $tasks[] = Task::fromArray($data);
            }

            return $tasks;
        }

        /**
         * Inspects a task and retrieves detailed information
         *
         * Returns comprehensive information about a task including its
         * container specification, status, timestamps, and error information.
         *
         * @param string $id Task ID to inspect
         * @return Task Task object containing detailed state and configuration information
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function inspect(string $id): Task
        {
            return Task::fromArray($this->client->request('GET', "/tasks/$id")['data']);
        }

        /**
         * Retrieves logs from a task
         *
         * Fetches stdout and/or stderr logs from the task's container.
         * Logs can be filtered by time range and limited to recent lines.
         *
         * @param string $id Task ID
         * @param bool $stdout If true, includes stdout logs
         * @param bool $stderr If true, includes stderr logs
         * @param bool $timestamps If true, prepends timestamps to each log line
         * @param string $tail Number of lines to show from the end of the logs; use 'all' for all logs
         * @param int|null $since Only return logs since this Unix timestamp
         * @return string Raw log output as a string
         * @throws ConnectionException Thrown on a connection error
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

            return $this->client->requestRaw('GET', "/tasks/$id/logs", null, $query);
        }
    }
