<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 14/03/15
 * Time: 20:49
 */

namespace Afk11\Bitcoin\Script;


use Afk11\Bitcoin\Key\PublicKeyFactory;
use Afk11\Bitcoin\Key\PublicKeyInterface;

class RedeemScript extends Script
{
    /**
     * @var int
     */
    private $m;

    /**
     * @var array
     */
    private $keys = [];

    /**
     * @param ScriptInterface $script
     */
    public function __construct(ScriptInterface $script)
    {
        $parse = $script->parse();

        $last = count($parse)-2;
        for ($i = 1; $i < $last; $i++) {
            $this->keys[] = PublicKeyFactory::fromHex($parse[$i]);
        }

        $this->m = $parse[0];
        $this->script = $script->getBuffer()->serialize();
    }

    /**
     * @return PublicKeyInterface[]
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * @param $index
     * @return mixed
     */
    public function getKey($index)
    {
        if (!isset($this->keys[$index])) {
            throw new \LogicException('No key at index ' . $index);
        }

        return $this->keys[$index];
    }
}