<?php
/**
 * @package Mediboard\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Tests;

use _HumbugBoxd1d863f2278d\Roave\BetterReflection\Reflection\Adapter\ReflectionClass;
use Countable;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Exception;
use JsonSerializable;
use Ox\Core\CAppUI;
use Ox\Core\CClassMap;
use Ox\Core\CModelObject;
use Ox\Core\Module\CModule;
use PHPUnit\Framework\TestCase;
use ReflectionClassConstant;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

class UnitTestMediboard extends TestCase
{

    // Common (unit & func)
    use TestMediboard;

    /**
     * setUp
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setContext();

        CModelObject::$spec = [];

        //$this->errorCount = $this->getErrorCount();
        $this->setConfig($this->parseComment('config', $this->newConfigs));
        $this->setPref($this->parseComment('pref', $this->newPrefs));
    }

    /**
     * @param array $preferences
     *
     * @return void
     */
    protected function setPref($preferences)
    {
        if (!$preferences) {
            return;
        }
        foreach ($preferences['standard'] as $_key => $_value) {
            CAppUI::$instance->user_prefs[$_key] = $_value;
        }
    }

    /**
     * @param string $message
     */
    public static function markTestSkipped(string $message = ''): void
    {
        $message = $message ?: 'Empty Skipped message';
        parent::markTestSkipped($message);
    }


    /**
     * Les méthodes template setUp() et tearDown() sont exécutées une fois pour chaque méthode de test
     * (et pour les nouvelles instances) de la classe de cas de test.
     */
    protected function tearDown(): void
    {
        // Check MB error log
        // $fail = $this->getErrorCount() > $this->errorCount;
        $fail = null;

        // Reset config & prefs
        $this->setConfig($this->oldConfigs);

        if ($fail) {
            $this->fail('Fail due to error count, please check MB error log...');
        }

        parent::tearDown();
    }

    /**
     * @param mixed  $object      Classname or object (instance of the class) that contains the method.
     * @param string $method_name Name of the method.
     * @param array  $params      Parameters of the method (Variable-length argument lists )
     *
     * @return mixed The method result.
     * @throws TestsException
     */
    public function invokePrivateMethod($object, $method_name, ...$params)
    {
        // Obj
        if (!is_object($object)) {
            if (!class_exists($object)) {
                throw new TestsException('The class does not exist ' . $object);
            }
            $object = new $object;
        }

        // Reflection
        try {
            $method = new ReflectionMethod($object, $method_name);
        } catch (ReflectionException $e) {
            throw new TestsException('The method does not exist ' . $e->getMessage());
        }

        // Accessibility
        if ($method->isPublic()) {
            throw new TestsException('Method is already public');
        }
        $method->setAccessible(true);

        // Invoke
        if ($method->isStatic()) {
            $object = null;
        }

        return $method->invoke($object, ...$params);
    }

    /**
     * @param mixed  $object     Classname or object (instance of the class) that contains the constant.
     * @param string $const_name Name of the constant to get value from.
     *
     * @return mixed The constant value
     * @throws TestsException
     */
    public function getPrivateConst($object, $const_name)
    {
        // Obj
        if (!is_object($object)) {
            if (!class_exists($object)) {
                throw new TestsException('The class does not exist ' . $object);
            }
            $object = new $object;
        }

        // Reflection
        try {
            $const = new ReflectionClassConstant($object, $const_name);
        } catch (ReflectionException $e) {
            throw new TestsException('The constant does not exist ' . $e->getMessage());
        }

        // Accessibility
        if ($const->isPublic()) {
            throw new TestsException('Constant is already public');
        }

        return $const->getValue();
    }

    /**
     * Import the given XML file with the given import class
     *
     * @param string $filePath    XML file path
     * @param string $importClass Import class
     *
     * @return void
     */
    public function importObject($filePath, $importClass = CTestXMLImport::class): void
    {
        $filePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $filePath;
        $import   = new $importClass($filePath);
        try {
            $import->import([], []);
        } catch (Exception $e) {
            dump($e->getMessage());
            dump($e->getTraceAsString());
        }
    }

    /**
     * @param CModule $module
     *
     * @return mixed
     * @throws TestsException
     */
    public static function toogleAcitveModule(CModule $module)
    {
        $module->mod_active = 1 - $module->mod_active;

        $msg = $module->store();
        if ($msg) {
            throw new TestsException($msg);
        }

        if (array_key_exists($module->mod_name, CModule::$active)) {
            unset(CModule::$active[$module->mod_name]);
        } else {
            CModule::$active[$module->mod_name] = $module;
        }
    }

    /**
     * @param mixed $iterable
     * @param array $expected
     * @param int   $max_count_iterable
     *
     * @return void
     */
    public function isIterable($iterable, $expected, $max_count_iterable)
    {
        $this->assertIsIterable($iterable);

        $this->assertEquals($expected[0], $iterable->current());

        $this->assertTrue($iterable->valid());

        $iterable->next();
        $this->assertEquals($expected[1], $iterable->current());

        $iterable->next();
        $this->assertEquals(2, $iterable->key());

        for ($i = 0; $i < $max_count_iterable * 2; $i++) {
            $iterable->next();
        }

        $this->assertFalse($iterable->valid());

        $iterable->rewind();
        $this->assertEquals(0, $iterable->key());
    }

    /**
     * @param mixed $countable
     * @param int   $expected
     *
     * @return void
     */
    public function isCountable($countable, $expected)
    {
        if (!$countable instanceof Countable) {
            $this->fail('The object is not a countable');
        }

        $this->assertCount($expected, $countable);
    }


    /**
     * @param mixed $serializable
     *
     * @return void
     */
    public function isJsonSerializable($serializable)
    {
        if (!$serializable instanceof JsonSerializable) {
            $this->fail('The object is not a serializable');
        }

        $this->assertJson(json_encode($serializable));
    }

    /**
     * Add classes to class map
     *
     * @param array $classes
     *
     * @throws ReflectionException
     */
    public function addClassesToMap(array $classes): void
    {
        $class_map    = CClassMap::getInstance();
        $property_ref = new ReflectionProperty($class_map, 'classmap');
        $property_ref->setAccessible(true);

        $property_ref->setValue($class_map, array_merge($property_ref->getValue($class_map), $classes));

        $property_ref->setAccessible(false);
    }

    /**
     * @param DOMDocument  $document
     * @param mixed        $expected
     * @param string       $xpath
     * @param string|null  $message
     * @param DOMNode|null $context
     */
    protected function assertXpathMatch(
        DOMDocument $document,
        $expected,
        string $xpath,
        string $message = "",
        DOMNode $context = null
    ): void {
        $xpathObj = new DOMXPath($document);

        $context = $context === null
            ? $document->documentElement
            : $context;

        $res = $xpathObj->evaluate($xpath, $context);

        $this->assertEquals(
            $expected,
            $res,
            $message
        );
    }

    /**
     * @param DOMDocument  $document
     * @param string       $pattern
     * @param string       $xpath
     * @param string|null  $message
     * @param DOMNode|null $context
     */
    protected function assertXpathRegMatch(
        DOMDocument $document,
        string $pattern,
        string $xpath,
        string $message = '',
        DOMNode $context = null
    ): void {
        $xpathObj = new DOMXPath($document);

        $context = $context === null
            ? $document->documentElement
            : $context;

        $res = $xpathObj->evaluate($xpath, $context);

        $this->assertMatchesRegularExpression(
            $pattern,
            $res,
            $message
        );
    }
}
