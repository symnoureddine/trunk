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
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class DeployMaJ
 *
 * @package Ox\Cli\Console
 */
class DeployMaJ extends DeployOperation {
  /**
   * @see parent::configure()
   */
  protected function configure() {
    $this
      ->setName('deploy:maj')
      ->setDescription('Synchronize MB with RSYNC')
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
      )
      ->addOption(
        'allow_trunk',
        't',
        InputOption::VALUE_NONE,
        'Allow TRUNK working copy for MASTER'
      );
  }

  /**
   * @see parent::showHeader()
   */
  protected function showHeader() {
    $this->output->writeln(
      <<<EOT
<fg=blue;bg=black>
       __  __     _        _
      |  \/  |   / \      | |
      | |\/| |  / _ \  _  | |
      | |  | | / ___ \| |_| |
      |_|  |_|/_/   \_\\\___/

</fg=blue;bg=black>
EOT
    );
  }

  /**
   * @param string $release_code Release code
   *
   * @return bool|mixed
   */
  protected function testBranch($release_code) {
    return ($this->master_branch == $release_code);
  }

  /**
   * @param array  $files         Array of files
   * @param string $instance      Instance
   * @param bool   $dry_run       Dry-run mode
   * @param bool   $merged_result Merged result
   *
   * @return array|bool
   */
  protected function rsync($files, $instance, $dry_run = false, &$merged_result = false) {
    $result = parent::rsync($files, $instance, $dry_run, $merged_result);

    if ($result) {
      foreach ($result as $_line) {
        if (is_array($merged_result)) {
          if (!isset($merged_result[$_line])) {
            $merged_result[$_line] = array();
          }

          $merged_result[$_line][] = $instance;
        }
      }
    }

    return $result;
  }

  /**
   * @param InputInterface  $input  Input Interface
   * @param OutputInterface $output Output Interface
   *
   * @return void
   * @throws Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->output           = $output;
    $this->input            = $input;
    $this->question_helper  = $this->getHelper('question');
    $this->path             = $input->getOption('path');
    $this->allow_trunk      = $input->getOption('allow_trunk');
    $this->ignore_externals = $input->getOption("update-ignore-externals");
    $this->update           = $input->getOption("update");

    $this->showHeader();

    if (!is_dir($this->path)) {
      throw new InvalidArgumentException("'$this->path' is not a valid directory.");
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
    $this->out($this->output, "Performing operation...");

    // External libraries installation
    $this->installLibraries();

    $files = $this->getIncludedAndExcluded();

    $merged_result = array();
    foreach ($to_perform as $_instance) {
      // RSYNC with dry run
      $this->rsync($files, $_instance, true, $merged_result);
    }

    // RSYNC file diff table output
    $this->showFileDiffTable($to_perform, $merged_result);

    if (!$this->question_helper->ask(
      $this->input,
      $this->output,
      new ConfirmationQuestion(
        'Confirm? [Y/n]',
        true
      )
    )
    ) {
      return;
    }

    // Progress bar
    $progress = new ProgressBar($this->output);
    $progress->start(count($to_perform));

    foreach ($to_perform as $_instance) {
      // RSYNC
      parent::rsync($files, $_instance);

      // Next progress bar step
      $progress->advance();
      $this->output->writeln("");
    }

    $progress->finish();

    // Re-check remote release
    $this->out($this->output, "Current instances release:");
    $instances = $this->checkBranches($instances);

    foreach ($instances as $_instance) {
      $perform = "error";

      if ($_instance["perform"]) {
        $perform = "info";
      }

      $this->output->writeln("- <$perform>" . $_instance["release_code"] . "</$perform> " . $_instance["path"]);
    }
  }
}
