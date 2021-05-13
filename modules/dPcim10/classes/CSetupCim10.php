<?php
/**
 * @package Mediboard\Cim10
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Cim10;

use Ox\Core\CAppUI;
use Ox\Core\CSetup;
use Ox\Core\CSQLDataSource;

/**
 * dPcim10
 */
class CSetupCim10 extends CSetup {

  function __construct() {
    parent::__construct();

    $this->mod_name = "dPcim10";

    $this->makeRevision("0.0");
    $query = "CREATE TABLE `cim10favoris` (
      `favoris_id` bigint(20) NOT NULL auto_increment,
      `favoris_user` int(11) NOT NULL default '0',
      `favoris_code` varchar(16) NOT NULL default '',
      PRIMARY KEY  (`favoris_id`)
      ) /*! ENGINE=MyISAM */ COMMENT='table des favoris cim10'";
    $this->addQuery($query);

    $this->makeRevision("0.1");
    $query = "ALTER TABLE `cim10favoris` 
      CHANGE `favoris_id` `favoris_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      CHANGE `favoris_user` `favoris_user` int(11) unsigned NOT NULL DEFAULT '0';";
    $this->addQuery($query);

    $this->makeRevision("0.11");
    $query = "ALTER TABLE `cim10favoris` 
              ADD INDEX (`favoris_user`);";
    $this->addQuery($query);

    $this->makeRevision("0.12");
    $this->addPrefQuery("new_search_cim10", "1");

    $this->makeRevision('0.13');

    $this->addPrefQuery('cim10_search_favoris', '0');

    $this->makeRevision("0.14");
    $this->setModuleCategory("referentiel", "referentiel");

    $this->mod_version = '0.15';


    if (array_key_exists('cim10', CAppUI::conf('db'))) {
      $dsn = CSQLDataSource::get('cim10');
      /* CIM10 OMS */
      if ($dsn->fetchRow($dsn->exec('SHOW TABLES LIKE \'master\';'))) {
        $query = "-- CIM10 OMS 2014 --
          SELECT * 
            FROM `master` 
            WHERE `code` = 'U83';";
        $this->addDatasource("cim10", $query);
      }
      else {
        $query = "-- CIM10 OMS
          SHOW TABLES LIKE 'master';";
        $this->addDatasource("cim10", $query);
      }

      /* CIM10 Atih */
      if ($dsn->fetchRow($dsn->exec('SHOW TABLES LIKE \'codes_atih\';'))) {
        $query = "-- CIM10 ATIH 2021 MaJ 12/03/21 --
          SELECT * 
            FROM `codes_atih` 
            WHERE `code` = 'U119' AND `libelle` LIKE '%COVID-19%';";
        $this->addDatasource("cim10", $query);
      }
      else {
        $query = "-- CIM10 ATIH
          SHOW TABLES LIKE 'codes_atih';";
        $this->addDatasource("cim10", $query);
      }

      /* CIM10 GM */
      $query = "-- CIM10 GM
        SHOW TABLES LIKE 'codes_gm';";
      $this->addDatasource("cim10", $query);
    }

    $query = "-- Import de la base DRC
      SELECT * 
      FROM `consultation_results` 
      WHERE `result_id` = 740;";
    $this->addDatasource('drc', $query);

    $query = "-- Import de la base CISP
      SELECT * 
      FROM `chapitre` 
      WHERE `lettre` = 'A';";
    $this->addDatasource('cisp', $query);
  }
}
