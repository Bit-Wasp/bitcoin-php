<?php

namespace Afk11\Bitcoin\Script;

use Afk11\Bitcoin\SerializableInterface;

interface ScriptInterface extends SerializableInterface
{
    /**
     * //TODO: this needed?
     * @return mixed
     */

    public function parse();

    public function serialize($type = null);
}
