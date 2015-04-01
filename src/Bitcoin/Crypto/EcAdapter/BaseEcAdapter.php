<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter;

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Key\PublicKey;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Signature\CompactSignature;
use Mdanter\Ecc\GeneratorPoint;
use BitWasp\Bitcoin\Signature\SignatureCollection;
use BitWasp\Bitcoin\Signature\SignatureInterface;
use BitWasp\Buffertools\Buffer;

abstract class BaseEcAdapter implements EcAdapterInterface
{
    /**
     * @var Math
     */
    private $math;

    /**
     * @var GeneratorPoint
     */
    private $generator;

    /**
     * @param Math $math
     * @param GeneratorPoint $G
     */
    public function __construct(Math $math, GeneratorPoint $G)
    {
        $this->math = $math;
        $this->generator = $G;
    }

    /**
     * @return Math
     */
    public function getMath()
    {
        return $this->math;
    }

    /**
     * @return GeneratorPoint
     */
    public function getGenerator()
    {
        return $this->generator;
    }

    /**
     * @param Buffer $publicKey
     * @return \Mdanter\Ecc\PointInterface
     * @throws \Exception
     */
    public function publicKeyFromBuffer(Buffer $publicKey)
    {
        $size = $publicKey->getSize();

        $compressed = $size == PublicKey::LENGTH_COMPRESSED;
        $xCoord = $publicKey->slice(1, 32)->getInt();
        $yCoord = $compressed
            ? $this->recoverYfromX($xCoord, $publicKey->slice(0, 1)->getHex())
            : $publicKey->slice(33, 32)->getInt();

        return new PublicKey(
            $this,
            $this->getGenerator()
                ->getCurve()
                ->getPoint($xCoord, $yCoord),
            $compressed
        );
    }

    /**
     * @param SignatureCollection $signatures
     * @param Buffer $messageHash
     * @param \BitWasp\Bitcoin\Key\PublicKeyInterface[] $publicKeys
     * @return SignatureInterface[]
     */
    public function associateSigs(SignatureCollection $signatures, Buffer $messageHash, array $publicKeys)
    {
        $sigCount = count($signatures);
        $linked = [];

        foreach ($signatures->getSignatures() as $c => $signature) {
            foreach ($publicKeys as $key) {
                $verify = $this->verify($messageHash, $key, $signature);
                if ($verify) {
                    $linked[$key->getPubKeyHash()] = $signature;
                    if (count($linked) == $sigCount) {
                        break 2;
                    } else {
                        break;
                    }
                }
            }
        }

        return $linked;
    }

    /**
     * @param integer $xCoord
     * @param string $prefix
     * @return int|string
     * @throws \Exception
     */
    public function recoverYfromX($xCoord, $prefix)
    {
        if (!in_array($prefix, array(PublicKey::KEY_COMPRESSED_ODD, PublicKey::KEY_COMPRESSED_EVEN))) {
            throw new \RuntimeException('Incorrect byte for a public key');
        }

        $math = $this->getMath();
        $curve = $this->getGenerator()->getCurve();
        $prime = $curve->getPrime();

        try {
            // x ^ 3
            $xCubed = $math->powMod($xCoord, 3, $prime);
            $ySquared = $math->add($xCubed, $curve->getB());

            // Calculate first root
            $root0 = $math->getNumberTheory()->squareRootModP($ySquared, $prime);
            if ($root0 == null) {
                throw new \RuntimeException('Unable to calculate sqrt mod p');
            }

            // Depending on the byte, we expect the Y value to be even or odd.
            // We only calculate the second y root if it's needed.
            if ($prefix == PublicKey::KEY_COMPRESSED_EVEN) {
                $yCoord = ($math->isEven($root0))
                    ? $root0
                    : $math->sub($prime, $root0);
            } else {
                $yCoord = (!$math->isEven($root0))
                    ? $root0
                    : $math->sub($prime, $root0);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $yCoord;
    }

    /**
     * @param CompactSignature $signature
     * @param Buffer $messageHash
     * @param PayToPubKeyHashAddress $address
     * @return bool
     */
    public function verifyMessage(Buffer $messageHash, PayToPubKeyHashAddress $address, CompactSignature $signature)
    {
        $publicKey = $this->recoverCompact($messageHash, $signature);
        return ($publicKey->getAddress()->getHash() == $address->getHash());
    }
}
