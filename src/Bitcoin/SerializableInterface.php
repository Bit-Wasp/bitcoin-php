<?php

namespace Afk11\Bitcoin;

interface SerializableInterface
{
    /**
     * @param null $type
     * @return mixed
     */
    public function getBuffer();
}
