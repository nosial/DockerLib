<?php

    namespace DockerLib\Objects\Container;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents Docker container configuration settings.
     * 
     * This class encapsulates the container's runtime configuration including
     * network settings, resource allocations, environment variables, command
     * execution parameters, and metadata labels.
     */
    class ContainerConfig implements SerializableInterface
    {
        /**
         * @var string|null The hostname of the container
         */
        private ?string $hostname;
        
        /**
         * @var string|null The domain name of the container
         */
        private ?string $domainname;
        
        /**
         * @var string|null The user that commands are run as inside the container
         */
        private ?string $user;
        
        /**
         * @var bool Whether to attach to stdin
         */
        private bool $attachStdin;
        
        /**
         * @var bool Whether to attach to stdout
         */
        private bool $attachStdout;
        
        /**
         * @var bool Whether to attach to stderr
         */
        private bool $attachStderr;
        
        /**
         * @var bool Whether to allocate a pseudo-TTY
         */
        private bool $tty;
        
        /**
         * @var bool Whether to open stdin
         */
        private bool $openStdin;
        
        /**
         * @var bool Whether to close stdin after one attach
         */
        private bool $stdinOnce;
        
        /**
         * @var array<string> Environment variables in the form ["VAR=value"]
         */
        private array $env;
        
        /**
         * @var array<string> Command to run specified as a string or an array of strings
         */
        private array $cmd;
        
        /**
         * @var string|null The name of the image to use when creating the container
         */
        private ?string $image;
        
        /**
         * @var string|null The working directory for commands to run in
         */
        private ?string $workingDir;
        
        /**
         * @var array<string>|null The entry point for the container as a string or an array of strings
         */
        private ?array $entrypoint;
        
        /**
         * @var array<string, mixed> Key/value metadata labels
         */
        private array $labels;

        /**
         * Constructs a new ContainerConfig instance from an array of data.
         * 
         * Accepts both camelCase and PascalCase keys for compatibility with
         * Docker API responses and internal data structures.
         *
         * @param array<string, mixed> $data Container configuration data
         */
        public function __construct(array $data)
        {
            $this->hostname = $data['hostname'] ?? $data['Hostname'] ?? null;
            $this->domainname = $data['domainname'] ?? $data['Domainname'] ?? null;
            $this->user = $data['user'] ?? $data['User'] ?? null;
            $this->attachStdin = $data['attachStdin'] ?? $data['AttachStdin'] ?? false;
            $this->attachStdout = $data['attachStdout'] ?? $data['AttachStdout'] ?? false;
            $this->attachStderr = $data['attachStderr'] ?? $data['AttachStderr'] ?? false;
            $this->tty = $data['tty'] ?? $data['Tty'] ?? false;
            $this->openStdin = $data['openStdin'] ?? $data['OpenStdin'] ?? false;
            $this->stdinOnce = $data['stdinOnce'] ?? $data['StdinOnce'] ?? false;
            $this->env = $data['env'] ?? $data['Env'] ?? [];
            $this->cmd = $data['cmd'] ?? $data['Cmd'] ?? [];
            $this->image = $data['image'] ?? $data['Image'] ?? null;
            $this->workingDir = $data['workingDir'] ?? $data['WorkingDir'] ?? null;
            $this->entrypoint = $data['entrypoint'] ?? $data['Entrypoint'] ?? null;
            $this->labels = $data['labels'] ?? $data['Labels'] ?? [];
        }

        /**
         * Gets the hostname of the container.
         *
         * @return string|null The hostname or null if not set
         */
        public function getHostname(): ?string
        {
            return $this->hostname;
        }

        /**
         * Gets the domain name of the container.
         *
         * @return string|null The domain name or null if not set
         */
        public function getDomainname(): ?string
        {
            return $this->domainname;
        }

        /**
         * Gets the user that commands are run as inside the container.
         *
         * @return string|null The username or UID or null if not set
         */
        public function getUser(): ?string
        {
            return $this->user;
        }

        /**
         * Checks if stdin should be attached.
         *
         * @return bool True if stdin is attached, false otherwise
         */
        public function isAttachStdin(): bool
        {
            return $this->attachStdin;
        }

        /**
         * Checks if stdout should be attached.
         *
         * @return bool True if stdout is attached, false otherwise
         */
        public function isAttachStdout(): bool
        {
            return $this->attachStdout;
        }

        /**
         * Checks if stderr should be attached.
         *
         * @return bool True if stderr is attached, false otherwise
         */
        public function isAttachStderr(): bool
        {
            return $this->attachStderr;
        }

        /**
         * Checks if a pseudo-TTY should be allocated.
         *
         * @return bool True if TTY is enabled, false otherwise
         */
        public function isTty(): bool
        {
            return $this->tty;
        }

        /**
         * Checks if stdin should be opened.
         *
         * @return bool True if stdin is open, false otherwise
         */
        public function isOpenStdin(): bool
        {
            return $this->openStdin;
        }

        /**
         * Checks if stdin should be closed after one attach.
         *
         * @return bool True if stdin closes after one attach, false otherwise
         */
        public function isStdinOnce(): bool
        {
            return $this->stdinOnce;
        }

        /**
         * Gets the environment variables.
         *
         * @return array<string> The environment variables array
         */
        public function getEnv(): array
        {
            return $this->env;
        }

        /**
         * Gets the command to run.
         *
         * @return array<string> The command array
         */
        public function getCmd(): array
        {
            return $this->cmd;
        }

        /**
         * Gets the name of the image to use when creating the container.
         *
         * @return string|null The image name or null if not set
         */
        public function getImage(): ?string
        {
            return $this->image;
        }

        /**
         * Gets the working directory for commands to run in.
         *
         * @return string|null The working directory path or null if not set
         */
        public function getWorkingDir(): ?string
        {
            return $this->workingDir;
        }

        /**
         * Gets the entry point for the container.
         *
         * @return array<string>|null The entry point array or null if not set
         */
        public function getEntrypoint(): ?array
        {
            return $this->entrypoint;
        }

        /**
         * Gets the metadata labels.
         *
         * @return array<string, mixed> The labels array
         */
        public function getLabels(): array
        {
            return $this->labels;
        }

        /**
         * Converts the ContainerConfig to an array representation.
         * 
         * Returns data in PascalCase format compatible with Docker API.
         *
         * @return array<string, mixed> The container configuration as an associative array
         */
        public function toArray(): array
        {
            return [
                'Hostname' => $this->hostname,
                'Domainname' => $this->domainname,
                'User' => $this->user,
                'AttachStdin' => $this->attachStdin,
                'AttachStdout' => $this->attachStdout,
                'AttachStderr' => $this->attachStderr,
                'Tty' => $this->tty,
                'OpenStdin' => $this->openStdin,
                'StdinOnce' => $this->stdinOnce,
                'Env' => $this->env,
                'Cmd' => $this->cmd,
                'Image' => $this->image,
                'WorkingDir' => $this->workingDir,
                'Entrypoint' => $this->entrypoint,
                'Labels' => $this->labels,
            ];
        }

        /**
         * Creates a ContainerConfig instance from an array of data.
         *
         * @param array<string, mixed> $data Container configuration data
         * @return self A new ContainerConfig instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }
    }
