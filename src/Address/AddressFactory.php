<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Buffertools\BufferInterface;

class AddressFactory
{
    /**
     * Returns a pay-to-pubkey-hash address for the given public key
     *
     * @param KeyInterface $key
     * @return PayToPubKeyHashAddress
     */
    public static function fromKey(KeyInterface $key)
    {
        return new PayToPubKeyHashAddress($key->getPubKeyHash());
    }

    /**
     * Takes the $p2shScript and generates the scriptHash address.
     *
     * @param ScriptInterface $p2shScript
     * @return ScriptHashAddress
     */
    public static function fromScript(ScriptInterface $p2shScript)
    {
        return new ScriptHashAddress($p2shScript->getScriptHash());
    }

    /**
     * @param ScriptInterface $outputScript
     * @return PayToPubKeyHashAddress|ScriptHashAddress
     */
    public static function fromOutputScript(ScriptInterface $outputScript)
    {
        $decode = (new OutputClassifier())->decode($outputScript);
        switch ($decode->getType()) {
            case OutputClassifier::PAYTOPUBKEYHASH:
                /** @var BufferInterface $solution */
                return new PayToPubKeyHashAddress($decode->getSolution());
            case OutputClassifier::PAYTOSCRIPTHASH:
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
        $data = Base58::decodeCheck($address);
        $prefixByte = $data->slice(0, 1)->getHex();

        if ($prefixByte === $network->getP2shByte()) {
            return new ScriptHashAddress($data->slice(1));
        } else if ($prefixByte === $network->getAddressByte()) {
            return new PayToPubKeyHashAddress($data->slice(1));
        } else {
            throw new \InvalidArgumentException("Invalid prefix [{$prefixByte}]");
        }
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
     * @param ScriptInterface $script
     * @return AddressInterface
     * @throws \RuntimeException
     */
    public static function getAssociatedAddress(ScriptInterface $script)
    {
        $classifier = new OutputClassifier();
        $decode = $classifier->decode($script);
        if ($decode->getType() === OutputClassifier::PAYTOPUBKEY) {
            return PublicKeyFactory::fromHex($decode->getSolution())->getAddress();
        } else {
            return self::fromOutputScript($script);
        }
    }
}
