<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Serializer\Signature;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Signature\CompactSignature;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\CompactSignatureSerializerInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\CompactSignatureInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
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
     * @return BufferInterface
     */
    private function doSerialize(CompactSignature $signature)
    {
        $sig_t = '';
        $recid = '';
        if (!secp256k1_ecdsa_recoverable_signature_serialize_compact($this->ecAdapter->getContext(), $signature->getResource(), $sig_t, $recid)) {
            throw new \RuntimeException('Secp256k1 serialize compact failure');
        }

        return new Buffer(chr((int)$signature->getFlags()) . $sig_t, 65, $this->ecAdapter->getMath());
    }

    /**
     * @param CompactSignatureInterface $signature
     * @return BufferInterface
     */
    public function serialize(CompactSignatureInterface $signature)
    {
        /** @var CompactSignature $signature */
        return $this->doSerialize($signature);
    }

    /**
     * @param string|BufferInterface $data
     * @return CompactSignature
     */
    public function parse($data)
    {
        $math = $this->ecAdapter->getMath();
        $buffer = (new Parser($data, $math))->getBuffer();

        if ($buffer->getSize() !== 65) {
            throw new \RuntimeException('Compact Sig must be 65 bytes');
        }

        $byte = (int) $buffer->slice(0, 1)->getInt();
        $sig = $buffer->slice(1, 64);

        $recoveryFlags = $byte - 27;
        if ($recoveryFlags > 7) {
            throw new \RuntimeException('Invalid signature type');
        }

        $isCompressed = $math->cmp($math->bitwiseAnd(gmp_init($recoveryFlags), gmp_init(4)), gmp_init(0)) !== 0;
        $recoveryId = $recoveryFlags - ($isCompressed ? 4 : 0);

        $sig_t = '';
        /** @var resource $sig_t */
        if (!secp256k1_ecdsa_recoverable_signature_parse_compact($this->ecAdapter->getContext(), $sig_t, $sig->getBinary(), $recoveryId)) {
            throw new \RuntimeException('Unable to parse compact signature');
        }

        return new CompactSignature($this->ecAdapter, $sig_t, $recoveryId, $isCompressed);
    }
}
