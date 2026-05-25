<?php

    namespace DockerLib\Objects;

    use DockerLib\Interfaces\SerializableInterface;

    /**
     * Represents Docker system information and configuration
     *
     * This class encapsulates detailed information about the Docker daemon,
     * including system resources, capabilities, configuration settings,
     * and runtime information.
     */
    class SystemInfo implements SerializableInterface
    {
        /**
         * Unique identifier of the Docker daemon
         *
         * @var string|null
         */
        private ?string $id;

        /**
         * Total number of containers on the system
         *
         * @var int|null
         */
        private ?int $containers;

        /**
         * Number of currently running containers
         *
         * @var int|null
         */
        private ?int $containersRunning;

        /**
         * Number of paused containers
         *
         * @var int|null
         */
        private ?int $containersPaused;

        /**
         * Number of stopped containers
         *
         * @var int|null
         */
        private ?int $containersStopped;

        /**
         * Total number of images on the system
         *
         * @var int|null
         */
        private ?int $images;

        /**
         * Storage driver being used (e.g., overlay2, aufs)
         *
         * @var string|null
         */
        private ?string $driver;

        /**
         * Status information for the storage driver
         *
         * @var array<array<string>>
         */
        private array $driverStatus;

        /**
         * Root directory of the Docker runtime
         *
         * @var string|null
         */
        private ?string $dockerRootDir;

        /**
         * System status information (swarm-specific)
         *
         * @var array<string, mixed>|null
         */
        private ?array $systemStatus;

        /**
         * Installed plugins (Volume, Network, Authorization, Log)
         *
         * @var array<string, mixed>
         */
        private array $plugins;

        /**
         * Whether memory limit support is enabled
         *
         * @var bool
         */
        private bool $memoryLimit;

        /**
         * Whether swap limit support is enabled
         *
         * @var bool
         */
        private bool $swapLimit;

        /**
         * Whether kernel memory limit support is enabled
         *
         * @var bool
         */
        private bool $kernelMemory;

        /**
         * Whether CPU CFS period support is enabled
         *
         * @var bool
         */
        private bool $cpuCfsPeriod;

        /**
         * Whether CPU CFS quota support is enabled
         *
         * @var bool
         */
        private bool $cpuCfsQuota;

        /**
         * Whether CPU shares support is enabled
         *
         * @var bool
         */
        private bool $cpuShares;

        /**
         * Whether CPU set support is enabled
         *
         * @var bool
         */
        private bool $cpuSet;

        /**
         * Whether OOM killer disable support is enabled
         *
         * @var bool
         */
        private bool $oomKillDisable;

        /**
         * Whether IPv4 forwarding is enabled
         *
         * @var bool
         */
        private bool $ipv4Forwarding;

        /**
         * Whether bridge netfilter iptables is enabled
         *
         * @var bool
         */
        private bool $bridgeNfIptables;

        /**
         * Whether bridge netfilter ip6tables is enabled
         *
         * @var bool
         */
        private bool $bridgeNfIp6tables;

        /**
         * Whether debug mode is enabled
         *
         * @var bool
         */
        private bool $debug;

        /**
         * Number of file descriptors
         *
         * @var int|null
         */
        private ?int $nFd;

        /**
         * Number of goroutines
         *
         * @var int|null
         */
        private ?int $nGoroutines;

        /**
         * Current system time in RFC3339 format
         *
         * @var string|null
         */
        private ?string $systemTime;

        /**
         * Default logging driver
         *
         * @var string|null
         */
        private ?string $loggingDriver;

        /**
         * Cgroup driver being used (cgroupfs or systemd)
         *
         * @var string|null
         */
        private ?string $cgroupDriver;

        /**
         * Cgroup version (1 or 2)
         *
         * @var string|null
         */
        private ?string $cgroupVersion;

        /**
         * Number of event listeners
         *
         * @var int|null
         */
        private ?int $nEventsListener;

        /**
         * Kernel version
         *
         * @var string|null
         */
        private ?string $kernelVersion;

        /**
         * Operating system name
         *
         * @var string|null
         */
        private ?string $operatingSystem;

        /**
         * Operating system version
         *
         * @var string|null
         */
        private ?string $osVersion;

        /**
         * Operating system type (linux, windows)
         *
         * @var string|null
         */
        private ?string $osType;

        /**
         * Hardware architecture (x86_64, aarch64, etc.)
         *
         * @var string|null
         */
        private ?string $architecture;

        /**
         * Number of CPUs
         *
         * @var int|null
         */
        private ?int $ncpu;

        /**
         * Total memory in bytes
         *
         * @var int|null
         */
        private ?int $memTotal;

        /**
         * Address of the index server
         *
         * @var string|null
         */
        private ?string $indexServerAddress;

        /**
         * Registry configuration settings
         *
         * @var array<string, mixed>|null
         */
        private ?array $registryConfig;

        /**
         * HTTP proxy configuration
         *
         * @var string|null
         */
        private ?string $httpProxy;

        /**
         * HTTPS proxy configuration
         *
         * @var string|null
         */
        private ?string $httpsProxy;

        /**
         * No proxy configuration
         *
         * @var string|null
         */
        private ?string $noProxy;

        /**
         * Name of the Docker host
         *
         * @var string|null
         */
        private ?string $name;

        /**
         * User-defined labels for the daemon
         *
         * @var array<string>
         */
        private array $labels;

        /**
         * Whether experimental features are enabled
         *
         * @var bool
         */
        private bool $experimentalBuild;

        /**
         * Docker server version
         *
         * @var string|null
         */
        private ?string $serverVersion;

        /**
         * Cluster store URL
         *
         * @var string|null
         */
        private ?string $clusterStore;

        /**
         * Cluster advertise address
         *
         * @var string|null
         */
        private ?string $clusterAdvertise;

        /**
         * Available OCI runtimes
         *
         * @var array<string, mixed>
         */
        private array $runtimes;

        /**
         * Default OCI runtime
         *
         * @var string|null
         */
        private ?string $defaultRuntime;

        /**
         * Swarm cluster information
         *
         * @var array<string, mixed>|null
         */
        private ?array $swarm;

        /**
         * Whether live restore is enabled
         *
         * @var bool
         */
        private bool $liveRestoreEnabled;

        /**
         * Default isolation technology (process, hyperv)
         *
         * @var string|null
         */
        private ?string $isolation;

        /**
         * Path to the init binary
         *
         * @var string|null
         */
        private ?string $initBinary;

        /**
         * Containerd commit information
         *
         * @var array<string, string>|null
         */
        private ?array $containerdCommit;

        /**
         * Runc commit information
         *
         * @var array<string, string>|null
         */
        private ?array $runcCommit;

        /**
         * Init commit information
         *
         * @var array<string, string>|null
         */
        private ?array $initCommit;

        /**
         * Enabled security options
         *
         * @var array<string>
         */
        private array $securityOptions;

        /**
         * Product license type
         *
         * @var string|null
         */
        private ?string $productLicense;

        /**
         * Warning messages from the daemon
         *
         * @var array<string>
         */
        private array $warnings;

        /**
         * Creates a new SystemInfo instance
         *
         * @param array<string, mixed> $data Raw system information data from Docker API
         */
        public function __construct(array $data = [])
        {
            $this->id = $data['ID'] ?? null;
            $this->containers = $data['Containers'] ?? null;
            $this->containersRunning = $data['ContainersRunning'] ?? null;
            $this->containersPaused = $data['ContainersPaused'] ?? null;
            $this->containersStopped = $data['ContainersStopped'] ?? null;
            $this->images = $data['Images'] ?? null;
            $this->driver = $data['Driver'] ?? null;
            $this->driverStatus = $data['DriverStatus'] ?? [];
            $this->dockerRootDir = $data['DockerRootDir'] ?? null;
            $this->systemStatus = $data['SystemStatus'] ?? null;
            $this->plugins = $data['Plugins'] ?? [];
            $this->memoryLimit = $data['MemoryLimit'] ?? false;
            $this->swapLimit = $data['SwapLimit'] ?? false;
            $this->kernelMemory = $data['KernelMemory'] ?? false;
            $this->cpuCfsPeriod = $data['CpuCfsPeriod'] ?? false;
            $this->cpuCfsQuota = $data['CpuCfsQuota'] ?? false;
            $this->cpuShares = $data['CPUShares'] ?? false;
            $this->cpuSet = $data['CPUSet'] ?? false;
            $this->oomKillDisable = $data['OomKillDisable'] ?? false;
            $this->ipv4Forwarding = $data['IPv4Forwarding'] ?? false;
            $this->bridgeNfIptables = $data['BridgeNfIptables'] ?? false;
            $this->bridgeNfIp6tables = $data['BridgeNfIp6tables'] ?? false;
            $this->debug = $data['Debug'] ?? false;
            $this->nFd = $data['NFd'] ?? null;
            $this->nGoroutines = $data['NGoroutines'] ?? null;
            $this->systemTime = $data['SystemTime'] ?? null;
            $this->loggingDriver = $data['LoggingDriver'] ?? null;
            $this->cgroupDriver = $data['CgroupDriver'] ?? null;
            $this->cgroupVersion = $data['CgroupVersion'] ?? null;
            $this->nEventsListener = $data['NEventsListener'] ?? null;
            $this->kernelVersion = $data['KernelVersion'] ?? null;
            $this->operatingSystem = $data['OperatingSystem'] ?? null;
            $this->osVersion = $data['OSVersion'] ?? null;
            $this->osType = $data['OSType'] ?? null;
            $this->architecture = $data['Architecture'] ?? null;
            $this->ncpu = $data['NCPU'] ?? null;
            $this->memTotal = $data['MemTotal'] ?? null;
            $this->indexServerAddress = $data['IndexServerAddress'] ?? null;
            $this->registryConfig = $data['RegistryConfig'] ?? null;
            $this->httpProxy = $data['HttpProxy'] ?? null;
            $this->httpsProxy = $data['HttpsProxy'] ?? null;
            $this->noProxy = $data['NoProxy'] ?? null;
            $this->name = $data['Name'] ?? null;
            $this->labels = $data['Labels'] ?? [];
            $this->experimentalBuild = $data['ExperimentalBuild'] ?? false;
            $this->serverVersion = $data['ServerVersion'] ?? null;
            $this->clusterStore = $data['ClusterStore'] ?? null;
            $this->clusterAdvertise = $data['ClusterAdvertise'] ?? null;
            $this->runtimes = $data['Runtimes'] ?? [];
            $this->defaultRuntime = $data['DefaultRuntime'] ?? null;
            $this->swarm = $data['Swarm'] ?? null;
            $this->liveRestoreEnabled = $data['LiveRestoreEnabled'] ?? false;
            $this->isolation = $data['Isolation'] ?? null;
            $this->initBinary = $data['InitBinary'] ?? null;
            $this->containerdCommit = $data['ContainerdCommit'] ?? null;
            $this->runcCommit = $data['RuncCommit'] ?? null;
            $this->initCommit = $data['InitCommit'] ?? null;
            $this->securityOptions = $data['SecurityOptions'] ?? [];
            $this->productLicense = $data['ProductLicense'] ?? null;
            $this->warnings = $data['Warnings'] ?? [];
        }

        /**
         * Gets the Docker daemon ID
         *
         * @return string|null
         */
        public function getId(): ?string
        {
            return $this->id;
        }

        /**
         * Gets the total number of containers
         *
         * @return int|null
         */
        public function getContainers(): ?int
        {
            return $this->containers;
        }

        /**
         * Gets the number of running containers
         *
         * @return int|null
         */
        public function getContainersRunning(): ?int
        {
            return $this->containersRunning;
        }

        /**
         * Gets the number of paused containers
         *
         * @return int|null
         */
        public function getContainersPaused(): ?int
        {
            return $this->containersPaused;
        }

        /**
         * Gets the number of stopped containers
         *
         * @return int|null
         */
        public function getContainersStopped(): ?int
        {
            return $this->containersStopped;
        }

        /**
         * Gets the total number of images
         *
         * @return int|null
         */
        public function getImages(): ?int
        {
            return $this->images;
        }

        /**
         * Gets the storage driver name
         *
         * @return string|null
         */
        public function getDriver(): ?string
        {
            return $this->driver;
        }

        /**
         * Gets the storage driver status information
         *
         * @return array<array<string>>
         */
        public function getDriverStatus(): array
        {
            return $this->driverStatus;
        }

        /**
         * Gets the Docker root directory path
         *
         * @return string|null
         */
        public function getDockerRootDir(): ?string
        {
            return $this->dockerRootDir;
        }

        /**
         * Gets the system status information
         *
         * @return array<string, mixed>|null
         */
        public function getSystemStatus(): ?array
        {
            return $this->systemStatus;
        }

        /**
         * Gets the installed plugins
         *
         * @return array<string, mixed>
         */
        public function getPlugins(): array
        {
            return $this->plugins;
        }

        /**
         * Checks if memory limit support is enabled
         *
         * @return bool
         */
        public function getMemoryLimit(): bool
        {
            return $this->memoryLimit;
        }

        /**
         * Checks if swap limit support is enabled
         *
         * @return bool
         */
        public function getSwapLimit(): bool
        {
            return $this->swapLimit;
        }

        /**
         * Checks if kernel memory limit support is enabled
         *
         * @return bool
         */
        public function getKernelMemory(): bool
        {
            return $this->kernelMemory;
        }

        /**
         * Checks if CPU CFS period support is enabled
         *
         * @return bool
         */
        public function getCpuCfsPeriod(): bool
        {
            return $this->cpuCfsPeriod;
        }

        /**
         * Checks if CPU CFS quota support is enabled
         *
         * @return bool
         */
        public function getCpuCfsQuota(): bool
        {
            return $this->cpuCfsQuota;
        }

        /**
         * Checks if CPU shares support is enabled
         *
         * @return bool
         */
        public function getCPUShares(): bool
        {
            return $this->cpuShares;
        }

        /**
         * Checks if CPU set support is enabled
         *
         * @return bool
         */
        public function getCPUSet(): bool
        {
            return $this->cpuSet;
        }

        /**
         * Checks if OOM killer disable support is enabled
         *
         * @return bool
         */
        public function getOomKillDisable(): bool
        {
            return $this->oomKillDisable;
        }

        /**
         * Checks if IPv4 forwarding is enabled
         *
         * @return bool
         */
        public function getIPv4Forwarding(): bool
        {
            return $this->ipv4Forwarding;
        }

        /**
         * Checks if bridge netfilter iptables is enabled
         *
         * @return bool
         */
        public function getBridgeNfIptables(): bool
        {
            return $this->bridgeNfIptables;
        }

        /**
         * Checks if bridge netfilter ip6tables is enabled
         *
         * @return bool
         */
        public function getBridgeNfIp6tables(): bool
        {
            return $this->bridgeNfIp6tables;
        }

        /**
         * Checks if debug mode is enabled
         *
         * @return bool
         */
        public function getDebug(): bool
        {
            return $this->debug;
        }

        /**
         * Gets the number of file descriptors
         *
         * @return int|null
         */
        public function getNFd(): ?int
        {
            return $this->nFd;
        }

        /**
         * Gets the number of goroutines
         *
         * @return int|null
         */
        public function getNGoroutines(): ?int
        {
            return $this->nGoroutines;
        }

        /**
         * Gets the current system time
         *
         * @return string|null
         */
        public function getSystemTime(): ?string
        {
            return $this->systemTime;
        }

        /**
         * Gets the default logging driver
         *
         * @return string|null
         */
        public function getLoggingDriver(): ?string
        {
            return $this->loggingDriver;
        }

        /**
         * Gets the cgroup driver name
         *
         * @return string|null
         */
        public function getCgroupDriver(): ?string
        {
            return $this->cgroupDriver;
        }

        /**
         * Gets the cgroup version
         *
         * @return string|null
         */
        public function getCgroupVersion(): ?string
        {
            return $this->cgroupVersion;
        }

        /**
         * Gets the number of event listeners
         *
         * @return int|null
         */
        public function getNEventsListener(): ?int
        {
            return $this->nEventsListener;
        }

        /**
         * Gets the kernel version
         *
         * @return string|null
         */
        public function getKernelVersion(): ?string
        {
            return $this->kernelVersion;
        }

        /**
         * Gets the operating system name
         *
         * @return string|null
         */
        public function getOperatingSystem(): ?string
        {
            return $this->operatingSystem;
        }

        /**
         * Gets the operating system version
         *
         * @return string|null
         */
        public function getOSVersion(): ?string
        {
            return $this->osVersion;
        }

        /**
         * Gets the operating system type
         *
         * @return string|null
         */
        public function getOSType(): ?string
        {
            return $this->osType;
        }

        /**
         * Gets the hardware architecture
         *
         * @return string|null
         */
        public function getArchitecture(): ?string
        {
            return $this->architecture;
        }

        /**
         * Gets the number of CPUs
         *
         * @return int|null
         */
        public function getNCPU(): ?int
        {
            return $this->ncpu;
        }

        /**
         * Gets the total memory in bytes
         *
         * @return int|null
         */
        public function getMemTotal(): ?int
        {
            return $this->memTotal;
        }

        /**
         * Gets the index server address
         *
         * @return string|null
         */
        public function getIndexServerAddress(): ?string
        {
            return $this->indexServerAddress;
        }

        /**
         * Gets the registry configuration
         *
         * @return array<string, mixed>|null
         */
        public function getRegistryConfig(): ?array
        {
            return $this->registryConfig;
        }

        /**
         * Gets the HTTP proxy configuration
         *
         * @return string|null
         */
        public function getHttpProxy(): ?string
        {
            return $this->httpProxy;
        }

        /**
         * Gets the HTTPS proxy configuration
         *
         * @return string|null
         */
        public function getHttpsProxy(): ?string
        {
            return $this->httpsProxy;
        }

        /**
         * Gets the no proxy configuration
         *
         * @return string|null
         */
        public function getNoProxy(): ?string
        {
            return $this->noProxy;
        }

        /**
         * Gets the Docker host name
         *
         * @return string|null
         */
        public function getName(): ?string
        {
            return $this->name;
        }

        /**
         * Gets the daemon labels
         *
         * @return array<string>
         */
        public function getLabels(): array
        {
            return $this->labels;
        }

        /**
         * Checks if experimental features are enabled
         *
         * @return bool
         */
        public function getExperimentalBuild(): bool
        {
            return $this->experimentalBuild;
        }

        /**
         * Gets the Docker server version
         *
         * @return string|null
         */
        public function getServerVersion(): ?string
        {
            return $this->serverVersion;
        }

        /**
         * Gets the cluster store URL
         *
         * @return string|null
         */
        public function getClusterStore(): ?string
        {
            return $this->clusterStore;
        }

        /**
         * Gets the cluster advertise address
         *
         * @return string|null
         */
        public function getClusterAdvertise(): ?string
        {
            return $this->clusterAdvertise;
        }

        /**
         * Gets the available OCI runtimes
         *
         * @return array<string, mixed>
         */
        public function getRuntimes(): array
        {
            return $this->runtimes;
        }

        /**
         * Gets the default OCI runtime
         *
         * @return string|null
         */
        public function getDefaultRuntime(): ?string
        {
            return $this->defaultRuntime;
        }

        /**
         * Gets the swarm cluster information
         *
         * @return array<string, mixed>|null
         */
        public function getSwarm(): ?array
        {
            return $this->swarm;
        }

        /**
         * Checks if live restore is enabled
         *
         * @return bool
         */
        public function getLiveRestoreEnabled(): bool
        {
            return $this->liveRestoreEnabled;
        }

        /**
         * Gets the default isolation technology
         *
         * @return string|null
         */
        public function getIsolation(): ?string
        {
            return $this->isolation;
        }

        /**
         * Gets the init binary path
         *
         * @return string|null
         */
        public function getInitBinary(): ?string
        {
            return $this->initBinary;
        }

        /**
         * Gets the containerd commit information
         *
         * @return array<string, string>|null
         */
        public function getContainerdCommit(): ?array
        {
            return $this->containerdCommit;
        }

        /**
         * Gets the runc commit information
         *
         * @return array<string, string>|null
         */
        public function getRuncCommit(): ?array
        {
            return $this->runcCommit;
        }

        /**
         * Gets the init commit information
         *
         * @return array<string, string>|null
         */
        public function getInitCommit(): ?array
        {
            return $this->initCommit;
        }

        /**
         * Gets the enabled security options
         *
         * @return array<string>
         */
        public function getSecurityOptions(): array
        {
            return $this->securityOptions;
        }

        /**
         * Gets the product license type
         *
         * @return string|null
         */
        public function getProductLicense(): ?string
        {
            return $this->productLicense;
        }

        /**
         * Gets the daemon warning messages
         *
         * @return array<string>
         */
        public function getWarnings(): array
        {
            return $this->warnings;
        }

        /**
         * Convert the object to an array
         *
         * @return array<string, mixed>
         */
        public function toArray(): array
        {
            return [
                'ID' => $this->id,
                'Containers' => $this->containers,
                'ContainersRunning' => $this->containersRunning,
                'ContainersPaused' => $this->containersPaused,
                'ContainersStopped' => $this->containersStopped,
                'Images' => $this->images,
                'Driver' => $this->driver,
                'DriverStatus' => $this->driverStatus,
                'DockerRootDir' => $this->dockerRootDir,
                'SystemStatus' => $this->systemStatus,
                'Plugins' => $this->plugins,
                'MemoryLimit' => $this->memoryLimit,
                'SwapLimit' => $this->swapLimit,
                'KernelMemory' => $this->kernelMemory,
                'CpuCfsPeriod' => $this->cpuCfsPeriod,
                'CpuCfsQuota' => $this->cpuCfsQuota,
                'CPUShares' => $this->cpuShares,
                'CPUSet' => $this->cpuSet,
                'OomKillDisable' => $this->oomKillDisable,
                'IPv4Forwarding' => $this->ipv4Forwarding,
                'BridgeNfIptables' => $this->bridgeNfIptables,
                'BridgeNfIp6tables' => $this->bridgeNfIp6tables,
                'Debug' => $this->debug,
                'NFd' => $this->nFd,
                'NGoroutines' => $this->nGoroutines,
                'SystemTime' => $this->systemTime,
                'LoggingDriver' => $this->loggingDriver,
                'CgroupDriver' => $this->cgroupDriver,
                'CgroupVersion' => $this->cgroupVersion,
                'NEventsListener' => $this->nEventsListener,
                'KernelVersion' => $this->kernelVersion,
                'OperatingSystem' => $this->operatingSystem,
                'OSVersion' => $this->osVersion,
                'OSType' => $this->osType,
                'Architecture' => $this->architecture,
                'NCPU' => $this->ncpu,
                'MemTotal' => $this->memTotal,
                'IndexServerAddress' => $this->indexServerAddress,
                'RegistryConfig' => $this->registryConfig,
                'HttpProxy' => $this->httpProxy,
                'HttpsProxy' => $this->httpsProxy,
                'NoProxy' => $this->noProxy,
                'Name' => $this->name,
                'Labels' => $this->labels,
                'ExperimentalBuild' => $this->experimentalBuild,
                'ServerVersion' => $this->serverVersion,
                'ClusterStore' => $this->clusterStore,
                'ClusterAdvertise' => $this->clusterAdvertise,
                'Runtimes' => $this->runtimes,
                'DefaultRuntime' => $this->defaultRuntime,
                'Swarm' => $this->swarm,
                'LiveRestoreEnabled' => $this->liveRestoreEnabled,
                'Isolation' => $this->isolation,
                'InitBinary' => $this->initBinary,
                'ContainerdCommit' => $this->containerdCommit,
                'RuncCommit' => $this->runcCommit,
                'InitCommit' => $this->initCommit,
                'SecurityOptions' => $this->securityOptions,
                'ProductLicense' => $this->productLicense,
                'Warnings' => $this->warnings,
            ];
        }

        /**
         * Create an instance from an array
         *
         * @param array<string, mixed> $data
         * @return self
         */
        public static function fromArray(array $data): self
        {
            return new self($data);
        }

        /**
         * Gets the raw data array (deprecated)
         *
         * @deprecated Use toArray() instead
         * @return array<string, mixed>
         */
        public function getRawData(): array
        {
            return $this->toArray();
        }
    }
