<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\Console;

use Exception;
use Ox\Cli\DeployOperation;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;

/**
 * Class DeployMeQ
 *
 * @package Ox\Cli\Console
 */
class DeployMeQ extends DeployOperation {
  /**
   * @see parent::configure()
   */
  protected function configure() {
    $this
      ->setName('deploy:meq')
      ->setDescription('Synchronize MB TESTING with RSYNC')
      ->setHelp('Performs an RSYNC command from MB Master')
      ->addOption(
        'update',
        'u',
        InputOption::VALUE_NONE,
        'Performs an SVN update'
      )
      ->addOption(
        'update-ignore-externals',
        'i',
        InputOption::VALUE_NONE,
        'Performs an SVN update ignoring externals'
      )
      ->addOption(
        'path',
        'p',
        InputOption::VALUE_OPTIONAL,
        'Working copy root',
        realpath(__DIR__ . "/../../../")
      );
  }

  /**
   * @see parent::showHeader()
   */
  protected function showHeader() {
    $this->output->writeln(
      <<<EOT
<fg=green;bg=black>
       __  __  _____   ___
      |  \/  || ____| / _ \
      | |\/| ||  _|  | | | |
      | |  | || |___ | |_| |
      |_|  |_||_____| \__\_\

</fg=green;bg=black>
EOT
    );
  }

  /**
   * @param string $release_code
   *
   * @return bool|mixed
   */
  protected function testBranch($release_code) {
    return ($this->master_branch > $release_code);
  }

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int|void|null
   * @throws Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->output           = $output;
    $this->input            = $input;
    $this->question_helper  = $this->getHelper('question');
    $this->path             = $input->getOption('path');
    $this->ignore_externals = $input->getOption("update-ignore-externals");
    $this->update           = $input->getOption("update");

    $this->showHeader();

    if (!is_dir($this->path)) {
      throw new InvalidArgumentException("$this->path is not a valid directory.");
    }

    if (!$this->getMasterBranch()) {
      return;
    }

    if ($this->ignore_externals || $this->update) {
      $this->update();
    }

    if (!$this->checkExternalsBranch()) {
      throw new Exception('Working copy and externals are not on the same branch.');
    }

    $instances = $this->promptInstances();

    $this->out($this->output, "Checking branches...");
    $instances_with_branch = $this->checkBranches($instances);

    $this->out($this->output, "Result (<error>[branch]</error> will not be performed):\n");

    $to_perform = array();
    foreach ($instances_with_branch as $_instance) {
      $perform = "error";

      if ($_instance["perform"]) {
        $perform      = "info";
        $to_perform[] = $_instance["path"];
      }

      $this->output->writeln("- <$perform>" . $_instance["release_code"] . "</$perform> " . $_instance["path"]);
    }

    if (!$to_perform) {
      $this->out($this->output, "<error>No instance to update</error>");

      return;
    }

    // Ask confirmation by typing MASTER release code
    $this->confirmOperation();

    $this->out($this->output, "<info>Confirmation OK</info>");
    $this->out($this->output, "Performing operation...\n");

    // External libraries installation
    $this->installLibraries();

    $files = $this->getIncludedAndExcluded();

    // Progress bar
    $progress = new ProgressBar($this->output);
    $progress->start(count($to_perform));

    foreach ($to_perform as $_instance) {
      // RSYNC
      $this->rsync($files, $_instance);

      // Next progress bar step
      $progress->advance();
      $this->output->writeln("");
    }

    $progress->finish();

    // Re-check remote release
    $this->out($this->output, "Current instances release:");
    $instances = $this->checkBranches($instances);

    foreach ($instances as $_instance) {
      $perform = "info";

      if ($_instance["perform"]) {
        $perform = "error";
      }

      $this->output->writeln("- <$perform>" . $_instance["release_code"] . "</$perform> " . $_instance["path"]);
    }
  }
}
