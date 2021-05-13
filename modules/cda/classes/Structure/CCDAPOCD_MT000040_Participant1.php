<?php

/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Structure;

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Interop\Cda\CCDADocumentCDA;
use Ox\Interop\Cda\CCDAFactory;
use Ox\Interop\Cda\Datatypes\Base\CCDA_adxp_streetAddressLine;
use Ox\Interop\Cda\Datatypes\Base\CCDA_en_family;
use Ox\Interop\Cda\Datatypes\Base\CCDA_en_given;
use Ox\Interop\Cda\Datatypes\Base\CCDAAD;
use Ox\Interop\Cda\Datatypes\Base\CCDACE;
use Ox\Interop\Cda\Datatypes\Base\CCDAII;
use Ox\Interop\Cda\Datatypes\Base\CCDAIVL_TS;
use Ox\Interop\Cda\Datatypes\Base\CCDAIVXB_TS;
use Ox\Interop\Cda\Datatypes\Base\CCDAPN;
use Ox\Interop\Cda\Datatypes\Voc\CCDAContextControl;
use Ox\Interop\Cda\Datatypes\Voc\CCDAParticipationType;
use Ox\Interop\Cda\Rim\CCDARIMParticipation;
use Ox\Interop\Eai\CItemReport;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Files\CDocumentItem;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * POCD_MT000040_Participant1 Class
 */
class CCDAPOCD_MT000040_Participant1 extends CCDARIMParticipation {

  /**
   * @var CCDAPOCD_MT000040_AssociatedEntity
   */
  public $associatedEntity;

  /**
   * Setter functionCode
   *
   * @param CCDACE $inst CCDACE
   *
   * @return void
   */
  function setFunctionCode(CCDACE $inst) {
    $this->functionCode = $inst;
  }

  /**
   * Getter functionCode
   *
   * @return CCDACE
   */
  function getFunctionCode() {
    return $this->functionCode;
  }

  /**
   * Setter time
   *
   * @param CCDAIVL_TS $inst CCDAIVL_TS
   *
   * @return void
   */
  function setTime(CCDAIVL_TS $inst) {
    $this->time = $inst;
  }

  /**
   * Getter time
   *
   * @return CCDAIVL_TS
   */
  function getTime() {
    return $this->time;
  }

  /**
   * Setter associatedEntity
   *
   * @param CCDAPOCD_MT000040_AssociatedEntity $inst CCDAPOCD_MT000040_AssociatedEntity
   *
   * @return void
   */
  function setAssociatedEntity(CCDAPOCD_MT000040_AssociatedEntity $inst) {
    $this->associatedEntity = $inst;
  }

  /**
   * Getter associatedEntity
   *
   * @return CCDAPOCD_MT000040_AssociatedEntity
   */
  function getAssociatedEntity() {
    return $this->associatedEntity;
  }

  /**
   * Setter typeCode
   *
   * @param String $inst String
   *
   * @return void
   */
  function setTypeCode($inst) {
    if (!$inst) {
      $this->typeCode = null;
      return;
    }
    $part = new CCDAParticipationType();
    $part->setData($inst);
    $this->typeCode = $part;
  }

  /**
   * Getter typeCode
   *
   * @return CCDAParticipationType
   */
  function getTypeCode() {
    return $this->typeCode;
  }

  /**
   * Assigne contextControlCode à OP
   *
   * @return void
   */
  function setContextControlCode() {
    $context = new CCDAContextControl();
    $context->setData("OP");
    $this->contextControlCode = $context;
  }

  /**
   * Getter contextControlCode
   *
   * @return CCDAContextControl
   */
  function getContextControlCode() {
    return $this->contextControlCode;
  }


  /**
   * Retourne les propriétés
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["typeId"]             = "CCDAPOCD_MT000040_InfrastructureRoot_typeId xml|element max|1";
    $props["functionCode"]       = "CCDACE xml|element max|1";
    $props["time"]               = "CCDAIVL_TS xml|element max|1";
    $props["associatedEntity"]   = "CCDAPOCD_MT000040_AssociatedEntity xml|element required";
    $props["typeCode"]           = "CCDAParticipationType xml|attribute required";
    $props["contextControlCode"] = "CCDAContextControl xml|attribute fixed|OP";
    return $props;
  }

    /**
     * @param $factory
     *
     * @return CCDAPOCD_MT000040_Participant1|null
     * @throws \Exception
     */
    public function setParticipant(CCDAFactory $factory): ?CCDAPOCD_MT000040_Participant1
    {
        $this->setTypeCode('INF');

        $ccdace = new CCDACE();
        $ccdace->setCode('PCP');
        $ccdace->setCodeSystem('2.16.840.1.113883.5.88');
        $ccdace->setDisplayName('Médecin Traitant');
        $this->setFunctionCode($ccdace);

        // Ajout du time
        $ivlTs = new CCDAIVL_TS();
        $cda = new CCDADocumentCDA();
        $low = $cda->getTimeToUtc(CMbDT::dateTime());
        $ivxbL = new CCDAIVXB_TS();
        $ivxbL->setValue($low);
        $ivlTs->setLow($ivxbL);
        $this->setTime($ivlTs);

        $mbObject = $factory->mbObject;
        if (!$mbObject instanceof CDocumentItem) {
            $factory->report->addData(
                CAppUI::tr('CReport-msg-Context of factory must be CDocumentItem'),
                CItemReport::SEVERITY_ERROR
            );
            return null;
        }

        $target        = $mbObject->loadTargetObject();
        $patient       = null;
        $etablissement = null;

        if ($target instanceof CSejour) {
            $etablissement = $target->loadRefEtablissement();
            $patient = $target->loadRefPatient();
        } elseif ($target instanceof CConsultation) {
            $etablissement = $target->loadRefGroup();
            $patient = $target->loadRefPatient();
        } elseif ($target instanceof CPatient) {
            $etablissement = CGroups::loadCurrent();
            $patient = $target;
        }

        if (!$etablissement) {
            $factory->report->addData(
                CAppUI::tr('CReport-msg-Impossible to retrieve group from target'),
                CItemReport::SEVERITY_ERROR
            );
            return null;
        }

        if (!$patient || !$patient->loadRefMedecinTraitant() || !$patient->loadRefMedecinTraitant()->_id) {
            $factory->report->addData(
                CAppUI::tr('CReport-msg-Impossible to retrieve patient from target or patient doesn\'t have treated doctor'),
                CItemReport::SEVERITY_ERROR
            );
            return null;
        }

        $mediUser = $patient->_ref_medecin_traitant;
        if (!$mediUser->rpps) {
            $factory->report->addData(
                CAppUI::tr('CReport-msg-Treated doctor doesn\'t have rpps'),
                CItemReport::SEVERITY_ERROR
            );
            return null;
        }

        // Ajout associatedEntity
        $associated = new CCDAPOCD_MT000040_AssociatedEntity();
        $associated->setClassCode("PROV");
        $id_root = new CCDAII();
        $id_root->setRoot('1.2.250.1.71.4.2.1');
        $id_root->setExtension($mediUser->rpps);
        $id_root->setAssigningAuthorityName('ASIP Santé');
        $associated->appendId($id_root);

        // Ajout entity
        $ad = new CCDAAD();
        $street = new CCDA_adxp_streetAddressLine();
        $street->setData($etablissement->adresse);
        $street2 = new CCDA_adxp_streetAddressLine();
        $street2->setData($etablissement->cp . " " . $etablissement->ville);

        $ad->append("streetAddressLine", $street);
        $ad->append("streetAddressLine", $street2);

        // Ajout person
        $person = new CCDAPOCD_MT000040_Person();
        $pn = new CCDAPN();

        $enxp = new CCDA_en_family();
        $enxp->setData($mediUser->_p_last_name);
        $pn->append("family", $enxp);

        $enxp = new CCDA_en_given();
        $enxp->setData($mediUser->_p_first_name);
        $pn->append("given", $enxp);

        $person->appendName($pn);
        $associated->setAssociatedPerson($person);

        $associated->appendAddr($ad);

        $this->setAssociatedEntity($associated);

        return $this;
    }

  /**
   * Fonction permettant de tester la classe
   *
   * @return array
   */
  function test() {
    $tabTest = parent::test();

    /**
     * Test avec un associatedEntity correct
     */

    $associated = new CCDAPOCD_MT000040_AssociatedEntity();
    $associated->setClassCode("RoleClassPassive");
    $this->setAssociatedEntity($associated);
    $tabTest[] = $this->sample("Test avec un associatedEntity correct", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un typeCode incorrect
     */

    $this->setTypeCode("TESTTEST");
    $tabTest[] = $this->sample("Test avec un typeCode incorrect", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un typeCode correct
     */

    $this->setTypeCode("CST");
    $tabTest[] = $this->sample("Test avec un typeCode correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un contextControlCode correct
     */

    $this->setContextControlCode();
    $tabTest[] = $this->sample("Test avec un contextControlCode correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un functionCode incorrect
     */

    $ce = new CCDACE();
    $ce->setCode(" ");
    $this->setFunctionCode($ce);
    $tabTest[] = $this->sample("Test avec un functionCode incorrect", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un functionCode correct
     */

    $ce->setCode("TEST");
    $this->setFunctionCode($ce);
    $tabTest[] = $this->sample("Test avec un functionCode correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un effectiveTime incorrect
     */

    $ivl_ts = new CCDAIVL_TS();
    $hi = new CCDAIVXB_TS();
    $hi->setValue("TESTTEST");
    $ivl_ts->setHigh($hi);
    $this->setTime($ivl_ts);
    $tabTest[] = $this->sample("Test avec un effectiveTime incorrect", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un effectiveTime correct
     */

    $hi->setValue("75679245900741.869627871786625715081550660290154484483335306381809807748522068");
    $ivl_ts->setHigh($hi);
    $this->setTime($ivl_ts);
    $tabTest[] = $this->sample("Test avec un effectiveTime correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
