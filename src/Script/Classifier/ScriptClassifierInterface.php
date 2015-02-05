<?php

namespace Bitcoin\Script\Classifier;

use Bitcoin\Script\Script;

/**
 * Interface ScriptClassifierInterface
 * @package Bitcoin\Script\Classifier
 */
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
