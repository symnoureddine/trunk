<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\Console;

use Exception;
use Ox\Core\CMbPath;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\InvalidArgumentException;

class CMbHostCompile extends Command {
  private const LOG_INFO = 0;
  private const LOG_TITLE = 1;
  private const LOG_ERROR = 2;

  private const PLATFORM_WIN = "win32";
  private const PLATFORM_MACOS = "macos";
  private const PLATFORM_LINUX = "linux_deb";

  private const PLATFORMS = array(
    self::PLATFORM_WIN,
    self::PLATFORM_MACOS,
    self::PLATFORM_LINUX
  );

  private const EXCLUDE_PATTERNS = array(
    '/\.py$/',
    '/\.crt$/',
    '/\.key$/',
    '/\.pid$/',
    '/\.po$/',
    '/\.pot$/',
    '/__pycache__/',
    '/\.idea/',
    '/\.svn/',
    '/\.git/',
    '/config\.ini/',
  );

  /** @var OutputInterface */
  private $output;

  /** @var InputInterface */
  private $input;

  /** @var QuestionHelper */
  private $question_helper;

  /** @var Filesystem */
  private $fs;

  /** @var string path to MbHost Project's setup directory */
  private $setup_path;

  /** @var string path to MbHost Project */
  private $path;

  /** @var string platform of the release */
  private $platform;

  private $architecture;
  private $version;
  private $package_name;
  private $standalone;

  /** @var string */
  private $cer_path;
  private $cer_pass;

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('mbhost:compile')
      ->setDescription('Compile MbHost package')
      ->setHelp('MediboardHost-setup compiler. Due to the compilers languages, you have to run this with the OS desired.')
      ->addOption(
        'architecture',
        'a',
        InputOption::VALUE_OPTIONAL,
        'The Architecture of the Operating System to deploy (x64, x86, ARM)'
      )
      ->addOption(
        'release',
        'r',
        InputOption::VALUE_OPTIONAL,
        'The value of the version to compile (X.X.X)'
      )
      ->addOption(
        'platform',
        'p',
        InputOption::VALUE_OPTIONAL,
        'The Operating System to deploy (Windows, WINNT, WIN32, Darwin, Linux)'
      )
      ->addOption(
        'standalone',
        's',
        InputOption::VALUE_NONE,
        'Compile without prerequisites'
      );
  }

  /**
   * @inheritDoc
   * @throws Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->input           = $input;
    $this->output          = $output;
    $this->question_helper = $this->getHelper('question');
    $this->fs              = new Filesystem();

    try {
      $this->getParams();
    }
    catch (Exception $e) {
      self::log($e->getMessage(), self::LOG_ERROR);

      return 1;
    }

    if (!$this->ask(new ConfirmationQuestion("Confirm compilation ? [Y/n]", true))) {
      return false;
    }

    $this->compile();

    return null;
  }

  /**
   * Compile the project
   *
   * @return void
   * @throws Exception
   */
  private function compile(): void {
    try {
      $this->setTypeMaj($this->standalone ? "False" : "True");
      $this->log("Compilation des locales...", self::LOG_TITLE);
      $this->compileLocales();

      $this->log("Décompression de Python ...", self::LOG_TITLE);
      $count = $this->extractPython();
      $this->log("$count fichiers extraits");

      $this->log("Compilation des scripts ...", self::LOG_TITLE);
      $out = $this->compileScripts();
      $this->log($out);

      $this->log("Copie des scripts ...", self::LOG_TITLE);
      $count = $this->copyScripts();
      $this->log("$count fichiers copiés");

      $this->log("Compilation du setup ...", self::LOG_TITLE);
      $out = $this->compileSetup();
      $this->log($out);

      $this->log("Signature de code du setup ...", self::LOG_TITLE);
      $out = $this->signSetup();
      $this->log($out);
    }
    catch (Exception $e) {
      $this->log("Nettoyage des fichiers ...", self::LOG_TITLE);

      $this->clearFiles();

      throw $e;
    }

    $this->log("Nettoyage des fichiers ...", self::LOG_TITLE);
    $this->clearFiles();
  }

  /**
   * Compile locales files (*.po to *.mo)
   *
   * @return void
   */
  private function compileLocales() {
    $app_path    = $this->path . "/app";
    $path_length = strlen($app_path);

    $locales = array_merge(
      glob("$app_path/mbhost/modules/*/locales/*/*.po"),
      glob("$app_path/mbhost/locales/*/*.po")
    );

    self::addPathmsgfmt();

    foreach ($locales as $_from) {
      $_to = dirname($_from) . "/LC_MESSAGES/" . substr(basename($_from), 0, -2) . "mo";

      $_from = self::escapeArg($_from);
      $_to   = self::escapeArg($_to);

      self::exec("msgfmt $_from -o $_to");

      $_from = substr($_from, $path_length);
      $_to = substr($_to, $path_length);
      $this->log("'{$_from}' => '{$_to}'");
    }
  }

  /**
   * @return int
   * @throws Exception
   */
  private function extractPython(): int {
    $python_zip = "$this->setup_path/python" . ($this->platform == self::PLATFORM_WIN ? "_$this->architecture" : "") . ".zip";

    $count = CMbPath::extract($python_zip, "{$this->setup_path}/package");

    if ($count === false || $count === 0) {
      throw new Exception("Could not extract '$python_zip' to '$this->setup_path/package'");
    }

    // Add perm on python executable
    if (in_array($this->platform, array(self::PLATFORM_LINUX, self::PLATFORM_MACOS))) {
      chmod("$this->setup_path/package/python/python.exe", 0755);
    }

    return $count;
  }

  /**
   * Compile python cripts
   *
   * @return string
   */
  private function compileScripts(): string {
    $python   = self::escapeArg("$this->setup_path/package/python/python.exe");
    $app_file = self::escapeArg("$this->path/app/mbhost.py");
    $app_dir  = self::escapeArg("$this->path/app/mbhost");

    return $this->exec("$python -OO -m compileall -b -f $app_file $app_dir");
  }

  /**
   * Copy scripts to the app directory
   *
   * @return int
   */
  private function copyScripts(): int {
    $path = $this->path . "/app";
    $tree = CMbPath::getTree($path);

    $patterns = self::EXCLUDE_PATTERNS;

    // Exclude modules resources for other platforms
    $platforms = self::PLATFORMS;
    unset($platforms[array_search($this->platform, self::PLATFORMS)]);

    foreach ($platforms as $_platform) {
      $patterns[] = "/resources\/$_platform/";
    }

    self::filterTree($tree, $patterns);

    // Copy files
    $target = "$this->setup_path/package/app";

    $all_files     = self::keysRecursive($tree);
    $prefix_length = mb_strlen($path);

    $count = 0;

    foreach ($all_files as $_file) {
      if (is_dir($_file)) {
        continue;
      }

      $from = $_file;
      $to   = $target . substr($_file, $prefix_length);

      CMbPath::forceDir(dirname($to));
      copy($from, $to);
      $count++;
    }

    return $count;
  }

  /**
   * Compile setup executable
   *
   * @return string
   * @throws Exception
   */
  private function compileSetup(): string {
    $prerequisites = $this->standalone ? "_alone" : "";
    $version = self::escapeArg($this->version);

    switch ($this->platform) {
      case self::PLATFORM_LINUX:
        $installer_script = self::escapeArg("$this->setup_path/make_deb.sh");
        return $this->exec("sh $installer_script");

      case self::PLATFORM_MACOS:
        return $this->exec("sh $this->setup_path/install.sh $version $prerequisites");

      case self::PLATFORM_WIN:
        //installer.nsi ou installer_alone.nsi
        $nsi = self::escapeArg("$this->setup_path/installer" . $prerequisites . ".nsi");
        $this->log("makensis /DPRODUCT_VERSION=$version /DARCHI=$this->architecture /DPREREQUISITES=$prerequisites $nsi", self::LOG_TITLE);

        return $this->exec("makensis /DPRODUCT_VERSION=$version /DARCHI=$this->architecture /DPREREQUISITES=$prerequisites $nsi");

      default:
        throw new Exception("Unknown platform : $this->platform (authorized platforms : Windows, WINNT, WIN32, Macos, Darwin, Linux)");
    }
  }

  /**
   * Code signing with certificate
   *
   * @return string
   * @throws Exception
   */
  private function signSetup(): string {
    switch ($this->platform) {
      case self::PLATFORM_WIN:
        $signtool = self::escapeArg("$this->setup_path/signtool.exe");

        self::log("$signtool sign /fd SHA256 /a /f $this->cer_path /p $this->cer_pass $this->setup_path/$this->package_name", self::LOG_TITLE);

        return $this->exec("$signtool sign /fd SHA256 /a /f $this->cer_path /p $this->cer_pass $this->setup_path/$this->package_name");

      case self::PLATFORM_MACOS:
        ## Premiere utilisation du certificat sur la machine qui compile : "security import $certificate -k ~/Library/Keychains/login.keychain -P $password"
        ## Pour ajouter le certificat dans le trousseau
        return $this->exec("codesign -s 'OPEN X TREM SAS' $this->setup_path/$this->package_name");

      default:
        return ("Erreur : impossible de signer pour la plateforme ($this->platform)");
    }
  }

  /**
   * Clear temporary files
   *
   * @return void
   * @throws Exception
   */
  private function clearFiles(): void {
    try {
      $this->remove("$this->setup_path/package/python");
      $this->remove("$this->setup_path/package/app");
    }
    catch (IOException $e) {
      throw new Exception("Error while removing : '$this->setup_path/package/python' not found. Maybe the compilation failed ?");
    }
    mkdir("$this->setup_path/package/app");
  }


  /**
   * Get source code version, given in app/mbhost/__init__.py
   *
   * @return string
   */
  private static function getSourceCodeVersion(): string {

    $init = self::getPath() . "/app/mbhost/__init__.py";

    $version = null;
    $lines   = file($init);

    foreach ($lines as $_line) {
      if (preg_match('/VERSION\s*=\s*["\'](?P<version>[^"\']+)["\']/', $_line, $matches)) {
        $version = $matches['version'];
        break;
      }
    }

    //Pattern x.x.x
    if (!preg_match("/^(\d+\.)(\d+\.)(\d+)$/", $version)) {
      throw new InvalidArgumentException("Invalid Version Format : $version (X.X.X expected)");
    }

    return $version;
  }

  /**
   * Set Source Code Version
   *
   * @param string $version
   * @return void
   */
  private function setSourceCodeVersion(string $version): void {
    //Pattern x.x.x
    if (!preg_match("/^(\d+\.){2}(\d+)$/", $version)) {
      throw new InvalidArgumentException("Invalid Version Format : $version (X.X.X expected)");
    }

    $init_file = self::getPath() . "/app/mbhost/__init__.py";

    $init_content = file_get_contents($init_file);
    $init_content = preg_replace("/VERSION =.*/", 'VERSION = "' . $version . '"', $init_content);

    file_put_contents($init_file, $init_content);
  }

  /**
   * Modifie le fichier config_dist.ini pour indiquer au service mbHost s'il faut installer les prérequis ("normal") ou non ("alone")
   * lors d'une mise à jour
   *
   * @param string $type Type de mise à jour (avec ou sans prérequis)
   *
   * @return int|false
   */
  private function setTypeMaj(string $type = "True") {
    $system_config_file = self::getPath() . "/app/mbhost/modules/system/config_dist.ini";

    $system_config = file_get_contents($system_config_file);
    $system_config = preg_replace("/type_maj =.*/", "type_maj = $type", $system_config);

    return file_put_contents($system_config_file, $system_config);
  }

  /**
   * Get application path
   *
   * @return string The path
   * @throws InvalidArgumentException
   */
  private static function getPath(): string {
    $app_path = self::getConf("mbHost app_path");

    if (!$app_path || !is_dir($app_path)) {
      throw new InvalidArgumentException("'$app_path' is not a valid directory");
    }

    return $app_path;
  }

  /**
   * Récupère une config d'instance en utilisant cli/getConfig.php
   *
   * @param $conf_str
   *
   * @return string
   */
  private static function getConf(string $conf_str): string {
    $getConfig = dirname(dirname(__DIR__)) . "/getConfig.php";

    return exec("php -f $getConfig ". escapeshellarg($conf_str));
  }

  /**
   * Get all keys from a tree, as a flat array
   *
   * @param array $array The tree
   *
   * @return array
   */
  private static function keysRecursive(array $array): array {
    $keys = array_keys($array);

    foreach ($array as &$i) {
      if (is_array($i)) {
        $keys = array_merge($keys, self::keysRecursive($i));
      }
    }

    return $keys;
  }

  /**
   * Filter a tree of paths
   *
   * @param array &$tree     The tree
   * @param array  $patterns Patterns to remove
   *
   * @return void
   */
  private static function filterTree(array &$tree, $patterns = array()) {
    foreach ($tree as $path => &$name) {
      foreach ($patterns as $_pattern) {
        if (preg_match($_pattern, $path)) {
          unset($tree[$path]);
          break;
        }
      }

      if (is_array($name)) {
        self::filterTree($name, $patterns);
      }
    }
  }

  /**
   * Escape an argument
   *
   * @param string $arg Arg
   *
   * @return string
   */
  private static function escapeArg(string $arg) {
    return escapeshellarg(str_replace("/", DIRECTORY_SEPARATOR, $arg));
  }

  /**
   * Executes a command
   *
   * @param string $cmd Command
   *
   * @return string Command output
   */
  private function exec(string $cmd) {
    $out = array();
    exec($cmd, $out);

    return implode("\n", $out);
  }

  /**
   * remove directory recursively
   *
   * @param $path
   *
   * @throws Exception
   */
  private function remove($path) {

    $this->fs->remove($path);
  }

  private function addPathmsgfmt() {
    if ($this->platform == self::PLATFORM_MACOS) {
      $env_path = getenv("PATH");
      putenv("PATH=$env_path:/usr/local/opt/gettext/bin");
    }
  }


  /**
   * @inheritdoc
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    $style = new OutputFormatterStyle('blue', null, array('bold'));
    $output->getFormatter()->setStyle('b', $style);

    $style = new OutputFormatterStyle(null, 'red', array('bold'));
    $output->getFormatter()->setStyle('error', $style);
  }

  private function log($text, $type = self::LOG_INFO): void {
    switch ($type) {
      default:
      case self::LOG_INFO:
        $color = "white";
        break;
      case self::LOG_TITLE:
        $color = "green";
        break;
      case self::LOG_ERROR:
        $color = "red";
        break;
    }
    $this->output->writeln(strftime("[%Y-%m-%d %H:%M:%S]"). " <fg={$color};bg=black>$text</fg={$color};bg=black>");
  }

  private function getParams(): void {
    $architecture     = $this->input->getOption('architecture');
    $this->standalone = $this->input->getOption('standalone');
    $platform         = $this->input->getOption('platform');
    $version          = $this->input->getOption('release');

    if (!in_array($architecture, array("x86", "x64", "x32", ""))) {
      throw new Exception("Unknown architecture : $architecture (authorized architectures : x86, x64, x32)");
    }

    if ($architecture == "x32") {
      $architecture = "x86";
    }

    if (!$platform) {
      $platform = PHP_OS;
    }

    $prerequisites      = $this->standalone ? "_alone" : "";
    $this->package_name = "MediboardHost-setup";

    switch ($platform) {
      case "Windows":
        // No break
      case "WINNT":
        $this->platform     = self::PLATFORM_WIN;
        $this->architecture = $architecture ?: "x64";
        $this->package_name .= $prerequisites . "_" . $this->architecture . ".exe";
        break;

      case "WIN32":
        $this->platform     = self::PLATFORM_WIN;
        $this->architecture = $architecture ?: "x86";
        $this->package_name .= $prerequisites . "_" . $this->architecture . ".exe";
        break;

      case "Darwin":
        // No break
      case "Macos":
        $this->platform     = self::PLATFORM_MACOS;
        $this->package_name .= $prerequisites . ".dmg";
        break;

      case "Linux":
        $this->platform     = self::PLATFORM_LINUX;
        $this->package_name .= $prerequisites . ".deb";
        break;

      default:
        throw new Exception("Unknown platform : $platform (authorized platforms : Windows, WINNT, WIN32, Macos, Darwin, Linux)");
    }

    $this->path = realpath($this->getPath());

    if (!$version) {
      $version = self::getSourceCodeVersion();
    }
    else {
      self::setSourceCodeVersion($version);
    }

    $this->version = $version;

    $this->setup_path = "$this->path/setup/$this->platform";

    $this->cer_path = self::getConf("mbHost cer_path");
    $this->cer_pass = self::getConf("mbHost cer_password");

    if (!$this->cer_path || !$this->cer_pass) {
      throw new Exception("Code Signing Certificate configurations fault. Please check them");
    }

    $this->output->writeln(
<<<EOT
  <fg=cyan;bg=black>
    MbHost Installation : 
    $this->setup_path/$this->package_name
    Version : $this->version
  
  </fg=cyan;bg=black>
EOT
    );
  }

  /**
   * @param Question $question
   *
   * @return mixed
   */
  private function ask(Question $question) {
    return $this->question_helper->ask($this->input, $this->output, $question);
  }
}
