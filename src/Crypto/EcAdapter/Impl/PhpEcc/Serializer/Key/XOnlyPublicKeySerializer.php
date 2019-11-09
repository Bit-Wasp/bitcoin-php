<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\XOnlyPublicKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\XOnlyPublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\XOnlyPublicKeySerializerInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use Mdanter\Ecc\Exception\SquareRootException;
use Mdanter\Ecc\Math\NumberTheory;
use Mdanter\Ecc\Primitives\Point;
use Mdanter\Ecc\Primitives\PointInterface;

class XOnlyPublicKeySerializer implements XOnlyPublicKeySerializerInterface
{
    /**
     * @var EcAdapter
     */
    private $ecAdapter;

    /**
     * @param EcAdapter $ecAdapter
     */
    public function __construct(EcAdapter $ecAdapter)
    {
        $this->ecAdapter = $ecAdapter;
    }

    private function doSerialize(XOnlyPublicKey $publicKey): BufferInterface
    {
        $x = $publicKey->getPoint()->getX();
        return Buffer::int(gmp_strval($x), 32);
    }

    /**
     * @param XOnlyPublicKeyInterface $publicKey
     * @return BufferInterface
     */
    public function serialize(XOnlyPublicKeyInterface $publicKey): BufferInterface
    {
        return $this->doSerialize($publicKey);
    }

    private function liftX(\GMP $x, PointInterface &$point = null): bool
    {
        $generator = $this->ecAdapter->getGenerator();
        $curve = $generator->getCurve();
        $xCubed = gmp_powm($x, 3, $curve->getPrime());
        $v = gmp_add($xCubed, gmp_add(
            gmp_mul($curve->getA(), $x),
            $curve->getB()
        ));
        $math = $this->ecAdapter->getMath();
        $nt = new NumberTheory($math);
        try {
            $y = $nt->squareRootModP($v, $curve->getPrime());
            $point = new Point($math, $curve, $x, $y, $generator->getOrder());
            return true;
        } catch (SquareRootException $e) {
            return false;
        }
    }

    /**
     * @param BufferInterface $buffer
     * @return XOnlyPublicKeyInterface
     */
    public function parse(BufferInterface $buffer): XOnlyPublicKeyInterface
    {
        if ($buffer->getSize() !== 32) {
            throw new \RuntimeException("incorrect size");
        }
        $x = $buffer->getGmp();
        $point = null;
        // todo: review, might not need this
        if (!$this->liftX($x, $point)) {
            throw new \RuntimeException("No square root for this point");
        }
        return new XOnlyPublicKey($this->ecAdapter, $point, true);
    }
}
