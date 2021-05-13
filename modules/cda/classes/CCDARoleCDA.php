<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda;

use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CPerson;
use Ox\Interop\Cda\Datatypes\Base\CCDACE;
use Ox\Interop\Cda\Datatypes\Base\CCDAII;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_AssignedAuthor;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_AssignedCustodian;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_AssignedEntity;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_Birthplace;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_HealthCareFacility;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_PatientRole;
use Ox\Interop\Eai\CItemReport;
use Ox\Interop\Eai\CMbOID;
use Ox\Interop\Eai\CReport;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * Classe regroupant les fonctions de type Role
 */
class CCDARoleCDA extends CCDADocumentCDA {

  /**
   * Affectation champ pour les fonction de type assigned
   *
   * @param CCDAPOCD_MT000040_AssignedAuthor $assigned CCDAPOCD_MT000040_AssignedAuthor
   * @param CPerson                          $mediUser Cperson
   *
   * @return void
   */
  function setAssigned($assigned, $mediUser) {
    if ($mediUser instanceof CUser) {
      $mediUser = $mediUser->loadRefMediuser();
    }
    $mediUser->loadRefFunction();

    $this->setIdPs($assigned, $mediUser);

    $this->setTelecom($assigned, $mediUser);

    $ad = $this->setAddress($mediUser);
    $assigned->appendAddr($ad);
  }

  /**
   * Création du role de l'auteur
   *
   * @return CCDAPOCD_MT000040_AssignedAuthor
   */
  function setAssignedAuthor() {
    $assigned = new CCDAPOCD_MT000040_AssignedAuthor();

    // CAS pour CDA Lettre de liaison
    if (self::$cda_factory->type_cda &&  self::$cda_factory->code_loinc_cda) {
      $assigned->setClassCode('ASSIGNED');
    }

    $praticien = self::$cda_factory->practicien;
    $spec = $praticien->_ref_other_spec;
    $this->setAssigned($assigned, $praticien);
    if ($spec->libelle) {
      $ce = new CCDACE();
      $ce->setCode($spec->code);
      $ce->setDisplayName($spec->libelle);
      $ce->setCodeSystem($spec->oid);
      $assigned->setCode($ce);
    }
    else {
        self::$cda_factory->report->addData(CAppUI::tr('CMediusers-msg-None ASIP specialty'), CItemReport::SEVERITY_ERROR);
    }

    $assigned->setAssignedPerson(parent::$entite->setPerson($praticien));
    $assigned->setRepresentedOrganization(parent::$entite->setOrganization($praticien));
    return $assigned;
  }

  /**
   * Création d'un assignedCustodian
   *
   * @return CCDAPOCD_MT000040_AssignedCustodian
   */
  function setAssignedCustodian() {
    $assignedCustodian = new CCDAPOCD_MT000040_AssignedCustodian();
    $assignedCustodian->setRepresentedCustodianOrganization(parent::$entite->setCustodianOrganization());
    return $assignedCustodian;
  }

  /**
   * Création du patientRole
   *
   * @return CCDAPOCD_MT000040_PatientRole|null
   */
  function setPatientRole() {
    $patientRole = new CCDAPOCD_MT000040_PatientRole();
    $patient = self::$cda_factory->patient;

    // CAS pour CDA Lettre de liaison
    if (self::$cda_factory->type_cda && self::$cda_factory->code_loinc_cda) {
      $patientRole->setClassCode('PAT');
    }

      // Pour CDA structuré et non structuré, on retourne une erreur pck on ne met pas d'autres identifiants et
      // par défaut, il en faut obligatoirement 1
        if (!$patient->getINSNIR()) {
            self::$cda_factory->report->addData(
                CAppUI::tr('CReport-msg-Patient doesn\'t have INS NIR'),
                CItemReport::SEVERITY_ERROR
            );
          return null;
        }

    if ($patient->getINSNIR()) {
      $ii = new CCDAII();
      $ii->setRoot(CAppUI::conf("dmp NIR_OID"));
      $ii->setExtension($patient->getINSNIR());
      $patientRole->appendId($ii);

      if ($patient->_ref_last_ins) {
        $ii = new CCDAII();
        $ii->setRoot("1.2.250.1.213.1.4.2");
        $ii->setExtension($patient->_ref_last_ins->ins);
        $patientRole->appendId($ii);
      }

      /*$ii = new CCDAII();
      $ii->setRoot(CMbOID::getOIDOfInstance($patient, self::$cda_factory->receiver));
      $ii->setExtension($patient->_id);
      $patientRole->appendId($ii);*/
    }

    //TODO : A gérer en version internationale
    /*if ($patient->_IPP) {
      $ii = new CCDAII();
      // @todo Gérer le master domaine
      //$group_domain = new CGroupDomain();
      //$group_domain->loadM
      //$ii->setRoot(self::$cda_factory->root);
      $ii->setRoot(CMbOID::getOIDOfInstance(self::$cda_factory->patient));
      $ii->setExtension($patient->_IPP);
      //libelle du domaine
      $ii->setAssigningAuthorityName("");
      $patientRole->appendId($ii);
    }*/

    $ad = $this->setAddress($patient);
    $patientRole->appendAddr($ad);

    $this->setTelecom($patientRole, $patient);

    $patientRole->setPatient(parent::$entite->setPatient());

    return $patientRole;
  }

  /**
   * Création du role lieu de naissance
   *
   * @return CCDAPOCD_MT000040_Birthplace
   */
  function setBirthPlace() {
    $birthplace = new CCDAPOCD_MT000040_Birthplace();
    $birthplace->setPlace(parent::$entite->setPlace());
    return $birthplace;
  }

  /**
   * Création de l'assignedEntity
   *
   * @param CUser|CMediUsers $user         CUser|CMediUsers
   * @param boolean          $organization false
   *
   * @return CCDAPOCD_MT000040_AssignedEntity
   */
  function setAssignedEntity($user, $organization = false) {
    $assignedEntity = new CCDAPOCD_MT000040_AssignedEntity();

    $this->setAssigned($assignedEntity, $user);

    if ($organization) {
      $assignedEntity->setRepresentedOrganization(parent::$entite->setOrganization($user));
    }

    $assignedEntity->setAssignedPerson(parent::$entite->setPerson($user));
    return $assignedEntity;
  }

  /**
   * Retourne un HealthCareFacility
   *
   * @return CCDAPOCD_MT000040_HealthCareFacility
   */
    public function setHealthCareFacility()
    {
        $healt  = new CCDAPOCD_MT000040_HealthCareFacility();
        $valeur = self::$cda_factory->healt_care;

        if (
            !CMbArray::get($valeur, 'code') || !CMbArray::get($valeur, 'codeSystem')
            || !CMbArray::get($valeur, 'displayName')
        ) {
            self::$cda_factory->report->addData(
                CAppUI::tr('CGroups-msg-None association CDA'),
                CItemReport::SEVERITY_ERROR
            );
        }

        $ce = new CCDACE();
        $ce->setCode($valeur["code"]);
        $ce->setCodeSystem($valeur["codeSystem"]);
        $ce->setDisplayName($valeur["displayName"]);
        $healt->setCode($ce);

        return $healt;
    }
}
