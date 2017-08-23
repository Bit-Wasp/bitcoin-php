<?php

namespace BitWasp\Bitcoin\Tests;

use BitWasp\Bitcoin\SegwitBech32;
use BitWasp\Bitcoin\Bech32;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Exceptions\Bech32Exception;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use function BitWasp\Bitcoin\Script\encodeOpN;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Buffertools\BufferInterface;

class Bech32Test extends AbstractTestCase
{
    public function getBip173Fixtures()
    {
        $publicKeyHex = "0279BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798";
        $publicKey = PublicKeyFactory::fromHex($publicKeyHex);
        $publicKeyScript = ScriptFactory::scriptPubKey()->p2pk($publicKey);
        $p2wsh = WitnessProgram::v0($publicKeyScript->getWitnessScriptHash());
        $p2wpkh = WitnessProgram::v0($publicKey->getPubKeyHash());

        $bc = NetworkFactory::bitcoin();
        $tb = NetworkFactory::bitcoinTestnet();

        return [
            [
                $bc,
                'bc1qrp33g0q5c5txsp9arysrx4k6zdkfs4nce4xj0gdcccefvpysxf3qccfmv3',
                $p2wsh->getScript()->getBuffer()->getHex(),
            ],
            [
                $tb,
                'tb1qrp33g0q5c5txsp9arysrx4k6zdkfs4nce4xj0gdcccefvpysxf3q0sl5k7',
                $p2wsh->getScript()->getBuffer()->getHex(),
            ],
            [
                $bc,
                'bc1qw508d6qejxtdg4y5r3zarvary0c5xw7kv8f3t4',
                $p2wpkh->getScript()->getBuffer()->getHex(),
            ],
            [
                $tb,
                'tb1qw508d6qejxtdg4y5r3zarvary0c5xw7kxpjzsx',
                $p2wpkh->getScript()->getBuffer()->getHex(),
            ],
        ];
    }

    /**
     * @return array
     */
    public function getValidChecksumFixtures()
    {
        return [
            ["A12UEL5L"],
            ["an83characterlonghumanreadablepartthatcontainsthenumber1andtheexcludedcharactersbio1tt5tgs"],
            ["abcdef1qpzry9x8gf2tvdw0s3jn54khce6mua7lmqqqxw"],
            ["11qqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqc8247j"],
            ["split1checkupstagehandshakeupstreamerranterredcaperred2y9e3w"],
        ];
    }

    public function getInvalidAddressFixtures()
    {
        return [
            ["tc1qw508d6qejxtdg4y5r3zarvary0c5xw7kg3g4ty"],
            ["bc1qw508d6qejxtdg4y5r3zarvary0c5xw7kv8f3t5"],
            ["BC13W508D6QEJXTDG4Y5R3ZARVARY0C5XW7KN40WF2"],
            ["bc1rw5uspcuh"],
            ["bc10w508d6qejxtdg4y5r3zarvary0c5xw7kw508d6qejxtdg4y5r3zarvary0c5xw7kw5rljs90"],
            ["BC1QR508D6QEJXTDG4Y5R3ZARVARYV98GJ9P"],
            ["tb1qrp33g0q5c5txsp9arysrx4k6zdkfs4nce4xj0gdcccefvpysxf3q0sL5k7"],
            ["tb1pw508d6qejxtdg4y5r3zarqfsj6c3"],
            ["tb1qrp33g0q5c5txsp9arysrx4k6zdkfs4nce4xj0gdcccefvpysxf3pjxtptv"],
        ];
    }

    /**
     * @return array
     */
    public function getAddressVectors()
    {
        $bc = NetworkFactory::bitcoin();
        $tb = NetworkFactory::bitcoinTestnet();

        return [
            [
                $bc,
                "BC1QW508D6QEJXTDG4Y5R3ZARVARY0C5XW7KV8F3T4",
                "0014751e76e8199196d454941c45d1b3a323f1433bd6"
            ],
            [
                $tb,
                "tb1qrp33g0q5c5txsp9arysrx4k6zdkfs4nce4xj0gdcccefvpysxf3q0sl5k7",
                "00201863143c14c5166804bd19203356da136c985678cd4d27a1b8c6329604903262"
            ],
            [
                $bc,
                "bc1pw508d6qejxtdg4y5r3zarvary0c5xw7kw508d6qejxtdg4y5r3zarvary0c5xw7k7grplx",
                "5128751e76e8199196d454941c45d1b3a323f1433bd6751e76e8199196d454941c45d1b3a323f1433bd6"
            ],
            [
                $bc,
                "BC1SW50QA3JX3S",
                "6002751e"
            ],
            [
                $bc,
                "bc1zw508d6qejxtdg4y5r3zarvaryvg6kdaj",
                "5210751e76e8199196d454941c45d1b3a323"
            ],
            [
                $tb,
                "tb1qqqqqp399et2xygdj5xreqhjjvcmzhxw4aywxecjdzew6hylgvsesrxh6hy",
                "0020000000c4a5cad46221b2a187905e5266362b99d5e91c6ce24d165dab93e86433"
            ],
        ];
    }

    /**
     * @param int $version
     * @param BufferInterface $program
     * @return \BitWasp\Bitcoin\Script\ScriptInterface
     */
    public function makeSegwitScript($version, BufferInterface $program)
    {
        return ScriptFactory::sequence([encodeOpN($version), $program]);
    }

    /**
     * @param NetworkInterface $network
     * @param string $bech32
     * @param string $scriptHex
     * @dataProvider getBip173Fixtures
     */
    public function test173Fixtures(NetworkInterface $network, $bech32, $scriptHex)
    {
        $wp = SegwitBech32::decode($bech32, $network);
        $this->assertEquals($scriptHex, $wp->getScript()->getHex());
    }

    /**
     * @param string $test
     * @dataProvider getValidChecksumFixtures
     */
    public function testValidChecksum($test)
    {
        Bech32::decode($test);

        $pos = strrpos($test, "1");
        $test = substr($test, 0, $pos+1) . chr(ord($test[$pos+1])^1) . substr($test, $pos+2);

        try {
            Bech32::decode($test);
            $threw = false;
        } catch (\Exception $e) {
            $threw = true;
        }

        $this->assertTrue($threw);
    }

    /**
     * @param NetworkInterface $network
     * @param string $bech32
     * @param string $hexScript
     * @dataProvider getAddressVectors
     */
    public function testValidAddress(NetworkInterface $network, $bech32, $hexScript)
    {
        $wp = SegwitBech32::decode($bech32, $network);
        $this->assertEquals($hexScript, $wp->getScript()->getHex());

        $addr = SegwitBech32::encode($wp, $network);
        $this->assertEquals(strtolower($bech32), strtolower($addr));
    }

    /**
     * @param string $bech32
     * @dataProvider getInvalidAddressFixtures
     */
    public function testInvalidAddress($bech32)
    {
        try {
            SegwitBech32::decode($bech32, NetworkFactory::bitcoin());
            $threw = false;
        } catch (\Exception $e) {
            $threw = true;
        }

        $this->assertTrue($threw, "expected mainnet hrp to fail");

        try {
            SegwitBech32::decode($bech32, NetworkFactory::bitcoinTestnet());
            $threw = false;
        } catch (\Exception $e) {
            $threw = true;
        }

        $this->assertTrue($threw, "expected testnet hrp to fail");
    }

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\Bech32Exception
     * @expectedExceptionMessage Invalid value for convert bits
     */
    public function testInvalidCharValue()
    {
        Bech32::convertBits([2 << 29], 1, 8, 5, true);
    }

    public function getFailedDecodeFixtures()
    {
        return [
            [str_pad("", 91, "A"), "Bech32 string cannot exceed 90 characters in length"],
            ["\x10", "Out of range character in bech32 string"],
            ["aB", "Data contains mixture of higher/lower case characters"],
            ["bcbcbc1bc", "Invalid location for `1` character"],
            ["bc1qw508d6qejxtdg4y5r3zarvary0c5xw7kv8f3t5", "Invalid bech32 checksum"],
        ];
    }

    /**
     * @param string $bech32
     * @dataProvider getFailedDecodeFixtures
     */
    public function testDecodeFails($bech32, $exceptionMsg)
    {
        $this->expectException(Bech32Exception::class);
        $this->expectExceptionMessage($exceptionMsg);
        Bech32::decode($bech32);
    }

    public function testSegwitAddrDefaultNetwork()
    {
        $bech32 = strtolower("BC1QW508D6QEJXTDG4Y5R3ZARVARY0C5XW7KV8F3T4");
        $p2wpkh = ScriptFactory::fromHex("0014751e76e8199196d454941c45d1b3a323f1433bd6");

        $witnessProgram = null;
        $this->assertTrue($p2wpkh->isWitness($witnessProgram));
        /** @var WitnessProgram $witnessProgram */
        $this->assertInstanceOf(WitnessProgram::class, $witnessProgram);

        $this->assertEquals(NetworkFactory::bitcoin()->getSegwitBech32Prefix(), Bitcoin::getDefaultNetwork()->getSegwitBech32Prefix());

        $encodedDefault = SegwitBech32::encode($witnessProgram);
        $encodedBitcoin = SegwitBech32::encode($witnessProgram, NetworkFactory::bitcoin());

        $this->assertEquals($bech32, $encodedDefault);
        $this->assertEquals($bech32, $encodedBitcoin);

        $testnet = SegwitBech32::encode($witnessProgram, NetworkFactory::bitcoinTestnet());
        $decodedTestnet = SegwitBech32::decode($testnet, NetworkFactory::bitcoinTestnet());

        $this->assertEquals($decodedTestnet->getVersion(), $witnessProgram->getVersion());
        $this->assertTrue($decodedTestnet->getProgram()->equals($witnessProgram->getProgram()));
    }
}
