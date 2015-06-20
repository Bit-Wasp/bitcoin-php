<?php

namespace BitWasp\Bitcoin\Serializer\Network\Structure;

use BitWasp\Bitcoin\Network\Structure\AlertDetail;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\TemplateFactory;

class AlertDetailSerializer
{
    /**
     * @return \BitWasp\Buffertools\Template
     */
    public function getTemplate()
    {
        return (new TemplateFactory())
            ->uint32le()
            ->uint64le()
            ->uint64le()
            ->uint32le()
            ->uint32le()
            ->vector(function (Parser & $parser) {
                return $parser->readBytes(4, true)->getInt();
            })
            ->uint32le()
            ->uint32le()
            ->vector(function (Parser & $parser) {
                return $parser->readBytes(4, true)->getInt();
            })
            ->uint32le()
            ->varstring()
            ->varstring()
            ->getTemplate();
    }

    public function fromParser(Parser & $parser)
    {
        $parsed = $this->getTemplate()->parse($parser);

        /** @var int|string $version */
        $version = $parsed[0];
        /** @var int|string $relayUntil */
        $relayUntil = $parsed[1];
        /** @var int|string $expiration */
        $expiration = $parsed[2];
        /** @var int|string $id */
        $id = $parsed[3];
        /** @var int|string $cancel */
        $cancel = $parsed[4];
        /** @var Buffer[] $setCancels */
        $setCancels = $parsed[5];
        /** @var int|string $minVer */
        $minVer = $parsed[6];
        /** @var int|string $maxVer */
        $maxVer = $parsed[7];
        /** @var Buffer[] $setSubVers */
        $setSubVers = $parsed[8];
        /** @var int|string $priority */
        $priority = $parsed[9];
        /** @var Buffer $comment */
        $comment = $parsed[10];
        /** @var Buffer $statusBar */
        $statusBar = $parsed[11];

        return new AlertDetail(
            $version,
            $relayUntil,
            $expiration,
            $id,
            $cancel,
            $minVer,
            $maxVer,
            $priority,
            $comment,
            $statusBar,
            $setCancels,
            $setSubVers
        );
    }

    /**
     * @param $data
     * @return AlertDetail
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }

    /**
     * @param AlertDetail $detail
     * @return \BitWasp\Buffertools\Buffer
     */
    public function serialize(AlertDetail $detail)
    {
        $setCancels = [];
        foreach ($detail->getSetCancel() as $toCancel) {
            $t = new Parser();
            $setCancels[] = $t->writeInt(4, $toCancel, true)->getBuffer();
        }

        $setSubVers = [];
        foreach ($detail->getSetSubVer() as $subVer) {
            $t = new Parser();
            $setSubVers[] = $t->writeInt(4, $subVer, true)->getBuffer();
        }

        return $this->getTemplate()->write([
            $detail->getVersion(),
            $detail->getRelayUntil(),
            $detail->getExpiration(),
            $detail->getId(),
            $detail->getCancel(),
            $setCancels,
            $detail->getMinVer(),
            $detail->getMaxVer(),
            $setSubVers,
            $detail->getPriority(),
            $detail->getComment(),
            $detail->getStatusBar()
        ]);
    }
}
