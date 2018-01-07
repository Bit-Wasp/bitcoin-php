<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\WitnessProgram;

class AddressFactory
{
    /**
     * Returns a pay-to-pubkey-hash address for the given public key
     *
     * @param KeyInterface $key
     * @return PayToPubKeyHashAddress
     */
    public static function p2pkh(KeyInterface $key): PayToPubKeyHashAddress
    {
        return new PayToPubKeyHashAddress($key->getPubKeyHash());
    }

    /**
     * Takes the $p2shScript and generates the scriptHash address.
     *
     * @param ScriptInterface $p2shScript
     * @return ScriptHashAddress
     */
    public static function p2sh(ScriptInterface $p2shScript): ScriptHashAddress
    {
        return new ScriptHashAddress($p2shScript->getScriptHash());
    }

    /**
     * @param WitnessProgram $wp
     * @return SegwitAddress
     */
    public static function fromWitnessProgram(WitnessProgram $wp): SegwitAddress
    {
        return new SegwitAddress($wp);
    }
}
