<?php

/**
 * @package Mediboard\Rpps
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Rpps;

use Exception;
use Ox\Core\CModelObject;
use Ox\Core\CSetup;
use Ox\Core\CSQLDataSource;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Admin\CViewAccessToken;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\System\Cron\CCronJob;

/**
 * Rpps Setup class
 */
class CSetupRpps extends CSetup
{
    /**
     * @see parent::__construct()
     */
    function __construct()
    {
        parent::__construct();

        $this->mod_name = "rpps";
        $this->makeRevision("0.0");
        $this->setModuleCategory("dossier_patient", "autre");

        $this->addDependency('dPpatients', '4.29');

        $this->makeRevision('0.01');

        $this->addMethod('addCrons');

        $this->makeRevision('0.02');

        $ds = CSQLDataSource::get('std');

        if (!$ds->hasField('medecin_exrcice_place', 'adeli')) {
            $query = "ALTER TABLE `medecin_exercice_place`
                    ADD COLUMN `adeli` VARCHAR(9),
                    ADD INDEX (`adeli`)";
            $this->addQuery($query);
            $this->makeRevision('0.03');
        } else {
            $this->makeEmptyRevision('0.03');
        }

        // Create exercice places from existing medecin
        if (!$ds->hasTable('exercice_place')) {
            $query = "INSERT INTO `medecin_exercice_place` (medecin_id, adresse, cp, commune, tel, tel2, fax, email, adeli)
                    SELECT medecin_id, adresse, cp, ville, tel, tel_autre, fax, email, adeli FROM medecin
                    WHERE adeli IS NOT NULL OR adresse IS NOT NULL;";
            $this->addQuery($query);
        }

        $this->makeRevision('0.04');

        $this->addMethod('addCronDisableExercicePlaces');

        $this->mod_version = '0.05';
    }

    protected function addCrons(): bool
    {
        $this->addCronSync();
        $this->addCronDump();

        return true;
    }

    protected function addCronDisableExercicePlaces(): bool
    {
        $cron       = new CCronJob();
        $cron->name = 'RPPS : Désactiver lieux d\'exercice';
        $cron->loadMatchingObjectEsc();

        if (!$cron->_id) {
            $token          = $this->createToken(
                "m=rpps\na=cron_disable_exercice_places",
                'RPPS : Désactiver lieux d\'exercice'
            );

            $cron->active   = '1';
            $cron->token_id = $token->_id;
            $cron->_second  = '0';
            $cron->_minute  = '*/15';
            $cron->_hour    = '*';
            $cron->_day     = '*';
            $cron->_month   = '*';
            $cron->_week    = '*';

            if ($msg = $cron->store()) {
                throw new Exception($msg, E_USER_ERROR);
            }
        }

        return true;
    }

    protected function addCronSync(): void
    {
        $cron       = new CCronJob();
        $cron->name = 'RPPS : Sync médecins';
        $cron->loadMatchingObjectEsc();

        if (!$cron->_id) {
            $token          = $this->createToken("m=rpps\na=cron_synchronize_medecin", 'RPPS : Sync médecins');
            $cron->active   = '1';
            $cron->token_id = $token->_id;
            $cron->_second  = '0';
            $cron->_minute  = '*';
            $cron->_hour    = '*';
            $cron->_day     = '*';
            $cron->_month   = '*';
            $cron->_week    = '*';

            if ($msg = $cron->store()) {
                throw new Exception($msg, E_USER_ERROR);
            }
        }
    }

    protected function addCronDump(): void
    {
        $cron       = new CCronJob();
        $cron->name = 'RPPS : Maj base externe';
        $cron->loadMatchingObjectEsc();

        if (!$cron->_id) {
            $token          = $this->createToken("m=rpps\na=ajax_populate_database", 'RPPS : Maj base externe');
            $cron->active   = '1';
            $cron->token_id = $token->_id;
            $cron->_second  = '0';
            $cron->_minute  = '0';
            $cron->_hour    = '8';
            $cron->_day     = '*';
            $cron->_month   = '*';
            $cron->_week    = '1';

            if ($msg = $cron->store()) {
                throw new Exception($msg, E_USER_ERROR);
            }
        }
    }

    protected function createToken(string $params, string $name): CViewAccessToken
    {
        $token               = new CViewAccessToken();
        $token->label        = $name;
        $token->params       = $params;
        $token->user_id      = CUser::get()->_id;
        $token->restricted   = '1';
        $token->_hash_length = 10;

        if ($msg = $token->store()) {
            throw new Exception($msg, E_USER_ERROR);
        }

        return $token;
    }
}
