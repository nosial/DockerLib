# DockerLib

DockerLib is a PHP library intended to be used with [ncc](https://github.com/nosial/ncc) to interact with the Docker Engine API.
This library provides a pure-PHP client that communicates with the Docker daemon over the Unix socket, wrapping every major
Docker API resource (containers, images, networks, volumes, exec, swarm, services, nodes, secrets, configs, plugins, tasks,
distribution, and system info) behind a clean, object-oriented interface. It also includes a Docker Compose implementation
for orchestrating multi-container applications.

## Table of Contents

<!-- TOC -->
* [DockerLib](#dockerlib)
  * [Table of Contents](#table-of-contents)
  * [Installation](#installation)
  * [Usage](#usage)
  * [Quick Start](#quick-start)
    * [Listing and Inspecting Containers](#listing-and-inspecting-containers)
    * [Creating and Managing Containers](#creating-and-managing-containers)
    * [Working with Images](#working-with-images)
    * [Managing Networks](#managing-networks)
    * [Managing Volumes](#managing-volumes)
    * [System Information](#system-information)
    * [Executing Commands in Containers](#executing-commands-in-containers)
    * [Docker Compose](#docker-compose)
  * [Docker Client](#docker-client)
  * [Manager Classes](#manager-classes)
  * [Object Models](#object-models)
  * [Docker Compose](#docker-compose-1)
    * [Configuration Reference](#configuration-reference)
    * [Compose File Support](#compose-file-support)
  * [License](#license)
<!-- TOC -->

## Installation

To use DockerLib in your ncc project you can simply add it as a dependency in your `project.yml`:

```yaml
dependencies:
  net.nosial.dockerlib: nosial/dockerlib@github
```

Then run the command to install the dependency:

```sh
ncc project install
```

## Usage

DockerLib works by creating a `Docker` client instance that connects to the Docker daemon via the Unix socket. From there,
you access specialized manager classes for each Docker resource type:

```php
<?php
    require 'ncc';
    import('net.nosial.dockerlib');

    use DockerLib\Docker;

    $docker = new Docker();

    // Verify the daemon is reachable
    if ($docker->ping())
    {
        echo "Docker daemon is running\n";
    }

    // Access managers
    $containers = $docker->containers();
    $images = $docker->images();
    $networks = $docker->networks();
    $volumes = $docker->volumes();
    $system = $docker->system();
?>
```

## Quick Start

### Listing and Inspecting Containers

```php
<?php
    use DockerLib\Docker;

    $docker = new Docker();

    // List all running containers
    $containers = $docker->containers()->list();

    foreach ($containers as $container)
    {
        echo $container->getName() . ' - ' . $container->getImage() . "\n";
    }

    // List all containers (including stopped)
    $allContainers = $docker->containers()->list([], true);

    // Inspect a specific container
    $inspected = $docker->containers()->inspect('container_id');
    echo $inspected->getStateString(); // "running", "exited", etc.
?>
```

### Creating and Managing Containers

```php
<?php
    use DockerLib\Docker;

    $docker = new Docker();

    // Create a container
    $container = $docker->containers()->create([
        'Image' => 'alpine:latest',
        'Cmd' => ['sleep', '60'],
        'Labels' => ['app' => 'myapp']
    ]);

    $id = $container->getId();

    // Start the container
    $docker->containers()->start($id);

    // Pause / Unpause
    $docker->containers()->pause($id);
    $docker->containers()->unpause($id);

    // Get container stats
    $stats = $docker->containers()->stats($id);
    echo $stats->getMemoryUsage() . ' bytes';

    // Stop and remove
    $docker->containers()->stop($id, 10);
    $docker->containers()->remove($id);
?>
```

### Working with Images

```php
<?php
    use DockerLib\Docker;

    $docker = new Docker();

    // Pull an image
    $stream = $docker->images()->pull('alpine', 'latest');
    while (($line = $stream->readLine()) !== null)
    {
        $data = json_decode($line, true);
        if (isset($data['status']))
        {
            echo $data['status'] . "\n";
        }
    }
    $stream->close();

    // List images
    $images = $docker->images()->list();
    foreach ($images as $image)
    {
        echo $image->getId() . ' - ' . implode(', ', $image->getRepoTags()) . "\n";
    }

    // Inspect an image
    $image = $docker->images()->inspect('alpine:latest');
    echo $image->getArchitecture() . ' - ' . $image->getSize() . ' bytes';

    // Search Docker Hub
    $results = $docker->images()->search('nginx', 10);

    // Tag and remove images
    $docker->images()->tag('alpine:latest', 'myrepo/alpine', 'custom');
    $docker->images()->remove('myrepo/alpine:custom');
?>
```

### Managing Networks

```php
<?php
    use DockerLib\Docker;

    $docker = new Docker();

    // Create a network
    $network = $docker->networks()->create([
        'Name' => 'my-network',
        'Driver' => 'bridge',
        'Labels' => ['app' => 'myapp']
    ]);

    // Inspect
    $inspected = $docker->networks()->inspect($network->getId());
    echo $inspected->getDriver() . ' - ' . $inspected->getScope();

    // Connect a container to the network
    $docker->networks()->connect($network->getId(), $containerId);

    // Disconnect a container
    $docker->networks()->disconnect($network->getId(), $containerId);

    // Create a network with custom subnet
    $custom = $docker->networks()->create([
        'Name' => 'custom-network',
        'Driver' => 'bridge',
        'IPAM' => [
            'Config' => [
                ['Subnet' => '172.28.0.0/16', 'Gateway' => '172.28.0.1']
            ]
        ]
    ]);

    // List with filters
    $networks = $docker->networks()->list(['label' => ['app=myapp']]);

    // Prune unused networks
    $docker->networks()->prune();

    // Remove
    $docker->networks()->remove($network->getId());
?>
```

### Managing Volumes

```php
<?php
    use DockerLib\Docker;

    $docker = new Docker();

    // Create a volume
    $volume = $docker->volumes()->create(['Name' => 'my-volume']);
    echo $volume->getName() . ' - ' . $volume->getDriver();

    // List volumes
    $volumes = $docker->volumes()->list();

    // Inspect
    $inspected = $docker->volumes()->inspect('my-volume');

    // Prune unused volumes
    $result = $docker->volumes()->prune();

    // Remove
    $docker->volumes()->remove('my-volume');
?>
```

### System Information

```php
<?php
    use DockerLib\Docker;

    $docker = new Docker();

    // Docker daemon info
    $info = $docker->system()->info();
    echo $info->getOperatingSystem() . "\n";
    echo $info->getContainers() . ' containers, ' . $info->getImages() . " images\n";

    // Daemon version
    $version = $docker->version();
    echo $version['data']['Version'] . ' (API ' . $version['data']['ApiVersion'] . ")\n";

    // Ping
    $alive = $docker->ping();
    $alive = $docker->system()->ping();
    $alive = $docker->system()->pingHead(); // HEAD request

    // Stream real-time events
    $events = $docker->system()->events();
    while (($line = $events->readLine()) !== null)
    {
        $event = json_decode($line, true);
        echo $event['Type'] . ': ' . ($event['Action'] ?? '') . "\n";
    }
    $events->close();
?>
```

### Executing Commands in Containers

```php
<?php
    use DockerLib\Docker;

    $docker = new Docker();

    // Create an exec instance
    $exec = $docker->exec()->create($containerId, [
        'Cmd' => ['ls', '-la'],
        'AttachStdout' => true,
        'AttachStderr' => true,
    ]);

    // Start and get output
    $stream = $docker->exec()->start($exec->getId(), true, true);
    $output = $stream->readAll();
    echo $output;
    $stream->close();
?>
```

### Docker Compose

```php
<?php
    use DockerLib\DockerCompose;

    // Load a docker-compose.yml file
    $compose = new DockerCompose('/path/to/docker-compose.yml');

    // Start all services (creates networks, volumes, and containers)
    $result = $compose->up();
    echo "Started: " . implode(', ', array_keys($result['containers'])) . "\n";

    // Build images for all services
    $compose->build();

    // Start with build
    $compose->up(true);

    // With progress callback
    $compose->up(true, function(string $resource, string $action, ?string $message = null) {
        echo "[$resource] $action: $message\n";
    });

    // Stop and remove all resources
    $compose->down();

    // Stop, remove volumes, and remove images
    $compose->down(true, true);
?>
```

## Docker Client

The `Docker` class is the main entry point for interacting with the Docker Engine API. It accepts an optional socket path
(defaults to `/var/run/docker.sock`) and initializes all manager classes.

| Method                            | Return Type           | Description                                                |
|-----------------------------------|-----------------------|------------------------------------------------------------|
| `__construct(string $socketPath)` | `Docker`              | Creates a new client, optionally with a custom socket path |
| `containers()`                    | `ContainerManager`    | Returns the container manager instance                     |
| `images()`                        | `ImageManager`        | Returns the image manager instance                         |
| `networks()`                      | `NetworkManager`      | Returns the network manager instance                       |
| `volumes()`                       | `VolumeManager`       | Returns the volume manager instance                        |
| `system()`                        | `SystemManager`       | Returns the system manager instance                        |
| `exec()`                          | `ExecManager`         | Returns the exec manager instance                          |
| `swarm()`                         | `SwarmManager`        | Returns the swarm manager instance                         |
| `services()`                      | `ServiceManager`      | Returns the service manager instance                       |
| `nodes()`                         | `NodeManager`         | Returns the node manager instance                          |
| `secrets()`                       | `SecretManager`       | Returns the secret manager instance                        |
| `configs()`                       | `ConfigManager`       | Returns the config manager instance                        |
| `plugins()`                       | `PluginManager`       | Returns the plugin manager instance                        |
| `tasks()`                         | `TaskManager`         | Returns the task manager instance                          |
| `distribution()`                  | `DistributionManager` | Returns the distribution manager instance                  |
| `ping()`                          | `bool`                | Sends a ping to verify the Docker daemon is reachable      |
| `version()`                       | `array`               | Returns the Docker daemon version information              |

All manager accessors return singleton instances — calling the same method multiple times returns the same manager object.

## Manager Classes

Each manager provides a set of methods that map to the Docker Engine API endpoints for a specific resource type.

| Manager               | Methods                                                                                                                                                                                                                                                           | Description                             |
|-----------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------|
| `ContainerManager`    | `list`, `create`, `inspect`, `start`, `stop`, `restart`, `kill`, `remove`, `pause`, `unpause`, `rename`, `logs`, `stats`, `top`, `changes`, `export`, `attach`, `wait`, `prune`, `update`, `resize`, `getArchive`, `putArchive`, `statArchive`, `attachWebSocket` | Full container lifecycle management     |
| `ImageManager`        | `list`, `inspect`, `history`, `pull`, `push`, `tag`, `remove`, `search`, `prune`, `build`, `import`, `export`, `exportAll`, `load`, `commit`, `pruneBuildCache`                                                                                                   | Image lifecycle and registry operations |
| `NetworkManager`      | `list`, `inspect`, `create`, `remove`, `connect`, `disconnect`, `prune`                                                                                                                                                                                           | Network CRUD and container connectivity |
| `VolumeManager`       | `list`, `inspect`, `create`, `remove`, `update`, `prune`                                                                                                                                                                                                          | Volume CRUD management                  |
| `SystemManager`       | `info`, `version`, `ping`, `pingHead`, `events`, `dataUsage`, `auth`, `createSession`                                                                                                                                                                             | Daemon-level operations                 |
| `ExecManager`         | `create`, `start`, `inspect`, `resize`                                                                                                                                                                                                                            | Execute commands in running containers  |
| `SwarmManager`        | `inspect`, `init`, `join`, `leave`, `update`, `unlockKey`, `unlock`                                                                                                                                                                                               | Swarm cluster management                |
| `ServiceManager`      | `list`, `inspect`, `create`, `update`, `remove`, `logs`                                                                                                                                                                                                           | Swarm service CRUD and logs             |
| `NodeManager`         | `list`, `inspect`, `update`, `remove`                                                                                                                                                                                                                             | Swarm node management                   |
| `SecretManager`       | `list`, `inspect`, `create`, `update`, `remove`                                                                                                                                                                                                                   | Swarm secret management                 |
| `ConfigManager`       | `list`, `inspect`, `create`, `update`, `remove`                                                                                                                                                                                                                   | Swarm config management                 |
| `PluginManager`       | `list`, `inspect`, `install`, `enable`, `disable`, `remove`, `upgrade`, `push`, `configure`, `privileges`, `create`                                                                                                                                               | Plugin lifecycle management             |
| `TaskManager`         | `list`, `inspect`, `logs`                                                                                                                                                                                                                                         | Swarm task inspection and logs          |
| `DistributionManager` | `inspect`                                                                                                                                                                                                                                                         | Registry distribution metadata          |

## Object Models

All object models implement `SerializableInterface` and are created via `fromArray()` factory methods. They provide
typed getter methods for their properties.

| Object            | Parent    | Description                                                                                  |
|-------------------|-----------|----------------------------------------------------------------------------------------------|
| `Container`       | —         | Full container representation from list or inspect                                           |
| `ContainerStats`  | —         | Real-time resource usage with CPU/memory percentage                                          |
| `Image`           | —         | Full image metadata from list or inspect                                                     |
| `Network`         | —         | Network configuration                                                                        |
| `Volume`          | —         | Volume metadata                                                                              |
| `Service`         | —         | Swarm service with convenience methods                                                       |
| `Swarm`           | —         | Swarm cluster state                                                                          |
| `Node`            | —         | Swarm node with role/availability detection                                                  |
| `Secret`          | —         | Swarm secret                                                                                 |
| `Config`          | —         | Swarm config                                                                                 |
| `Plugin`          | —         | Plugin metadata                                                                              |
| `Task`            | —         | Swarm task with state detection                                                              |
| `ExecInstance`    | —         | Exec command instance                                                                        |
| `StreamResponse`  | —         | Streaming HTTP response with `read()`, `readLine()`, `readAll()`, `readChunked()`, `close()` |
| `SystemInfo`      | —         | Full `docker info` response (~70 properties)                                                 |
| `SystemDataUsage` | —         | Disk usage from `system/df`                                                                  |
| `Distribution`    | —         | Registry image manifest info                                                                 |
| `ContainerConfig` | Container | Container runtime configuration                                                              |
| `HostConfig`      | Container | Host-level container settings (memory, CPU, etc.)                                            |
| `Mount`           | Container | Volume/bind/tmpfs mount point                                                                |
| `NetworkSettings` | Container | Container network state                                                                      |
| `Port`            | Container | Port mapping                                                                                 |
| `ImageConfig`     | Image     | Image default config for containers                                                          |
| `RootFS`          | Image     | Root filesystem layer info                                                                   |
| `GraphDriver`     | Image     | Storage driver metadata                                                                      |
| `IPAM`            | Network   | IP address management configuration                                                          |
| `IPAMConfig`      | IPAM      | Single IPAM subnet config                                                                    |

## Docker Compose

The `DockerCompose` class provides Docker Compose-like functionality for managing multi-container applications. It
parses a `docker-compose.yml` file and orchestrates the creation and management of networks, volumes, and containers.

### Configuration Reference

DockerLib directly maps to the [Docker Engine API](https://docs.docker.com/engine/api/) JSON schema. Configuration arrays
passed to manager methods follow the same structure as the Docker API request bodies.

### Compose File Support

The `DockerCompose` class parses standard `docker-compose.yml` files and supports:

| Feature               | Description                                          |
|-----------------------|------------------------------------------------------|
| Services              | Container definitions with image, build, ports, etc. |
| Networks              | Custom bridge/overlay networks with IPAM             |
| Volumes               | Named volumes and host path bindings                 |
| Port Mappings         | Host-to-container port publishing                    |
| Environment Variables | Static and dynamic environment configuration         |
| Restart Policies      | no, always, on-failure, unless-stopped               |
| Build Args            | Arguments passed to Dockerfile during builds         |
| Resource Labels       | Automatic `com.docker.compose.*` labeling            |

The project name is automatically derived from the directory containing the compose file, and all created resources
are prefixed with `{projectName}_` for isolation.

## License

DockerLib is licensed under the MIT License, see [LICENSE](LICENSE) for more information.
