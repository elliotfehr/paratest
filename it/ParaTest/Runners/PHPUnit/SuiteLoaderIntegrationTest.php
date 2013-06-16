<?php namespace ParaTest\Runners\PHPUnit;

class SuiteLoaderIntegrationTest extends \TestBase
{
    protected $loader;
    protected $files;
    protected $testDir;

    public function setUp()
    {
        $this->loader = new SuiteLoader();
        $tests = FIXTURES . DS . 'tests';
        $this->testDir = $tests;
        $this->files = array_map(function($e) use($tests) { return $tests . DS . $e; }, array(
            'EnvironmentTest.php',
            'GroupsTest.php',
            'LegacyNamespaceTest.php',
            'LongRunningTest.php',
            'UnitTestWithClassAnnotationTest.php',
            'UnitTestWithMethodAnnotationsTest.php',
            'UnitTestWithErrorTest.php',
            'level1' . DS . 'UnitTestInSubLevelTest.php',
            'level1' . DS . 'AnotherUnitTestInSubLevelTest.php',
            'level1' . DS . 'level2' . DS . 'UnitTestInSubSubLevelTest.php',
            'level1' . DS . 'level2' . DS . 'AnotherUnitTestInSubSubLevelTest.php'
        ));
    }

    public function testLoadFileGetsPathOfFile()
    {
        $path = FIXTURES . DS . 'tests' . DS . 'UnitTestWithClassAnnotationTest.php';
        $paths = $this->getLoadedPaths($path);
        $this->assertEquals($path, array_shift($paths));
    }

    public function testLoadFileShouldLoadFileWhereNameDoesNotEndInTest()
    {
        $path = FIXTURES . DS . 'tests' . DS . 'TestOfUnits.php';
        $paths = $this->getLoadedPaths($path);
        $this->assertEquals($path, array_shift($paths));
    }

    public function testLoadDirGetsPathOfAllTestsWithKeys()
    {
        $this->loader->load($this->testDir);
        $loaded = $this->getObjectValue($this->loader, 'loadedSuites');
        foreach($loaded as $path => $test)
            $this->assertContains($path, $this->files);
        return $loaded;
    }

    /**
     * @depends testLoadDirGetsPathOfAllTestsWithKeys
     */
    public function testFirstParallelSuiteHasCorrectFunctions($paraSuites)
    {
        $first = $this->byClassName('GroupsTest', $paraSuites);
        $functions = $first->getFunctions();
        $this->assertEquals(5, sizeof($functions));
        $this->assertEquals('testTruth', $functions[0]->getName());
        $this->assertEquals('testFalsehood', $functions[1]->getName());
        $this->assertEquals('testArrayLength', $functions[2]->getName());
        $this->assertEquals('testStringLength', $functions[3]->getName());
        $this->assertEquals('testAddition', $functions[4]->getName());
    }

    /**
     * @depends testLoadDirGetsPathOfAllTestsWithKeys
     */
    public function testSecondParallelSuiteHasCorrectFunctions($paraSuites)
    {
        $second = $this->byClassName('LegacyNamespaceTest', $paraSuites);
        $functions = $second->getFunctions();
        $this->assertEquals(0, sizeof($functions));
    }

    /**
     * @depends testLoadDirGetsPathOfAllTestsWithKeys
     */
    public function testThirdParallelSuiteHasCorrectFunctions($paraSuites)
    {
        $third = $this->byClassName('LongRunningTest', $paraSuites);
        $functions = $third->getFunctions();
        $this->assertEquals(3, sizeof($functions));
        $this->assertEquals('testOne', $functions[0]->getName());
        $this->assertEquals('testTwo', $functions[1]->getName());
        $this->assertEquals('testThree', $functions[2]->getName());
    }

    private function byClassName($name, array $suites)
    {
        foreach ($suites as $path => $suite) {
            if (preg_match("|/{$name}.php$|", $path)) {
                return $suite;
            }
        }
    }

    public function testGetTestMethodsReturnCorrectNumberOfSuiteTestMethods()
    {
        $this->loader->load($this->testDir);
        $methods = $this->loader->getTestMethods();
        $this->assertEquals(33, sizeof($methods));
        return $methods;
    }

    /**
     * @depends testGetTestMethodsReturnCorrectNumberOfSuiteTestMethods
     */
    public function testTestMethodsShouldBeInstanceOfTestMethod($methods)
    {
        foreach($methods as $method)
            $this->assertInstanceOf('ParaTest\\Runners\\PHPUnit\\TestMethod', $method);
    }

    public function testGetTestMethodsOnlyReturnsMethodsOfGroupIfOptionIsSpecified()
    {
        $options = new Options(array('group' => 'group1'));
        $loader = new SuiteLoader($options);
        $groupsTest = FIXTURES . DS . 'tests' . DS . 'GroupsTest.php';
        $loader->load($groupsTest);
        $methods = $loader->getTestMethods();
        $this->assertEquals(2, sizeof($methods));
        $this->assertEquals('testTruth', $methods[0]->getName());
        $this->assertEquals('testFalsehood', $methods[1]->getName());
    }

    protected function getLoadedPaths($path)
    {
        $this->loader->load($path);
        $loaded = $this->getObjectValue($this->loader, 'loadedSuites');
        $paths = array_keys($loaded);
        return $paths;
    }
}