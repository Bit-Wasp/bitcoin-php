<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Serializer\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature\CompactSignature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature\Signature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\CompactSignatureSerializerInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\CompactSignatureInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;

class CompactSignatureSerializer implements CompactSignatureSerializerInterface
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

    /**
     * @param CompactSignature $signature
     * @return Buffer
     */
    private function doSerialize(CompactSignature $signature)
    {
        $sig_t = '';
        if (!secp256k1_ecdsa_signature_serialize_compact($this->ecAdapter->getContext(), $signature->getResource(), $sig_t)) {
            throw new \RuntimeException('Secp256k1 serialize compact failure');
        }
        return new Buffer(chr((int)$signature->getFlags()) . $sig_t);
    }

    /**
     * @param CompactSignatureInterface $signature
     * @return Buffer
     */
    public function serialize(CompactSignatureInterface $signature)
    {
        /** @var CompactSignature $signature */
        return $this->doSerialize($signature);
    }

    /**
     * @param $data
     * @return Signature
     */
    public function parse($data)
    {
        $math = $this->ecAdapter->getMath();
        $buffer = (new Parser($data))->getBuffer();
        if ($buffer->getSize() !== 65) {
            throw new \RuntimeException('Compact Sig must be 65 bytes');
        }

        $sig = $buffer->slice(0, 64);
        $byte = $buffer->slice(-1)->getInt();

        $recoveryFlags = $math->sub($byte, 27);
        $isCompressed = ($math->bitwiseAnd($recoveryFlags, 4) != 0);
        $recoveryId = $recoveryFlags - ($isCompressed ? 4 : 0);

        $sig_t = '';
        if (!secp256k1_ecdsa_signature_parse_compact($this->ecAdapter->getContext(), $sig->getBinary(), $sig_t)) {
            throw new \RuntimeException('');
        }

        /** @var resource $sig_t */
        return new CompactSignature($this->ecAdapter, $sig_t, $recoveryId, $isCompressed);
    }
}
