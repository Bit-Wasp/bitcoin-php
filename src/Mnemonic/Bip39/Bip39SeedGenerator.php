<?php

namespace BitWasp\Bitcoin\Mnemonic\Bip39;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Buffertools\Buffer;

class Bip39SeedGenerator
{
    /**
     * @var Bip39Mnemonic
     */
    private $mnemonic;

    /**
     * @param Bip39Mnemonic $mnemonic
     */
    public function __construct(Bip39Mnemonic $mnemonic)
    {
        $this->mnemonic = $mnemonic;
    }

    /**
     * @param $string
     * @return Buffer
     * @throws \Exception
     */
    private function normalize($string)
    {
        if (!class_exists('Normalizer')) {
            if (mb_detect_encoding($string) == "UTF-8") {
                throw new \Exception("UTF-8 passphrase is not supported without the PECL intl extension installed.");
            } else {
                return $string;
            }
        }

        return new Buffer(\Normalizer::normalize($string, \Normalizer::FORM_KD));
    }

    /**
     * @param $mnemonic
     * @param $passphrase
     * @return \BitWasp\Buffertools\Buffer
     * @throws \Exception
     */
    public function getSeed($mnemonic, $passphrase = '')
    {
        return Hash::pbkdf2(
            'sha512',
            $this->normalize($mnemonic),
            $this->normalize("mnemonic" . $passphrase),
            2048,
            64
        );
    }
}
