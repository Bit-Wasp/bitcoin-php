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

    public function isPayToPublicKeyHash();
    public function isPayToPublicKey();
    public function isPayToScriptHash();
    public function isMultisig();
    public function classify();
};
