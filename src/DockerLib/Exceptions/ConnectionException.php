<?php

    namespace DockerLib\Exceptions;

    /**
     * Exception thrown when connection to Docker daemon fails
     * 
     * This exception is thrown when the library cannot establish a connection
     * to the Docker socket or when the socket is not available.
     * 
     * @package DockerLib\Exceptions
     */
    class ConnectionException extends DockerException
    {
    }
