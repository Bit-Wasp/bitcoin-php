<?php

namespace BitWasp\Bitcoin\Key;


use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Buffertools\Buffer;

class ElectrumKeyFactory
{
    /**
     * @param Buffer $entropy
     * @param EcAdapterInterface $ecAdapter
     * @return ElectrumKey
     */
    public function generateMasterKey(Buffer $entropy, EcAdapterInterface $ecAdapter = null)
    {
        $seed = $entropy->getBinary();
        $str = $seed;

        for ($i = 0; $i < 100000; $i++) {
            $str = hash('sha256', $seed . $str, true);
        }

        $str = new Buffer($str);

        return new ElectrumKey(
            $ecAdapter ?: Bitcoin::getEcAdapter(),
            PrivateKeyFactory::fromHex($str->getHex())
        );
    }
}