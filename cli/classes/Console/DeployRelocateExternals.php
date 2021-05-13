<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\Console;

use Ox\Cli\MediboardCommand;
use Ox\Cli\SvnClient\WorkingCopy;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * deploy:switchbranch command
 */
class DeployRelocateExternals extends MediboardCommand {
  /**
   * @see parent::configure()
   */
  protected function configure() {
    $aliases = array(
      'deploy:rx'
    );

    $this
      ->setName('deploy:relocateexternals')
      ->setAliases($aliases)
      ->setDescription('Relocate externals')
      ->setHelp('Change externals\' repository address')
      ->addArgument(
        'url',
        InputArgument::REQUIRED,
        'New repository URL'
      )
      ->addOption(
        'update',
        'u',
        InputOption::VALUE_OPTIONAL,
        'Update working copy after change'
      );
  }

  /**
   * @see parent::execute()
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $tail_paths = array("trunk", "branches");

    $url = rtrim($input->getArgument('url'), '/');
    $update = $input->getOption('update');

    if (preg_match('@/('.implode('|', $tail_paths).')@', $url, $matches)) {
      $pos = strpos($url, "/$matches[1]");

      $url = substr($url, 0, $pos);

      $this->out($output, "<b>Repository URL taken for relocation: '$url'</b>");
    }

    $wc = new WorkingCopy(realpath(__DIR__ . "/../../../"));

    // Switch externals
    $paths = array(
      ".",
      "modules",
      "style",
    );

    $source_urls = array();

    foreach ($paths as $_path) {
      $externals = @$this->getExternals($wc, $_path);
      $this->out($output, "Scanning '$_path' externals");
      if (!$externals) {
        continue;
      }

      foreach ($externals as $_external) {
        $parts = preg_split('/\s+/', $_external);

        foreach ($parts as $_part) {
          if (strpos($_part, ':') === false) {
            continue;
          }

          foreach ($tail_paths as $_tail_path) {
            if (($_pos = strpos($_part, '/'.$_tail_path)) > 0) {
              $_source_url = ltrim(substr($_part, 0, $_pos), '# ');

              if (!isset($source_urls[$_source_url])) {
                $source_urls[$_source_url] = 0;
              }

              $source_urls[$_source_url]++;
              break;
            }
          }
        }
      }
    }

    if (count($source_urls) == 0) {
      $this->out($output, "No externals properties found on this working copy");
      return;
    }

    $source_url_strings = array();
    foreach ($source_urls as $_source => $_count) {
      $source_url_strings[] = "$_source - <b>Used $_count times</b>";
    }

    $question_helper = new QuestionHelper();

    $question = new ChoiceQuestion(
      'Select source URLS',
      $source_url_strings,
      0
    );
    $question->setErrorMessage('Value "%s" is not valid');
    $question->setMultiselect(true);

    $selected_urls = $question_helper->ask(
      $input,
      $output,
      $question
    );

    $selected_urls = array_map(
      function ($i) {
        return trim(
          explode(
            '-',
            $i
          )[0]
        );
      },
      $selected_urls
    );

    foreach ($paths as $_path) {
      $externals = @$this->getExternals($wc, $_path, true);

      if (!$externals) {
        continue;
      }

      foreach ($selected_urls as $_source_url) {
        $externals = str_replace($_source_url, $url, $externals);
      }

      $wc->setProperty($_path, "svn:externals", $externals);

      $this->out($output, "Properties updated on '<b>$_path</b>'");
    }

    if ($update) {
      $this->out($output, $wc->update());
    }
  }

  /**
   * Get the externals property
   *
   * @param WorkingCopy $wc   Working copy
   * @param string      $path Path to get the svn:externals property of
   *
   * @return array|null
   */
  protected function getExternals(WorkingCopy $wc, $path, $raw = false) {
    $properties = $wc->listProperties($path);

    if (!isset($properties["svn:externals"])) {
      return null;
    }

    if ($raw) {
      return $properties["svn:externals"];
    }

    return preg_split("/[\r\n]+/", $properties["svn:externals"]);
  }
}
