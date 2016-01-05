<?php

namespace BitWasp\Bitcoin\Key\Deterministic;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Mnemonic\Electrum\ElectrumWordListInterface;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class ElectrumKeyFactory
{

    /**
     * Pass a secret exponent (integer)
     * @param integer|string $secret
     * @param EcAdapterInterface $ecAdapter
     * @return ElectrumKey
     */
    public static function fromSecretExponent($secret, EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();

        return new ElectrumKey(
            $ecAdapter,
            PrivateKeyFactory::fromInt($secret, false, $ecAdapter)
        );
    }

    /**
     * Generate a master private key given a
     * @param BufferInterface $seed
     * @param EcAdapterInterface $ecAdapter
     * @return ElectrumKey
     */
    public static function generateMasterKey(BufferInterface $seed, EcAdapterInterface $ecAdapter = null)
    {
        // Really weird, did electrum actually hash hex string seeds?
        $binary = $oldseed = $seed->getHex();

        // Perform sha256 hash 5 times per iteration
        for ($i = 0; $i < 5*20000; $i++) {
            // Hash should return binary data
            $binary = hash('sha256', $binary . $oldseed, true);
        }

        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        // Convert binary data to hex.
        $str = new Buffer($binary, 32, $ecAdapter->getMath());

        return self::fromSecretExponent(
            $str->getInt(),
            $ecAdapter
        );
    }

    /**
     * Provide an electrum mnemonic and derive the master key
     *
     * @param $mnemonic
     * @param ElectrumWordListInterface $wordList
     * @param EcAdapterInterface $ecAdapter
     * @return ElectrumKey
     */
    public static function fromMnemonic($mnemonic, ElectrumWordListInterface $wordList = null, EcAdapterInterface $ecAdapter = null)
    {
        $ecAdapter = $ecAdapter ?: Bitcoin::getEcAdapter();
        $mnemonicConverter = MnemonicFactory::electrum($wordList, $ecAdapter);
        $entropy = $mnemonicConverter->mnemonicToEntropy($mnemonic);

        return self::generateMasterKey($entropy, $ecAdapter);
    }

    /**
     * Takes a key which is assumed to be either the master private key or master public key.
     *
     * @param KeyInterface $key
     * @param EcAdapterInterface $ecAdapter
     * @return ElectrumKey
     */
    public static function fromKey(KeyInterface $key, EcAdapterInterface $ecAdapter = null)
    {
        return new ElectrumKey(
            $ecAdapter ?: Bitcoin::getEcAdapter(),
            $key
        );
    }
}
