<?php

/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\Console;

use DateInterval;
use DateTime;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use Ox\Cli\MediboardCommand;
use Ox\Core\CMbDT;
use Ox\Core\CSQLDataSource;
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
 * Class SkhopeServerAction
 * @package Ox\Cli\Console
 */
class SkhopeServerAction extends MediboardCommand
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
            ->setName('skhopeServer:getActions')
            ->setDescription('Get actions to execute on the boxes')
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
            $this->getActions();
            $this->purgeOldActions();
        } catch (Exception $e) {
            $this->out($this->output, '<error>Failed to get actions\n' . $e->getMessage() . '</error>');

            return 1;
        }

        $this->out($this->output, 'Actions received');

        return null;
    }

    /**
     * Receive actions
     *
     * @throws Exception
     */
    private function getActions(): void
    {
        $token = $this->getStaticConf('skhopeServer general token');

        if (!$token) {
            throw new Exception('Missing token to connect to ERP instance');
        }

        $monitor_group_id = $this->getStaticConf('skhopeServer general monitor_group_id');
        $body             = $this->getActionsToSend();

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
            trim($source->host, '/') . '/api/skhopeMaster/getActions/' . $monitor_group_id,
            json_encode($body['external_ids'])
        );

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            throw new Exception($response->getGuzzleResponse()->getReasonPhrase());
        }

        $this->storeActions($response->getBody());
        $this->markActionsAsSent($body['internal_ids']);
    }

    private function getActionsToSend(): array
    {
        $results = [
            'external_ids' =>
                [
                    'driver' => [],
                    'reboot' => [],
                    'update' => [],
                 ],
            'internal_ids' =>
                [
                    'driver' => [],
                    'reboot' => [],
                    'update' => [],
                ]
        ];

        $pdo = $this->getPDO();

        $datetime = CMbDT::dateTime('-5 minutes');

        foreach (['driver', 'reboot', 'update'] as $_type) {
            $stmt = $pdo->prepare(
                "SELECT `box_action_{$_type}_server_id`, `box_action_{$_type}_id`
                 FROM `box_action_{$_type}_server`
                 WHERE `datetime` <= :datetime
                 AND `effectue` = '1'
                 AND `sent` != '1'
                 AND `box_action_{$_type}_id` IS NOT NULL
                 ORDER BY `datetime` ASC"
            );

            $stmt->execute(
                [
                    ':datetime' => $datetime,
                ]
            );

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $_action_driver) {
                $results['internal_ids'][$_type][] = $_action_driver["box_action_{$_type}_server_id"];
                $results['external_ids'][$_type][] = $_action_driver["box_action_{$_type}_id"];
            }
        }

        return $results;
    }

    private function markActionsAsSent(array $boxes_actions): void
    {
        $pdo = $this->getPDO();
        foreach ($boxes_actions as $_type => $ids) {
            $stmt                 = $pdo->prepare(
                "UPDATE `box_action_{$_type}_server` SET sent = '1' WHERE `box_action_{$_type}_server_id` " . CSQLDataSource::prepareIn($ids)
            );
            $stmt->execute();
        }
    }

    private function storeActions(array $body): void
    {
        $pdo = $this->getPDO();

        $now      = new DateTime();
        $datetime = $now->add(new DateInterval('PT1M'))->format('Y-m-d H:i:s');

        foreach ($body as $_hostname => $_actions_by_type) {
            $stmt = $pdo->prepare(
                "SELECT `box_server_id` FROM `box_server`
                 WHERE `hostname` = :pattern"
            );

            $stmt->execute([':pattern' => $_hostname]);
            $box_server_id = $stmt->fetch(PDO::FETCH_COLUMN);

            foreach ($_actions_by_type as $_type => $_action_details) {
                switch ($_type) {
                    case 'update':
                        if (!$_action_details['id']) {
                            break;
                        }

                        $stmt = $pdo->prepare(
                            "INSERT INTO `box_action_update_server`
                                 (`box_action_update_id`, `concentrator_version`, `concentrator`, `box_id`, `datetime`)
                             VALUES (
                                 :box_action_update_id,
                                 :concentrator_version,
                                 :concentrator,
                                 :box_id,
                                 :datetime
                             )"
                        );

                        $stmt->execute(
                            [
                                ':box_action_update_id' => $_action_details['id'],
                                ':concentrator_version' => $_action_details['version'],
                                ':concentrator'         => $_action_details['binary'],
                                ':box_id'               => $box_server_id,
                                ':datetime'             => $datetime,
                            ]
                        );
                        break;

                    case 'reboot':
                        if (is_countable($_action_details)) {
                            foreach ($_action_details as $_id_reboot) {
                                $stmt = $pdo->prepare(
                                    "INSERT INTO `box_action_reboot_server`
                                         (`box_action_reboot_id`, `box_id`, `datetime`)
                                     VALUES (:box_action_reboot_id, :box_id, :datetime)"
                                );
                                $stmt->execute(
                                    [
                                        ':box_action_reboot_id' => $_id_reboot,
                                        ':box_id'               => $box_server_id,
                                        ':datetime'             => $datetime,
                                    ]
                                );
                            }
                        }
                        break;

                    case 'drivers':
                        foreach ($_action_details['list'] as $_driver) {
                            $stmt = $pdo->prepare(
                                "INSERT INTO `box_action_driver_server`
                                     (
                                         `box_action_driver_id`,
                                         `driver_name`,
                                         `device_name`,
                                         `interface_type`,
                                         `ip`,
                                         `port_distant`,
                                         `port_local`,
                                         `box_id`,
                                         `datetime`
                                    )
                                 VALUES (
                                     :box_action_driver_id,
                                     :driver_name,
                                     :device_name,
                                     :interface_type,
                                     :ip,
                                     :port_distant,
                                     :port_local,
                                     :box_id,
                                     :datetime
                                 )"
                            );

                            $stmt->execute(
                                [
                                    ':box_action_driver_id' => array_shift($_action_details['ids']),
                                    ':driver_name'          => $_driver['driver_name'],
                                    ':device_name'          => $_driver['DeviceName'],
                                    ':interface_type'       => $_driver['InterfaceType'],
                                    ':ip'                   => $_driver['IP'],
                                    ':port_distant'         => $_driver['PortDistant'],
                                    ':port_local'           => $_driver['PortLocal'],
                                    ':box_id'               => $box_server_id,
                                    ':datetime'             => $datetime,
                                ]
                            );
                        }
                        break;

                    default:
                }
            }
        }
    }

    private function purgeOldActions()
    {
        $pdo = $this->getPDO();

        $interval         = new DateInterval('P1D');
        $interval->invert = 1;
        $date             = (new DateTime())->add($interval)->format('Y-m-d 23:59:59');

        foreach (['driver', 'reboot', 'update'] as $_type) {
            $stmt = $pdo->prepare("DELETE FROM `box_action_{$_type}` WHERE `datetime` <= :pattern");
            $stmt->execute([':pattern' => $date]);
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
