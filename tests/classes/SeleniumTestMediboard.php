<?php

/**
 * @package Mediboard\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Tests;

use Exception;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Ox\Core\CMbPath;
use Ox\Core\CSQLDataSource;
use Ox\Mediboard\System\CPreferences;
use PHPUnit\Extensions\Selenium2TestCase;

/**
 * SeleniumTestMediboard
 *
 * @description specific extension of PHPUnit Selenium 2 for Mediboard framework
 */
class SeleniumTestMediboard extends Selenium2TestCase
{
    // common (unit & func)
    use TestMediboard;

    public $excluded_tests = ["testUpdateModuleOK", "testFreshInstallOk", "testLogin"];


    // copy from old home SeliniumTestSuite
    /** @var RemoteWebDriver $driver */
    //public $driver;
    /** @var string $testId */
    //private $testId;
    /** @var array $parameters */
    //protected $parameters;
    /** @var array $browsers */
    //public static $browsers = array();


    /**
     * Setup browser session and size
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setContext();
        $this->setBrowser("chrome");
        $this->setBrowserUrl($this->base_url);

        $this->errorCount = $this->getErrorCount();
        $this->setConfig($this->parseComment('config', $this->newConfigs));
        $this->setPref($this->parseComment('pref', $this->newPrefs));
    }


    /**
     * Set MB preferences according to test comment
     * Preferences are set for all cases (default, profile and users)
     *
     * @param array $preferences Array containing preference keys and values
     *
     * @return void
     */
    private function setPref($preferences)
    {
        if (!$preferences) {
            return;
        }

        $ds = CSQLDataSource::get("std");
        foreach ($preferences as $_type => $_preferences) {
            foreach ($_preferences as $_key => $_value) {
                $where        = [];
                $where['key'] = "= '$_key'";
                $where[]      = "value != '$_value' AND value IS NOT NULL";

                $pref      = new CPreferences();
                $old_prefs = $pref->loadList($where);

                /** @var CPreferences[] $old_prefs */
                foreach ($old_prefs as $_old_pref) {
                    if ($_old_pref->value != $_value) {
                        $this->oldPrefs[] = $_old_pref;

                        $old_value        = $_old_pref->value;
                        $_old_pref->value = $_value;
                        $_old_pref->rawStore();

                        // needed because oldPrefs is an array of object references
                        $_old_pref->value = $old_value;
                    }
                }
            }
        }
    }

    /**
     * Reset preference values based on an array
     *
     * @param CPreferences $preferences preference list
     *
     * @return void
     */
    private function resetPref($preferences = null)
    {
        if (!$preferences) {
            $preferences = $this->oldPrefs;
        }

        foreach ($preferences as $_pref) {
            $_pref->rawStore();
        }
    }


    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        // Reset default content
        $this->frame(null);

        // Check MB error log
        $fail = $this->getErrorCount() > $this->errorCount;

        // Objects' deletion
        //    if (!in_array($this->getName(false), $this->excluded_tests, true)) {
        //      static::removeObject();
        //    }

        // shot only on failure
        if ($this->hasFailed()) {
            $this->screenshot();
        }

        // todo ref_test_functional
        $this->stop();

        // Reset config & prefs
        $this->setConfig($this->oldConfigs);
        $this->resetPref();

        if ($fail) {
            dump("please check MB error log");
            //$this->fail("Fail due to error count, please check MB error log...");
        }
        parent::tearDown();
    }

    /**
     * Wait for the end of ajax by checking the data-loaded attribute
     *
     * @param string $id          the id of the ajax div
     * @param int    $waitTimeout timeout value
     *
     * @return void
     */
    public function waitForAjax($id, $waitTimeout = 30)
    {
        try {
            $this->waitUntil(
                function () use ($id) {
                    return $this->byId($id)->attribute("data-loaded") == "1";
                },
                5000
            );
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * @param string $text of element
     *
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function selectElementByText($text)
    {
        return $this->byXPath("//*[contains(text(),'$text')]");
    }

    /**
     * Return the form field element by its form id and its field name
     *
     * @param string $formId    the form id
     * @param string $fieldName the name of the field
     *
     */
    function getFormField($formId, $fieldName)
    {
        return $this->byId($formId . "_" . $fieldName);
    }

    /**
     * Get opened modal count
     *
     * @return int
     */
    function getModalCount()
    {
        return (int)$this->executeScript("return Control.Modal.stack.length");
    }

    /**
     * @param string     $script
     * @param array|null $args
     */
    function executeScript($script, array $args = [])
    {
        return $this->execute(
            [
                'script' => $script,
                'args'   => $args,
            ]
        );
    }

    function triggerOnmouseover($xpath)
    {
        $script = "document.evaluate(\"$xpath\", document, null, XPathResult.ANY_TYPE, null ).iterateNext().onmouseover()";
        //dump($script);
        $this->executeScript($script);
        sleep(1);
    }

    function triggerDoubleClick($element)
    {
        $this->executeScript("$('$element').ondblclick();");
        sleep(1);
    }

    /**
     * @param int $ms to wait
     */
    function wait($ms = 0)
    {
        $this->timeouts()->implicitWait($ms);
    }


    /**
     * @return string
     */
    function screenshot()
    {
        $shot = $this->currentScreenshot();
        $sep  = DIRECTORY_SEPARATOR;
        $dir  = __dir__ . $sep . ".." . $sep . ".." . $sep . "tmp" . $sep . "screenshot";
        CMbPath::forceDir($dir);
        $file = $dir . $sep . uniqid() . ".png";
        file_put_contents($file, $shot);

        return $file;
    }


    /**
     * Change current window focus on frame
     *
     * @return void
     */
    public function changeFrameFocus()
    {
        if ($this->getModalCount() === 0) {
            sleep(2);
        }

        if (!$frame = $this->executeScript("return Control.Modal.stack.last().container.down('iframe');")) {
            return;
        }

        $iframes = $this->findElementsByXpath("//*[contains(local-name(), 'iframe')]");

        foreach ($iframes as $_iframe) {
            if ($_iframe->getId() == $frame['ELEMENT']) {
                break;
            }
        }

        $this->frame($_iframe);
    }

    /**
     * Return an autocomplete subelement by its parent id and its text
     *
     * @param string $inputId The input id
     * @param string $value   The searched value
     *
     * @return Facebook\WebDriver\Remote\RemoteWebElement
     */
    public function selectAutocompleteByText($inputId, $value)
    {
        $selector = "//*[@id='$inputId']/parent::*//*[not(self::script or self::noscript)]" .
            "[not(contains(@style, 'display: none'))][contains(text(),'$value')]";

        return $this->byXPath($selector);
    }

    /**
     * @param string $selector css selector
     *
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function byCss($selector)
    {
        return $this->byCssSelector($selector);
    }

    /**
     * @param string $selector css
     *
     * @return array
     */
    public function findElementsByCss($selector)
    {
        return $this->elements($this->using('css selector')->value($selector));
    }


    /**
     * @param string $id id
     *
     * @return array
     */
    public function findElementsById($id)
    {
        return $this->elements($this->using('id')->value($id));
    }

    /**
     * @param string $xpath xpath
     *
     * @return array
     */
    public function findElementsByXpath($xpath)
    {
        return $this->elements($this->using('xpath')->value($xpath));
    }


    /**
     * Wait until a single window is displayed
     *
     * @return void
     */
    public function waitUntilSingleWindow()
    {
        echo "waitUntilSingleWindow";
        try {
            $this->waitUntil(
                function () {
                    return count($this->windowHandles()) === 1;
                },
                5000
            );
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }


    /**
     * Set value to input with specified id with JavaScript
     *
     * @param string $id    InputField id
     * @param string $value Value
     *
     * @return void
     */
    public function setInputValueById($id, $value)
    {
        $this->executeScript("\$V('$id','$value')");
    }

    /**
     * Get the value of the input with the specified id with JavaScript
     *
     * @param string $id        InputField id
     * @param bool   $enabled   Check if the element is enabled
     * @param bool   $displayed Check if the element is displayed
     *
     * @return string
     */
    public function getInputValueById($id, $enabled = true, $displayed = true)
    {
        return $this->byId($id, 30, $enabled, $displayed)->attribute('value');
    }

    /**
     * @return void
     * @deprecated
     * Create browser instance
     *
     */
    public function createBrowser()
    {
        /** @var DesiredCapabilities $capabilities */
        //    $browser_type = $this->parameters['type'];
        //    $capabilities = DesiredCapabilities::$browser_type();
        //    $desiredCapabilities = $this->getDesiredCapabilities();
        //    foreach ($desiredCapabilities as $_name => $_value) {
        //      $capabilities->setCapability($_name, $_value);
        //    }
        //
        //    $this->driver = RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities, 60000, 120000);
        //    $this->manage()->window()->maximize();
    }


    /**
     * @return void
     * @deprecated
     * Setup the browsers according to the Mediboard configuration
     *
     */
    public static function browserSetup()
    {
        //    if (count(self::$browsers)) {
        //      return;
        //    }
        //    $browsersList = CAppUI::conf("sourceCode selenium_browsers");
        //    foreach ($browsersList as $browserName => $active) {
        //      if ($active) {
        //        self::$browsers[] = SeleniumBrowserSuite::$allBrowsers[$browserName];
        //      }
        //    }
        //    return;
    }


    /**
     * @deprecated
     * Override parent function to be sure that the browserSetup() function is called
     *
     * @inheritdoc
     */
    public static function suite_old($className)
    {
        //    self::browserSetup();
        //    return SeleniumTestSuite::fromTestCaseClass($className);
    }


    /**
     * Call the specified mediboard controller with javascript
     *
     * @param string $module     Module name
     * @param string $controller Controller name
     * @param string $params     Parameters
     *
     * @return void
     */
    public function callController($module, $controller, $params)
    {
        $script = "callController('$module', '$controller', " . json_encode($params) . ");";
        $this->executeScript($script);
    }

    /**
     * Select option element containing specified text
     *
     * @param string  $id     Select element id
     * @param string  $value  Searched value
     * @param boolean $strict Use strict equality
     *
     * @return void
     */
    public function selectOptionByText($id, $value, $strict = false)
    {
        $strict ?
            $this->byXPath("//select[@id='$id']//option[text() = '$value']")->click() :
            $this->byXPath("//select[@id='$id']//option[contains(text(),'$value')]")->click();
    }

    /**
     * Select option element containing specified value
     *
     * @param string $id    Select element id
     * @param string $value Searched value
     *
     * @return void
     */
    public function selectOptionByValue($id, $value)
    {
        $this->byCss("select#$id option[value='$value']")->click();
    }

    /**
     * Import the given XML file with the given import class
     *
     * @param string $filePath    XML file path
     * @param string $importClass Import class
     *
     * @return void
     */
    public function importObject($filePath, $importClass = CTestXMLImport::class)
    {
        $params = [
            "filePath"    => $filePath,
            "importClass" => $importClass,
        ];
        $this->callController("system", "do_import_test", $params);

        // todo use $this->waitUntil
        sleep(5);

        $systemMsgElem = $this->byId("systemMsg");
        $msg           = utf8_decode($systemMsgElem->text());
        //$systemMsgElem->click();


        if (strpos($msg, "Import terminé") === false) {
            $this->fail("Error during objects' importation\n$msg");
        }
    }


    /**
     * Set value and retry until element value is different from given value
     *
     * @param string $id    The element ID
     * @param string $value The value to send
     *
     * @return void
     */
    public function valueRetryByID($id, $value)
    {
        $element = $this->byId($id);

        while ($element->attribute('value') != $value) {
            $element->clear();
            $element->value($value);
            $element = $this->byId($id);
            sleep(1);
        }
    }

}
