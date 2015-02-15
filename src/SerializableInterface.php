<?php

namespace Afk11\Bitcoin;

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
