<?php

namespace BitWasp\Bitcoin\Deterministic;


use BitWasp\Bitcoin\Key\HierarchicalKey;
use BitWasp\Bitcoin\Key\HierarchicalKeySequence;
use BitWasp\Bitcoin\Script\ScriptFactory;

class MultisigHD
{
    /**
     * @var int|string
     */
    private $m;

    /**
     * @var string
     */
    private $path;

    /**
     * @var HierarchicalKey[]
     */
    private $keys;

    /**
     * @var bool
     */
    private $sort;

    /**
     * @param $m
     * @param $path
     * @param array $keys
     * @param bool $sort
     */
    public function __construct($m, $path, array $keys, HierarchicalKeySequence $sequences, $sort = false)
    {
        if (count($keys) < 1) {
            throw new \RuntimeException('Must have at least one public key for Multisig HD Script');
        }

        foreach ($keys as $key) {
            $this->addKey($key);
        }
        $this->m = $m;
        $this->sort = $sort;
        $this->path = $path;
        $this->sequences = $sequences;
    }

    /**
     * @param HierarchicalKey $key
     */
    private function addKey(HierarchicalKey $key)
    {
        $this->keys[] = $key;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return \BitWasp\Bitcoin\Key\HierarchicalKey[]
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * @param $sequence
     * @return MultisigHD
     */
    public function deriveChild($sequence)
    {
        $keys = array_map(
            function (HierarchicalKey $hk) use ($sequence) {
                return $hk->deriveChild($sequence);
            },
            $this->keys
        );

        if ($this->sort) {
            $keys = \BitWasp\Buffertools\Buffertools::sort($keys, function (HierarchicalKey $key) {
                return $key->getPublicKey()->getBuffer();
            });
        }

        $path = $this->path . "/" . $sequence;

        return new self($this->m, $path, $keys, $this->sequences, $this->sort);
    }

    /**
     * @param $path
     * @return MultisigHD
     */
    public function derivePath($path)
    {
        $decoded = explode("/", $this->keys[0]->decodePath($path));

        $child = $this;
        foreach ($decoded as $p) {
            $child = $child->deriveChild($p);
        }

        return $child;
    }

    /**
     * @return \BitWasp\Bitcoin\Script\RedeemScript
     */
    public function getRedeemScript()
    {
        return ScriptFactory::multisig(
            $this->m,
            array_map(function (HierarchicalKey $key) {
                    return $key->getPublicKey();
                },
                $this->keys
            ),
            $this->sort
        );
    }
}