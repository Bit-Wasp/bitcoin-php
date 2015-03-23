<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter;

use BitWasp\Bitcoin\Key\PublicKey;
use BitWasp\Bitcoin\Math\Math;
use Mdanter\Ecc\GeneratorPoint;
use BitWasp\Bitcoin\Signature\SignatureCollection;
use BitWasp\Bitcoin\Signature\SignatureInterface;
use BitWasp\Bitcoin\Buffer;

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
                $verify = $this->verify($key, $signature, $messageHash);
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

    public function recoverYfromX($xCoord, $byte)
    {
        if (!in_array($byte, array(PublicKey::KEY_COMPRESSED_ODD, PublicKey::KEY_COMPRESSED_EVEN))) {
            throw new \RuntimeException('Incorrect byte for a public key');
        }

        $math   = $this->getMath();
        $theory = $math->getNumberTheory();
        $curve = $this->generator->getCurve();

        try {
            // x ^ 3
            $xCubed   = $math->powMod($xCoord, 3, $curve->getPrime());
            $ySquared = $math->add($xCubed, $curve->getB());

            // Calculate first root
            $root0 = $theory->squareRootModP($ySquared, $curve->getPrime());

            if ($root0 == null) {
                throw new \RuntimeException('Unable to calculate sqrt mod p');
            }

            // Depending on the byte, we expect the Y value to be even or odd.
            // We only calculate the second y root if it's needed.
            if ($byte == PublicKey::KEY_COMPRESSED_EVEN) {
                $yCoord = ($math->isEven($root0))
                    ? $root0
                    : $math->sub($curve->getPrime(), $root0);
            } else {
                $yCoord = (!$math->isEven($root0))
                    ? $root0
                    : $math->sub($curve->getPrime(), $root0);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $yCoord;
    }
}
