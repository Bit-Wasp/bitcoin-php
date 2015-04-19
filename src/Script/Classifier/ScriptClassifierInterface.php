<?php

namespace BitWasp\Bitcoin\Script\Classifier;

interface ScriptClassifierInterface
{
    const PAYTOPUBKEY = 'pubkey';
    const PAYTOPUBKEYHASH = 'pubkeyhash';
    const PAYTOSCRIPTHASH = 'scripthash';
    const MULTISIG = 'multisig';
    const UNKNOWN = 'unknown';
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
     * @return string
     */
    public function classify();
}
