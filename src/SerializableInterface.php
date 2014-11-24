<?php

namespace Bitcoin;

/**
 * Interface SerializableInterface
 * @package Bitcoin
 */
interface SerializableInterface
{
    /**
     * @param null $type
     * @return mixed
     */
    public function serialize($type = null);

    /**
     * @return string
     */
    public function __toString();

    /**
     * @return int
     */
    public function getSize();
}
