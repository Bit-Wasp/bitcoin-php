<?php

namespace BitWasp\Bitcoin\Script\Classifier;

use BitWasp\Buffertools\BufferInterface;

interface ScriptClassifierInterface
{
    const PAYTOPUBKEY = 'pubkey';
    const PAYTOPUBKEYHASH = 'pubkeyhash';
    const PAYTOSCRIPTHASH = 'scripthash';
    const WITNESS_V0_KEYHASH = 'witness_v0_keyhash';
    const WITNESS_V0_SCRIPTHASH = 'witness_v0_scripthash';
    const MULTISIG = 'multisig';
    const UNKNOWN = 'unknown';
    const NONSTANDARD = 'nonstandard';

    /**
     * @param BufferInterface $pubKeyHash
     * @return bool
     */
    public function isPayToPublicKeyHash(& $pubKeyHash = null);

    /**
     * @param BufferInterface $publicKey
     * @return bool
     */
    public function isPayToPublicKey(& $publicKey = null);

    /**
     * @param BufferInterface[] $publicKeys
     * @return bool
     */
    public function isMultisig(& $publicKeys = []);

    /**
     * @param BufferInterface $scriptHash
     * @return bool
     */
    public function isPayToScriptHash(& $scriptHash = null);

    /**
     * @param BufferInterface $witnessHash
     * @return bool
     */
    public function isWitness(& $witnessHash = null);

    /**
     * @param BufferInterface|BufferInterface[] $solution
     * @return string
     */
    public function classify(&$solution = null);
}
