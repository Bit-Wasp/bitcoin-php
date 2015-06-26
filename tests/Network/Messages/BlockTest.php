<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 27/03/15
 * Time: 01:09
 */

namespace BitWasp\Bitcoin\Test\Network\Messages;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Block\BlockFactory;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\MessageFactory;
use BitWasp\Bitcoin\Network\Messages\Block;
use BitWasp\Bitcoin\Serializer\Network\NetworkMessageSerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class BlockTest extends AbstractTestCase
{
    public function testBlock()
    {
        $txHex = '01000000'.
            '01'.
            '0000000000000000000000000000000000000000000000000000000000000000FFFFFFFF'.
            '4D'.
            '04FFFF001D0104455468652054696D65732030332F4A616E2F32303039204368616E63656C6C6F72206F6E206272696E6B206F66207365636F6E64206261696C6F757420666F722062616E6B73'.
            'FFFFFFFF'.
            '01'.
            '00F2052A01000000'.
            '43'.
            '4104678AFDB0FE5548271967F1A67130B7105CD6A828E03909A67962E0EA1F61DEB649F6BC3F4CEF38C4F35504E51EC112DE5C384DF7BA0B8D578A4C702B6BF11D5FAC'.
            '00000000';

        $blockHex = '01000000'.
            '0000000000000000000000000000000000000000000000000000000000000000' .
            '3BA3EDFD7A7B12B27AC72C3E67768F617FC81BC3888A51323A9FB8AA4B1E5E4A' .
            '29AB5F49'.
            'FFFF001D'.
            '1DAC2B7C'.
            '01'.
            $txHex;

        $newBlock = BlockFactory::fromHex($blockHex);

        $factory = new MessageFactory(Bitcoin::getDefaultNetwork(), new Random());
        $block = $factory->block($newBlock);

        $this->assertEquals('block', $block->getNetworkCommand());
        $this->assertEquals($newBlock, $block->getBlock());
        $this->assertEquals($newBlock->getHex(), $block->getHex());
    }

    public function testBlockSerializer()
    {
        $parser = new NetworkMessageSerializer(Bitcoin::getDefaultNetwork());
        $txHex = '01000000'.
            '01'.
            '0000000000000000000000000000000000000000000000000000000000000000FFFFFFFF'.
            '4D'.
            '04FFFF001D0104455468652054696D65732030332F4A616E2F32303039204368616E63656C6C6F72206F6E206272696E6B206F66207365636F6E64206261696C6F757420666F722062616E6B73'.
            'FFFFFFFF'.
            '01'.
            '00F2052A01000000'.
            '43'.
            '4104678AFDB0FE5548271967F1A67130B7105CD6A828E03909A67962E0EA1F61DEB649F6BC3F4CEF38C4F35504E51EC112DE5C384DF7BA0B8D578A4C702B6BF11D5FAC'.
            '00000000';

        $blockHex = '01000000'.
            '0000000000000000000000000000000000000000000000000000000000000000' .
            '3BA3EDFD7A7B12B27AC72C3E67768F617FC81BC3888A51323A9FB8AA4B1E5E4A' .
            '29AB5F49'.
            'FFFF001D'.
            '1DAC2B7C'.
            '01'.
            $txHex;

        $newBlock = BlockFactory::fromHex($blockHex);
        $block = new Block($newBlock);

        $serialized = $block->getNetworkMessage()->getBuffer();
        $parsed = $parser->parse($serialized)->getPayload();

        $this->assertEquals($block, $parsed);
    }
}
