<?php

    namespace DockerLib\Objects;

    use DockerLib\Interfaces\SerializableInterface;
    use RuntimeException;

    /**
     * Represents a streaming HTTP response from the Docker API
     *
     * This class handles streaming responses from Docker API endpoints,
     * allowing for efficient reading of large or continuous data streams
     * such as container logs, build output, or event streams.
     */
    class StreamResponse implements SerializableInterface
    {
        /**
         * The underlying socket resource for the stream
         *
         * @var resource
         */
        private $socket;

        /**
         * HTTP status code of the response
         *
         * @var int
         */
        private int $statusCode;

        /**
         * HTTP response headers
         *
         * @var array<string, string>
         */
        private array $headers;

        /**
         * Whether the stream has been closed
         *
         * @var bool
         */
        private bool $closed = false;

        /**
         * Creates a new StreamResponse instance
         *
         * @param resource $socket The socket resource for reading the stream
         * @param int $statusCode HTTP status code
         * @param array<string, string> $headers HTTP response headers
         */
        public function __construct($socket, int $statusCode, array $headers)
        {
            $this->socket = $socket;
            $this->statusCode = $statusCode;
            $this->headers = $headers;
        }

        /**
         * Gets the HTTP status code
         *
         * @return int
         */
        public function getStatusCode(): int
        {
            return $this->statusCode;
        }

        /**
         * Gets the HTTP response headers
         *
         * @return array<string, string>
         */
        public function getHeaders(): array
        {
            return $this->headers;
        }

        /**
         * Reads a specified number of bytes from the stream
         *
         * @param int $length Maximum number of bytes to read (default: 8192)
         * @return string|null The read data, or null if stream is closed or at EOF
         */
        public function read(int $length = 8192): ?string
        {
            if ($this->closed || feof($this->socket))
            {
                return null;
            }

            $data = fread($this->socket, $length);
            return $data === false ? null : $data;
        }

        /**
         * Reads a single line from the stream
         *
         * @return string|null The line read, or null if stream is closed or at EOF
         */
        public function readLine(): ?string
        {
            if ($this->closed || feof($this->socket))
            {
                return null;
            }

            $line = fgets($this->socket);
            return $line === false ? null : $line;
        }

        /**
         * Reads all remaining content from the stream and closes it
         *
         * @return string All remaining content from the stream
         */
        public function readAll(): string
        {
            if ($this->closed)
            {
                return '';
            }

            $content = stream_get_contents($this->socket);
            $this->close();
            return $content === false ? '' : $content;
        }

        /**
         * Reads the next chunk from a chunked transfer encoding stream
         *
         * @return string|null The chunk data, or null if no more chunks or stream is closed
         */
        public function readChunked(): ?string
        {
            if ($this->closed || feof($this->socket))
            {
                return null;
            }

            $line = fgets($this->socket);
            if ($line === false)
            {
                return null;
            }

            $chunkSize = hexdec(trim($line));
            if ($chunkSize === 0) {
                fgets($this->socket);
                $this->close();
                return null;
            }

            $chunk = stream_get_contents($this->socket, $chunkSize);
            fgets($this->socket);
            return $chunk === false ? null : $chunk;
        }

        /**
         * Closes the stream and releases the socket resource
         *
         * @return void
         */
        public function close(): void
        {
            if (!$this->closed && $this->socket !== null)
            {
                fclose($this->socket);
                $this->closed = true;
            }
        }

        /**
         * Converts the stream response metadata to an array
         *
         * @return array<string, mixed>
         */
        public function toArray(): array
        {
            return [
                'statusCode' => $this->statusCode,
                'headers' => $this->headers,
            ];
        }

        /**
         * Creates a StreamResponse instance from an array
         *
         * Note: This creates a dummy memory stream and is primarily used for
         * deserialization. StreamResponse instances are typically created directly
         * from API responses.
         *
         * @param array<string, mixed> $data Array containing statusCode and headers
         * @return self
         */
        public static function fromArray(array $data): self
        {
            // Create a dummy resource for fromArray. StreamResponse is primarily instantiated
            // directly from API responses, not from deserialization.
            $socket = fopen('php://memory', 'r');
            if ($socket === false)
            {
                throw new RuntimeException('Failed to create memory stream');
            }

            return new self(
                $socket,
                $data['statusCode'] ?? 200,
                $data['headers'] ?? []
            );
        }

        /**
         * Alias for readAll() - reads all content and closes the stream
         *
         * @return string
         */
        public function getResponse(): string
        {
            return $this->readAll();
        }

        /**
         * Destructor ensures the stream is properly closed
         *
         * @return void
         */
        public function __destruct()
        {
            $this->close();
        }
    }
