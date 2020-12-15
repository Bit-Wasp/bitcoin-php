<?php

namespace BitWasp\Bitcoin\Network\Networks;

class QtumRegtest extends QtumTestnet
{
	/**
	 * {@inheritdoc}
	 * @see Network::$bech32PrefixMap
	 */
	protected $bech32PrefixMap = [
			self::BECH32_PREFIX_SEGWIT => "qcrt",
	];
	
    protected $p2pMagic = "dab5bffa";
}
