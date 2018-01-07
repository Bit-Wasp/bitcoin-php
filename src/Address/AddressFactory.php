<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptType;
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

    /**
     * @param ScriptInterface $outputScript
     * @return Address
     */
    public static function fromOutputScript(ScriptInterface $outputScript): Address
    {
        $reader = new AddressCreator();
        return $reader->fromOutputScript($outputScript);
    }

    /**
     * @param string $address
     * @param NetworkInterface|null $network
     * @return Address
     * @throws \BitWasp\Bitcoin\Exceptions\UnrecognizedAddressException
     */
    public static function fromString(string $address, NetworkInterface $network = null): Address
    {
        $network = $network ?: Bitcoin::getNetwork();
        $reader = new AddressCreator();
        return $reader->fromString($address, $network);
    }

    /**
     * @param string $address
     * @param NetworkInterface $network
     * @return bool
     */
    public static function isValidAddress(string $address, NetworkInterface $network = null): bool
    {
        try {
            self::fromString($address, $network);
            $is_valid = true;
        } catch (\Exception $e) {
            $is_valid = false;
        }

        return $is_valid;
    }

    /**
     * Following a loose definition of 'associated', returns
     * the current script types, and a PayToPubKeyHash address for P2PK.
     *
     * @param ScriptInterface $script
     * @return AddressInterface
     * @throws \RuntimeException
     */
    public static function getAssociatedAddress(ScriptInterface $script): AddressInterface
    {
        $classifier = new OutputClassifier();
        $decode = $classifier->decode($script);
        if ($decode->getType() === ScriptType::P2PK) {
            $pubKey = PublicKeyFactory::fromBuffer($decode->getSolution());
            return new PayToPubKeyHashAddress($pubKey->getPubKeyHash());
        } else {
            return self::fromOutputScript($script);
        }
    }
}
