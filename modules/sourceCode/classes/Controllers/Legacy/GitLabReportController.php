<?php
/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Erp\SourceCode\Controllers\Legacy;

use DateTime;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CLegacyController;
use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Core\CMbString;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Core\HttpClient\Client;
use Ox\Erp\SourceCode\Gitlab\Api\CGitLabApiClient;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabBranch;
use Ox\Erp\SourceCode\Gitlab\Entity\CGitlabProject;
use Ox\Erp\SourceCode\Gitlab\Manager\CGitlabManager;
use Ox\Mediboard\System\CExchangeSource;
use Ox\Mediboard\System\CSourceHTTP;


class GitLabReportController extends CLegacyController
{

    public function generate_gitlab_report()
    {
        $this->checkPermAdmin();

        $from_date  = CView::get("from_date", "dateTime notNull");
        $to_date    = CView::get("to_date", "dateTime notNull");
        $project_id = CView::get("project_id", "str");
        $branch_id  = CView::get("branch_id", "str");
        $debug      = CView::get('debug', 'str default|off') === 'on';
        $email      = CView::get('email', 'email', null);

        CView::checkin();

        try {
            $project     = false;
            $branch      = false;
            $project_uid = CGitLabApiClient::MEDIBOARD_PROJECT_ID;
            $branch_name = CGitlabManager::DEFAULT_BRANCH;

            if ($project_id) {
                $project     = CGitlabProject::findOrFail(intval($project_id));
                $project_uid = $project->id;
                if ($branch_id) {
                    $branch      = CGitlabBranch::findOrFail(intval($branch_id));
                    $branch_name = $branch->name;
                }
            }

            $source = CExchangeSource::get('gitlab_api', CSourceHTTP::TYPE);
            if (!$source->_id) {
                throw new CMbException('CExchangeSource-error-Missing source');
            }

            // dates
            $until    = new DateTime($to_date);
            $until    = $until->format('Y-m-d\TH:i:s');
            $since    = new DateTime($from_date);
            $since    = $since->format('Y-m-d\TH:i:s');
            $until_dt = new DateTime($until);
            $since_dt = new DateTime($since);
            $debug    = $debug ? true : false;

            // debug
            if ($debug) {
                d($since_dt, 'since_dt');
                d($until_dt, 'until_dt');
                d($email, 'email');
            }

            // client
            $client = new Client($source);
            $gitlab = new CGitLabApiClient($client, $debug);

            $infos_repo     = $gitlab->getInfosRepository($project_uid, $branch_name, $since_dt, $until_dt);
            $infos_mr       = $gitlab->getInfosMR($project_uid, $branch_name, $since_dt, $until_dt);
            $infos_pipeline = $gitlab->getInfosPipeline($project_uid, $branch_name, $since_dt, $until_dt);
            $infos_tu       = $gitlab->getInfosTU($project_uid, $branch_name, $until_dt);
//            $infos_tf       = $gitlab->getInfosTF($project_uid, $branch_name, $until_dt);
            $infos_urls     = $gitlab->getInfosUrls();

            // Get coverage from report
//            $url         = $infos_urls['coverage'];
//            $report_html = file_get_contents($url);
//            $doc         = new DOMDocument();
//            @$doc->loadHTML($report_html);
//            $xpath                        = new DOMXpath($doc);
//            $coverage_detail              = [];
//            $coverage_detail['core_taux'] = $xpath->query('/html/body/div/div/table/tbody/tr[2]/td[3]/div')->item(
//                0
//            )->textContent;
//            $coverage_detail_core_lines   = $xpath->query('/html/body/div/div/table/tbody/tr[2]/td[4]/div')->item(
//                0
//            )->textContent;
//            $coverage_detail_core_lines   = explode('/', $coverage_detail_core_lines);
//            foreach ($coverage_detail_core_lines as $key => $value) {
//                $value                            = str_replace(chr(194), '', $value);
//                $value                            = utf8_decode($value);
//                $value                            = str_replace('?', '', $value);
//                $coverage_detail_core_lines[$key] = (int)$value;
//            }
//            [$coverage_detail['core_lines_cover'], $coverage_detail['core_lines_total']] = $coverage_detail_core_lines;
//
//            $coverage_detail['modules_taux'] = $xpath->query('/html/body/div/div/table/tbody/tr[3]/td[3]/div')->item(
//                0
//            )->textContent;
//            $coverage_detail_modules_lines   = $xpath->query('/html/body/div/div/table/tbody/tr[3]/td[4]/div')->item(
//                0
//            )->textContent;
//            $coverage_detail_modules_lines   = explode('/', $coverage_detail_modules_lines);
//            foreach ($coverage_detail_modules_lines as $key => $value) {
//                $value                               = str_replace(chr(194), '', $value);
//                $value                               = utf8_decode($value);
//                $value                               = str_replace('?', '', $value);
//                $coverage_detail_modules_lines[$key] = (int)$value;
//            }
//            [
//                $coverage_detail['modules_lines_cover'],
//                $coverage_detail['modules_lines_total'],
//            ] = $coverage_detail_modules_lines;

            // Statistics get data from last period
            $until_dt_stat = new DateTime($since);
            $since_dt_stat = new DateTime($since);

            // @todo: warning, the interval now depends of entry date parameters !
            $interval_stat = $until_dt->diff($since_dt);
            $since_dt_stat = $since_dt_stat->add($interval_stat);

            // Debug
            if ($debug) {
                d($interval_stat, 'interval_stat');
                d($since_dt_stat, 'since_dt_stat');
                d($until_dt_stat, 'until_dt_stat');
            }

            // Get data from api
            $infos_repo_stat = $gitlab->getInfosRepository($project_uid, $branch_name, $since_dt_stat, $until_dt_stat);
            $infos_mr_stat   = $gitlab->getInfosMR($project_uid, $branch_name, $since_dt_stat, $until_dt_stat);
            $infos_tu_stat   = $gitlab->getInfosTU($project_uid, $branch_name, $until_dt_stat);
//            $infos_tf_stat   = $gitlab->getInfosTF($project_uid, $branch_name, $until_dt_stat);

            $diff_commits = $infos_repo['commits'] - $infos_repo_stat['commits'];
            $diff_mr      = $infos_mr['total'] - $infos_mr_stat['total'];
            $diff_tu       = $infos_tu['output']['Tests'] - $infos_tu_stat['output']['Tests'];
//            $diff_tf       = $infos_tf['output']['Tests'] - $infos_tf_stat['output']['Tests'];
            $diff_coverage = $infos_tu['coverage'] - $infos_tu_stat['coverage'];

            $stats = [
                'commits'          => $infos_repo_stat['commits'],
                'commits_percent'  => $infos_repo_stat['commits'] > 0 ? round(
                    ($diff_commits / $infos_repo_stat['commits']) * 100
                ) : 100,
                'mr'               => $infos_mr_stat['total'],
                'mr_percent'       => $infos_mr_stat['total'] > 0 ? round(
                    ($diff_mr / $infos_mr_stat['total']) * 100
                ) : 100,
                'tu'               => $infos_tu_stat['output']['Tests'],
                'tu_percent'       => $infos_tu_stat['output']['Tests'] > 0 ? round(
                    ($diff_tu / $infos_tu_stat['output']['Tests']) * 100
                ) : 100,
//                'tf'               => $infos_tf_stat['output']['Tests'],
//                'tf_percent'       => $infos_tf_stat['output']['Tests'] > 0 ? round(
//                    ($diff_tf / $infos_tf_stat['output']['Tests']) * 100
//                ) : 100,
                'coverage'         => $infos_tu_stat['coverage'],
                'coverage_percent' => $infos_tu_stat['coverage'] > 0 ? round(
                    ($diff_coverage / $infos_tu_stat['coverage']) * 100
                ) : 100,
            ];

            // Tpl
            $tpl_vars = [
                'project_name' => ucfirst($project->name),
                'branch_name' => $branch_name,
                'year' => (new DateTime())->format('Y'),
                'since_format' => $since_dt->format('d M Y H:i'),
                'until_format' => $until_dt->format('d M Y H:i'),
                'infos_repo' => $infos_repo,
                'infos_mr' => $infos_mr,
                'infos_tu' => $infos_tu,
                'infos_urls' => $infos_urls,
                'stats' => $stats,
                'stats_title' => 'since ' . $since_dt_stat->format('d M Y H:i') . ' until ' . $until_dt_stat->format(
                        'd M Y H:i'
                    )
            ];

            $html = $this->renderSmarty('inc_vw_gitlab_generated_report.tpl', $tpl_vars, null, true);

            if ($email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    // Send email
                    $html    = CMbString::purifyHTML($html);
                    $subject = sprintf(
                        'GitLab-CI %s : %s |  Rapport du %s au %s',
                        $project->name,
                        $branch_name,
                        $since_dt->format('d-m-Y'),
                        $until_dt->format('d-m-Y')
                    );

                    CApp::sendEmail($subject, $html, [], [], [], $email);
                } else {
                    CAppUI::setMsg('sourceCode-error-Invalid email format', UI_MSG_ERROR);
                }
            } else {
                echo $html;
            }
        } catch (CMbException $e) {
            CAppUI::setMsg($e->getMessage(), UI_MSG_ERROR);
        } catch (Exception $e) {
            CAppUI::setMsg($e->getMessage(), UI_MSG_ERROR);
        }

        echo CAppUI::getMsg(true);
    }

    public function vw_gitlab_report()
    {
        $this->checkPermAdmin();

        $to_date   = CValue::get("to_date", CMbDT::dateTime());
        $from_date = CMbDT::dateTime("-1 week", CValue::get("from_date", $to_date));

        CView::checkin();

        $this->renderSmarty(
            'vw_gitlab_report',
            [
                'from_date' => $from_date,
                'to_date'   => $to_date
            ]
        );
    }
}
