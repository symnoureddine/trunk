<?php

/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Cabinet\Generators;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CRequest;
use Ox\Core\CSQLDataSource;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\Cabinet\CActeNGAP;
use Ox\Mediboard\Ccam\CCodable;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\Generators\CMediusersGenerator;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Generate a CActeNGAP object
 */
class CActeNGAPGenerator extends CObjectGenerator
{
    /** @var string */
    public static $mb_class    = CActeNGAP::class;
    /** @var string[]  */
    public static $dependances = [CMediusers::class];
    /** @var string[][]  */
    public static $ds          = [
        "ccamV2" => ["NGAP"],
    ];

    /** @var CActeNGAP */
    protected $object;
    /** @var CCodable */
    protected $target_object;
    /** @var CMediusers */
    protected $executant;

    /**
     * Set the target object of the meta object
     *
     * @param CMbObject $object The target object
     *
     * @return static
     */
    public function setTargetObject(CMbObject $object): self
    {
        $this->target_object = $object;

        if (
            $object->_id && property_exists($this->object, 'object_id')
            && property_exists($this->object, 'object_class')
            && method_exists($this->object, 'setObject')
        ) {
            $this->object->setObject($object);
        }

        return $this;
    }

    /**
     *  Generate a CActeNGAP
     *
     * @return self
     * @throws Exception
     */
    public function generate(): self
    {
        if (!$this->target_object || !$this->target_object->_id || !$this->target_object instanceof CCodable) {
            $this->setTargetObject((new CConsultationGenerator())->generate());
        }

        if (!$this->executant || !$this->executant->_id || !$this->executant instanceof CMediusers) {
            $user_generator = new CMediusersGenerator();
            if ($this->target_object instanceof CSejour) {
                $user_generator->setGroup($this->target_object->group_id);
            }
            $this->setExecutant($user_generator->generate());
        }

        /* Reloading the target object because the begin and end of the sejour can change for an unknown reason */
        $this->object->loadTargetObject();
        $this->object->execution = $this->object->_ref_object->getActeExecution();

        $this->object->code        = $this->getRandomNGAPCode();
        $this->object->quantite    = 1;
        $this->object->coefficient = 1;
        $this->object->lettre_cle  = '1';
        $this->object->updateMontantBase();

        if ($msg = $this->object->store()) {
            CAppUI::setMsg($msg, UI_MSG_WARNING);
        } else {
            CAppUI::setMsg("CActeNGAP-msg-create", UI_MSG_OK);
            $this->trace(static::TRACE_STORE, $this->object);
        }

        return $this;
    }

    /**
     * Set the executioner of the act
     *
     * @param CMediusers $user The user
     *
     * @return self
     */
    public function setExecutant(CMediusers $user): self
    {
        if ($user->_id) {
            $this->executant            = $user;
            $this->object->executant_id = $user->_id;
        }

        return $this;
    }

    /**
     * Return a random NGAP code
     *
     * @param bool $lettre_cle Indicate that the act must be a main act
     *
     * @return string
     * @throws Exception
     *
     */
    protected function getRandomNGAPCode(bool $lettre_cle = true): ?string
    {
        $ds = CSQLDataSource::get('ccamV2');

        $query = new CRequest();
        $query->addTable('codes_ngap');

        if ($lettre_cle) {
            $query->addWhereClause('codes_ngap.lettre_cle', ' = 1');
        }

        $query->addLJoinClause('tarif_ngap', 'codes_ngap.code = tarif_ngap.code');

        $date = CMbDT::date($this->object->execution);
        $query->addWhere("tarif_ngap.debut <= '$date' OR tarif_ngap.debut IS NULL");
        $query->addWhere("tarif_ngap.fin >= '$date' OR tarif_ngap.fin IS NULL");

        if ($this->executant && $this->executant->spec_cpam_id) {
            $query->addLJoinClause(
                'specialite_to_tarif_ngap',
                'specialite_to_tarif_ngap.tarif_id = tarif_ngap.tarif_ngap_id'
            );
            $query->addWhereClause('specialite_to_tarif_ngap.specialite', " = {$this->executant->spec_cpam_id}");
        }

        try {
            $total = $ds->loadResult($query->makeSelectCount());
        } catch (Exception $e) {
            CAppUI::setMsg($e->getMessage(), UI_MSG_WARNING);

            return null;
        }

        /* If no code is returned, we used the NGAP code C as a fallback */
        $code = 'C';
        if ($total) {
            $start         = rand(0, $total - 1);
            $query->select = [];
            $query->addSelect('codes_ngap.code');
            $query->setLimit("$start, 1");

            try {
                $result = $ds->loadHash($query->makeSelect());
            } catch (Exception $e) {
                CAppUI::setMsg($e->getMessage(), UI_MSG_WARNING);

                return null;
            }

            if ($result && array_key_exists('code', $result)) {
                $code = $result['code'];
            }
        }

        return $code;
    }
}
