<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Key\KeyToScript\Decorator;

use BitWasp\Bitcoin\Key\KeyToScript\Decorator\P2shScriptDecorator;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\KeyToScriptDataFactory;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\P2pkhScriptDataFactory;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\P2pkScriptDataFactory;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\P2wpkhScriptDataFactory;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class P2shScriptDecoratorTest extends AbstractTestCase
{
    public function getAllowedScriptFactories()
    {
        return [
            [new P2pkhScriptDataFactory()],
            [new P2pkScriptDataFactory()],
            [new P2wpkhScriptDataFactory()],
        ];
    }

    /**
     * @dataProvider getAllowedScriptFactories
     * @param KeyToScriptDataFactory $factory
     * @throws \BitWasp\Bitcoin\Exceptions\DisallowedScriptDataFactoryException
     */
    public function testAllowedScriptType(KeyToScriptDataFactory $factory)
    {
        $p2shFactory = new P2shScriptDecorator($factory);
        $this->assertEquals(ScriptType::P2SH . "|" . $factory->getScriptType(), $p2shFactory->getScriptType());
    }
}
