<?php


    namespace DockerLib\Managers;

    use DockerLib\Classes\Logger;
    use DockerLib\Classes\SocketClient;
    use DockerLib\Exceptions\ConnectionException;
    use DockerLib\Exceptions\ResponseException;
    use DockerLib\Objects\Distribution;

    /**
     * Manages Docker image distribution information
     *
     * Provides operations for retrieving image distribution metadata from registries.
     * This includes manifest information, supported platforms, and image descriptors
     * without pulling the entire image. Useful for inspecting multi-platform images
     * and registry metadata.
     */
    class DistributionManager
    {
        private SocketClient $client;

        /**
         * Creates a new DistributionManager instance
         *
         * @param SocketClient $client The socket client for Docker API communication
         */
        public function __construct(SocketClient $client)
        {
            $this->client = $client;
        }

        /**
         * Get image information from the registry
         *
         * Retrieves distribution metadata including manifest information,
         * supported platforms, and image descriptors for the specified image.
         *
         * @param string $name Image name or ID
         * @return Distribution Distribution information including descriptor and platforms
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function inspect(string $name): Distribution
        {
            Logger::getLogger()->debug("Inspecting distribution for image: $name");
            $response = $this->client->request('GET', "/distribution/$name/json");
            Logger::getLogger()->verbose("Distribution inspection complete: $name");
            return Distribution::fromArray($response['data']);
        }
    }
