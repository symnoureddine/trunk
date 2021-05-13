<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\Console;

use Ox\Cli\MediboardCommand;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Format mediboard code base headers
 */
class FormatCodeBaseHeaders extends MediboardCommand {

  /** @var OutputInterface */
  protected $output;

  /** @var string $repository Repository name */
  protected $repository;

  /** @var string $path Path */
  protected $path;

  /** @var array File types with associated comments */
  protected $types = array(
    'php' => array(
      "opening_tag" => "/**",
      "closing_tag" => " */"
    ),
    'js'  => array(
      "opening_tag" => "/**",
      "closing_tag" => " */"
    ),
    'tpl' => array(
      "opening_tag" => "{{*",
      "closing_tag" => "*}}"
    ),
    'css' => array(
      "opening_tag" => "/**",
      "closing_tag" => " */"
    )
  );

  /** @var array Excluded dirs */
  protected $excluded_dirs = array(
    'vendor',
    'files',
    'shell',
    'tmp',
    'lib',
    'libpkg',
    'locales',
    'html',
    'CodeSniffer',
    'tests'
  );

  /** @var array OpenXtrem licenses */
  protected $licenses = array(
    'gpl'  => array(
      'https://www.gnu.org/licenses/gpl.html GNU General Public License',
      'https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License'
    ),
    'oxol' => array(
      'https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License'
    )
  );

  /**
   * @inheritdoc
   */
  protected function configure() {
    $aliases = array(
      'codebase:fh'
    );

    $this
      ->setName('codebase:formatheaders')
      ->setAliases($aliases)
      ->setDescription('Format code base headers')
      ->setHelp('Format code base headers by adding package, author and license tag')
      ->addOption(
        'repository',
        'r',
        InputOption::VALUE_REQUIRED,
        'The name of the repository',
        'gpl'
      )
      ->addOption(
        'path',
        'p',
        InputOption::VALUE_REQUIRED,
        'Root dir for which we want to format headers',
        realpath(__DIR__ . "/../../../")
      )
      ->addOption(
        'languages',
        'l',
        InputOption::VALUE_REQUIRED,
        'A comma separated list of languages to perform format on',
        implode(',', array_keys($this->types))
      );
  }

  /**
   * @see parent::showHeader()
   */
  protected function showHeader() {
    $this->output->writeln(
      <<<EOT
<fg=red;bg=black>
 _____                          _     _   _                _               
|  ___|__  _ __ _ __ ___   __ _| |_  | | | | ___  __ _  __| | ___ _ __ ___ 
| |_ / _ \| '__| '_ ` _ \ / _` | __| | |_| |/ _ \/ _` |/ _` |/ _ \ '__/ __|
|  _| (_) | |  | | | | | | (_| | |_  |  _  |  __/ (_| | (_| |  __/ |  \__ \
|_|  \___/|_|  |_| |_| |_|\__,_|\__| |_| |_|\___|\__,_|\__,_|\___|_|  |___/
</fg=red;bg=black>
EOT
    );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->output     = $output;
    $this->repository = $input->getOption('repository');
    $this->path       = $input->getOption('path');
    $languages        = explode(',', $input->getOption('languages'));
    $progress         = new ProgressBar($this->output);
    $this->showHeader();

    $path = realpath($input->getOption('path'));
    if (!is_dir($path)) {
      $this->output->writeln("Given path isn't a directory, please provide a valid path.");

      return;
    }

    $this->output->writeln(' Getting file list...');
    $extensions    = implode('|', $languages);
    $separator     = '\\' . DIRECTORY_SEPARATOR;
    $excluded_dirs = $separator . implode("$separator|$separator", $this->excluded_dirs) . $separator;

    // Regex iterator to include specific extensions and to exclude specific dirs
    $regexIterator = new RegexIterator(
      new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path)
      ),
      "/^((?!$excluded_dirs).)*\.($extensions)$/",
      RecursiveRegexIterator::GET_MATCH
    );

    $progress->start(iterator_count($regexIterator));

    foreach ($regexIterator as $_file) {
      // Get file extension and content
      $ext     = pathinfo($_file[0], PATHINFO_EXTENSION);
      $content = file_get_contents($_file[0]);

      // Check format
      if ($this->hasRightFormat($content)) {
        $progress->advance();
        continue;
      }

      // Process file according to its extension
      switch ($ext) {
        case 'php':
          $this->processPhpFile($_file[0], $content);
          break;
        case 'js':
          $this->processJsFile($_file[0], $content);
          break;
        case 'css':
          $this->processCssFile($_file[0], $content);
          break;
        case 'tpl':
          $this->processTplFile($_file[0], $content);
          break;
        default:
          // File extension not handled; Shouldn't happened;
          break;
      }
      $progress->advance();
    }
    $progress->finish();
  }

  /**
   * Check file comment format by looking for 'SAS OpenXtrem' string
   *
   * @param string $content File content
   *
   * @return bool True if file has right format
   */
  protected function hasRightFormat($content) {
    return (strpos($content, 'SAS OpenXtrem') !== false);
  }

  /**
   * Set the header content
   *
   * @param string $filepath     File path
   * @param string $firstChar    File content first char (after doc block)
   * @param string $extension    File extension for comment tag
   * @param string $extraComment Extra comment to add to the header
   *
   * @return string
   */
  protected function getHeaderContent($filepath, $firstChar, $extension, $extraComment = null) {
    $openingTag = $this->types[$extension]['opening_tag'];
    $closingTag = $this->types[$extension]['closing_tag'];

    $file_header =  $openingTag . PHP_EOL;
    $file_header .= " * @package Mediboard\\" . $this->getPackage($filepath) . PHP_EOL;
    $file_header .= " * @author  SAS OpenXtrem <dev@openxtrem.com>" . PHP_EOL;
    foreach ($this->licenses[$this->repository] as $_license) {
      $file_header .= " * @license $_license" . PHP_EOL;
    };

    $file_header .= $closingTag . PHP_EOL . PHP_EOL;

    if ($extraComment) {
      $file_header .=
        $openingTag . PHP_EOL . " * $extraComment". PHP_EOL . $closingTag . PHP_EOL . (($firstChar === "/") ? PHP_EOL : "");
    }

    return $file_header;
  }

  /**
   * Extract package name form filepath
   *
   * @param string $filepath Filepath
   *
   * @return string package name
   */
  protected function getPackage($filepath) {
    // Package is module name
    if (preg_match('/modules\\' . DIRECTORY_SEPARATOR . '(.[^\\' . DIRECTORY_SEPARATOR . ']*)/i', $filepath, $matches)) {
      $package = $matches[1];
      if (strpos($package, 'dP') !== false) {
        $package = substr($package, 2);
      }
      // Mobile subpackage
      if (strpos($filepath, 'Mobile' . DIRECTORY_SEPARATOR) !== false) {
        $package = "Mobile\\" . ucfirst($package);
      }
      // Tests subpackage
      if (strpos($filepath, DIRECTORY_SEPARATOR . 'tests') !== false) {
        $package .= "\\Tests";
      }

      return ucfirst($package);
    }

    // Package is in a root dir (cli, includes, install or tests)
    $package_names = array('cli', 'includes', 'install', 'tests', 'Mobile');
    foreach ($package_names as $_name) {
      if (strpos($filepath, $_name) !== false) {
        $package = ucfirst($_name);
        if (strpos($filepath, DIRECTORY_SEPARATOR . 'tests') !== false && $package != "Tests") {
          $package .= "\\Tests";
        }

        return $package;
      }
    }

    // Special case for style folder in order to get style name
    if (preg_match('/style\\' . DIRECTORY_SEPARATOR . '(.[^\\' . DIRECTORY_SEPARATOR . ']*)/', $filepath, $matches)) {
      return "Style\\" . ucfirst($matches[1]);
    }

    // Special case for classes/ root dir
    $folder = basename(dirname($filepath));
    if ($folder !== "classes" && dirname($filepath) != realpath($this->path)) {
      $package = "Core\\" . ucfirst($folder);
    }
    // Default case
    else {
      $package = "Core";
    }

    return $package;
  }

  /**
   * Add comment headers to PHP files
   *
   * @param string $filepath File path
   * @param string $content  File content
   *
   * @return void
   */
  protected function processPhpFile($filepath, $content) {
    $extra_comment = null;
    // Get back malformed header comment if it's not a class file
    if (strpos($filepath, 'class') === false) {
      if (preg_match('!<\?php\s*\/\*\*\s*\*\s([^\$@\s].*)!', $content, $matches)) {
        $extra_comment = trim($matches[1]);
      }
    }

    // Cleanup first line
    $content = preg_replace('/.*(\r\n|\n)/', '', $content, 1);

    // Remove file comment already present
    if ((strpos($content, '@author') !== false) && (($pos = strpos($content, '*/')) !== false)) {
      $content = substr($content, $pos + 2);
    }

    $header = "<?php" . PHP_EOL . $this->getHeaderContent($filepath, (strlen($content) > 0) ? $content[0] : "", 'php', $extra_comment);
    file_put_contents($filepath, $header . ltrim($content));
  }

  /**
   * Add comment headers to Javascript files
   *
   * @param string $filepath File path
   * @param string $content  File content
   *
   * @return void
   */
  protected function processJsFile($filepath, $content) {
    $extra_comment = null;

    // Comment present on the first line
    if ($content[0] === '/' && $content[1] !== '/') {
      if (preg_match('!^\/\**\s*\*\s([^\$@\s].*)!', $content, $matches)) {
        $extra_comment = trim($matches[1]);
      }

      // Cleanup first line
      $content = preg_replace('/.*(\r\n|\n)/', '', $content, 1);

      // Remove file comment already present
      if (($pos = strpos($content, '*/')) !== false) {
        $content = substr($content, $pos + 2);
      }
    }

    $header = $this->getHeaderContent($filepath, $content[0], 'js', $extra_comment);
    file_put_contents($filepath, $header . ltrim($content));
  }

  /**
   * Add comment headers to CSS files
   *
   * @param string $filepath File path
   * @param string $content  File content
   *
   * @return void
   */
  protected function processCssFile($filepath, $content) {
    if (!$content) {
      return;
    }

    if ($content[0] === '/') {
      // Remove /* $Id$ */ comment
      $content = preg_replace('/^\/\*\s\$.*/', '', $content, 1);
    }
    $header = $this->getHeaderContent($filepath, $content[0], 'css');
    file_put_contents($filepath, $header . ltrim($content));
  }

  /**
   * Add comment headers to tpl (smarty) files
   *
   * @param string $filepath File path
   * @param string $content  File content
   *
   * @return void
   */
  protected function processTplFile($filepath, $content) {
    $lines = preg_split('/(\r\n|\n|\r)/', ltrim($content));

    $i = 0; $count = count($lines);
    // Remove {{* *}} and <!-- --> style comment on the first line
    if ((strpos($lines[$i], '{{*') !== false && strpos($lines[$i], '*}}') !== false)
        || (strpos($lines[$i], '<!--') !== false && strpos($lines[$i], '-->') !== false)
    ) {
      unset($lines[$i]);
      $i++; $count--;

      // Remove empty line after comment
      if ($i < $count && strlen(trim($lines[$i])) == 0) {
        unset($lines[$i]);
        $i++; $count--;
      }
    }

    // Remove multi line comment
    if ($i < $count && strpos($lines[$i], '{{*') !== false) {
      unset($lines[$i]);
      $i++;
      do {
        unset($lines[$i]);
        $i++;
      } while (strpos($lines[$i], '*}}') === false);
      unset($lines[$i]);
    }

    $content = ltrim(implode(PHP_EOL, $lines));
    $header  = $this->getHeaderContent($filepath, (strlen($content) > 0) ? $content[0] : "", 'tpl');
    file_put_contents($filepath, $header . $content);
  }
}
