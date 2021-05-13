<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\SvnClient\Command;

use Ox\Cli\SvnClient\WorkingCopy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CustomCommand
 *
 * @package Ox\Cli\SvnClient\Command
 */
class CustomCommand extends Command {

  /**
   * @return void
   */
  protected function configure() {
    $this
      ->setName('svn:cmd')
      ->setDescription("Custom command")
      ->addArgument(
        'path',
        InputArgument::REQUIRED,
        'The working copy dir'
      )
      ->addArgument(
        'cmd',
        InputArgument::REQUIRED,
        'The command to execute'
      );
  }

  /**
   * @param InputInterface  $input  Input Interface
   * @param OutputInterface $output Output Interface
   *
   * @return int|void|null
   * @throws \Ox\Cli\SvnClient\Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $path = $input->getArgument('path');
    $command = $input->getArgument('cmd');

    $wc = new WorkingCopy($path);
    var_dump($wc->$command());

    $output->writeln("'$command' command executed on '$path'");
  }
}