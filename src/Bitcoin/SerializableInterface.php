<?php

namespace Afk11\Bitcoin;

interface SerializableInterface
{
    /**
     * @return Buffer
     */
    public function getBuffer();
}
