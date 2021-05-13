<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients;

use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CRequest;
use Ox\Core\CSQLDataSource;
use Ox\Mediboard\System\CObjectClass;

/**
 * Tools for state patient
 */
class CPatientStateTools implements IShortNameAutoloadable
{
    static $color = [
        "VIDE"   => "#FFFFEE",
        "PROV"   => "#33B1FF",
        "VALI"   => "#CC9900",
        "DPOT"   => "#9999CC",
        "ANOM"   => "#FF66FF",
        "CACH"   => "#B2B2B3",
        "merged" => "#EEA072",
    ];

    /**
     * Set the PROV status for the patient stateless
     *
     * @param String $state patient state
     *
     * @return int
     * @throws \Exception
     */
    static function createStatus($state = "PROV")
    {
        $ds = CSQLDataSource::get("std");

        $ds->exec("UPDATE `patients` SET `status`='$state' WHERE `status` IS NULL;");

        return $ds->affectedRows();
    }

    /**
     * Get the number patient stateless
     *
     * @return int
     * @throws \Exception
     */
    static function verifyStatus()
    {
        $patient = new CPatient();
        $where   = [
            "status" => "IS NULL",
        ];

        return $patient->countList($where);
    }

    /**
     * Get the patient by date
     *
     * @param string $before before date
     * @param string $now    now date
     *
     * @return array
     * @throws \Exception
     */
    static function getPatientStateByDate($before, $now)
    {
        $ds      = CSQLDataSource::get("std");
        $request = new CRequest(false);
        $request->addSelect("DATE(datetime) AS 'date', state, count(*) as 'total'");
        $request->addTable("patient_state");
        $request->addWhere("DATE(datetime) BETWEEN '$before' AND '$now'");
        $request->addGroup("DAY(datetime), state");

        return $ds->loadList($request->makeSelect());
    }

    /**
     * Get the patient merge by date
     *
     * @param string $before before date
     * @param string $now    now date
     *
     * @return array
     * @throws \Exception
     */
    static function getPatientMergeByDate($before, $now)
    {
        $ds = CSQLDataSource::get("std");
        $ds->exec("SET SESSION group_concat_max_len = 100000;");

        // User logs
        $where            = [
            "date >= '$before 00:00:00'",
            "date <= '$now 23:59:59'",
            "type = 'merge'",
            "object_class = 'CPatient'",
        ];
        $request_user_log = new CRequest(false);
        $request_user_log->addSelect(
            "DATE(date) AS 'date', COUNT(*) AS 'total', GROUP_CONCAT( object_id  SEPARATOR '-') as ids"
        );
        $request_user_log->addTable("user_log");
        $request_user_log->addWhere($where);
        $request_user_log->addGroup("DATE(date)");

        $user_log_results = $ds->loadList($request_user_log->makeSelect());

        // User actions
        $where = [
            "date >= '$before 00:00:00'",
            "date <= '$now 23:59:59'",
            "type = 'merge'",
            "object_class_id" => $ds->prepare('= ?', CObjectClass::getID('CPatient')),
        ];

        $request_user_action = new CRequest(false);
        $request_user_action->addSelect(
            "DATE(date) AS 'date', COUNT(*) AS 'total', GROUP_CONCAT( object_id  SEPARATOR '-') as ids"
        );
        $request_user_action->addTable("user_action");
        $request_user_action->addWhere($where);
        $request_user_action->addGroup("DATE(date)");

        $user_action_results = $ds->loadList($request_user_action->makeSelect());

        // Merging results from USER LOGS and USER ACTIONS
        $result_by_date = [];

        foreach ($user_log_results as $_result) {
            $_date                  = $_result['date'];
            $result_by_date[$_date] = $_result;
        }

        foreach ($user_action_results as $_result) {
            $_date = $_result['date'];

            if (!isset($result_by_date[$_date])) {
                $result_by_date[$_date] = $_result;
            } else {
                $result_by_date[$_date]['total'] += $_result['total'];
                $result_by_date[$_date]['ids']   .= '-' . $_result['ids'];
            }
        }

        // Creating final array
        $result = [];
        foreach ($result_by_date as $_date => $_result) {
            $result[] = [
                'date'  => $_date,
                'total' => $_result['total'],
                'ids'   => $_result['ids'],
            ];
        }

        return $result;
    }

    /**
     * Create the pie graph
     *
     * @param String[] $count_status number patient by status
     *
     * @return array
     */
    static function createGraphPie($count_status)
    {
        $series = [
            "title"   => "CPatientState.proportion",
            "count"   => null,
            "unit"    => lcfirst(CAppUI::tr("CPatient|pl")),
            "datum"   => [],
            "options" => null,
        ];

        $total = 0;
        foreach ($count_status as $_count) {
            $count             = $_count["total"];
            $status            = $_count["status"];
            $total             += $count;
            $series["datum"][] = [
                "label" => CAppUI::tr("CPatient.status.$status"),
                "data"  => $count,
                "color" => self::$color[$status],
            ];
        }

        $series["count"]   = $total;
        $series["options"] = [
            "series" => [
                "unit" => lcfirst(CAppUI::tr("CPatient|pl")),
                "pie"  => [
                    "innerRadius" => 0.5,
                    "show"        => true,
                    "label"       => [
                        "show"      => true,
                        "threshold" => 0.02,
                    ],
                ],
            ],
            "legend" => [
                "show" => false,
            ],
            "grid"   => [
                "hoverable" => true,
            ],
        ];

        return $series;
    }

    /**
     * Create the bar graph
     *
     * @param array   $values   number patient status by date
     * @param Integer $interval interval between two date
     *
     * @return array
     */
    static function createGraphBar($values, $interval)
    {
        $series2 = [
            "title"   => "CPatientState.dayproportion",
            "unit"    => lcfirst(CAppUI::tr("CPatient|pl")),
            "count"   => 0,
            "datum"   => null,
            "options" => [
                "xaxis"  => [
                    "position" => "bottom",
                    "min"      => 0,
                    "max"      => $interval + 1,
                    "ticks"    => [],
                ],
                "yaxes"  => [
                    "0" => [
                        "position"     => "left",
                        "tickDecimals" => false,
                    ],
                    "1" => [
                        "position" => "right",
                    ],
                ],
                "legend" => [
                    "show" => true,
                ],
                "series" => [
                    "stack" => true,
                ],
                "grid"   => [
                    "hoverable" => true,
                ],
            ],
        ];

        if (array_key_exists('merged', $values)) {
            $series2['options']['grid']['clickable'] = true;
        }

        $total = 0;
        $datum = [];
        foreach ($values as $_status => $_result) {
            $abscisse = -1;
            $data     = [];

            foreach ($_result as $_day => $_count) {
                // When merged patients searched, value if count + patient IDs
                if (is_array($_count) && $_status == 'merged') {
                    $_ids   = $_count['ids'];
                    $_count = $_count['count'];
                } else {
                    $_ids = null;
                }

                $abscisse                               += 1;
                $series2["options"]["xaxis"]["ticks"][] = [$abscisse + 0.5, CMbDT::transform(null, $_day, "%d/%m")];

                $data[] = [
                    $abscisse,
                    $_count,
                    'day' => CMbDT::transform(null, $_day, CAppUI::conf("date")),
                    'ids' => $_ids,
                ];

                $total += $_count;
            }

            $datum[] = [
                "data"  => $data,
                "yaxis" => 1,
                "label" => CAppUI::tr("CPatient.status." . $_status),
                "color" => self::$color[$_status],
                "unit"  => lcfirst(CAppUI::tr("CPatient|pl")),
                "bars"  => [
                    "show" => true,
                ],
            ];
        }

        $series2["datum"] = $datum;
        $series2['count'] = $total;

        return $series2;
    }
}
