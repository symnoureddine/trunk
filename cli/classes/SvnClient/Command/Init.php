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
 * Class Init
 *
 * @package Ox\Cli\SvnClient\Command
 */
class Init extends Command {

  /**
   * @return void
   */
  protected function configure() {
    $this
      ->setName('svn:init')
      ->setDescription("Init working copy")
      ->addArgument(
        'path',
        InputArgument::REQUIRED,
        'Where is the working copy ?'
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

    new WorkingCopy($path);

    $output->writeln("'$path' is a valid working copy");
  }
}