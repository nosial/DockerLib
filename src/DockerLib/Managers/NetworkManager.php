<?php


    namespace DockerLib\Managers;

    use DockerLib\Classes\SocketClient;
    use DockerLib\Exceptions\ConnectionException;
    use DockerLib\Exceptions\ResponseException;
    use DockerLib\Objects\Network;
    use InvalidArgumentException;

    /**
     * Manages Docker networks
     *
     * Provides operations for managing Docker networks including creating custom networks,
     * connecting/disconnecting containers, and managing network isolation. Supports bridge,
     * overlay, and other network drivers for container communication.
     */
    class NetworkManager
    {
        private SocketClient $client;

        /**
         * Creates a new NetworkManager instance
         *
         * @param SocketClient $client The socket client for Docker API communication
         */
        public function __construct(SocketClient $client)
        {
            $this->client = $client;
        }

        /**
         * Lists all Docker networks with optional filtering
         *
         * Returns a list of networks available on the Docker host.
         * Can be filtered by driver, type, name, or other criteria.
         *
         * @param array $filters Filters to apply (e.g., ['driver' => ['bridge'], 'name' => ['my-network']])
         * @return array<Network> Array of Network objects matching the specified criteria
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

            $response = $this->client->request('GET', '/networks', null, $query);
            $networks = [];

            foreach ($response['data'] as $data)
            {
                $networks[] = Network::fromArray($data);
            }

            return $networks;
        }

        /**
         * Inspects a Docker network and retrieves detailed information
         *
         * Returns comprehensive information about a network including its configuration,
         * connected containers, IP address management, and driver options.
         *
         * @param string $id Network ID or name to inspect
         * @return Network Network object containing detailed configuration and state information
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function inspect(string $id): Network
        {
            return Network::fromArray($this->client->request('GET', "/networks/$id")['data']);
        }

        /**
         * Creates a new Docker network
         *
         * Creates a custom network with the specified configuration.
         * Supports various network drivers like bridge, overlay, macvlan, and ipvlan.
         *
         * @param array $config Network configuration including 'Name' (required), 'Driver', 'IPAM', 'Options', etc.
         * @return Network The created Network object with full configuration details
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function create(array $config): Network
        {
            if (!isset($config['Name']))
            {
                throw new InvalidArgumentException('Network name is required in config');
            }
            
            $response = $this->client->request('POST', '/networks/create', $config);
            return $this->inspect($response['data']['Id']);
        }

        /**
         * Removes a Docker network
         *
         * Deletes a network from the Docker host. The network must not have any
         * containers connected to it before removal.
         *
         * @param string $id Network ID or name to remove
         * @return void
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function remove(string $id): void
        {
            $this->client->request('DELETE', "/networks/$id");
        }

        /**
         * Connects a container to a network
         *
         * Attaches a container to the specified network, allowing it to communicate
         * with other containers on the same network.
         *
         * @param string $id Network ID or name to connect to
         * @param string $container Container ID or name to connect
         * @param array $config Optional endpoint configuration (e.g., ['IPAMConfig' => ['IPv4Address' => '172.20.0.5']])
         * @return void
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function connect(string $id, string $container, array $config = []): void
        {
            $data = ['Container' => $container];
            
            if (!empty($config))
            {
                $data['EndpointConfig'] = $config;
            }

            $this->client->request('POST', "/networks/$id/connect", $data);
        }

        /**
         * Disconnects a container from a network
         *
         * Detaches a container from the specified network, removing its ability
         * to communicate with containers on that network.
         *
         * @param string $id Network ID or name to disconnect from
         * @param string $container Container ID or name to disconnect
         * @param bool $force If true, forcefully disconnects the container even if it's the default network
         * @return void
         * @throws ConnectionException Thrown on a connection error
         * @throws ResponseException Thrown on a response error
         */
        public function disconnect(string $id, string $container, bool $force = false): void
        {
            $data = [
                'Container' => $container,
                'Force' => $force,
            ];

            $this->client->request('POST', "/networks/$id/disconnect", $data);
        }

        /**
         * Removes unused networks
         *
         * Deletes networks that are not being used by any containers.
         * This helps reclaim resources and clean up unused network configurations.
         *
         * @param array $filters Filters to apply (e.g., ['until' => ['<timestamp>'], 'label' => ['key=value']])
         * @return array Pruning results with 'NetworksDeleted' array
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

            $response = $this->client->request('POST', '/networks/prune', null, $query);
            return $response['data'];
        }
    }
