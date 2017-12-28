<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Signature\Signature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\DerSignatureSerializerInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Template;
use BitWasp\Buffertools\TemplateFactory;

class DerSignatureSerializer implements DerSignatureSerializerInterface
{
    /**
     * @var EcAdapter
     */
    private $ecAdapter;

    /**
     * @param EcAdapter $adapter
     */
    public function __construct(EcAdapter $adapter)
    {
        $this->ecAdapter = $adapter;
    }

    /**
     * @return EcAdapterInterface
     */
    public function getEcAdapter(): EcAdapterInterface
    {
        return $this->ecAdapter;
    }

    /**
     * @return Template
     */
    private function getInnerTemplate(): Template
    {
        return (new TemplateFactory())
            ->uint8()
            ->varstring()
            ->uint8()
            ->varstring()
            ->getTemplate();
    }

    /**
     * @return Template
     */
    private function getOuterTemplate(): Template
    {
        return (new TemplateFactory())
            ->uint8()
            ->varstring()
            ->getTemplate();
    }

    /**
     * @param SignatureInterface|\Mdanter\Ecc\Crypto\Signature\Signature $signature
     * @return BufferInterface
     */
    public function serialize(SignatureInterface $signature): BufferInterface
    {
        $math = $this->ecAdapter->getMath();

        // Ensure that the R and S hex's are of even length
        $rBin = $math->intToString($signature->getR());
        $sBin = $math->intToString($signature->getS());

        // Pad R and S if their highest bit is flipped, ie,
        // they are negative.
        $rt = $rBin[0] & pack('H*', '80');
        if (ord($rt) === 128) {
            $rBin = pack('H*', '00') . $rBin;
        }

        $st = $sBin[0] & pack('H*', '80');
        if (ord($st) === 128) {
            $sBin = pack('H*', '00') . $sBin;
        }

        return $this->getOuterTemplate()->write([
            0x30,
            $this->getInnerTemplate()->write([
                0x02,
                new Buffer($rBin, null),
                0x02,
                new Buffer($sBin, null)
            ])
        ]);
    }

    /**
     * @param Parser $parser
     * @return SignatureInterface
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser $parser): SignatureInterface
    {
        try {
            list (, $inner) = $this->getOuterTemplate()->parse($parser);
            list (, $r, , $s) = $this->getInnerTemplate()->parse(new Parser($inner));
            /** @var Buffer $r */
            /** @var Buffer $s */

            return new Signature(
                $this->ecAdapter,
                $r->getGmp(),
                $s->getGmp()
            );
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract full signature from parser');
        }
    }

    /**
     * @param BufferInterface $derSignature
     * @return SignatureInterface
     * @throws ParserOutOfRange
     */
    public function parse(BufferInterface $derSignature): SignatureInterface
    {
        return $this->fromParser(new Parser($derSignature));
    }
}
