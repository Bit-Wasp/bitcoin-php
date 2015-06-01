<?php

namespace BitWasp\Bitcoin\Key\Deterministic;

use BitWasp\Buffertools\Buffertools;
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
     * @var HierarchicalKeySequence
     */
    private $sequences;

    /**
     * @var bool
     */
    private $sort;

    /**
     * @param int|string $m
     * @param string $path
     * @param array $keys
     * @param HierarchicalKeySequence $sequences
     * @param bool $sort
     */
    public function __construct($m, $path, array $keys, HierarchicalKeySequence $sequences, $sort = false)
    {
        if (count($keys) < 1) {
            throw new \RuntimeException('Must have at least one HierarchicalKey for Multisig HD Script');
        }

        // Sort here to guarantee calls to getKeys() returns keys in the same order as the redeemScript.
        if ($sort) {
            $keys = Buffertools::sort($keys, function (HierarchicalKey $key) {
                return $key->getPublicKey()->getBuffer();
            });
        }

        $this->m = $m;
        $this->path = $path;
        foreach ($keys as $key) {
            $this->keys[] = $key;
        }
        $this->sequences = $sequences;
        $this->sort = $sort;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Return the composite keys of this MultisigHD wallet entry.
     * This will strictly adhere to the choice on whether keys should be sorted, since this is done in the constructor.
     *
     * @return \BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey[]
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * Returns the redeemScript. Note - keys are already sorted in the constructor, so this is not required in ScriptFactory.
     *
     * @return \BitWasp\Bitcoin\Script\RedeemScript
     */
    public function getRedeemScript()
    {
        return ScriptFactory::multisig(
            $this->m,
            array_map(
                function (HierarchicalKey $key) {
                    return $key->getPublicKey();
                },
                $this->keys
            )
        );
    }

    /**
     * Derive each HK child and produce a new MultisigHD object
     *
     * @param int|string $sequence
     * @return MultisigHD
     */
    public function deriveChild($sequence)
    {
        return new self(
            $this->m,
            $this->path . "/" . $this->sequences->getNode($sequence),
            array_map(
                function (HierarchicalKey $hk) use ($sequence) {
                    return $hk->deriveChild($sequence);
                },
                $this->keys
            ),
            $this->sequences,
            $this->sort
        );
    }

    /**
     * Derive a path in the tree of available addresses.
     *
     * @param string $path
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
}
