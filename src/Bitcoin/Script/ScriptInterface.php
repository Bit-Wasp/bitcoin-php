<?php

namespace BitWasp\Bitcoin\Script;

use BitWasp\Bitcoin\SerializableInterface;
use BitWasp\Bitcoin\Address\Address;

interface ScriptInterface extends SerializableInterface
{
    const PAYTOPUBKEY = 'pubkey';
    const PAYTOPUBKEYHASH = 'pubkeyhash';
    const PAYTOSCRIPTHASH = 'scripthash';
    const MULTISIG = 'multisig';
    const UNKNOWN = 'unknown';
    const NONSTANDARD = 'nonstandard';

    /**
     * @return mixed
     */
    public function getScriptHash();

    /**
     * @return Address
     */
    public function getAddress();

    /**
     * @return ScriptParser
     */
    public function getScriptParser();

    /**
     * @return Opcodes
     */
    public function getOpcodes();

    /**
     * @return mixed
     */
    public function isPushOnly();
}
