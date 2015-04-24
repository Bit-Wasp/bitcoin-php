<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 24/04/15
 * Time: 11:41
 */

namespace Mnemonic\Electrum;

use BitWasp\Bitcoin\Mnemonic\Electrum\ElectrumWordList;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class ElectrumWordListTest extends AbstractTestCase
{
    public function testGetWordList()
    {
        $wl = new ElectrumWordList();
        $this->assertEquals(1626, count($wl));
        $this->assertEquals(1626, count($wl->getWords()));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testUnknownWord()
    {
        $wl = new ElectrumWordList();
        $wl->getWord('unknownword');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionOutOfRange()
    {
        $wl = new ElectrumWordList();

        $word = $wl->getIndex(1);
        $this->assertInternalType('string', $word);

        $wl->getIndex(101010101);
    }
}
