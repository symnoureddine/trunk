<?php
/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Erp\SourceCode;

use Ox\Core\CAppUI;
use ReflectionClass;
use ReflectionException;

/**
 * Parse test comment to show them on the install view
 * Class CParseTestComment
 */
class CParseTestComment {

  /** @var array $testFiles */
  private $testFiles;
  /** @var array $testClasses */
  public $testClasses;
  /** @var string $currentTag */
  private $currentTag;
  /** @var array $testsInfos */
  public $testsInfos;
  /** @var int $functionCount */
  public $functionCount;
  /** @var string $repository_dir */
  public $repository_dir;
  /** @var array $excludedMethods */
  public $excludedMethods = array('setUp', 'tearDown', 'credentialProvider', 'getAccount');

  /**
   * CParseTestComment constructor.
   */
  public function __construct() {
    $this->functionCount = 0;
    if ($this->repository_dir = CAppUI::conf('root_dir')) {
      $this->repository_dir = rtrim($this->repository_dir, '/') . '/';
      $this->loadTestsFiles();
      $this->getTestClasses();
      $this->setTestsInfos();
    }
  }

  /**
   * Load phpunit.xml and add all test files in array
   *
   * @return void
   */
  private function loadTestsFiles() {
    $path_module = $this->repository_dir . '/modules/*/tests/{Unit,Functional}/*Test.php';
    $path_core   = $this->repository_dir . '/core/tests/{Unit,Functional}/*Test.php';
    $files       = array_merge(glob($path_module, GLOB_BRACE), glob($path_core, GLOB_BRACE));
    foreach ($files as $_file) {
      $this->testFiles[] = $_file;
    }
  }

  /**
   * Includes all tests files to parse comment.
   * @return void
   * @deprecated
   */
  private function includeTestFiles() {
    ob_start();
    include_once __DIR__ . "/../../../dev/PHPUnit/phpunit.phar";
    ob_end_clean();
    include_once __DIR__ . "/../../../tests/bootstrap.php";
  }

  /**
   * Parse and set testFiles array to get the class names and the module name
   *
   * @return void
   */
  private function getTestClasses() {
    foreach ($this->testFiles as $_file) {
      $_class_name = $this->includeTestClass($_file);
      (preg_match("/\/(\w+)\/tests/", $_file, $matches)) ? $module = $matches[1] : $module = "tests";
      $this->testClasses[] = array(
        'class'  => $_class_name,
        'module' => $module
      );
    }
  }

  /**
   * @param string $_file
   *
   * @return string Class name
   */
  private function includeTestClass($_file) {
    include_once $_file;
    $classes = get_declared_classes();

    return end($classes);
  }

  /**
   * Return an associative array containing for each module class name and the string comment
   *
   * @return array
   * @throws ReflectionException
   */
  private function getAllClassInfos() {
    $res = array();
    foreach ($this->testClasses as $_testClass) {
      if (class_exists($_testClass['class'])) {
        $rc                                               = new ReflectionClass($_testClass['class']);
        $res[$_testClass['module']][$_testClass['class']] = array(
          'comment' => $rc->getDocComment(),
        );
      }
    }

    return $res;
  }

  /**
   * Parse a doc bloc comment to an array containing each line of the comment
   *
   * @param string $docComment Class or function doc block comment
   *
   * @return array
   */
  private function docCommentToArray($docComment) {
    if (!preg_match('#^/\*\*(.*)\*/#s', $docComment, $matches)) {
      return array();
    }
    $matches = trim($matches[1]);
    if (!preg_match_all('#^\s*\*(.*)#m', $matches, $lines)) {
      return array();
    }
    $lines = $lines[1];

    return $lines;
  }

  /**
   * Convert a class doc block comment to an array
   *
   * @param string $docComment Class or function doc block comment
   *
   * @return array
   */
  private function classDocCommentToArray($docComment) {
    $docCommentList = array_filter($this->docCommentToArray($docComment));
    $res            = array();
    // Comment header
    while ($current = current($docCommentList)) {
      $current = trim($current);
      if (strpos($current, "@") === 0) {
        break;
      }
      elseif ($current !== "") {
        isset($res['header']) ? $res['header'] .= " " . $current : $res['header'] = $current;
      }
      next($docCommentList);
    }

    // Comment tags
    while ($current = current($docCommentList)) {
      $current = trim($current);
      $next    = next($docCommentList);

      if (strpos($current, '@') === 0) {
        preg_match('/@.*? /', $current, $matches);
        $tag               = explode('@', $matches[0]);
        $tag               = trim($tag[1]);
        $this->currentTag  = $tag;
        $content           = explode($tag, $current);
        $res['tags'][$tag] = trim($content[1]);
      }
      else {
        $res['tags'][$this->currentTag] .= " " . $current;
      }
      if (strpos($next, '@') === 0) {
        break;
      }
    }

    return $res;
  }

  /**
   * Return all function comments for a specified class
   *
   * @param string $class class name
   *
   * @return array
   * @throws ReflectionException
   */
  private function getAllFunctionComment($class) {
    $rc      = new ReflectionClass($class);
    $methods = $rc->getMethods();
    $res     = array();
    foreach ($methods as $_method) {
      if ($class === $_method->class && !in_array($_method->name, $this->excludedMethods, true)) {
        $comment            = $rc->getMethod($_method->name)->getDocComment();
        $parsedCommentArray = $this->docCommentToArray($comment);
        $parsedComment      = '';
        $tags               = array();
        if (count($parsedCommentArray) > 0) {
          foreach ($parsedCommentArray as $_comment) {
            if (strpos(trim($_comment), "@") === 0) {
              preg_match('/@.*? /', $_comment, $matches);
              if (isset($matches[0])) {
                $tag     = explode('@', $matches[0]);
                $tag     = trim($tag[1]);
                $content = explode($tag, $_comment);
                isset($tags[$tag]) ? $tags[$tag] .= ' | ' . trim($content[1]) : $tags[$tag] = trim($content[1]);
              }
            }
            else {
              $parsedComment .= trim($_comment) . PHP_EOL;
            }
          }
        }
        $res[$_method->name] = array(
          'comment' => $comment ? $parsedComment : '',
          'tags'    => $tags,
        );
        $this->functionCount++;
      }
    }
    return $res;
  }

  /**
   * Parse all comment value in the class infos array and set the testsInfos array
   *
   * Format:
   *  array(
   *     [module] => array
   *       [class] => array
   *         [comment] => array
   *           [header] => value,
   *           [tags] => array
   *             [tag1] => value ,
   *             [tag2] => value
   *             ...
   *          [functions] => array
   *            [name] => value,
   *            [comment] => value,
   *            [tags] => array
   *
   * @return void
   * @throws ReflectionException
   */
  private function setTestsInfos() {
    $classInfoList = $this->getAllClassInfos();
    foreach ($classInfoList as &$_classInfo) {
      foreach ($_classInfo as $className => &$_class) {
        $_class['functions'] = $this->getAllFunctionComment($className);
        $_class['comment']   = $this->classDocCommentToArray($_class['comment']);
      }
    }
    uksort(
      $classInfoList,
      function ($a, $b) {
        return strcmp(CAppUI::tr("module-$a-court"), CAppUI::tr("module-$b-court"));
      }
    );
    $this->testsInfos = $classInfoList;
  }
}