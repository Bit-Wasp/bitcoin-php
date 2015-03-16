<?php

namespace Afk11\Bitcoin\Script;

use Afk11\Bitcoin\SerializableInterface;
use Afk11\Bitcoin\Address\Address;

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
