<?php

namespace BitWasp\Bitcoin\Address;

use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptInterface;

use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Buffertools\Buffer;
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
        $type = (new OutputClassifier())->classify($outputScript);
        $parsed = $outputScript->getScriptParser()->decode();

        if ($type === OutputClassifier::PAYTOPUBKEYHASH) {
            /** @var \BitWasp\Buffertools\BufferInterface $hash */
            $hash = $parsed[2]->getData();
            return new PayToPubKeyHashAddress($hash);
        } else if ($type === OutputClassifier::PAYTOSCRIPTHASH) {
            /** @var \BitWasp\Buffertools\BufferInterface $hash */
            $hash = $parsed[1]->getData();
            return new ScriptHashAddress($hash);
        }

        throw new \RuntimeException('Script type is not associated with an address');
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
     * @return AddressInterface
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
     * @param NetworkInterface $network
     * @return String
     * @throws \RuntimeException
     */
    public static function getAssociatedAddress(ScriptInterface $script, NetworkInterface $network = null)
    {
        $classifier = new OutputClassifier();
        $network = $network ?: Bitcoin::getNetwork();
        
        try {
            $publicKey = null;
            if ($classifier->isPayToPublicKey($script, $publicKey)) {
                /** @var BufferInterface $publicKey */
                $address = PublicKeyFactory::fromHex($publicKey)->getAddress();
            } else {
                $address = self::fromOutputScript($script);
            }

            return Base58::encodeCheck(Buffer::hex($network->getAddressByte() . $address->getHash(), 21));
        } catch (\Exception $e) {
            throw new \RuntimeException('No address associated with this script type');
        }
    }
}
