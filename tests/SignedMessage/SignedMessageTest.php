<?php

namespace BitWasp\Bitcoin\Tests\SignedMessage;


use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Serializer\MessageSigner\SignedMessageSerializer;
use BitWasp\Bitcoin\Serializer\Signature\CompactSignatureSerializer;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class SignedMessage extends AbstractTestCase
{
    public function sampleMessage()
    {
        return
            [
                'hi',
                'n2Z2DFCxG6vktyX1MFkKAQPQFsrmniGKj5',
                '-----BEGIN BITCOIN SIGNED MESSAGE-----
hi
-----BEGIN SIGNATURE-----
IBpGR29vEbbl4kmpK0fcDsT75GPeH2dg5O199D3iIkS3VcDoQahJMGJEDozXot8JGULWjN9Llq79aF+FogOoz/M=
-----END BITCOIN SIGNED MESSAGE-----'
            ];
    }

    public function testParsesMessage()
    {
        list ($message, $address, $content) = $this->sampleMessage();
        $serializer = new SignedMessageSerializer(new CompactSignatureSerializer(Bitcoin::getMath()));
        $signed = $serializer->parse($content);

        $this->assertSame($message, $signed->getMessage());
        $this->assertSame('11884306385941066859834558634967777927278716082145975036347303871472774300855', $signed->getCompactSignature()->getR());
        $this->assertSame('38787429741286654786942380905403782954160859974631158035207591010286944440307', $signed->getCompactSignature()->getS());
        $this->assertSame(1, $signed->getCompactSignature()->getRecoveryId());
        $this->assertSame(true, $signed->getCompactSignature()->isCompressed());
    }
}