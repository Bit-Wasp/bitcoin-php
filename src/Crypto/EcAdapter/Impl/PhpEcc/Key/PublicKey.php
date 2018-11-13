<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Key\PublicKeySerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\Key;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Buffertools\BufferInterface;
use Mdanter\Ecc\Crypto\Signature\Signer;
use Mdanter\Ecc\Primitives\CurveFpInterface;
use Mdanter\Ecc\Primitives\GeneratorPoint;
use Mdanter\Ecc\Primitives\PointInterface;

class PublicKey extends Key implements PublicKeyInterface, \Mdanter\Ecc\Crypto\Key\PublicKeyInterface
{
    /**
     * @var EcAdapter
     */
    private $ecAdapter;

    /**
     * @var PointInterface
     */
    private $point;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var bool
     */
    private $compressed;

    /**
     * PublicKey constructor.
     * @param EcAdapter $ecAdapter
     * @param PointInterface $point
     * @param bool $compressed
     * @param string $prefix
     */
    public function __construct(
        EcAdapter $ecAdapter,
        PointInterface $point,
        bool $compressed = false,
        string $prefix = null
    ) {
        $this->ecAdapter = $ecAdapter;
        $this->point = $point;
        $this->prefix = $prefix;
        $this->compressed = $compressed;
    }

    /**
     * @return GeneratorPoint
     */
    public function getGenerator(): GeneratorPoint
    {
        return $this->ecAdapter->getGenerator();
    }

    /**
     * @return \Mdanter\Ecc\Primitives\CurveFpInterface
     */
    public function getCurve(): CurveFpInterface
    {
        return $this->ecAdapter->getGenerator()->getCurve();
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return PointInterface
     */
    public function getPoint(): PointInterface
    {
        return $this->point;
    }

    /**
     * @param BufferInterface $msg32
     * @param SignatureInterface $signature
     * @return bool
     */
    public function verify(BufferInterface $msg32, SignatureInterface $signature): bool
    {
        $hash = gmp_init($msg32->getHex(), 16);
        $signer = new Signer($this->ecAdapter->getMath());
        return $signer->verify($this, $signature, $hash);
    }

    /**
     * @param \GMP $tweak
     * @return KeyInterface
     */
    public function tweakAdd(\GMP $tweak): KeyInterface
    {
        $offset = $this->ecAdapter->getGenerator()->mul($tweak);
        $newPoint = $this->point->add($offset);
        return new PublicKey($this->ecAdapter, $newPoint, $this->compressed);
    }

    /**
     * @param \GMP $tweak
     * @return KeyInterface
     */
    public function tweakMul(\GMP $tweak): KeyInterface
    {
        $point = $this->point->mul($tweak);
        return new PublicKey($this->ecAdapter, $point, $this->compressed);
    }

    /**
     * @param BufferInterface $publicKey
     * @return bool
     */
    public static function isCompressedOrUncompressed(BufferInterface $publicKey): bool
    {
        $vchPubKey = $publicKey->getBinary();
        if ($publicKey->getSize() < self::LENGTH_COMPRESSED) {
            return false;
        }

        if ($vchPubKey[0] === self::KEY_UNCOMPRESSED) {
            if ($publicKey->getSize() !== self::LENGTH_UNCOMPRESSED) {
                // Invalid length for uncompressed key
                return false;
            }
        } elseif (in_array($vchPubKey[0], [
            self::KEY_COMPRESSED_EVEN,
            self::KEY_COMPRESSED_ODD
        ])) {
            if ($publicKey->getSize() !== self::LENGTH_COMPRESSED) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isCompressed(): bool
    {
        return $this->compressed;
    }

    /**
     * @param PublicKey $other
     * @return bool
     */
    private function doEquals(PublicKey $other): bool
    {
        return $this->compressed === $other->compressed
            && $this->point->equals($other->point)
            && (($this->prefix === null || $other->prefix === null) || ($this->prefix === $other->prefix));
    }

    /**
     * @param PublicKeyInterface $other
     * @return bool
     */
    public function equals(PublicKeyInterface $other): bool
    {
        /** @var self $other */
        return $this->doEquals($other);
    }

    /**
     * @return BufferInterface
     */
    public function getBuffer(): BufferInterface
    {
        return (new PublicKeySerializer($this->ecAdapter))->serialize($this);
    }
}
