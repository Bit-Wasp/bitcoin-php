<?php

namespace BitWasp\Bitcoin\Network\P2P;

use BitWasp\Bitcoin\Network\BlockLocator;
use BitWasp\Bitcoin\Chain\Headerchain;
use BitWasp\Bitcoin\Chain\Blockchain;
use BitWasp\Bitcoin\Network\Messages\Inv;
use BitWasp\Bitcoin\Network\Structure\NetworkAddress;

class Node
{
    /**
     * @var Headerchain|Blockchain
     */
    private $chain;

    /**
     * @var PeerLocator
     */
    private $peers;

    /**
     * @var BlockLocator
     */
    private $locator;

    /**
     * @param NetworkAddress $local
     * @param $chain
     * @param PeerLocator $peers
     */
    public function __construct(NetworkAddress $local, $chain, PeerLocator $peers)
    {
        $this->version = 70002;
        $this->local = $local;
        $this->chain = $chain;
        $this->peers = $peers;
        $this->locator = new BlockLocator();
    }

    /**
     * @return PeerLocator
     */
    public function peers()
    {
        return $this->peers;
    }

    /**
     * @return Blockchain|Headerchain
     */
    public function chain()
    {
        return $this->chain;
    }

    /**
     * @param bool|false $all
     * @return array
     */
    public function locator($all = false)
    {
        return $this->locator->hashes($this->chain->currentHeight(), $this->chain->index(), $all);
    }

    /**
     * @param Peer $peer
     * @param Inv $vInv
     */
    public function processInv(Peer $peer, Inv $vInv)
    {
        $vDontHave = [];
        foreach ($vInv->getItems() as $vector) {
            $key = $vector->getHash()->getHex();
            if ($vector->isBlock()) {
                if (!$this->chain->index()->height()->contains($key)) {
                    $vDontHave[] = $vector;
                }
            } elseif ($vector->isTx()) {
            } elseif ($vector->isFilteredBlock()) {
                if (!$this->chain->index()->height()->contains($key)) {
                    $vDontHave[] = $vector;
                }
            }
        }

        if (count($vDontHave) > 0) {
            echo "send getdata\n";
            $peer->getdata($vDontHave);
        }
    }
}
