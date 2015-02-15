<?php

namespace Afk11\Bitcoin\Script;

interface ScriptInterface
{
    /**
     * //TODO: this needed?
     * @return mixed
     */

    public function parse();

    public function serialize($type = null);
}
