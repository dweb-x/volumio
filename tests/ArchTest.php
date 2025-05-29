<?php

namespace Dwebx\Volumio\Tests;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class ArchTest extends TestCase
{
    /**
     * Test that debugging functions are not used in the codebase.
     */
    public function test_no_debugging_functions_are_used(): void
    {
        $debugFunctions = ['dd', 'dump', 'ray'];
        $srcDir = __DIR__.'/../src';

        $foundDebugCalls = [];

        $directory = new RecursiveDirectoryIterator($srcDir);
        $iterator = new RecursiveIteratorIterator($directory);
        $phpFiles = new RegexIterator($iterator, '/\.php$/');

        foreach ($phpFiles as $phpFile) {
            $content = file_get_contents($phpFile->getRealPath());

            foreach ($debugFunctions as $function) {
                // Look for function calls like dd(...) or ray(...) but not inside comments or strings
                if (preg_match('/[^a-zA-Z0-9_]'.$function.'\s*\(/i', $content)) {
                    $foundDebugCalls[] = $phpFile->getPathname().': '.$function;
                }
            }
        }

        $this->assertEmpty($foundDebugCalls, 'Debugging functions found in code: '.implode(', ', $foundDebugCalls));
    }
}
