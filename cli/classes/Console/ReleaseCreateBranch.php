<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\Console;

use DateTime;
use Ox\Cli\MediboardCommand;
use Ox\Cli\SvnClient\Repository;
use Ox\Core\CMbArray;
use RuntimeException;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Release:CreateBranch command
 */
class ReleaseCreateBranch extends MediboardCommand {
  private $repositories = array('gpl', 'oxol');

  /**
   * @inheritdoc
   */
  protected function configure() {
    $aliases = array(
      'release:branch',
    );

    $this
      ->setName('release:createbranch')
      ->setAliases($aliases)
      ->setDescription('Create branch')
      ->setHelp('Create a branch on given repository for the current month')
      ->addArgument(
        'repository',
        InputArgument::REQUIRED,
        'Wich repository ? Accepted values are ' . implode(', ', $this->repositories)
      )
      ->addOption(
        'branch_name',
        'b',
        InputOption::VALUE_OPTIONAL,
        'Branch name',
        $this->getDefaultBranchName()
      )
      ->addOption(
        'repository_url',
        'r',
        InputOption::VALUE_OPTIONAL,
        'Repository url',
        "https://svn.openxtrem.com/"
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->output = $output;
    $question_helper = new QuestionHelper();

    $this->showHeader();

    $repository_name = $input->getArgument('repository');
    $branch_name     = $input->getOption('branch_name');
    $repository_url  = $input->getOption('repository_url');

    if (!in_array($repository_name, $this->repositories)) {
      $msg = "$repository_name is not a valid repository. Allowed values are : " . implode(', ', $this->repositories);
      throw new InvalidArgumentException($msg);
    }

    if (!preg_match('/^\d{4}_\d{2}$/', $branch_name, $matches)) {
      $msg = "Bad branch name format. It should be like 2000_01, but '$branch_name' founded";
      throw new InvalidArgumentException($msg);
    }


    $repository = new Repository("$repository_url/$repository_name");
    $branches   = CMbArray::pluck($repository->getBranches(), 'name');
    if (in_array($branch_name, $branches)) {
      throw new RuntimeException("Branch '$branch_name' already exists.");
    }

    $question = new ConfirmationQuestion('Do you want to create branch '.$branch_name.' ? [Y/n]', true);
    if (!$question_helper->ask($input, $output, $question)) {
      return false;
    };

    $this->out($output, "Creating branch: $branch_name");

    list($year, $month) = explode('_', $branch_name);
    $str    = $this->getHumanReadableBranchName($branch_name);
    $return = $repository->branch($branch_name, "Scm : Création de la branche de $str");

    $this->out($output, $return);
  }

  /**
   * Gets branch name based on current date
   *
   * @return string
   */
  private function getDefaultBranchName() {
    $now   = new DateTime('now');
    $month = $now->format('m');
    $year  = $now->format('Y');

    return "{$year}_{$month}";
  }

  /**
   * Gets a human readable branch name (month in letter)
   *
   * @param string $branch_name Branch name
   *
   * @return string
   */
  private function getHumanReadableBranchName($branch_name = null) {
    if ($branch_name) {
      list($year, $month) = explode('_', $branch_name);
      $dt = new DateTime("$year-$month");
    }
    else {
      $dt = new DateTime('now');
    }

    setlocale(LC_TIME, 'fr_FR', 'fr', 'FR');

    return strftime('%B %Y', strtotime($dt->format('Y-m-d')));
  }

  /**
   * @inheritdoc
   */
  protected function showHeader() {
    $this->output->writeln(
      <<<EOT
<fg=blue;bg=black>
  ___ ___    _   _  _  ___ _  _ 
 | _ ) _ \  /_\ | \| |/ __| || |
 | _ \   / / _ \| .` | (__| __ |
 |___/_|_\/_/ \_\_|\_|\___|_||_|
</fg=blue;bg=black>
EOT
    );
  }
}
