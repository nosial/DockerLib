<?php

    namespace DockerLib\Exceptions;

    /**
     * Exception thrown when Docker daemon returns an error response
     * 
     * This exception is thrown when the Docker daemon returns an HTTP error
     * response (status code 400 or higher), indicating that the requested
     * operation failed or was invalid.
     * 
     * @package DockerLib\Exceptions
     */
    class ResponseException extends DockerException
    {
    }
