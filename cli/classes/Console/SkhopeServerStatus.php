<?php

/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\Console;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use Ox\Cli\MediboardCommand;
use Ox\Core\HttpClient\Client;
use Ox\Core\SHM;
use Ox\Mediboard\System\CSourceHTTP;
use PDO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SkhopeServerStatus
 * @package Ox\Cli\Console
 */
class SkhopeServerStatus extends MediboardCommand
{
    /** @var InputInterface */
    protected $input;

    /** @var OutputInterface */
    protected $output;

    /** @var string */
    protected $token;

    /** @var array */
    protected static $db_infos;

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('skhopeServer:sendStatus')
            ->setDescription('Send status and boxes')
            ->addOption(
                'token',
                't',
                InputOption::VALUE_REQUIRED,
                'The local token to authenticate on local server'
            );
    }

    /**
     * @return void
     * @throws Exception
     *
     */
    protected function getParams(): void
    {
        $this->token = $this->input->getOption('token');

        if (!$this->token) {
            throw new Exception('Token is mandatory');
        }
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        try {
            SHM::init();
            $this->getParams();
            $this->send();
        } catch (Exception $e) {
            $this->out($this->output, '<error>Failed to send status\n' . $e->getMessage() . '</error>');

            return 1;
        }

        $this->out($this->output, 'Status sent');

        return null;
    }

    /**
     * Envoi des sondes
     *
     * @throws Exception
     */
    private function send(): void
    {
        $token = $this->getStaticConf('skhopeServer general token');

        if (!$token) {
            throw new Exception('Missing token to connect to ERP instance');
        }

        $body = [];
        $monitor_group_id = $this->getStaticConf('skhopeServer general monitor_group_id');

        foreach ($this->getBoxes() as $_box) {
            $body[$_box['hostname']] = $this->getStatuses($_box['box_server_id']);
        }

        $source        = new CSourceHTTP();
        $source->host  = $this->getStaticConf('skhopeServer general url_erp', 'https://erp.openxtrem.com');
        $source->token = $token;

        $guzzle_client = new GuzzleClient(
            [
                'timeout' => 60,
            ]
        );

        // Send to master
        $client = new Client($source, $guzzle_client);
        $client->setTokenHeader();

        $response = $client->call(
            Request::METHOD_POST,
            trim($source->host, '/') . '/api/skhopeMaster/addStatuses/' . $monitor_group_id,
            json_encode($body)
        );

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            throw new Exception($response->getGuzzleResponse()->getReasonPhrase());
        }

        // Mark statuses as sent
        $this->markStatusesAsSent($body);

        // Update boxes list from response
        $source->token = $this->token;
        $client->setTokenHeader();

        $response = $client->call(
            'POST',
            trim($this->getInstanceConf('base_url'), '/') . '/api/skhopeServer/updateBoxes',
            $response->getBody()
        );

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            throw new Exception($response->getGuzzleResponse()->getReasonPhrase());
        }
    }

    private function getBoxes(): array
    {
        $pdo = $this->getPDO();

        $stmt = $pdo->prepare(
            'SELECT `hostname`, `box_server_id` FROM `box_server` WHERE active = "1" ORDER BY `hostname`'
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getStatuses(int $box_server_id): array
    {
        $results = [];

        $pdo = $this->getPDO();

        $stmt = $pdo->prepare(
            'SELECT * FROM `box_status_server` WHERE `box_server_id` = :pattern AND sent != "1" ORDER BY `datetime` ASC'
        );
        $stmt->execute([':pattern' => $box_server_id]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $_status) {
            $_result = [
                'status'  => $_status,
                'wires'   => [],
                'sockets' => [],
                'drivers' => [],
            ];

            $box_status_server_id = $_result['status']['box_status_server_id'];

            $stmt = $pdo->prepare('SELECT * FROM `box_wire_server` WHERE `box_status_server_id` = :pattern');
            $stmt->execute([':pattern' => $box_status_server_id]);

            $_result['wires'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare('SELECT * FROM `box_socket_server` WHERE `box_status_server_id` = :pattern');
            $stmt->execute([':pattern' => $box_status_server_id]);

            $_result['sockets'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare('SELECT * FROM `box_driver_server` WHERE `box_status_server_id`= :pattern');
            $stmt->execute([':pattern' => $box_status_server_id]);

            $_result['drivers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $results[] = $_result;
        }

        return $results;
    }

    private function markStatusesAsSent(array $boxes_statuses): void
    {
        $pdo = $this->getPDO();

        foreach ($boxes_statuses as $_statuses) {
            foreach ($_statuses as $_status) {
                $box_status_server_id = $_status['status']['box_status_server_id'];
                $stmt                 = $pdo->prepare(
                    'UPDATE `box_status_server` SET sent = "1" WHERE box_status_server_id = :pattern'
                );
                $stmt->execute([':pattern' => $box_status_server_id]);
            }
        }
    }

    private function getPDO(string $db = 'std'): PDO
    {
        $db_infos = $this->getDbInfos($db);

        $dbname = $db_infos['dbname'];
        $dbhost = $db_infos['dbhost'];
        $dbuser = $db_infos['dbuser'];
        $dbpass = $db_infos['dbpass'];

        if (!$dbname || !$dbhost || !$dbuser) {
            throw new Exception('Missing informations to connect to the the skhopeServer database');
        }

        return new PDO("mysql:dbname={$dbname};host={$dbhost}", $dbuser, $dbpass);
    }

    private function getDbInfos(string $db = 'std'): array
    {
        if (isset(self::$db_infos[$db])) {
            return self::$db_infos[$db];
        }

        return self::$db_infos[$db] = $this->getInstanceConf("db {$db}");
    }

    /**
     * @param string $conf
     *
     * @return mixed
     */
    private function getInstanceConf(string $conf)
    {
        global $dPconfig;

        require __DIR__ . '/../../../includes/config_all.php';

        return $this->getConf($conf, $dPconfig);
    }

    /**
     * @param string      $conf
     * @param string|null $default
     *
     * @return mixed
     * @throws Exception
     */
    private function getStaticConf(string $conf, string $default = null)
    {
        $pdo = $this->getPDO();

        $stmt = $pdo->prepare("SELECT `value` FROM `configuration` WHERE feature = :feature AND static = '1';");
        $stmt->execute([':feature' => $conf]);

        $result = $stmt->fetch();

        return (isset($result[0])) ? $result[0] : $default;
    }
}
