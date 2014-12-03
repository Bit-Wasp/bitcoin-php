<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 15/11/14
 * Time: 05:15
 */

namespace Bitcoin;


class TransactionTest extends \PHPUnit_Framework_TestCase
{
    protected $network = null;
    protected $transaction = null;

    public function __construct()
    {
        $this->network = new Network('00', '05', '80');
    }

    public function setUp()
    {
        $this->transaction = new Transaction($this->network);
    }

    public function testGetNetwork()
    {
        $this->assertSame($this->transaction->getNetwork(), $this->network);
    }

    public function testGetVersionEmpty()
    {
        $this->assertNull($this->transaction->getVersion());
    }

    /**
     * @depends testGetVersionEmpty
     */
    public function testSetVersion()
    {
        $this->transaction->setVersion('1');
        $this->assertSame($this->transaction->getVersion(), '1');
    }
} 