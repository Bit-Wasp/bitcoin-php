<?php

namespace Afk11\Bitcoin\Script\Classifier;

use Afk11\Bitcoin\Script\Script;

interface ScriptClassifierInterface
{
    const PAYTOPUBKEY = 'pubkey';
    const PAYTOPUBKEYHASH = 'pubkeyhash';
    const PAYTOSCRIPTHASH = 'scripthash';
    const MULTISIG = 'multisig';
    const NONSTANDARD = 'nonstandard';

    /**
     * @return bool
     */
    public function isPayToPublicKeyHash();

    /**
     * @return bool
     */
    public function isPayToPublicKey();

    /**
     * @return bool
     */
    public function isPayToScriptHash();

    /**
     * @return bool
     */
    public function isMultisig();

    /**
     * @return bool
     */
    public function classify();
}
