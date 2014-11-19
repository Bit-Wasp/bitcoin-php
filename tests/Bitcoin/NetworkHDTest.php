<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 15/11/14
 * Time: 17:17
 */

namespace Bitcoin;


class NetworkHDTest extends \PHPUnit_Framework_TestCase
{
    protected $networkhd;

    public function setUp()
    {
        $this->networkhd = new NetworkHD('00','05','80');
    }

    public function testDefaults()
    {
        $this->assertSame('00', $this->networkhd->getAddressByte());
        $this->assertSame('05', $this->networkhd->getP2shByte());
        $this->assertSame('80', $this->networkhd->getPrivByte());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage No HD xpub byte was set
     */
    public function testGetHDPubByteException()
    {
        $this->networkhd->getHDPubByte();
    }

    /**
     * @depends testGetHDPubByteException
     */
    public function testSetHDPubByte()
    {
        $this->networkhd->setHDPubByte('0488B21E');
        $this->assertSame('0488B21E', $this->networkhd->getHDPubByte());
    }

    public function testSetHDPubByteException()
    {
        try {
            $this->networkhd->setHDPubByte('');
            
        } catch (\Exception $e) {

        }
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage No HD xpriv byte was set
     */
    public function testGetHDPrivByteException()
    {
        $this->networkhd->getHDPrivByte();
    }

    /**
     * @depends testGetHDPrivByteException
     */
    public function testGetHDPrivByte()
    {
        $this->networkhd->setHDPrivByte('0488B21E');
        $this->assertSame('0488B21E', $this->networkhd->getHDPrivByte());
    }

} 