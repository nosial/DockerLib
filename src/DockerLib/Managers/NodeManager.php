<?php


    namespace DockerLib\Managers;

    use DockerLib\Classes\SocketClient;
    use DockerLib\Exceptions\ConnectionException;
    use DockerLib\Exceptions\ResponseException;
    use DockerLib\Objects\Node;

    /**
     * Manages Docker Swarm nodes
     *
     * Provides operations for managing nodes in a Docker Swarm cluster.
     * Nodes are Docker engines participating in the swarm, either as managers
     * or workers. Allows inspecting, updating, and removing nodes from the swarm.
     */
    class NodeManager
    {
        private SocketClient $client;

        /**
         * Creates a new NodeManager instance
         *
         * @param SocketClient $client The socket client for Docker API communication
         */
        public function __construct(SocketClient $client)
        {
            $this->client = $client;
        }

        /**
         * Lists all nodes in the swarm with optional filtering
         *
         * Returns a list of nodes participating in the swarm.
         * Can be filtered by role, membership state, or labels.
         *
         * @param array $filters Filters to apply (e.g., ['role' => ['manager'], 'membership' => ['accepted']])
         * @return array<Node> Array of Node objects matching the specified criteria
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

            $response = $this->client->request('GET', '/nodes', null, $query);
            $nodes = [];

            foreach ($response['data'] as $data)
            {
                $nodes[] = Node::fromArray($data);
            }

            return $nodes;
        }

        /**
         * Inspects a node and retrieves detailed information
         *
         * Returns comprehensive information about a swarm node including
         * its role, status, resources, and manager status (if applicable).
         *
         * @param string $id Node ID or hostname to inspect
         * @return Node Node object containing detailed configuration and state information
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function inspect(string $id): Node
        {
            return Node::fromArray($this->client->request('GET', "/nodes/$id")['data']);
        }

        /**
         * Updates a node's configuration
         *
         * Modifies a node's attributes such as availability (active/pause/drain),
         * role (worker/manager), or labels. Requires the current node version.
         *
         * @param string $id Node ID or hostname to update
         * @param int $version The current version of the node (from inspect())
         * @param array $config Updated node specification with 'Availability', 'Role', 'Labels', etc.
         * @return void
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function update(string $id, int $version, array $config): void
        {
            $this->client->request('POST', "/nodes/$id/update", $config, ['version' => $version]);
        }

        /**
         * Removes a node from the swarm
         *
         * Deletes a node from the swarm. The node must be in a down state
         * or the force option must be used.
         *
         * @param string $id Node ID or hostname to remove
         * @param bool $force If true, forcefully removes the node even if it's still active
         * @return void
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function remove(string $id, bool $force = false): void
        {
            $this->client->request('DELETE', "/nodes/$id", null, ['force' => $force ? 1 : 0]);
        }
    }
