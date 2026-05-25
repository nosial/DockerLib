<?php

    namespace DockerLib\Interfaces;

    use InvalidArgumentException;

    /**
     * Interface for objects that can be serialized to and from array representations.
     *
     * This interface provides a contract for bidirectional serialization between objects
     * and array structures. It is particularly useful for data transfer operations such as
     * JSON encoding/decoding, database persistence, API responses, and cache storage.
     *
     * Implementing classes should ensure that the data returned by toArray() can be used
     * to reconstruct an equivalent object via fromArray(), maintaining data integrity
     * during the serialization cycle.
     *
     * @package DockerLib\Interfaces
     * @since 1.0.0
     */
    interface SerializableInterface
    {
        /**
         * Converts the object to an associative array representation.
         *
         * This method serializes the object's state into an array structure suitable
         * for storage, transmission, or further processing. The returned array should
         * contain all necessary data to reconstruct the object using fromArray().
         *
         * Implementations should:
         * - Include all relevant properties needed to reconstruct the object
         * - Use string keys for associative arrays
         * - Recursively serialize nested objects implementing this interface
         * - Exclude transient or non-serializable data
         *
         * @return array An associative array representation of the object's state
         *
         * @see self::fromArray() For reconstructing the object from array data
         */
        public function toArray(): array;

        /**
         * Creates a new instance from an array representation.
         *
         * This static factory method reconstructs an object from its array representation,
         * typically created by toArray(). It should handle the deserialization process
         * and return a fully initialized instance of the implementing class.
         *
         * Implementations should:
         * - Validate required array keys and data types
         * - Provide sensible defaults for optional fields
         * - Recursively deserialize nested structures
         * - Throw appropriate exceptions for invalid or malformed data
         *
         * @param array $data An associative array containing the serialized object data
         * @return static A new instance of the implementing class populated with the provided data
         * @throws InvalidArgumentException If the provided data is invalid or incomplete
         * @see self::toArray() For the inverse operation
         */
        public static function fromArray(array $data): self;
    }
