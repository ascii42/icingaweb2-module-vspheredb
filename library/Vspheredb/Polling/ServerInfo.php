<?php

namespace Icinga\Module\Vspheredb\Polling;

use gipfl\Json\JsonSerialization;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;
use InvalidArgumentException;
use function array_key_exists;

class ServerInfo implements JsonSerialization
{
    /** @var array */
    protected $properties;

    /**
     * ServerInfo constructor.
     * @param array $properties
     */
    public function __construct(array $properties)
    {
        $this->properties = $properties;
    }

    public static function fromSerialization($object)
    {
        // Validation will be implemented once this is remote
        return new static((array) $object);
    }

    /**
     * @param VCenterServer $server
     * @return static
     */
    public static function fromServer(VCenterServer $server)
    {
        return new static($server->getProperties());
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->properties)) {
            if ($this->properties[$key] === null) {
                return $default;
            } else {
                return $this->properties[$key];
            }
        }

        throw new InvalidArgumentException("Trying to access invalid property: '$key'");
    }

    public function jsonSerialize()
    {
        return (object) $this->properties;
    }

    public function getIdentifier()
    {
        return sprintf(
            '%s://%s@%s',
            $this->get('scheme'),
            $this->get('username'),
            $this->get('host')
        );
    }
}
