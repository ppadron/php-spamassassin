<?php

require_once 'PHPUnit/Framework.php';

class AllTests {

    public static function suite()
    {
        $suite    = new PHPUnit_Framework_TestSuite('PHPUnit');
        $iterator = new GlobIterator(dirname(__FILE__) . '/*Test.php');

        foreach ($iterator as $file) {
            require_once $file->getPathname();
            $className = str_replace('.php', '', $file->getFilename());
            $suite->addTestSuite($className);
        }
        
        return $suite;
    }
}
