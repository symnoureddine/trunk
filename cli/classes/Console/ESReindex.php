<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\Console;

use Ox\Cli\MediboardCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class ESReindex
 *
 * @package Ox\Cli\Console
 */
class ESReindex extends MediboardCommand {
  /** @var OutputInterface */
  protected $output;

  /** @var InputInterface */
  protected $input;

  /** @var QuestionHelper */
  protected $question_helper;

  /** @var string */
  protected $scroll_time;

  /** @var integer */
  protected $scroll_size;

  /** @var string */
  protected $to_index;

  /** @var string */
  protected $from_index;

  /** @var array */
  protected $types_to_index;

  /** @var string */
  protected $scroll_id;

  /** @var float */
  protected $start_time;

  /** @var float */
  protected $end_time;

  /**
   * @see parent::configure()
   */
  protected function configure() {
    $this
      ->setName('es:reindex')
      ->setDescription('Reindexing your ElasticSearch data with zero downtime')
      ->setHelp('Performs a scroll search and a bulk indexing')
      ->addArgument(
        'types_to_index',
        InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
        'Which mappings do you want to index?'
      );
  }

  /**
   * Display header information
   *
   * @return void
   */
  protected function showHeader() {
    $this->out($this->output, '<fg=red;bg=black>Reindexing your ElasticSearch data with zero downtime</fg=red;bg=black>');
  }

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int|void|null
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->input  = $input;
    $this->output = $output;
    $this->question_helper = $this->getHelper('question');

    $this->showHeader();
    $this->setParams();

    foreach ($this->types_to_index as $_mapping) {
      $this->reindex($_mapping);
    }
  }

  /**
   * @param  string $type_to_index
   * @return void
   */
  protected function reindex($type_to_index) {
    $this->start_time = microtime(true);

    do {
      $this->out($this->output, "Indexing $type_to_index...");

      $ch = curl_init();

      $url = "http://localhost:9200/$this->from_index/$type_to_index/_search?scroll=$this->scroll_time&size=$this->scroll_size";
      if ($this->scroll_id) {
        $url = "http://localhost:9200/_search/scroll?scroll=$this->scroll_time&scroll_id=$this->scroll_id";
      }
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      $this->out($this->output, "Requesting $url...");
      $output = curl_exec($ch);
      $this->out($this->output, "Request completed.");
      $result = json_decode($output, true);

      $this->scroll_id = $result['_scroll_id'];
      $hits      = $result['hits']['hits'];

      if (!$hits || empty($hits)) {
        $this->end_time = microtime(true);
        $this->out($this->output, "No data to send.");
        $this->out($this->output, "$type_to_index reindexing completed.");
        $this->out($this->output, "Elapsed time: " . ($this->end_time - $this->start_time));

        $next_type_to_index = next($this->types_to_index);
        $question_label = 'Reindex another mapping? [y/N]';
        if ($next_type_to_index) {
          $question_label = 'Reindex another mapping ? (Next mapping: '.$next_type_to_index.') [y/N]';
        }

        $question = new ConfirmationQuestion(
          $question_label,
          false
        );

        if ($this->question_helper->ask($this->input, $this->output, $question)) {
          if ($next_type_to_index) {
            continue;
          }
          else {
            $this->askForReindexing();
          }
        }
        else {
          $this->out($this->output, 'Exiting...');
          exit();
        }
      }

      $formatted_hits = array();
      foreach ($hits as $_k => $_hit) {
        $_hit['_index'] = $this->to_index;

        $formatted_hits[] = json_encode(array('index' => array('_id' => $_hit['_id'])));
        $formatted_hits[] = json_encode($_hit['_source']);
      }
      $formatted_hits = implode("\n", $formatted_hits) . "\n";

      // Save data into temporary memfile
      $fp = fopen('php://temp', 'r+');
      fwrite($fp, $formatted_hits);
      rewind($fp);

      $url = "http://localhost:9200/$this->to_index/$type_to_index/_bulk";

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_PUT, true);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
      curl_setopt($ch, CURLOPT_INFILE, $fp);
      curl_setopt($ch, CURLOPT_INFILESIZE, strlen($formatted_hits));

      $this->out($this->output, "Sending data...");
      curl_exec($ch);
      $this->out($this->output, "Data sent.");
      fclose($fp);

    } while ($hits && $this->scroll_id);
  }

  /**
   * @return void
   */
  protected function askForReindexing() {
    $question = new Question("Mapping to index: ");
    $question->setValidator(
      function ($answer) {
        if (!trim($answer)) {
          throw new \RunTimeException("You have to select a mapping");
        }
        return $answer;
      }
    );

    $type_to_index = $this->question_helper->ask(
      $this->input,
      $this->output,
      $question
    );

    $this->types_to_index = array();
    $this->reindex($type_to_index);
  }

  /**
   * @return void
   */
  protected function setParams() {

    /*
     * Scroll time
     */
    $question = new Question("Select scroll time, ie 10m: ");
    $question->setValidator(
      function ($answer) {
        if (!preg_match('/\d+(s|m|h|d|w|M|y)/', trim($answer))) {
          throw new \RunTimeException("Wrong scroll time format, ie '10m': $answer");
        }
        return $answer;
      }
    );
    $this->scroll_time = $this->question_helper->ask(
      $this->input,
      $this->output,
      $question
    );

    /*
     * Scroll size
     */
    $question = new Question("Select scroll size, ie 100: ");
    $question->setValidator(
      function ($answer) {
        if (!preg_match('/\d+/', trim($answer))) {
          throw new \RunTimeException("Wrong scroll size format, ie '100': $answer");
        }
        return $answer;
      }
    );
    $this->scroll_size = $this->question_helper->ask(
      $this->input,
      $this->output,
      $question
    );

    /*
     * From index
     */
    $question = new Question("Index to index from: ");
    $question->setValidator(
      function ($answer) {
        if (!trim($answer)) {
          throw new \RunTimeException("You have to select an index name.");
        }
        return $answer;
      }
    );
    $this->from_index = $this->question_helper->ask(
      $this->input,
      $this->output,
      $question
    );

    /*
     * To index
     */
    $question = new Question("Index to index to: ");
    $question->setValidator(
      function ($answer) {
        if (!trim($answer)) {
          throw new \RunTimeException("You have to select an index name.");
        }
        return $answer;
      }
    );
    $this->to_index = $this->question_helper->ask(
      $this->input,
      $this->output,
      $question
    );


    $this->types_to_index = $this->input->getArgument('types_to_index');
    if (!$this->types_to_index) {
      $this->out($this->output, '<fg=red;bg=black>No mapping selected, please select a mapping to index.</fg=red;bg=black>');

      /**
       * Mapping to index
       */
      $question = new Question("Mapping to index: ");
      $question->setValidator(
        function ($answer) {
          if (!trim($answer)) {
            throw new \RunTimeException("You have to select a mapping");
          }
          return $answer;
        }
      );
      $this->types_to_index[] = $this->question_helper->ask(
        $this->input,
        $this->output,
        $question
      );
    }
  }
}
