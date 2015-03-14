<?php

namespace Afk11\Bitcoin;

interface SerializableInterface
{
    /**
     * @param null $type
     * @return Buffer
     */
    public function getBuffer();
}
