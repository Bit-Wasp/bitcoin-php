<?php

namespace BitWasp\Bitcoin\Tests\Script\Classifier;

use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class OutputClassifierTest extends AbstractTestCase
{
    public function testIsMultisigFail()
    {
        $script = new Script();
        $classifier = new OutputClassifier();
        $this->assertFalse($classifier->isMultisig($script));
    }

    public function testIsKnown()
    {
        $script = new Script();
        $classifier = new OutputClassifier();
        $this->assertEquals(OutputClassifier::UNKNOWN, $classifier->classify($script));
    }
}
