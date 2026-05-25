<?php

    namespace DockerLib\Objects\Image;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents the configuration settings of a Docker image.
     *
     * This class encapsulates all runtime configuration parameters for a Docker image,
     * including hostname, user, environment variables, volumes, entrypoint, and other
     * container execution settings.
     *
     * @package DockerLib\Objects\Image
     */
    class ImageConfig implements SerializableInterface
    {
        /**
         * The hostname to use for the container
         *
         * @var string|null
         */
        private ?string $hostname;

        /**
         * The domain name to use for the container
         *
         * @var string|null
         */
        private ?string $domainname;

        /**
         * The user (or UID) to run the container as
         *
         * @var string|null
         */
        private ?string $user;

        /**
         * Whether to attach to stdin
         *
         * @var bool
         */
        private bool $attachStdin;

        /**
         * Whether to attach to stdout
         *
         * @var bool
         */
        private bool $attachStdout;

        /**
         * Whether to attach to stderr
         *
         * @var bool
         */
        private bool $attachStderr;

        /**
         * Set of ports exposed by the container
         *
         * @var array<string, mixed>
         */
        private array $exposedPorts;

        /**
         * Whether to allocate a pseudo-TTY
         *
         * @var bool
         */
        private bool $tty;

        /**
         * Whether to keep stdin open even if not attached
         *
         * @var bool
         */
        private bool $openStdin;

        /**
         * Whether to close stdin after one attached client disconnects
         *
         * @var bool
         */
        private bool $stdinOnce;

        /**
         * List of environment variables to set in the container
         *
         * @var array<string>
         */
        private array $env;

        /**
         * Command to run when starting the container
         *
         * @var array<string>
         */
        private array $cmd;

        /**
         * The name of the image to use
         *
         * @var string|null
         */
        private ?string $image;

        /**
         * Set of volumes to mount in the container
         *
         * @var array<string, mixed>
         */
        private array $volumes;

        /**
         * The working directory for commands to run in
         *
         * @var string|null
         */
        private ?string $workingDir;

        /**
         * The entrypoint for the container (can be array or string)
         *
         * @var array<string>|string|null
         */
        private mixed $entrypoint;

        /**
         * Key-value pairs of labels to apply to the container
         *
         * @var array<string, string>
         */
        private array $labels;

        /**
         * ImageConfig constructor.
         *
         * Accepts configuration data in both camelCase and PascalCase formats
         * to support various Docker API response formats.
         *
         * @param array<string, mixed> $data Configuration data from Docker API
         */
        public function __construct(array $data)
        {
            $this->hostname = $data['hostname'] ?? $data['Hostname'] ?? null;
            $this->domainname = $data['domainname'] ?? $data['Domainname'] ?? null;
            $this->user = $data['user'] ?? $data['User'] ?? null;
            $this->attachStdin = $data['attachStdin'] ?? $data['AttachStdin'] ?? false;
            $this->attachStdout = $data['attachStdout'] ?? $data['AttachStdout'] ?? false;
            $this->attachStderr = $data['attachStderr'] ?? $data['AttachStderr'] ?? false;
            $this->exposedPorts = $data['exposedPorts'] ?? $data['ExposedPorts'] ?? [];
            $this->tty = $data['tty'] ?? $data['Tty'] ?? false;
            $this->openStdin = $data['openStdin'] ?? $data['OpenStdin'] ?? false;
            $this->stdinOnce = $data['stdinOnce'] ?? $data['StdinOnce'] ?? false;
            $this->env = $data['env'] ?? $data['Env'] ?? [];
            $this->cmd = $data['cmd'] ?? $data['Cmd'] ?? [];
            $this->image = $data['image'] ?? $data['Image'] ?? null;
            $this->volumes = $data['volumes'] ?? $data['Volumes'] ?? [];
            $this->workingDir = $data['workingDir'] ?? $data['WorkingDir'] ?? null;
            $this->entrypoint = $data['entrypoint'] ?? $data['Entrypoint'] ?? null;
            $this->labels = $data['labels'] ?? $data['Labels'] ?? [];
        }

        /**
         * Get the hostname.
         *
         * @return string|null The hostname or null if not set
         */
        public function getHostname(): ?string
        {
            return $this->hostname;
        }

        /**
         * Get the domain name.
         *
         * @return string|null The domain name or null if not set
         */
        public function getDomainname(): ?string
        {
            return $this->domainname;
        }

        /**
         * Get the user.
         *
         * @return string|null The user or UID, or null if not set
         */
        public function getUser(): ?string
        {
            return $this->user;
        }

        /**
         * Check if stdin should be attached.
         *
         * @return bool True if stdin should be attached, false otherwise
         */
        public function isAttachStdin(): bool
        {
            return $this->attachStdin;
        }

        /**
         * Check if stdout should be attached.
         *
         * @return bool True if stdout should be attached, false otherwise
         */
        public function isAttachStdout(): bool
        {
            return $this->attachStdout;
        }

        /**
         * Check if stderr should be attached.
         *
         * @return bool True if stderr should be attached, false otherwise
         */
        public function isAttachStderr(): bool
        {
            return $this->attachStderr;
        }

        /**
         * Get the exposed ports.
         *
         * @return array<string, mixed> Array of exposed ports
         */
        public function getExposedPorts(): array
        {
            return $this->exposedPorts;
        }

        /**
         * Check if a pseudo-TTY should be allocated.
         *
         * @return bool True if TTY should be allocated, false otherwise
         */
        public function isTty(): bool
        {
            return $this->tty;
        }

        /**
         * Check if stdin should be kept open.
         *
         * @return bool True if stdin should remain open, false otherwise
         */
        public function isOpenStdin(): bool
        {
            return $this->openStdin;
        }

        /**
         * Check if stdin should be closed after one client disconnects.
         *
         * @return bool True if stdin should close after one client, false otherwise
         */
        public function isStdinOnce(): bool
        {
            return $this->stdinOnce;
        }

        /**
         * Get the environment variables.
         *
         * @return array<string> Array of environment variables
         */
        public function getEnv(): array
        {
            return $this->env;
        }

        /**
         * Get the command to run.
         *
         * @return array<string> Array of command arguments
         */
        public function getCmd(): array
        {
            return $this->cmd;
        }

        /**
         * Get the image name.
         *
         * @return string|null The image name or null if not set
         */
        public function getImage(): ?string
        {
            return $this->image;
        }

        /**
         * Get the volumes.
         *
         * @return array<string, mixed> Array of volume definitions
         */
        public function getVolumes(): array
        {
            return $this->volumes;
        }

        /**
         * Get the working directory.
         *
         * @return string|null The working directory path or null if not set
         */
        public function getWorkingDir(): ?string
        {
            return $this->workingDir;
        }

        /**
         * Get the entrypoint.
         *
         * @return array<string>|string|null The entrypoint as array, string, or null if not set
         */
        public function getEntrypoint(): array|string|null
        {
            return $this->entrypoint;
        }

        /**
         * Get the labels.
         *
         * @return array<string, string> Key-value pairs of labels
         */
        public function getLabels(): array
        {
            return $this->labels;
        }

        /**
         * Convert the image configuration to an array representation.
         *
         * @return array<string, mixed> Array representation with PascalCase keys matching Docker API format
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
                'ExposedPorts' => $this->exposedPorts,
                'Tty' => $this->tty,
                'OpenStdin' => $this->openStdin,
                'StdinOnce' => $this->stdinOnce,
                'Env' => $this->env,
                'Cmd' => $this->cmd,
                'Image' => $this->image,
                'Volumes' => $this->volumes,
                'WorkingDir' => $this->workingDir,
                'Entrypoint' => $this->entrypoint,
                'Labels' => $this->labels,
            ];
        }

        /**
         * Create an ImageConfig instance from an array.
         *
         * @param array<string, mixed> $data Configuration data from Docker API
         * @return self New ImageConfig instance
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }
    }
