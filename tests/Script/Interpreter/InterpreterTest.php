<?php

namespace BitWasp\Bitcoin\Tests\Script\Interpreter;


use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterFactory;
use BitWasp\Buffertools\Buffer;

class InterpreterTest
{
    private function loadExternalTestFiles($dir)
    {
        $results = array();
        $basedir = __DIR__ . '/../../Data/bitcoinconsensus_testcases/';
        $fulldir = $basedir . $dir . '/';
        foreach (scandir($fulldir) as $file) {
            if (in_array($file, array('.','..'))) {
                continue;
            }
            $results[] = $fulldir . $file;
        }
        return $results;
    }

    private function loadVectors($dir) {
        $vectors = array();
        foreach ($this->loadExternalTestFiles($dir) as $c => $file) {
            $vectors[] = explode("\n", file_get_contents($file));
        }
        return $vectors;
    }

    public function getVectors()
    {
        return array_merge(
            $this->loadVectors('0.10-positive'),
            $this->loadVectors('0.10-negative')
        );
    }

    /**
     * @dataProvider getVectors
     */
    public function testCases(InterpreterFactory $factory, $scriptPubKey, $tx, $nInput, $flags, $result)
    {
        $scriptPubKey = Buffer::hex($scriptPubKey);
        $tx = Buffer::hex($tx);

        $interpreter = $factory->create($tx, $factory->flags($flags));
        $interpreter->verify($tx, $scriptPubKey, $nInput);

    }
}