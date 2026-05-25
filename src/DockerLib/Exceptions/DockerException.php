<?php

    namespace DockerLib\Exceptions;

    use Exception;

    /**
     * Base exception class for all Docker-related exceptions
     * 
     * This is the base exception class that all other DockerLib exceptions
     * extend from. It can be used to catch any Docker-related error.
     * 
     * @package DockerLib\Exceptions
     */
    class DockerException extends Exception
    {
    }
