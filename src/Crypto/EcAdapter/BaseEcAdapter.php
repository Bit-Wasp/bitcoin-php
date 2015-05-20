<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter;

use BitWasp\Bitcoin\Key\PublicKey;
use BitWasp\Bitcoin\Math\Math;
use Mdanter\Ecc\Primitives\GeneratorPoint;
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
     * @return string
     */
    public function halfOrder()
    {
        return $this->getMath()->rightShift($this->getGenerator()->getOrder(), 1);
    }

    /**
     * @param int|string $int
     * @param int|string $max
     * @return bool
     */
    public function checkInt($int, $max)
    {
        $math = $this->getMath();

        return $math->cmp($int, $max) < 0
        && $math->cmp($int, 0) !== 0;
    }

    /**
     * @param $element
     * @param bool $half
     * @return bool
     */
    public function validateSignatureElement($element, $half = false)
    {
        return $this->checkInt(
            $element,
            $half
            ? $this->halfOrder()
            : $this->getGenerator()->getOrder()
        );
    }

    /**
     * @param Buffer $publicKey
     * @return \Mdanter\Ecc\Primitives\PointInterface
     * @throws \Exception
     */
    public function publicKeyFromBuffer(Buffer $publicKey)
    {
        $compressed = $publicKey->getSize() == PublicKey::LENGTH_COMPRESSED;
        $xCoord = $publicKey->slice(1, 32)->getInt();

        return new PublicKey(
            $this,
            $this->getGenerator()
                ->getCurve()
                ->getPoint(
                    $xCoord,
                    $compressed
                    ? $this->recoverYfromX($xCoord, $publicKey->slice(0, 1)->getHex())
                    : $publicKey->slice(33, 32)->getInt()
                ),
            $compressed
        );
    }

    /**
     * @param array $signatures
     * @param Buffer $messageHash
     * @param \BitWasp\Bitcoin\Key\PublicKeyInterface[] $publicKeys
     * @return array
     */
    public function associateSigs(array $signatures, Buffer $messageHash, array $publicKeys)
    {
        $sigCount = count($signatures);
        $linked = [];

        foreach ($signatures as $c => $signature) {
            foreach ($publicKeys as $key) {
                $verify = $this->verify($messageHash, $key, $signature);
                if ($verify) {
                    $linked[$key->getPubKeyHash()->getHex()][] = $signature;
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

        // Calculate first root
        $root0 = $math->getNumberTheory()->squareRootModP(
            $math->add(
                $math->powMod(
                    $xCoord,
                    3,
                    $prime
                ),
                $curve->getB()
            ),
            $prime
        );

        // Depending on the byte, we expect the Y value to be even or odd.
        // We only calculate the second y root if it's needed.
        return (($prefix == PublicKey::KEY_COMPRESSED_EVEN) == $math->isEven($root0))
            ? $root0
            : $math->sub($prime, $root0);
    }
}
