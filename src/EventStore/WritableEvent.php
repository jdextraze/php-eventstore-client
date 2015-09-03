<?php

namespace EventStore;

use EventStore\ValueObjects\Identity\UUID;
use InvalidArgumentException;
use stdClass;

/**
 * Class WritableEvent
 * @package EventStore
 */
final class WritableEvent implements WritableToStream
{
    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array|object
     */
    private $data;

    /**
     * @var array|object
     */
    private $metadata;

    /**
     * @param  string       $type
     * @param  array|object $data
     * @param  array|object $metadata
     * @return WritableEvent
     */
    public static function newInstance($type, $data, $metadata = null)
    {
        return new self(new UUID(), $type, $data, $metadata);
    }

    /**
     * @param UUID         $uuid
     * @param string       $type
     * @param array|object $data
     * @param array|object $metadata
     */
    public function __construct(UUID $uuid, $type, $data, $metadata = null)
    {
        if (!is_array($data) && !is_object($data)) {
            throw new InvalidArgumentException('Data expected array or object');
        }
        if (!is_null($metadata) && !is_array($data) && !is_object($data)) {
            throw new InvalidArgumentException('Metadata expected array, object or null');
        }

        $this->uuid = $uuid;
        $this->type = $type;
        $this->data = $data;
        $this->metadata = $metadata ?: new stdClass();
    }

    /**
     * @return array
     */
    public function toStreamData()
    {
        return [
            'eventId'   => $this->uuid->toNative(),
            'eventType' => $this->type,
            'data'      => $this->data,
            'metadata'  => $this->metadata
        ];
    }
}
