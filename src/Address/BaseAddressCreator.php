<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\WitnessProgram;

abstract class BaseAddressCreator
{
    /**
     * @param string $strAddress
     * @param NetworkInterface|null $network
     * @return Address
     */
    abstract public function fromString($strAddress, NetworkInterface $network = null);

    /**
     * @param ScriptInterface $script
     * @return Address
     */
    abstract public function fromOutputScript(ScriptInterface $script);

    /**
     * Returns a pay-to-pubkey-hash address for the given public key
     *
     * @param KeyInterface $key
     * @return PayToPubKeyHashAddress
     */
    abstract public function fromKey(KeyInterface $key);

    /**
     * Takes the $p2shScript and generates the scriptHash address.
     *
     * @param ScriptInterface $p2shScript
     * @return ScriptHashAddress
     */
    abstract public function fromRedeemScript(ScriptInterface $p2shScript);

    /**
     * @param WitnessProgram $wp
     * @return SegwitAddress
     */
    abstract public function fromWitnessProgram(WitnessProgram $wp);
}
