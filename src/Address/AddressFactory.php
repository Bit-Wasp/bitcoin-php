<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Script\WitnessScript;
use BitWasp\Bitcoin\SegwitBech32;
use BitWasp\Buffertools\BufferInterface;

class AddressFactory
{
    /**
     * Returns a pay-to-pubkey-hash address for the given public key
     *
     * @param KeyInterface $key
     * @return PayToPubKeyHashAddress
     */
    public static function p2pkh(KeyInterface $key)
    {
        return new PayToPubKeyHashAddress($key->getPubKeyHash());
    }

    /**
     * Takes the $p2shScript and generates the scriptHash address.
     *
     * @param ScriptInterface $p2shScript
     * @return ScriptHashAddress
     */
    public static function p2sh(ScriptInterface $p2shScript)
    {
        return new ScriptHashAddress($p2shScript->getScriptHash());
    }

    /**
     * @param WitnessProgram $wp
     * @return SegwitAddress
     */
    public static function fromWitnessProgram(WitnessProgram $wp)
    {
        return new SegwitAddress($wp);
    }

    /**
     * @param ScriptInterface $outputScript
     * @return AddressInterface
     */
    public static function fromOutputScript(ScriptInterface $outputScript)
    {
        if ($outputScript instanceof P2shScript || $outputScript instanceof WitnessScript) {
            throw new \RuntimeException("P2shScript & WitnessScript's are not accepted by fromOutputScript");
        }

        $wp = null;
        if ($outputScript->isWitness($wp)) {
            /** @var WitnessProgram $wp */
            return new SegwitAddress($wp);
        }

        $decode = (new OutputClassifier())->decode($outputScript);
        switch ($decode->getType()) {
            case ScriptType::P2PKH:
                /** @var BufferInterface $solution */
                return new PayToPubKeyHashAddress($decode->getSolution());
            case ScriptType::P2SH:
                /** @var BufferInterface $solution */
                return new ScriptHashAddress($decode->getSolution());
            default:
                throw new \RuntimeException('Script type is not associated with an address');
        }
    }

    /**
     * @param string $address
     * @param NetworkInterface $network
     * @return AddressInterface
     * @throws \BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure
     */
    public static function fromString($address, NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();

        try {
            $data = Base58::decodeCheck($address);
            $prefixByte = $data->slice(0, 1)->getHex();

            if ($prefixByte === $network->getP2shByte()) {
                return new ScriptHashAddress($data->slice(1));
            } else if ($prefixByte === $network->getAddressByte()) {
                return new PayToPubKeyHashAddress($data->slice(1));
            }
        } catch (\Exception $e) {
            // continue on for Bech32
        }

        try {
            return new SegwitAddress(SegwitBech32::decode($address, $network));
        } catch (\Exception $e) {
            // continue on
        }

        throw new \InvalidArgumentException("Address not recognized");
    }

    /**
     * @param string $address
     * @param NetworkInterface $network
     * @return bool
     * @throws \BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure
     */
    public static function isValidAddress($address, NetworkInterface $network = null)
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
    public static function getAssociatedAddress(ScriptInterface $script)
    {
        $classifier = new OutputClassifier();
        $decode = $classifier->decode($script);
        if ($decode->getType() === ScriptType::P2PK) {
            $pubKey = PublicKeyFactory::fromHex($decode->getSolution());
            return new PayToPubKeyHashAddress($pubKey->getPubKeyHash());
        } else {
            return self::fromOutputScript($script);
        }
    }
}
