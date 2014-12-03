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

    }
} 