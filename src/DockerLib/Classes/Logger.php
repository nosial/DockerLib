<?php

    namespace DockerLib\Classes;

    use LogLib2\Logger as LogLib2Logger;

    /**
     * Logger wrapper providing access to the LogLib2 logger instance
     * 
     * This class provides a singleton wrapper around the LogLib2 logger,
     * ensuring a single logger instance is used throughout the library
     * for consistent logging across all Docker operations.
     * 
     * @package DockerLib\Classes
     */
    class Logger
    {
        private static ?LogLib2Logger $instance = null;

        /**
         * Gets the shared LogLib2 logger instance
         * 
         * Returns a singleton instance of the LogLib2 logger configured for DockerLib.
         * Creates the instance on first call and returns the same instance on subsequent calls.
         *
         * @return LogLib2Logger The logger instance for logging Docker operations
         */
        public static function getLogger(): LogLib2Logger
        {
            if (self::$instance === null)
            {
                self::$instance = new LogLib2Logger('net.nosial.dockerlib');
            }
            
            return self::$instance;
        }
    }
