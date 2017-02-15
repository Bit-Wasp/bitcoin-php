<?php

namespace BitWasp\Bitcoin\Key\Deterministic;

use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Buffertools\Buffertools;

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
     * @var P2shScript
     */
    private $redeemScript;

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
            $keys = $this->sortHierarchicalKeys($keys);
        }

        foreach ($keys as $key) {
            $this->keys[] = $key;
        }

        $this->m = $m;
        $this->path = $path;
        $this->sort = $sort;
        $this->sequences = $sequences;
        $this->redeemScript = new P2shScript(ScriptFactory::scriptPubKey()->multisig($m, array_map(
            function (HierarchicalKey $key) {
                return $key->getPublicKey();
            },
            $this->keys
        ), false));
    }

    /**
     * @param HierarchicalKey[] $keys
     * @return HierarchicalKey[]
     */
    private function sortHierarchicalKeys(array $keys)
    {
        return Buffertools::sort($keys, function (HierarchicalKey $key) {
            return $key->getPublicKey()->getBuffer();
        });
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
     * @return HierarchicalKey[]
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * Returns the redeemScript. Note - keys are already sorted in the constructor, so this is not required in ScriptFactory.
     *
     * @return P2shScript
     */
    public function getRedeemScript()
    {
        return $this->redeemScript;
    }

    /**
     * @return \BitWasp\Bitcoin\Script\ScriptInterface
     */
    public function getScriptPubKey()
    {
        return $this->redeemScript->getOutputScript();
    }

    /**
     * @return \BitWasp\Bitcoin\Address\ScriptHashAddress
     */
    public function getAddress()
    {
        return $this->redeemScript->getAddress();
    }

    /**
     * Derive each HK child and produce a new MultisigHD object
     *
     * @param int|string $sequence
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
            $keys = $this->sortHierarchicalKeys($keys);
        }

        return new self(
            $this->m,
            $this->path . '/' . $this->sequences->getNode($sequence),
            $keys,
            $this->sequences,
            $this->sort
        );
    }

    /**
     * @param array|\stdClass|\Traversable $list
     * @return MultisigHD
     */
    public function deriveFromList($list)
    {
        HierarchicalKeySequence::validateListType($list);

        $account = $this;
        foreach ($list as $sequence) {
            $account = $account->deriveChild($sequence);
        }

        return $account;
    }

    /**
     * Derive a path in the tree of available addresses.
     *
     * @param string $path
     * @return MultisigHD
     */
    public function derivePath($path)
    {
        return $this->deriveFromList($this->sequences->decodePath($path));
    }
}
