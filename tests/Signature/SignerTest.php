<?php

namespace Bitcoin\Tests\Signature;

use Bitcoin\Exceptions\SignatureNotCanonical;
use Bitcoin\Key\PrivateKey;
use Bitcoin\Crypto\Random;
use Bitcoin\Signature\Signature;
use Bitcoin\Signature\Signer;
use Bitcoin\Signature\K\RandomK;
use Bitcoin\Util\Buffer;
use Bitcoin\Bitcoin;

/**
 * Class SignatureTest
 * @package Bitcoin
 */
class SignerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    public $sigType;

    public function __construct()
    {
        $this->sigType = 'Bitcoin\Signature\Signature';
    }

    public function testPrivateKeySign()
    {
        /**
         * This looks enough times to try and catch some of the outliers..
         * - Odd length hex strings need to be padded with one single '0' value,
         *   which happens on occasion when converting from decimal to hex.
         * - Padding also must be applied to prevent r and s from being negative.
         * Signature lengths vary with a certain probability, but the most annoying
         * thing while writing this test was cases where r / s were 31.5 bytes.
         * Should be at least 100 to catch these, but it can take a while
         */
        $math = Bitcoin::getMath();
        $G = Bitcoin::getGenerator();
        $signer = new Signer($math, $G);
        $pk = new PrivateKey('4141414141414141414141414141414141414141414141414141414141414141');

        for ($i = 0; $i < 10; $i++) {
            $buf = Random::bytes(32);
            $sig = $signer->sign($pk, $buf, new RandomK());

            $this->assertInstanceOf($this->sigType, $sig);
            $this->assertTrue(Signature::isCanonical(new Buffer($sig->serialize())));
        }
    }
}