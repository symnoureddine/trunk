<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda;
use Ox\Core\CAppUI;
use Ox\Interop\Cda\Datatypes\Base\CCDA_adxp_city;
use Ox\Interop\Cda\Datatypes\Base\CCDA_adxp_country;
use Ox\Interop\Cda\Datatypes\Base\CCDA_adxp_postalCode;
use Ox\Interop\Cda\Datatypes\Base\CCDA_adxp_streetAddressLine;
use Ox\Interop\Cda\Datatypes\Base\CCDA_en_family;
use Ox\Interop\Cda\Datatypes\Base\CCDA_en_given;
use Ox\Interop\Cda\Datatypes\Base\CCDAAD;
use Ox\Interop\Cda\Datatypes\Base\CCDACE;
use Ox\Interop\Cda\Datatypes\Base\CCDAII;
use Ox\Interop\Cda\Datatypes\Base\CCDAON;
use Ox\Interop\Cda\Datatypes\Base\CCDAPN;
use Ox\Interop\Cda\Datatypes\Base\CCDATEL;
use Ox\Interop\Cda\Datatypes\Base\CCDATS;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_CustodianOrganization;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_Organization;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_Patient;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_Person;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_Place;
use Ox\Interop\Eai\CItemReport;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Files\CDocumentItem;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Patients\CPaysInsee;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Classe regroupant les fonctions de type Entite
 */
class CCDAEntiteCDA extends CCDADocumentCDA {

  /**
   * Création d'une personne
   *
   * @param CMediUsers $mediUser CMediUsers
   *
   * @return CCDAPOCD_MT000040_Person
   */
  function setPerson($mediUser) {
    $person = new CCDAPOCD_MT000040_Person();

    $pn = new CCDAPN();

    $enxp = new CCDA_en_family();
    $enxp->setData($mediUser->_p_last_name);
    $pn->append("family", $enxp);

    $enxp = new CCDA_en_given();
    $enxp->setData($mediUser->_p_first_name);
    $pn->append("given", $enxp);

    if ($mediUser instanceof CPatient) {
      $enxp = new CCDA_en_given();
      $enxp->setData($mediUser->_prenom_2);
      $pn->append("given", $enxp);

      $enxp = new CCDA_en_given();
      $enxp->setData($mediUser->_prenom_3);
      $pn->append("given", $enxp);

      $enxp = new CCDA_en_given();
      $enxp->setData($mediUser->_prenom_4);
      $pn->append("given", $enxp);
    }

    $person->appendName($pn);

    return $person;
  }

  /**
   * Création de patient
   *
   * @return CCDAPOCD_MT000040_Patient
   */
  function setPatient() {
    $patientCDA = new CCDAPOCD_MT000040_Patient();

    $patient = self::$cda_factory->patient;
    $pn = new CCDAPN();

    $enxp = new CCDA_en_family();
    $enxp->setData($patient->_p_last_name);
    $enxp->setQualifier(array("SP"));
    if ($patient->_p_maiden_name) {
      $enxp2 = new CCDA_en_family();
      $enxp2->setQualifier(array("BR"));
      $enxp2->setData($patient->_p_maiden_name);
      $pn->append("family", $enxp2);
      $enxp->setQualifier(array("SP"));
    }
    $pn->append("family", $enxp);

    $enxp = new CCDA_en_given();
    $enxp->setData($patient->_p_first_name);
    $pn->append("given", $enxp);

    $enxp = new CCDA_en_given();
    $enxp->setData($patient->_prenom_2);
    $pn->append("given", $enxp);

    $enxp = new CCDA_en_given();
    $enxp->setData($patient->_prenom_3);
    $pn->append("given", $enxp);

    $enxp = new CCDA_en_given();
    $enxp->setData($patient->_prenom_4);
    $pn->append("given", $enxp);

    $patientCDA->appendName($pn);

    $gender = $this->getAdministrativeGenderCode($patient->sexe);
    $patientCDA->setAdministrativeGenderCode($gender);

    $date = $this->getTimeToUtc($patient->_p_birth_date, true);
    $ts = new CCDATS();
    $ts->setValue($date);
    if (!$date) {
      $ts->setNullFlavor("NASK");
    }
    $patientCDA->setBirthTime($ts);

    $status = $this->getMaritalStatus($patient->situation_famille);
    $patientCDA->setMaritalStatusCode($status);

    $patientCDA->setBirthplace(parent::$role->setBirthPlace());

    return $patientCDA;
  }

  /**
   * Création d'un endroit
   *
   * @return CCDAPOCD_MT000040_Place
   */
  function setPlace() {
    $place = new CCDAPOCD_MT000040_Place();
    $patient = self::$cda_factory->patient;
    $birthPlace = $patient->lieu_naissance;
    $birthPostalCode = $patient->cp_naissance;

    if (!$birthPlace && !$birthPostalCode && self::$cda_factory->xds_type != "SISRA") {
      return null;
    }

    $pays_naissance_insee = CPaysInsee::getPaysByNumerique($patient->pays_naissance_insee);

    $ad = new CCDAAD();
    $adxp = new CCDA_adxp_city();
    $adxp->setData($birthPlace);
    $ad->append("city", $adxp);
    $adxp = new CCDA_adxp_postalCode();
    if (self::$cda_factory->xds_type == "SISRA") {
      // Par défaut on met "00000" car on a pas la base INSEE pour avoir le code Pays
      $adxp->setData($pays_naissance_insee->alpha_3 == "FRA" ? $birthPostalCode : "00000");
    }
    else {
      $adxp->setData($birthPostalCode);
    }
    $ad->append("postalCode", $adxp);

    // Pour Sisra, ajout du pays de naissance
    if (self::$cda_factory->xds_type == "SISRA") {
      $adxp = new CCDA_adxp_country();
      $adxp->setData($pays_naissance_insee->nom_fr);
      $ad->append("country", $adxp);
    }

    $place->setAddr($ad);

    return $place;
  }

  /**
   * création d'un custodianOrgnaization
   *
   * @return CCDAPOCD_MT000040_CustodianOrganization
   */
  function setCustodianOrganization() {
    $mbObject = self::$cda_factory->mbObject;

    if ($mbObject instanceof CSejour) {
      $etablissement = $mbObject->loadRefEtablissement();
    }
    elseif ($mbObject instanceof CConsultation) {
      $etablissement = $mbObject->loadRefPraticien()->loadRefFunction()->loadRefGroup();
    }
    elseif ($mbObject instanceof COperation) {
        $etablissement = $mbObject->loadRefSejour()->loadRefEtablissement();
    }
    elseif ($mbObject instanceof CDocumentItem) {
      $target = $mbObject->loadTargetObject();

      if ($target instanceof CConsultation) {
        $etablissement = $target->loadRefGroup();
      }
      elseif ($target instanceof CSejour) {
        $etablissement = $target->loadRefEtablissement();
      }
      elseif ($mbObject instanceof COperation) {
        $etablissement = $target->loadRefSejour()->loadRefEtablissement();
      }
      else {
        $etablissement = CGroups::loadCurrent();
      }
    }
    else {
      $etablissement = CGroups::loadCurrent();
    }

    $custOrg = new CCDAPOCD_MT000040_CustodianOrganization();
    $this->setIdEtablissement($custOrg, $etablissement);
    // CDA structuré (1 seul id) => soit root soit siret soit finess
    if (self::$cda_factory->level == 1) {
      $ii = new CCDAII();
      $ii->setRoot(self::$cda_factory->root);
      $custOrg->appendId($ii);
    }

    $name = $etablissement->_name;

    $on = new CCDAON();
    $on->setData($name);
    $custOrg->setName($on);

    $tel = new CCDATEL();
    $tel->setValue("tel:$etablissement->tel");
    $custOrg->setTelecom($tel);

    $ad = new CCDAAD();
    $street = new CCDA_adxp_streetAddressLine();
    $street->setData($etablissement->adresse);
    $street2 = new CCDA_adxp_streetAddressLine();
    $street2->setData($etablissement->cp." ".$etablissement->ville);

    $ad->append("streetAddressLine", $street);
    $ad->append("streetAddressLine", $street2);
    $custOrg->setAddr($ad);

    return $custOrg;
  }

  /**
   * Création d'une organisation
   *
   * @param CMediUsers $user CMediUsers
   *
   * @return CCDAPOCD_MT000040_Organization
   */
  function setOrganization($user) {
    $factory = self::$cda_factory;
    $organization = new CCDAPOCD_MT000040_Organization();

    $user->loadRefFunction();

    $mbObject = $factory->mbObject;
    if ($mbObject instanceof CDocumentItem) {
      $target = $mbObject->loadTargetObject();

      if ($target instanceof CSejour) {
        $etablissement = $target->loadRefEtablissement();
      }
      elseif ($target instanceof COperation) {
        $etablissement = $target->loadRefSejour()->loadRefEtablissement();
      }
      else {
        $etablissement = $user->_ref_function->loadRefGroup();
      }
    }
    else {
      $etablissement = $user->_ref_function->loadRefGroup();
    }

    $this->setIdEtablissement($organization, $etablissement);
    // CDA Structuré (1 seul ID) => soit root soit siret soit finess
    if ($factory->level == 1) {
      $ii = new CCDAII();
      $ii->setRoot($factory->root);
      $organization->appendId($ii);
    }

    $insdustry = $factory->industry_code;

    $ce = new CCDACE();
    $ce->setCode($insdustry["code"]);
    $ce->setDisplayName($insdustry["displayName"]);
    $ce->setCodeSystem($insdustry["codeSystem"]);
    $organization->setStandardIndustryClassCode($ce);

    $name = $etablissement->_name;

    $on = new CCDAON();
    $on->setData($name);
    $organization->appendName($on);

    if (!$etablissement->tel) {
        $factory->report->addData(
            CAppUI::tr('CGroups-msg-None tel'),
            CItemReport::SEVERITY_ERROR
        );
    }

    $tel = new CCDATEL();
    $tel->setValue("tel:$etablissement->tel");
    $organization->appendTelecom($tel);

    $ad = new CCDAAD();
    $street = new CCDA_adxp_streetAddressLine();
    $street->setData($etablissement->adresse);
    $street2 = new CCDA_adxp_streetAddressLine();
    $street2->setData($etablissement->cp." ".$etablissement->ville);

    $ad->append("streetAddressLine", $street);
    $ad->append("streetAddressLine", $street2);
    $organization->appendAddr($ad);

    return $organization;
  }
}
