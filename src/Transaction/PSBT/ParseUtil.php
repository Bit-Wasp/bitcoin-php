<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\PSBT;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
use BitWasp\Bitcoin\Exceptions\InvalidPSBTException;
use BitWasp\Buffertools\BufferInterface;

trait ParseUtil
{
    private static function parsePublicKeyKey(BufferInterface $key): BufferInterface
    {
        $keySize = $key->getSize();
        if ($keySize !== PublicKey::LENGTH_COMPRESSED + 1 && $keySize !== PublicKey::LENGTH_UNCOMPRESSED + 1) {
            throw new InvalidPSBTException("Invalid key length");
        }
        $pubKey = $key->slice(1);
        if (!PublicKey::isCompressedOrUncompressed($pubKey)) {
            throw new InvalidPSBTException("Invalid public key encoding");
        }
        return $pubKey;
    }

    /**
     * Returns array[fpr(int), path(int[])]
     *
     * @param BufferInterface $value
     * @return array
     * @throws InvalidPSBTException
     */
    private static function parseBip32DerivationValue(BufferInterface $value): array
    {
        $len = $value->getSize();
        if ($len % 4 !== 0 || $len === 0) {
            throw new InvalidPSBTException("Invalid length for BIP32 derivation");
        }

        $pieces = $len / 4;
        $path = unpack("N{$pieces}", $value->getBinary());
        $fpr = array_shift($path);
        return [$fpr, $path];
    }
}
