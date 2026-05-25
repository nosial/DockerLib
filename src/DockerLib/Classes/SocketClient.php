<?php

    namespace DockerLib\Classes;

    use DockerLib\Exceptions\ConnectionException;
    use DockerLib\Exceptions\ResponseException;
    use DockerLib\Objects\StreamResponse;
    use stdClass;

    /**
     * Low-level socket client for communicating with Docker daemon
     * 
     * This class handles the direct communication with the Docker daemon through
     * a Unix socket. It manages HTTP request/response cycles, handles chunked
     * transfer encoding, and provides both request/response and streaming interfaces.
     * 
     * @package DockerLib\Classes
     */
    class SocketClient
    {
        private string $socketPath;
        /** @noinspection PhpMissingFieldTypeInspection */
        private $socket = null;
        private int $timeout = 30;

        /**
         * Constructs a new SocketClient instance
         * 
         * Initializes the socket client with the specified Unix socket path.
         * The socket connection is established on demand when requests are made.
         *
         * @param string $socketPath Path to the Docker Unix socket, defaults to '/var/run/docker.sock'
         */
        public function __construct(string $socketPath = '/var/run/docker.sock')
        {
            $this->socketPath = $socketPath;
        }

        /**
         * Sets the timeout for socket operations
         * 
         * Configures the timeout in seconds for socket connection and read/write operations.
         *
         * @param int $timeout Timeout in seconds
         * @return void
         */
        public function setTimeout(int $timeout): void
        {
            $this->timeout = $timeout;
        }

        /**
         * Connect to the docker's socket
         *
         * @throws ConnectionException Thrown if there was an error with the connection
         */
        private function connect(): void
        {
            if (!file_exists($this->socketPath))
            {
                throw new ConnectionException("Docker socket not found at $this->socketPath");
            }

            $this->socket = @fsockopen('unix://' . $this->socketPath, -1, $errno, $errstr, $this->timeout);

            if ($this->socket === false)
            {
                throw new ConnectionException("Failed to connect to Docker socket: $errstr ($errno)");
            }

            stream_set_timeout($this->socket, $this->timeout);
            stream_set_blocking($this->socket, true);
            stream_set_write_buffer($this->socket, 0);
        }

        /**
         * Disconnects from the Docker socket
         * Closes the socket connection if it is open and resets the socket property.
         *
         * @return void
         */
        private function disconnect(): void
        {
            if (is_resource($this->socket))
            {
                fclose($this->socket);
            }

            $this->socket = null;
        }

        /**
         * Sends a JSON HTTP request to the Docker daemon and returns the decoded response
         * 
         * Sends an HTTP request with optional JSON payload to the specified endpoint.
         * The response body is automatically decoded from JSON if applicable.
         *
         * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
         * @param string $endpoint API endpoint path (e.g., '/containers/json')
         * @param array|null $data Optional data to send as JSON in the request body
         * @param array $query Optional query parameters to append to the URL
         * @param array $headers Optional additional HTTP headers
         * @return array Response array containing 'statusCode', 'headers', and 'data' keys
         * @throws ConnectionException If unable to connect to the Docker socket
         * @throws ResponseException If the response status code is 400 or higher
         */
        public function request(string $method, string $endpoint, ?array $data = null, array $query = [], array $headers = []): array
        {
            $this->connect();

            try
            {
                $queryString = empty($query) ? '' : '?' . http_build_query($query);
                $path = $endpoint . $queryString;

                $body = '';
                if ($data !== null)
                {
                    // Convert empty sequential arrays to stdClass so they JSON-encode as {}
                    // Docker API expects {} for top-level POST bodies (e.g., volume create)
                    if (is_array($data) && empty($data) && array_is_list($data))
                    {
                        $data = new stdClass();
                    }
                    else
                    {
                        $data = $this->normalizeArraysForJson($data);
                    }
                    $body = json_encode($data);
                    $headers['Content-Type'] = 'application/json';
                    $headers['Content-Length'] = strlen($body);
                }

                $request = "$method $path HTTP/1.1\r\n";
                $request .= "Host: localhost\r\n";

                foreach ($headers as $key => $value)
                {
                    $request .= "$key: $value\r\n";
                }

                $request .= "Connection: close\r\n";
                $request .= "\r\n";

                if ($body !== '') {
                    $request .= $body;
                }

                if(is_resource($this->socket))
                {
                    fwrite($this->socket, $request);
                }
                else
                {
                    throw new ConnectionException("The socket resource is not available");
                }

                return $this->readResponse();
            }
            finally
            {
                $this->disconnect();
            }
        }

        /**
         * Sends a JSON HTTP request and returns a stream for reading the response
         * 
         * Similar to request() but returns a StreamResponse object that allows
         * reading the response body incrementally, useful for large responses or
         * continuous streams like logs or event monitoring.
         *
         * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
         * @param string $endpoint API endpoint path
         * @param array|null $data Optional data to send as JSON in the request body
         * @param array $query Optional query parameters to append to the URL
         * @param array $headers Optional additional HTTP headers
         * @return StreamResponse A stream object for reading the response
         * @throws ConnectionException If unable to connect to the Docker socket
         * @throws ResponseException If unable to read the response status or headers
         * @noinspection PhpParamsInspection
         * @noinspection DuplicatedCode
         */
        public function stream(string $method, string $endpoint, ?array $data = null, array $query = [], array $headers = []): StreamResponse
        {
            $this->connect();

            $queryString = empty($query) ? '' : '?' . http_build_query($query);
            $path = $endpoint . $queryString;

            $body = '';
            if ($data !== null)
            {
                $body = json_encode($data);
                $headers['Content-Type'] = 'application/json';
                $headers['Content-Length'] = strlen($body);
            }

            $request = "$method $path HTTP/1.1\r\n";
            $request .= "Host: localhost\r\n";

            foreach ($headers as $key => $value) {
                $request .= "$key: $value\r\n";
            }

            $request .= "Connection: close\r\n";
            $request .= "\r\n";

            if ($body !== '') {
                $request .= $body;
            }

            fwrite($this->socket, $request);
            $statusLine = fgets($this->socket);
            if ($statusLine === false) {
                throw new ResponseException("Failed to read response status");
            }

            if (!preg_match('/^HTTP\/\d\.\d (\d{3})/', $statusLine, $matches))
            {
                throw new ResponseException("Invalid HTTP response");
            }

            $statusCode = (int)$matches[1];

            $responseHeaders = [];
            while (($line = fgets($this->socket)) !== false)
            {
                $line = trim($line);
                if ($line === '')
                {
                    break;
                }
                if (str_contains($line, ':'))
                {
                    list($key, $value) = explode(':', $line, 2);
                    $responseHeaders[strtolower(trim($key))] = trim($value);
                }
            }

            return new StreamResponse($this->socket, $statusCode, $responseHeaders);
        }

        /**
         * Reads the HTTP response from the socket and returns the status code, headers, and decoded body
         *
         * @throws ResponseException If unable to read the response status or if the response indicates an error (status code >= 400)
         * @noinspection DuplicatedCode
         * @noinspection PhpParamsInspection
         */
        private function readResponse(): array
        {
            $statusLine = fgets($this->socket);
            if ($statusLine === false)
            {
                throw new ResponseException("Failed to read response status");
            }

            if (!preg_match('/^HTTP\/\d\.\d (\d{3})/', $statusLine, $matches))
            {
                throw new ResponseException("Invalid HTTP response");
            }

            $statusCode = (int)$matches[1];

            $headers = [];
            while (($line = fgets($this->socket)) !== false)
            {
                $line = trim($line);

                if ($line === '')
                {
                    break;
                }

                if (str_contains($line, ':'))
                {
                    list($key, $value) = explode(':', $line, 2);
                    $headers[strtolower(trim($key))] = trim($value);
                }
            }

            $body = '';
            if (isset($headers['transfer-encoding']) && $headers['transfer-encoding'] === 'chunked')
            {
                $body = $this->readChunkedBody();
            }
            elseif (isset($headers['content-length']))
            {
                $length = (int)$headers['content-length'];
                if ($length > 0)
                {
                    $body = stream_get_contents($this->socket, $length);
                }
            }
            else
            {
                $body = stream_get_contents($this->socket);
            }

            if ($statusCode >= 400)
            {
                $error = json_decode($body, true);
                $message = $error['message'] ?? "HTTP Error $statusCode";
                throw new ResponseException($message, $statusCode);
            }

            if (empty($body))
            {
                return ['statusCode' => $statusCode, 'headers' => $headers, 'data' => null];
            }

            $decoded = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE)
            {
                return ['statusCode' => $statusCode, 'headers' => $headers, 'data' => $body];
            }

            return ['statusCode' => $statusCode, 'headers' => $headers, 'data' => $decoded];
        }

        /**
         * Reads a chunked transfer encoded response body from the socket
         *
         * Handles reading the response body when the 'Transfer-Encoding: chunked' header is present.
         * Reads each chunk according to the chunk size specified in the response and concatenates
         * them until the final chunk (size 0) is reached.
         *
         * @return string The complete response body after decoding the chunks
         * @noinspection PhpParamsInspection
         */
        private function readChunkedBody(): string
        {
            $body = '';
            while (true) {
                $line = fgets($this->socket);
                if ($line === false) {
                    break;
                }

                $chunkSize = hexdec(trim($line));
                if ($chunkSize === 0) {
                    fgets($this->socket);
                    break;
                }

                $chunk = stream_get_contents($this->socket, $chunkSize);
                $body .= $chunk;
                fgets($this->socket);
            }

            return $body;
        }

        /**
         * Normalizes arrays in the data for proper JSON encoding
         *
         * Converts empty arrays to stdClass objects for specific fields that should be treated as objects (maps) in the Docker API.
         * Also handles nested structures like NetworkingConfig->EndpointsConfig to ensure they are encoded correctly.
         *
         * @param mixed $data The data to normalize, typically an array representing a Docker API request body
         * @return mixed The normalized data with empty arrays converted to stdClass where appropriate
         */
        private function normalizeArraysForJson(mixed $data): mixed
        {
            if (!is_array($data))
            {
                return $data;
            }

            // Fields that should always be objects (maps) even when empty
            $objectFields = ['Labels', 'Orchestration', 'Raft', 'Dispatcher', 'CAConfig', 'EncryptionConfig'];
            
            // Check if this is an associative array (object-like) or numeric array (list-like)

            foreach ($data as $key => $value)
            {
                // Convert specific fields to objects when empty
                if (in_array($key, $objectFields) && is_array($value) && empty($value))
                {
                    $data[$key] = new stdClass();
                }
                // Handle nested NetworkingConfig->EndpointsConfig specially
                elseif ($key === 'NetworkingConfig' && is_array($value))
                {
                    if (isset($value['EndpointsConfig']) && is_array($value['EndpointsConfig']))
                    {
                        // Each endpoint should be an object, not an array
                        $endpoints = array_map(function ($config)
                        {
                            return is_array($config) && !empty($config) ? $this->normalizeArraysForJson($config) : new stdClass();
                        }, $value['EndpointsConfig']);
                        $data[$key]['EndpointsConfig'] = $endpoints;
                    }
                }
                // Recursively process nested arrays
                elseif (is_array($value))
                {
                    $data[$key] = $this->normalizeArraysForJson($value);
                }
            }

            return $data;
        }

        /**
         * Sends a raw HTTP request and returns the raw response body
         *
         * Sends an HTTP request with optional raw data payload and returns the
         * raw response body without any JSON decoding.
         *
         * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
         * @param string $endpoint API endpoint path
         * @param string|null $data Optional raw data to send in the request body
         * @param array $query Optional query parameters to append to the URL
         * @param array $headers Optional additional HTTP headers
         * @return string The raw response body
         * @throws ResponseException If unable to read the response
         * @noinspection PhpParamsInspection
         */
        public function requestRaw(string $method, string $endpoint, ?string $data = null, array $query = [], array $headers = []): string
        {
            $this->connect();

            try
            {
                $queryString = empty($query) ? '' : '?' . http_build_query($query);
                $path = $endpoint . $queryString;

                if ($data !== null)
                {
                    $headers['Content-Length'] = strlen($data);
                }

                $request = "$method $path HTTP/1.1\r\n";
                $request .= "Host: localhost\r\n";

                foreach ($headers as $key => $value)
                {
                    $request .= "$key: $value\r\n";
                }

                $request .= "Connection: close\r\n";
                $request .= "\r\n";

                if ($data !== null)
                {
                    $request .= $data;
                }

                fwrite($this->socket, $request);

                $statusLine = fgets($this->socket);
                if ($statusLine === false)
                {
                    throw new ResponseException("Failed to read response status");
                }

                while (($line = fgets($this->socket)) !== false)
                {
                    if (trim($line) === '')
                    {
                        break;
                    }
                }

                return stream_get_contents($this->socket);
            }
            finally
            {
                $this->disconnect();
            }
        }

        /**
         * Sends a raw HTTP request and returns a stream for reading the response
         * 
         * Sends an HTTP request with optional raw data payload. For large payloads (>8KB),
         * automatically uses chunked transfer encoding. Returns a StreamResponse object
         * for reading the response incrementally.
         *
         * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
         * @param string $endpoint API endpoint path
         * @param string|null $data Optional raw data to send in the request body
         * @param array $query Optional query parameters to append to the URL
         * @param array $headers Optional additional HTTP headers
         * @return StreamResponse A stream object for reading the response
         * @throws ConnectionException If unable to connect to the Docker socket
         * @throws ResponseException If unable to read the response or write data to socket
         * @noinspection PhpParamsInspection
         * @noinspection DuplicatedCode
         */
        public function streamRaw(string $method, string $endpoint, ?string $data = null, array $query = [], array $headers = []): StreamResponse
        {
            $this->connect();

            $queryString = empty($query) ? '' : '?' . http_build_query($query);
            $path = $endpoint . $queryString;

            // Use chunked transfer encoding for large payloads
            $useChunked = ($data !== null && strlen($data) > 8192);
            
            if ($useChunked)
            {
                $headers['Transfer-Encoding'] = 'chunked';
            }
            elseif ($data !== null)
            {
                $headers['Content-Length'] = strlen($data);
            }

            $request = "$method $path HTTP/1.1\r\n";
            $request .= "Host: localhost\r\n";

            foreach ($headers as $key => $value)
            {
                $request .= "$key: $value\r\n";
            }

            $request .= "Connection: close\r\n";
            $request .= "\r\n";

            // Write headers
            fwrite($this->socket, $request);
            
            // Write data
            if ($data !== null)
            {
                if ($useChunked)
                {
                    // Send data in chunks with chunk encoding
                    $chunkSize = 8192;
                    $offset = 0;
                    $dataLen = strlen($data);
                    
                    while ($offset < $dataLen)
                    {
                        $chunk = substr($data, $offset, $chunkSize);
                        $chunkLen = strlen($chunk);
                        
                        // Write chunk size in hex
                        $written = fwrite($this->socket, dechex($chunkLen) . "\r\n");
                        if ($written === false)
                        {
                            throw new ResponseException("Failed to write chunk size to socket");
                        }
                        
                        // Write chunk data
                        $written = fwrite($this->socket, $chunk . "\r\n");
                        if ($written === false)
                        {
                            throw new ResponseException("Failed to write chunk data to socket");
                        }
                        
                        $offset += $chunkLen;
                    }
                    
                    // Write final chunk (size 0)
                    fwrite($this->socket, "0\r\n\r\n");
                }
                else
                {
                    // Write data directly
                    $written = fwrite($this->socket, $data);
                    if ($written === false)
                    {
                        throw new ResponseException("Failed to write data to socket");
                    }
                }
            }

            $statusLine = fgets($this->socket);
            if ($statusLine === false)
            {
                throw new ResponseException("Failed to read response status");
            }

            if (!preg_match('/^HTTP\/\d\.\d (\d{3})/', $statusLine, $matches))
            {
                throw new ResponseException("Invalid HTTP response");
            }

            $statusCode = (int)$matches[1];
            $responseHeaders = [];

            while (($line = fgets($this->socket)) !== false)
            {
                $line = trim($line);
                if ($line === '')
                {
                    break;
                }

                if (str_contains($line, ':'))
                {
                    list($key, $value) = explode(':', $line, 2);
                    $responseHeaders[strtolower(trim($key))] = trim($value);
                }
            }

            return new StreamResponse($this->socket, $statusCode, $responseHeaders);
        }
    }
