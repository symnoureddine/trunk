<?php
/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Erp\SourceCode;

use Ox\Core\CSetup;

/**
 * Code Source Setup class
 */
class CSetupSourceCode extends CSetup
{

    /**
     * @see parent::__construct()
     */
    function __construct()
    {
        parent::__construct();

        $this->mod_name = "sourceCode";
        $this->makeRevision("0.0");

        $this->makeRevision("0.01");

        $query = "CREATE TABLE `refactoring` (
                `refactoring_id` INT (11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `label` VARCHAR (255) NOT NULL,
                `description` TEXT NOT NULL,
                `status` ENUM ('new','inprogress','closed','postponed') NOT NULL DEFAULT 'new',
                `creation_date` DATETIME NOT NULL,
                `closing_date` DATETIME,
                `progress_max` INT (11) UNSIGNED,
                `progress_current` INT (11) UNSIGNED
              )/*! ENGINE=MyISAM */;";
        $this->addQuery($query);
        $this->makeRevision('0.07');

        $query = "ALTER TABLE `refactoring` 
                ADD (`regexp` VARCHAR (255),
                     `file_mask` VARCHAR (255))";

        $this->addQuery($query);
        $this->makeRevision('0.08');

        $query = "ALTER TABLE `refactoring` 
                ADD (`exclude_dir` VARCHAR (255))";

        $this->addQuery($query);
        $this->makeRevision('0.09');

        $this->addPrefQuery('sourceCode_enable_auto_refresh', '1');
        $this->addPrefQuery('sourceCode_auto_refresh_interval', '300');

        $this->makeRevision('0.10');

        $query = "CREATE TABLE `code_style_violation` (
                `code_style_violation_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `commit_file_item_id` INT (11) UNSIGNED NOT NULL,
                `code_style_sniff_id` INT (11) UNSIGNED NOT NULL,
                `message` VARCHAR (255) NOT NULL,
                `column` INT (11) NOT NULL DEFAULT '0',
                `line` INT (11) NOT NULL DEFAULT '0'
              )/*! ENGINE=MyISAM */;";
        $this->addQuery($query);

        $query = "ALTER TABLE `code_style_violation` 
                ADD INDEX (`commit_file_item_id`),
                ADD INDEX (`code_style_sniff_id`);";
        $this->addQuery($query);

        $query = "CREATE TABLE `code_style_correction` (
                `code_style_correction_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `commit_file_item_id` INT (11) UNSIGNED NOT NULL,
                `code_style_sniff_id` INT (11) UNSIGNED NOT NULL,
                `message` VARCHAR (255) NOT NULL,
                `column` INT (11) NOT NULL DEFAULT '0',
                `line` INT (11) NOT NULL DEFAULT '0'
              )/*! ENGINE=MyISAM */;";
        $this->addQuery($query);

        $query = "ALTER TABLE `code_style_correction` 
                ADD INDEX (`commit_file_item_id`),
                ADD INDEX (`code_style_sniff_id`);";
        $this->addQuery($query);

        $query = "CREATE TABLE `code_style_sniff` (
                `code_style_sniff_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `standard` VARCHAR (64) NOT NULL,
                `category` VARCHAR (64) NOT NULL,
                `type` VARCHAR (64) NOT NULL,
                `error` VARCHAR (64)
              )/*! ENGINE=MyISAM */;";
        $this->addQuery($query);

        $query = "CREATE TABLE `commit` (
                `commit_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `revision` INT (11) NOT NULL,
                `repository_id` INT (11) UNSIGNED NOT NULL,
                `branch_id` INT (11) UNSIGNED NOT NULL,
                `datetime` DATETIME NOT NULL,
                `mediuser_id` INT (11) UNSIGNED NOT NULL,
                `message` VARCHAR (255) NOT NULL,
                `type` VARCHAR (6)
              )/*! ENGINE=MyISAM */;";
        $this->addQuery($query);

        $query = "ALTER TABLE `commit` 
                ADD INDEX (`repository_id`),
                ADD INDEX (`branch_id`),
                ADD INDEX (`mediuser_id`),
                ADD INDEX (`datetime`);";
        $this->addQuery($query);

        $query = "CREATE TABLE `commit_file` (
                `commit_file_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `path` VARCHAR (255) NOT NULL
              )/*! ENGINE=MyISAM */;";
        $this->addQuery($query);

        $query = "CREATE TABLE `commit_file_item` (
                `commit_file_item_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `commit_id` INT (11) UNSIGNED NOT NULL,
                `commit_file_id` INT (11) UNSIGNED NOT NULL
              )/*! ENGINE=MyISAM */;";
        $this->addQuery($query);

        $query = "ALTER TABLE `commit_file_item` 
                ADD INDEX (`commit_id`),
                ADD INDEX (`commit_file_id`);";
        $this->addQuery($query);

        $query = "CREATE TABLE `repository` (
                `repository_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `name` VARCHAR (255) NOT NULL,
                `url` VARCHAR (255) NOT NULL
              )/*! ENGINE=MyISAM */;";
        $this->addQuery($query);

        $query = "CREATE TABLE `branch` (
                `branch_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `name` VARCHAR (255) NOT NULL
              )/*! ENGINE=MyISAM */;";
        $this->addQuery($query);

        $this->makeRevision("0.11");
        $this->setModuleCategory("erp", "ox");

        $this->makeRevision("0.12");

        $query = "CREATE TABLE `gitlab_project` (
                `ox_gitlab_project_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `id` INT (11) UNSIGNED NOT NULL,
                `name` VARCHAR (255),
                `name_with_namespace` VARCHAR (255),
                `web_url` VARCHAR (255) NOT NULL,
                `ready` ENUM ('0','1') NOT NULL DEFAULT '0'
              )/*! ENGINE=MyISAM */;
              ALTER TABLE `gitlab_project` 
                ADD INDEX (`id`);";
        $this->addQuery($query);

        $query = "CREATE TABLE `gitlab_branch` (
                `ox_gitlab_branch_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `ox_gitlab_project_id` INT (11) UNSIGNED NOT NULL,
                `name` VARCHAR (255) NOT NULL,
                `web_url` VARCHAR (255) NOT NULL
              )/*! ENGINE=MyISAM */;
              ALTER TABLE `gitlab_branch` 
                ADD INDEX (`ox_gitlab_project_id`);";
        $this->addQuery($query);

        $query = "CREATE TABLE `gitlab_commit` (
                `ox_gitlab_commit_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `id` VARCHAR (40) NOT NULL,
                `short_id` VARCHAR (40) NOT NULL,
                `title` VARCHAR (255) NOT NULL,
                `message` TEXT DEFAULT NULL,
                `author_email` VARCHAR (255) NOT NULL,
                `web_url` VARCHAR (255) NOT NULL,
                `type` VARCHAR (16) NOT NULL,
                `authored_date` DATETIME NOT NULL,
                `ox_user_id` INT (11) UNSIGNED
              )/*! ENGINE=MyISAM */;
            ALTER TABLE `gitlab_commit` 
                ADD INDEX (`id`),
                ADD INDEX (`authored_date`),
                ADD INDEX (`ox_user_id`);";
        $this->addQuery($query);

        $query = "CREATE TABLE `gitlab_commit_reference` (
                `ox_gitlab_commit_reference_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `ox_gitlab_commit_id` INT (11) UNSIGNED NOT NULL,
                `ox_gitlab_branch_id` INT (11) UNSIGNED NOT NULL
              )/*! ENGINE=MyISAM */;
            ALTER TABLE `gitlab_commit_reference` 
                ADD INDEX (`ox_gitlab_commit_id`),
                ADD INDEX (`ox_gitlab_branch_id`);";
        $this->addQuery($query);

        $this->makeRevision("0.13");

        $query = "TRUNCATE TABLE `gitlab_commit`;";
        $this->addQuery($query);

        $query = "ALTER TABLE `gitlab_commit` 
                  ADD COLUMN `ox_gitlab_branch_id` INT (11) UNSIGNED NOT NULL;";
        $this->addQuery($query);

        $query = "ALTER TABLE `gitlab_commit` 
                  ADD INDEX (`ox_gitlab_branch_id`);";
        $this->addQuery($query);

        $query = "DROP TABLE `gitlab_commit_reference`;";
        $this->addQuery($query);

        $this->makeRevision("0.14");

        $query = "CREATE TABLE `gitlab_pipeline` (
                `ox_gitlab_pipeline_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `ox_gitlab_project_id` INT (11) UNSIGNED NOT NULL,
                `id` INT (11) NOT NULL,
                `status` VARCHAR (40) NOT NULL,
                `ref` VARCHAR (255) NOT NULL,
                `sha` VARCHAR (40) NOT NULL,
                `tag` VARCHAR (255),
                `created_at` DATETIME NOT NULL,
                `finished_at` DATETIME,
                `coverage` VARCHAR (20),
                `duration` VARCHAR (20),
                `web_url` VARCHAR (255)
              )/*! ENGINE=MyISAM */;";
        $this->addQuery($query);

        $this->makeRevision("0.15");

        $query = "ALTER TABLE `gitlab_pipeline` 
                ADD INDEX (`ox_gitlab_project_id`),
                ADD INDEX (`created_at`),
                ADD INDEX (`finished_at`);";
        $this->addQuery($query);

        $this->makeRevision("0.16");

        $query = "CREATE TABLE `gitlab_job` (
                `ox_gitlab_job_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `ox_gitlab_pipeline_id` INT (11) UNSIGNED NOT NULL,
                `id` INT (11) NOT NULL,
                `name` VARCHAR (60) NOT NULL,
                `stage` VARCHAR (40) NOT NULL,
                `status` VARCHAR (40) NOT NULL,
                `tag` VARCHAR (255),
                `created_at` DATETIME NOT NULL,
                `finished_at` DATETIME,
                `coverage` VARCHAR (20),
                `duration` VARCHAR (20),
                `web_url` VARCHAR (255)
              )/*! ENGINE=MyISAM */;";
        $this->addQuery($query);

        $this->makeRevision("0.17");

        $query = "ALTER TABLE `gitlab_job` 
                ADD INDEX (`ox_gitlab_pipeline_id`),
                ADD INDEX (`created_at`),
                ADD INDEX (`finished_at`);";
        $this->addQuery($query);

        $this->makeRevision("0.18");

        $query = "ALTER TABLE `gitlab_pipeline` 
                CHANGE `status` `status` VARCHAR (255) NOT NULL,
                CHANGE `coverage` `coverage` FLOAT,
                CHANGE `duration` `duration` INT (11) DEFAULT '0';";
        $this->addQuery($query);

        $this->makeRevision("0.19");

        $query = "ALTER TABLE `gitlab_job` 
                CHANGE `name` `name` VARCHAR (255) NOT NULL,
                CHANGE `stage` `stage` VARCHAR (255) NOT NULL,
                CHANGE `status` `status` VARCHAR (255) NOT NULL,
                CHANGE `coverage` `coverage` FLOAT,
                CHANGE `duration` `duration` INT (11) NOT NULL;";
        $this->addQuery($query);

        $this->makeRevision("0.20");

        $query = "CREATE TABLE `gitlab_job_tests_report` (
                `ox_gitlab_job_tests_report_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `ox_gitlab_job_id` INT (11) UNSIGNED NOT NULL,
                `tests` INT (11) NOT NULL DEFAULT '0',
                `assertions` INT (11) NOT NULL DEFAULT '0',
                `failures` INT (11) NOT NULL DEFAULT '0',
                `errors` INT (11) NOT NULL DEFAULT '0',
                `skipped` INT (11) NOT NULL DEFAULT '0',
                `incomplete` INT (11) NOT NULL DEFAULT '0',
                `risky` INT (11) NOT NULL DEFAULT '0',
                `classes_ratio` FLOAT UNSIGNED NOT NULL DEFAULT '0',
                `classes_covered` INT (11) NOT NULL DEFAULT '0',
                `classes_all` INT (11) NOT NULL DEFAULT '0',
                `methods_ratio` FLOAT UNSIGNED NOT NULL DEFAULT '0',
                `methods_covered` INT (11) NOT NULL DEFAULT '0',
                `methods_all` INT (11) NOT NULL DEFAULT '0',
                `lines_ratio` FLOAT UNSIGNED NOT NULL DEFAULT '0',
                `lines_covered` INT (11) NOT NULL DEFAULT '0',
                `lines_all` INT (11) NOT NULL DEFAULT '0'
              )/*! ENGINE=MyISAM */;";
        $this->addQuery($query);

        $this->makeRevision("0.21");

        $query = "ALTER TABLE `gitlab_job_tests_report` 
                ADD INDEX (`ox_gitlab_job_id`);";
        $this->addQuery($query);

        $this->makeRevision("0.22");

        $query = "CREATE TABLE `gitlab_job_class_report` (
                `ox_gitlab_job_class_report_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `ox_gitlab_job_tests_report_id` INT (11) UNSIGNED NOT NULL,
                `namespace` VARCHAR (255) NOT NULL,
                `class` VARCHAR (255) NOT NULL,
                `coverage` FLOAT NOT NULL DEFAULT '0.0',
                `lines_covered` INT (11) NOT NULL DEFAULT '0',
                `lines_all` INT (11) NOT NULL DEFAULT '0'
              )/*! ENGINE=MyISAM */;";
        $this->addQuery($query);

        $this->makeRevision("0.23");

        $query = "ALTER TABLE `gitlab_job_class_report` 
                ADD INDEX (`ox_gitlab_job_tests_report_id`),
                ADD INDEX (`namespace`),
                ADD INDEX (`class`);";
        $this->addQuery($query);

        $this->makeRevision("0.24");

        $query = "ALTER TABLE `gitlab_job_tests_report` 
                CHANGE `class` `class` VARCHAR (255);";
        $this->addQuery($query);

        $this->makeRevision("0.25");

        $query = "ALTER TABLE `gitlab_job_tests_report` 
                ADD COLUMN `warnings` INT (11) NOT NULL DEFAULT '0' AFTER `assertions`;";
        $this->addQuery($query);

        $this->mod_version = "0.26";
    }
}
