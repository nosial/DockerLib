<?php

    namespace DockerLib\Managers;

    use DockerLib\Classes\Logger;
    use DockerLib\Classes\SocketClient;
    use DockerLib\Exceptions\ConnectionException;
    use DockerLib\Exceptions\ResponseException;
    use DockerLib\Objects\Image;
    use DockerLib\Objects\StreamResponse;

    /**
     * Manages Docker images
     *
     * Provides comprehensive operations for managing Docker images including listing, pulling,
     * pushing, building, inspecting, and removing images. Handles image lifecycle management,
     * registry operations, and build cache management.
     */
    class ImageManager
    {
        private SocketClient $client;

        /**
         * Creates a new ImageManager instance
         *
         * @param SocketClient $client The socket client for Docker API communication
         */
        public function __construct(SocketClient $client)
        {
            $this->client = $client;
        }

        /**
         * Lists Docker images with optional filtering
         *
         * Returns a list of images from the Docker host, optionally filtered by criteria
         * such as reference, dangling status, or labels.
         *
         * @param array $filters Filters to apply (e.g., ['dangling' => ['true'], 'reference' => ['ubuntu:*']])
         * @param bool $all If true, shows all images including intermediate layers
         * @param bool $digests If true, includes image digests in the response
         * @return Image[] Array of Image objects matching the specified criteria
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function list(array $filters = [], bool $all = false, bool $digests = false): array
        {
            Logger::getLogger()->debug("Listing images with filters: " . json_encode($filters));

            $query = [
                'all' => $all ? 1 : 0,
                'digests' => $digests ? 1 : 0,
            ];

            if (!empty($filters))
            {
                $query['filters'] = json_encode($filters);
            }

            $response = $this->client->request('GET', '/images/json', null, $query);
            $images = [];

            foreach ($response['data'] as $data)
            {
                $images[] = Image::fromArray($data);
            }

            Logger::getLogger()->info("Listed " . count($images) . " image(s)");
            return $images;
        }

        /**
         * Inspects a Docker image and retrieves detailed information
         *
         * Returns comprehensive information about an image including its configuration,
         * layers, size, creation date, and metadata.
         *
         * @param string $name Image name, ID, or reference (e.g., "ubuntu:latest", "sha256:abc123...")
         * @return Image Image object containing detailed configuration and metadata
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function inspect(string $name): Image
        {
            Logger::getLogger()->debug("Inspecting image: $name");
            $response = $this->client->request('GET', "/images/$name/json");
            Logger::getLogger()->verbose("Image inspection complete: $name");

            return Image::fromArray($response['data']);
        }

        /**
         * Retrieves the history of an image
         *
         * Shows the history of an image including all layers and the commands
         * that created each layer.
         *
         * @param string $name Image name, ID, or reference
         * @return array Array of history entries with 'Id', 'Created', 'CreatedBy', 'Size', etc.
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function history(string $name): array
        {
            return $this->client->request('GET', "/images/$name/history")['data'];
        }

        /**
         * Pulls an image from a Docker registry
         *
         * Downloads an image or repository from a registry. Supports authentication
         * and provides progress updates through an optional callback.
         *
         * @param string $image Image name without tag (e.g., "ubuntu", "myrepo/myimage")
         * @param string|null $tag Image tag to pull (e.g., "latest", "1.0"); defaults to "latest" if not specified
         * @param string|null $authConfig Base64-encoded authentication credentials for the registry
         * @param callable|null $callback Optional callback function to receive progress updates (receives array with status info)
         * @return StreamResponse Stream object containing the pull operation response
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function pull(string $image, ?string $tag = null, ?string $authConfig = null, ?callable $callback = null): StreamResponse
        {
            $fullImage = $tag !== null ? "$image:$tag" : $image;
            Logger::getLogger()->info("Pulling image: $fullImage");

            $query = ['fromImage' => $image];
            if ($tag !== null)
            {
                $query['tag'] = $tag;
            }

            $headers = [];
            if ($authConfig !== null)
            {
                $headers['X-Registry-Auth'] = base64_encode($authConfig);
            }

            $stream = $this->client->stream('POST', '/images/create', null, $query, $headers);
            
            // Always read and log the stream for progress visibility
            while (($line = $stream->readLine()) !== null)
            {
                $data = json_decode($line, true);
                if ($data !== null)
                {
                    // Log progress to debug level for visibility
                    if (isset($data['status']))
                    {
                        $message = $data['status'];
                        if (isset($data['progress']))
                        {
                            $message .= " " . $data['progress'];
                        }
                        if (isset($data['id']))
                        {
                            $message = "[" . $data['id'] . "] " . $message;
                        }

                        Logger::getLogger()->debug("Pull: $message");
                    }

                    if ($callback !== null)
                    {
                        $callback($data);
                    }
                }
            }

            Logger::getLogger()->info("Image pull completed: $fullImage");
            
            return $stream;
        }

        /**
         * Pushes an image to a Docker registry
         *
         * Uploads an image or repository to a registry. Requires authentication
         * and provides progress updates through an optional callback.
         *
         * @param string $name Image name to push (must include registry if not Docker Hub)
         * @param string|null $tag Image tag to push (e.g., "latest", "1.0")
         * @param string|null $authConfig Base64-encoded authentication credentials for the registry
         * @param callable|null $callback Optional callback function to receive progress updates (receives array with status info)
         * @return StreamResponse Stream object containing the push operation response
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function push(string $name, ?string $tag = null, ?string $authConfig = null, ?callable $callback = null): StreamResponse
        {
            $fullImage = $tag !== null ? "$name:$tag" : $name;
            Logger::getLogger()->info("Pushing image: $fullImage");

            $query = [];
            if ($tag !== null)
            {
                $query['tag'] = $tag;
            }

            $headers = [];
            if ($authConfig !== null)
            {
                $headers['X-Registry-Auth'] = base64_encode($authConfig);
            }

            $stream = $this->client->stream('POST', "/images/$name/push", null, $query, $headers);
            
            // Always read and log the stream for progress visibility
            while (($line = $stream->readLine()) !== null)
            {
                $data = json_decode($line, true);
                if ($data !== null)
                {
                    // Log progress to debug level for visibility
                    if (isset($data['status']))
                    {
                        $message = $data['status'];
                        if (isset($data['progress']))
                        {
                            $message .= " " . $data['progress'];
                        }
                        if (isset($data['id']))
                        {
                            $message = "[" . $data['id'] . "] " . $message;
                        }

                        Logger::getLogger()->debug("Push: $message");
                    }

                    if ($callback !== null)
                    {
                        $callback($data);
                    }
                }
            }

            Logger::getLogger()->info("Image push completed: $fullImage");
            return $stream;
        }

        /**
         * Tags an image with a new repository and tag
         *
         * Creates a new tag reference pointing to an existing image.
         * Useful for versioning or preparing images for push to a registry.
         *
         * @param string $name Source image name or ID
         * @param string $repo Target repository name (e.g., "myrepo/myimage", "registry.example.com/app")
         * @param string|null $tag Target tag name (e.g., "latest", "v1.0"); defaults to "latest" if not specified
         * @return void
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function tag(string $name, string $repo, ?string $tag = null): void
        {
            $query = ['repo' => $repo];
            if ($tag !== null)
            {
                $query['tag'] = $tag;
            }

            $this->client->request('POST', "/images/$name/tag", null, $query);
        }

        /**
         * Removes an image from the Docker host
         *
         * Deletes an image and its associated layers. Can force removal even if
         * the image is being used by containers.
         *
         * @param string $name Image name, ID, or reference to remove
         * @param bool $force If true, forces removal even if image is being used by stopped containers
         * @param bool $noprune If true, does not delete untagged parent images
         * @return array Array of deleted image references and untagged images
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function remove(string $name, bool $force = false, bool $noprune = false): array
        {
            Logger::getLogger()->info("Removing image: $name" . ($force ? " (forced)" : ""));

            $query = [
                'force' => $force ? 1 : 0,
                'noprune' => $noprune ? 1 : 0,
            ];

            $response = $this->client->request('DELETE', "/images/$name", null, $query);
            Logger::getLogger()->verbose("Image removed: $name");
            return $response['data'];
        }

        /**
         * Searches for images on Docker Hub
         *
         * Performs a search for images on Docker Hub registry matching the search term.
         * Returns repository information including star counts and descriptions.
         *
         * @param string $term Search term to find images (e.g., "ubuntu", "nginx")
         * @param int $limit Maximum number of results to return
         * @param array $filters Filters to apply to search results (e.g., ['is-official' => ['true']])
         * @return array Array of search results with 'name', 'description', 'star_count', 'is_official', etc.
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function search(string $term, int $limit = 25, array $filters = []): array
        {
            Logger::getLogger()->debug("Searching images for: $term");

            $query = [
                'term' => $term,
                'limit' => $limit,
            ];

            if (!empty($filters))
            {
                $query['filters'] = json_encode($filters);
            }

            $response = $this->client->request('GET', '/images/search', null, $query);
            Logger::getLogger()->verbose("Found " . count($response['data']) . " image(s) for term: $term");

            return $response['data'];
        }

        /**
         * Removes unused images
         *
         * Deletes images that are not being used by any containers.
         * Can optionally remove all unused images, not just dangling ones.
         *
         * @param array $filters Filters to apply (e.g., ['until' => ['<timestamp>'], 'label' => ['key=value']])
         * @param bool $all If true, removes all unused images; if false, only removes dangling images (untagged)
         * @return array Pruning results with 'ImagesDeleted' and 'SpaceReclaimed' keys
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function prune(array $filters = [], bool $all = false): array
        {
            Logger::getLogger()->info("Pruning images" . ($all ? " (all unused)" : ""));


            $query = [];
            if (!empty($filters))
            {
                $query['filters'] = json_encode($filters);
            }

            if ($all)
            {
                $query['all'] = 1;
            }

            $response = $this->client->request('POST', '/images/prune', null, $query);
            Logger::getLogger()->info("Pruned images, reclaimed " . $response['data']['SpaceReclaimed'] . " bytes");
            return $response['data'];
        }

        /**
         * Builds a Docker image from a Dockerfile
         *
         * Builds a new image from a Dockerfile and context. Supports build arguments,
         * caching control, and provides progress updates through an optional callback.
         *
         * @param array $buildArgs Build-time variables to pass (e.g., ['VERSION' => '1.0', 'ENV' => 'production'])
         * @param string|null $tag Name and optionally a tag for the image (e.g., "myimage:latest")
         * @param bool $nocache If true, does not use cache when building the image
         * @param bool $pull If true, always attempts to pull a newer version of the base image
         * @param bool $rm If true, removes intermediate containers after a successful build
         * @param callable|null $callback Optional callback function to receive build progress updates
         * @return StreamResponse Stream object containing the build operation output
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function build(array $buildArgs = [], ?string $tag = null, bool $nocache = false, bool $pull = false, bool $rm = true, ?callable $callback = null): StreamResponse
        {
            Logger::getLogger()->info("Building image" . ($tag !== null ? " with tag: $tag" : ""));
            $query = [
                'nocache' => $nocache ? 1 : 0,
                'pull' => $pull ? 1 : 0,
                'rm' => $rm ? 1 : 0,
            ];

            if ($tag !== null)
            {
                $query['t'] = $tag;
            }

            if (!empty($buildArgs))
            {
                $query['buildargs'] = json_encode($buildArgs);
                Logger::getLogger()->debug("Build args: " . json_encode($buildArgs));

            }

            $headers = ['Content-Type' => 'application/x-tar'];

            $stream = $this->client->stream('POST', '/build', null, $query, $headers);
            
            // Always read and log the stream for progress visibility
            while (($line = $stream->readLine()) !== null)
            {
                $data = json_decode($line, true);
                if ($data !== null)
                {
                    // Log build output to debug level for visibility
                    if (isset($data['stream']))
                    {
                        $message = trim($data['stream']);
                        if (!empty($message))
                        {
                            Logger::getLogger()->debug("Build: $message");
                        }
                    }
                    elseif (isset($data['status']))
                    {
                        Logger::getLogger()->debug("Build: " . $data['status']);
                    }
                    elseif (isset($data['error']))
                    {
                        Logger::getLogger()->error("Build error: " . $data['error']);
                    }

                    if ($callback !== null)
                    {
                        $callback($data);
                    }
                }
            }

            Logger::getLogger()->info("Image build completed");
            
            return $stream;
        }

        /**
         * Imports an image from a tarball
         *
         * Loads a complete image from a tar archive that was created by the export method.
         * The tarball should contain a complete image with all layers.
         *
         * @param string $tarball Path to the tar archive file
         * @return array Array of import responses with status information
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function import(string $tarball): array
        {
            // Docker's import actually uses /images/load for tarballs from /images/get
            // The /images/create?fromSrc endpoint is for importing filesystem changes, not complete images
            $query = ['quiet' => 0];
            $headers = ['Content-Type' => 'application/x-tar'];
            $tarData = file_get_contents($tarball);
            
            $stream = $this->client->streamRaw('POST', '/images/load', $tarData, $query, $headers);
            
            // Read the stream to consume all responses
            $responses = [];
            while (($line = $stream->readLine()) !== null)
            {
                $data = json_decode($line, true);
                if ($data !== null)
                {
                    $responses[] = $data;
                }
            }
            
            return $responses;
        }

        /**
         * Exports a Docker image as a tar archive
         *
         * Saves an image to a tar archive that can be imported on another Docker host.
         * The archive contains all layers and metadata of the image.
         *
         * @param string $name Image name, ID, or reference to export
         * @return string Raw tar archive data as a binary string
         * @throws ResponseException Thrown on a response error
         */
        public function export(string $name): string
        {
            return $this->client->requestRaw('GET', "/images/$name/get");
        }

        /**
         * Exports multiple Docker images as a tar archive
         *
         * Saves one or more images to a combined tar archive.
         * The archive can contain multiple images and all their layers.
         *
         * @param array $names Array of image names to export; if empty, exports all images
         * @return string Raw tar archive data as a binary string
         * @throws ResponseException Thrown on a response error
         */
        public function exportAll(array $names = []): string
        {
            $query = [];
            if (!empty($names))
            {
                $query['names'] = $names;
            }

            return $this->client->requestRaw('GET', '/images/get', null, $query);
        }

        /**
         * Loads images from a tar archive
         *
         * Imports images from a tar archive created by the export or exportAll methods.
         * Can load multiple images from a single archive.
         *
         * @param string $tarball Path to the tar archive file containing one or more images
         * @param bool $quiet If true, suppresses progress output
         * @return StreamResponse Stream object containing the load operation response
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function load(string $tarball, bool $quiet = false): StreamResponse
        {
            $query = ['quiet' => $quiet ? 1 : 0];
            $headers = ['Content-Type' => 'application/x-tar'];
            $tarData = file_get_contents($tarball);

            return $this->client->streamRaw('POST', '/images/load', $tarData, $query, $headers);
        }

        /**
         * Create a new image from a container
         *
         * @param string $container The ID or name of the container to commit
         * @param string|null $repo Repository name for the created image
         * @param string|null $tag Tag name for the created image
         * @param string|null $comment Commit message
         * @param string|null $author Author of the commit
         * @param bool $pause Whether to pause the container before committing
         * @param string|null $changes Dockerfile instructions to apply while committing (string, newline-separated)
         * @param array|null $containerConfig ContainerConfig overrides
         * @return array Response containing the new image ID
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function commit(string $container, ?string $repo = null, ?string $tag = null, ?string $comment = null, ?string $author = null, bool $pause = true, ?string $changes = null, ?array $containerConfig = null): array
        {
            Logger::getLogger()->info("Committing container: $container" . ($repo ? " as $repo" : ""));

            $query = [
                'container' => $container,
                'pause' => $pause ? 1 : 0,
            ];

            if ($repo !== null)
            {
                $query['repo'] = $repo;
            }

            if ($tag !== null)
            {
                $query['tag'] = $tag;
            }

            if ($comment !== null)
            {
                $query['comment'] = $comment;
            }

            if ($author !== null)
            {
                $query['author'] = $author;
            }

            if ($changes !== null)
            {
                $query['changes'] = $changes;
            }

            // Body should be ContainerConfig or null/empty object
            $body = $containerConfig ?? null;
            $response = $this->client->request('POST', '/commit', $body, $query);
            Logger::getLogger()->info("Container committed successfully");

            return $response['data'];
        }

        /**
         * Delete builder cache
         *
         * @param int|null $reservedSpace Amount of disk space in bytes to keep for cache
         * @param int|null $maxUsedSpace Maximum amount of disk space allowed to keep for cache
         * @param int|null $minFreeSpace Target amount of free disk space after pruning
         * @param bool $all Remove all types of build cache
         * @param array $filters Filters to apply
         * @return array Pruning result with SpaceReclaimed
         * @throws ConnectionException Thrown on a socket connection error
         * @throws ResponseException Thrown on a response error
         */
        public function pruneBuildCache(?int $reservedSpace = null, ?int $maxUsedSpace = null, ?int $minFreeSpace = null, bool $all = false, array $filters = []): array
        {
            Logger::getLogger()->info("Pruning build cache" . ($all ? " (all)" : ""));

            $query = [];

            if ($reservedSpace !== null)
            {
                $query['reserved-space'] = $reservedSpace;
            }

            if ($maxUsedSpace !== null)
            {
                $query['max-used-space'] = $maxUsedSpace;
            }

            if ($minFreeSpace !== null)
            {
                $query['min-free-space'] = $minFreeSpace;
            }

            if ($all)
            {
                $query['all'] = 1;
            }

            if (!empty($filters))
            {
                $query['filters'] = json_encode($filters);
            }

            $response = $this->client->request('POST', '/build/prune', null, $query);
            Logger::getLogger()->info("Build cache pruned, reclaimed " . ($response['data']['SpaceReclaimed'] ?? 0) . " bytes");
            return $response['data'];
        }
    }
