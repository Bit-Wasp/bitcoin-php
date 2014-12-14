<?php

namespace {
    // This allow us to configure the behavior of the "global mock"
    $mockOpensslPseudoRandomBytes = false;
}

namespace Bitcoin\Util\Random {
    function openssl_pseudo_random_bytes() {
        global $mockOpensslPseudoRandomBytes;
        if (isset($mockOpensslPseudoRandomBytes) && $mockOpensslPseudoRandomBytes == true) {
            return false;
        } else {
            return call_user_func_array('\openssl_pseudo_random_bytes', func_get_args());
        }
    }
}

namespace Bitcoin\Tests\Util;

use Bitcoin\Util\Random;

class Random extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        global $mockOpensslPseudoRandomBytes;
        $mockOpensslPseudoRandomBytes = false;
    }

    /**
     *
     */
    public function __construct()
    {
        $this->sigType = 'Bitcoin\Signature\Signature';
    }

    public function setUp()
    {
        $this->sig = null;
    }
}