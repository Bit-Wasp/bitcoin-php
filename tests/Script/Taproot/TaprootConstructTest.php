<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Script\Taproot;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\XOnlyPublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\XOnlyPublicKeySerializerInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use function BitWasp\Bitcoin\Script\Taproot\hashTapBranch;
use function BitWasp\Bitcoin\Script\Taproot\taprootConstruct;
use function BitWasp\Bitcoin\Script\Taproot\hashTapLeaf;
use function BitWasp\Bitcoin\Script\Taproot\taprootTreeHelper;
use const BitWasp\Bitcoin\Script\Interpreter\TAPROOT_CONTROL_BASE_SIZE;
use const BitWasp\Bitcoin\Script\Interpreter\TAPROOT_CONTROL_BRANCH_SIZE;
use const BitWasp\Bitcoin\Script\Interpreter\TAPROOT_CONTROL_MAX_SIZE;
use const BitWasp\Bitcoin\Script\Interpreter\TAPROOT_LEAF_MASK;
use const BitWasp\Bitcoin\Script\Interpreter\TAPROOT_LEAF_TAPSCRIPT;

class TaprootConstructTest extends AbstractTestCase
{
    /**
     * Ensure types are in order for return value of taprootConstruct
     * @param array $list
     * @param int $scriptCount
     */
    private function verifyConstructList(array $list, int $scriptCount)
    {
        $this->assertCount(4, $list, 'result should have 4 items in list');
        $this->assertInstanceOf(ScriptInterface::class, $list[0]);
        if ($scriptCount > 0) {
            $this->assertInstanceOf(BufferInterface::class, $list[1], 'expecting tweak if scripts set');
        } else {
            $this->assertNull($list[1], 'no tweak if there are no scripts in tree');
        }

        $this->assertInternalType('array', $list[2], 'scripts should always be an array');
        if ($scriptCount > 0) {
            $this->assertCount($scriptCount, $list[2]);
            foreach ($list[2] as $script) {
                $this->assertInstanceOf(ScriptInterface::class, $script);
            }
        } else {
            $this->assertEmpty($list[2], 'scripts should be empty when called with empty list');
        }

        $this->assertInternalType('array', $list[3], 'control should always be an array');
        if ($scriptCount > 0) {
            $this->assertCount($scriptCount, $list[3]);
            foreach ($list[3] as $control) {
                $this->assertInternalType('string', $control);
                // check min
                $this->assertTrue(strlen($control) >= TAPROOT_CONTROL_BASE_SIZE);
                // check max
                $this->assertTrue(strlen($control) <= TAPROOT_CONTROL_MAX_SIZE);
                // check size-base evenly divides TAPROOT_CONTROL_BRANCH_SIZE
                $this->assertEquals(0, (strlen($control) - TAPROOT_CONTROL_BASE_SIZE) % TAPROOT_CONTROL_BRANCH_SIZE);
            }
        } else {
            $this->assertEmpty($list[3], 'control should be empty when called with empty list');
        }
    }

    /**
     * Verifies scriptPubKey by checking:
     *  - witness version == 1
     *  - witness program == xonly if no tweak, xonly.tweakAdd(tweak) otherwise
     * Returns the external key
     * @param ScriptInterface $scriptPubKey
     * @param XOnlyPublicKeyInterface $xonly
     * @param BufferInterface|null $tweak
     * @return XOnlyPublicKeyInterface
     */
    private function verifyTaprootWitnessProgram(ScriptInterface $scriptPubKey, XOnlyPublicKeyInterface $xonly, BufferInterface $tweak = null): XOnlyPublicKeyInterface
    {
        $wp = null;
        $this->assertTrue($scriptPubKey->isWitness($wp));
        /** @var WitnessProgram $wp */
        $this->assertEquals(1, $wp->getVersion());
        $outputKey = $xonly;
        if ($tweak !== null) {
            $outputKey = $outputKey->tweakAdd($tweak);
        }
        $this->assertEquals($wp->getProgram()->getHex(), $outputKey->getHex());
        return $outputKey;
    }

    /**
     * If no scripts are passed, then
     *   - externalKey == internalKey
     *   - tweak is null
     *   - scripts is empty array
     *   - control is empty array
     *
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $adapter
     * @throws \Exception
     */
    public function testNoScriptsIsSimplyInternalKey(EcAdapterInterface $adapter)
    {
        $xonlyBytes = Buffer::hex('f44bc3e92e304464d33664cdb5ed75c204cb2786a40d4882551a66b0065faa11');
        /** @var XOnlyPublicKeySerializerInterface $xonlySerializer */
        $xonlySerializer = EcSerializer::getSerializer(XOnlyPublicKeySerializerInterface::class, true, $adapter);
        $xonly = $xonlySerializer->parse($xonlyBytes);
        $wp = null;

        $tree = [];

        /** @var ScriptInterface $scriptPubKey */
        $ret = taprootConstruct($xonly, $tree);
        $this->verifyConstructList($ret, 0);
        list ($scriptPubKey, $tweak, $scripts, $control) = $ret;

        // scriptPubKey
        $this->verifyTaprootWitnessProgram($scriptPubKey, $xonly, $tweak);
    }

    /**
     * Tests taprootConstruct with 1 script, and checks control per below
     *
     *   [leaf1]
     *      |
     *
     * leaf         control
     * 1            leafVersion+internal+'<empty>'
     *
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $adapter
     * @throws \Exception
     */
    public function testSingleScript(EcAdapterInterface $adapter)
    {
        $xonlyBytes = Buffer::hex('3cb5bdfd40d1f7bc059216b2db1708f876311be1c08dfe68b553c1c99084ce88');
        /** @var XOnlyPublicKeySerializerInterface $xonlySerializer */
        $xonlySerializer = EcSerializer::getSerializer(XOnlyPublicKeySerializerInterface::class, true, $adapter);
        $xonly = $xonlySerializer->parse($xonlyBytes);
        $wp = null;

        $p2pkh1 = ScriptFactory::scriptPubKey()->p2pkh(new Buffer(str_repeat("A", 20)));
        $tree = [[TAPROOT_LEAF_TAPSCRIPT, $p2pkh1]];

        /** @var ScriptInterface $scriptPubKey */
        $ret = taprootConstruct($xonly, $tree);
        $this->verifyConstructList($ret, 1);
        list ($scriptPubKey, $tweak, $scripts, $control) = $ret;

        // scriptPubKey
        $outputKey = $this->verifyTaprootWitnessProgram($scriptPubKey, $xonly, $tweak);

        // tweak
        /** @var BufferInterface $hash */
        list (, $hash) = taprootTreeHelper($tree);
        $tweakCheck = Hash::taggedSha256("TapTweak", new Buffer($xonly->getBinary() . $hash->getBinary()));
        $this->assertEquals($tweakCheck->getHex(), $tweak->getHex());

        // scripts
        $this->assertEquals($p2pkh1->getHex(), $scripts[0]->getHex());

        // control
        // no extra hashes as first level deep
        $expectControl = chr((TAPROOT_LEAF_TAPSCRIPT & TAPROOT_LEAF_MASK) + ($outputKey->hasSquareY() ? 0 : 1)) . $xonly->getBinary() . '';
        $this->assertEquals($expectControl, $control[0]);
    }

    /**
     * Tests taprootConstruct with 2 scripts, and checks control per below
     *
     *   [leaf1] [leaf2]
     *      |_______|
     *          |
     *
     * leaf         control
     * 1            leafVersion+internal+hashTapLeaf2
     * 2            leafVersion+internal+hashTapLeaf1
     *
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $adapter
     * @throws \Exception
     */
    public function testTwoScripts(EcAdapterInterface $adapter)
    {
        $xonlyBytes = Buffer::hex('3b39617a6d966e9728ee0853e28fc9a22a995ef6b12c1eeb45047774f614290c');
        /** @var XOnlyPublicKeySerializerInterface $xonlySerializer */
        $xonlySerializer = EcSerializer::getSerializer(XOnlyPublicKeySerializerInterface::class, true, $adapter);
        $xonly = $xonlySerializer->parse($xonlyBytes);
        $wp = null;

        $p2pkh1 = ScriptFactory::scriptPubKey()->p2pkh(new Buffer(str_repeat("A", 20)));
        $p2pkh2 = ScriptFactory::scriptPubKey()->p2pkh(new Buffer(str_repeat("B", 20)));
        $tree = [
            [TAPROOT_LEAF_TAPSCRIPT, $p2pkh1,],
            [TAPROOT_LEAF_TAPSCRIPT, $p2pkh2,],
        ];

        /** @var ScriptInterface $scriptPubKey */
        $ret = taprootConstruct($xonly, $tree);

        $this->verifyConstructList($ret, 2);
        list ($scriptPubKey, $tweak, $scripts, $control) = $ret;

        // scriptPubKey
        $outputKey = $this->verifyTaprootWitnessProgram($scriptPubKey, $xonly, $tweak);

        // tweak
        /** @var BufferInterface $hash */
        list (, $hash) = taprootTreeHelper($tree);
        $tweakCheck = Hash::taggedSha256("TapTweak", new Buffer($xonly->getBinary() . $hash->getBinary()));
        $this->assertEquals($tweakCheck->getHex(), $tweak->getHex());

        // scripts
        $this->assertEquals($p2pkh1->getHex(), $scripts[0]->getHex());
        $this->assertEquals($p2pkh2->getHex(), $scripts[1]->getHex());

        // control
        // no extra hashes as first level deep
        $leaf1 = hashTapLeaf(TAPROOT_LEAF_TAPSCRIPT, $scripts[0]->getBuffer());
        $leaf2 = hashTapLeaf(TAPROOT_LEAF_TAPSCRIPT, $scripts[1]->getBuffer());

        $controlBase = chr((TAPROOT_LEAF_TAPSCRIPT & TAPROOT_LEAF_MASK) + ($outputKey->hasSquareY() ? 0 : 1)) . $xonly->getBinary();
        $this->assertEquals($controlBase . $leaf2->getBinary(), $control[0]);
        $this->assertEquals($controlBase . $leaf1->getBinary(), $control[1]);
    }

    /**
     * Tests taprootConstruct with 3 scripts, and checks control per below
     *
     *   [leaf1] [leaf2]
     *      |_______|   [leaf3]
     *          |_________|
     *              |
     *
     * leaf         control
     * 1            leafVersion+internal+hashTapLeaf2+hashTapLeaf3
     * 2            leafVersion+internal+hashTapLeaf1+hashTapLeaf3
     * 3            leafVersion+internal+hashTapBranch(hashTapLeaf1,hashTapLeaf2)
     *
     * @dataProvider getEcAdapters
     * @param EcAdapterInterface $adapter
     * @throws \Exception
     */
    public function testThreeScripts(EcAdapterInterface $adapter)
    {
        $xonlyBytes = Buffer::hex('3b39617a6d966e9728ee0853e28fc9a22a995ef6b12c1eeb45047774f614290c');
        /** @var XOnlyPublicKeySerializerInterface $xonlySerializer */
        $xonlySerializer = EcSerializer::getSerializer(XOnlyPublicKeySerializerInterface::class, true, $adapter);
        $xonly = $xonlySerializer->parse($xonlyBytes);
        $wp = null;

        $p2pkh1 = ScriptFactory::scriptPubKey()->p2pkh(new Buffer(str_repeat("A", 20)));
        $p2pkh2 = ScriptFactory::scriptPubKey()->p2pkh(new Buffer(str_repeat("B", 20)));
        $p2pkh3 = ScriptFactory::scriptPubKey()->p2pkh(new Buffer(str_repeat("C", 20)));

        $tree = [
            [
                [TAPROOT_LEAF_TAPSCRIPT, $p2pkh1,],
                [TAPROOT_LEAF_TAPSCRIPT, $p2pkh2,],
            ],
            [TAPROOT_LEAF_TAPSCRIPT, $p2pkh3,],
        ];

        /** @var ScriptInterface $scriptPubKey */
        $ret = taprootConstruct($xonly, $tree);

        $this->verifyConstructList($ret, 3);
        list ($scriptPubKey, $tweak, $scripts, $control) = $ret;

        // scriptPubKey
        $outputKey = $this->verifyTaprootWitnessProgram($scriptPubKey, $xonly, $tweak);

        // tweak
        /** @var BufferInterface $hash */
        list (, $hash) = taprootTreeHelper($tree);
        $tweakCheck = Hash::taggedSha256("TapTweak", new Buffer($xonly->getBinary() . $hash->getBinary()));
        $this->assertEquals($tweakCheck->getHex(), $tweak->getHex());

        // scripts
        $this->assertEquals($p2pkh1->getHex(), $scripts[0]->getHex());
        $this->assertEquals($p2pkh2->getHex(), $scripts[1]->getHex());
        $this->assertEquals($p2pkh3->getHex(), $scripts[2]->getHex());

        // control
        // no extra hashes as first level deep
        $leaf1 = hashTapLeaf(TAPROOT_LEAF_TAPSCRIPT, $scripts[0]->getBuffer());
        $leaf2 = hashTapLeaf(TAPROOT_LEAF_TAPSCRIPT, $scripts[1]->getBuffer());
        $leaf3 = hashTapLeaf(TAPROOT_LEAF_TAPSCRIPT, $scripts[2]->getBuffer());
        $branch12 = hashTapBranch($leaf1, $leaf2);

        $controlBase = chr((TAPROOT_LEAF_TAPSCRIPT & TAPROOT_LEAF_MASK) + ($outputKey->hasSquareY() ? 0 : 1)) . $xonly->getBinary();
        $this->assertEquals($controlBase . $leaf2->getBinary() . $leaf3->getBinary(), $control[0]);
        $this->assertEquals($controlBase . $leaf1->getBinary() . $leaf3->getBinary(), $control[1]);
        $this->assertEquals($controlBase . $branch12->getBinary(), $control[2]);
    }
}
