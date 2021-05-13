<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda;
use DateTime;
use DateTimeZone;
use Ox\Core\CAppUI;
use Ox\Core\CMbException;
use Ox\Core\CPerson;
use Ox\Interop\Cda\Datatypes\Base\CCDA_adxp_streetAddressLine;
use Ox\Interop\Cda\Datatypes\Base\CCDAAD;
use Ox\Interop\Cda\Datatypes\Base\CCDACE;
use Ox\Interop\Cda\Datatypes\Base\CCDAII;
use Ox\Interop\Cda\Datatypes\Base\CCDAIVL_TS;
use Ox\Interop\Cda\Datatypes\Base\CCDAIVXB_TS;
use Ox\Interop\Cda\Datatypes\Base\CCDATEL;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_ClinicalDocument;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_CustodianOrganization;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_PatientRole;
use Ox\Interop\Eai\CItemReport;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * Regroupe les fonctions pour créer un document CDA
 */
class CCDADocumentCDA extends CCDAClasseCda {

  /** @var CCDARoleCDA */
  static $role;
  /** @var CCDAParticipationCDA */
  static $participation;
  /** @var CCDAActRelationshipCDA */
  static $actRelationship;
  /** @var CCDAActCDA */
  static $act;
  /** @var CCDAEntiteCDA */
  static $entite;
  /** @var  CCDAFactory */
  static $cda_factory;

  /**
   * Création du CDA
   *
   * @param CCDAFactory $cda_factory cda factory
   *
   * @return CCDAPOCD_MT000040_ClinicalDocument
   */
  function generateCDA($cda_factory) {
    self::$participation   = new CCDAParticipationCDA();
    self::$entite          = new CCDAEntiteCDA();
    self::$act             = new CCDAActCDA();
    self::$actRelationship = new CCDAActRelationshipCDA();
    self::$role            = new CCDARoleCDA();
    self::$cda_factory     = $cda_factory;

    $act = new CCDAActCDA();
    $CDA = $act->setClinicalDocument();

    return $CDA;
  }

  /**
   * Transforme une chaine date au format time CDA
   *
   * @param String $date      String
   * @param bool   $naissance false
   *
   * @return string
   */
  function getTimeToUtc($date, $naissance = false) {
    if (!$date) {
      return null;
    }
    if ($naissance) {
      $date = Datetime::createFromFormat("Y-m-d", $date);
      return $date->format("Ymd");
    }
    $timezone = new DateTimeZone(CAppUI::conf("timezone"));
    $date     = new DateTime($date, $timezone);

    return $date->format("YmdHisO");
  }

  /**
   * Création de l'adresse de la personne passé en paramètre
   *
   * @param CPerson $user CPerson
   *
   * @return CCDAAD
   */
  function setAddress($user) {
    $userCity =  $user->_p_city;
    $userPostalCode = $user->_p_postal_code;
    $userStreetAddress = $user->_p_street_address;

    $ad = new CCDAAD();
    if (!$userCity && !$userPostalCode && !$userStreetAddress) {
      $ad->setNullFlavor("NASK");
      return $ad;
    }

    $ad->setUse(array('H'));

    $addresses = preg_split("#[\t\n\v\f\r]+#", $userStreetAddress, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($addresses as $_addr) {
      $street = new CCDA_adxp_streetAddressLine();
      $street->setData($_addr);
      $ad->append("streetAddressLine", $street);
    }

    $street2 = new CCDA_adxp_streetAddressLine();
    $street2->setData($userPostalCode." ".$userCity);
    $ad->append("streetAddressLine", $street2);

    return $ad;
  }

  /**
   * Ajoute les téléphone de la personne dans l'objet qui a appelé cette méthode
   *
   * @param CCDAPOCD_MT000040_PatientRole $object  CCDAPOCD_MT000040_PatientRole
   * @param CPerson                       $patient CPerson
   *
   * @return void
   */
  function setTelecom($object, $patient) {
    $patientPhoneNumber = $patient->_p_phone_number;
    $patientMobilePhoneNumber = $patient->_p_mobile_phone_number;
    $patientEmail = $patient->_p_email;

    $tel = new CCDATEL();
    if (!$patientPhoneNumber && !$patientMobilePhoneNumber && !$patientEmail) {
      $tel->setNullFlavor("NASK");
      $object->appendTelecom($tel);
      return;
    }

    $tel->setValue($patientPhoneNumber ? "tel:".$patientPhoneNumber : "");
    $object->appendTelecom($tel);

    $tel = new CCDATEL();
    $tel->setValue($patientMobilePhoneNumber ? "tel:".$patientMobilePhoneNumber : "");
    $object->appendTelecom($tel);

    $tel = new CCDATEL();
    $tel->setValue($patientEmail ? "mailto:".$patientEmail : "");
    $object->appendTelecom($tel);
  }

  /**
   * Retourne le code associé à la situation familiale
   *
   * @param String $status String
   *
   * @return CCDACE
   */
  function getMaritalStatus($status) {
    $ce = new CCDACE();
    $ce->setCodeSystem("1.3.6.1.4.1.21367.100.1");
    switch ($status) {
      case "S":
        $ce->setCode("S");
        $ce->setDisplayName("Célibataire");
        break;
      case "M":
        $ce->setCode("M");
        $ce->setDisplayName("Marié");
        break;
      case "G":
        $ce->setCode("G");
        $ce->setDisplayName("Concubin");
        break;
      case "D":
        $ce->setCode("D");
        $ce->setDisplayName("Divorcé");
        break;
      case "W":
        $ce->setCode("W");
        $ce->setDisplayName("Veuf/Veuve");
        break;
      case "A":
        $ce->setCode("A");
        $ce->setDisplayName("Séparé");
        break;
      case "P":
        $ce->setCode("P");
        $ce->setDisplayName("Pacte civil de solidarité (PACS)");
        break;
      default:
        $ce->setCode("U");
        $ce->setDisplayName("Inconnu");
    }
    return $ce;
  }

  /**
   * Retourne le code associé au sexe de la personne
   *
   * @param String $sexe String
   *
   * @return CCDACE
   */
  function getAdministrativeGenderCode($sexe) {
    $ce = new CCDACE();
    $ce->setCode(mb_strtoupper($sexe));
    $ce->setCodeSystem("2.16.840.1.113883.5.1");
    switch ($sexe) {
      case "f":
        $ce->setDisplayName("Féminin");
        break;
      case "m":
        $ce->setDisplayName("Masculin");
        break;
      default:
        $ce->setCode("U");
        $ce->setDisplayName("Inconnu");
    }
    return $ce;
  }

  /**
   * Attribution de l'id au PS
   *
   * @param Object           $assigned Object
   * @param CUser|CMediUsers $user     CUser|CMediUsers
   *
   * @return void
   */
  function setIdPs($assigned, $user) {
    $factory = self::$cda_factory;

    if (!$user->adeli && !$user->rpps) {
        $factory->report->addData(
            CAppUI::tr('CReport-msg-Doctor doesn\'t have RPPS and ADELI'),
            CItemReport::SEVERITY_ERROR
        );
        return;
    }

    // CDA type structuré => 1 seul ID
    if ($factory->level == 3 && $factory->type_cda) {
      if ($user->rpps) {
        $ii = new CCDAII();
        $ii->setRoot("1.2.250.1.71.4.2.1");
        $ii->setAssigningAuthorityName("GIP-CPS");
        $ii->setExtension("8$user->rpps");
        $assigned->appendId($ii);
        return;
      }

      if ($user->adeli) {
        $ii = new CCDAII();
        $ii->setRoot("1.2.250.1.71.4.2.1");
        $ii->setAssigningAuthorityName("GIP-CPS");
        $ii->setExtension("0$user->adeli");
        $assigned->appendId($ii);
        return;
      }
    }

    if ($user->adeli) {
      $ii = new CCDAII();
      $ii->setRoot("1.2.250.1.71.4.2.1");
      $ii->setAssigningAuthorityName("GIP-CPS");
      $ii->setExtension("0$user->adeli");
      $assigned->appendId($ii);
    }

    if ($user->rpps) {
      $ii = new CCDAII();
      $ii->setRoot("1.2.250.1.71.4.2.1");
      $ii->setAssigningAuthorityName("GIP-CPS");
      $ii->setExtension("8$user->rpps");
      $assigned->appendId($ii);
    }
  }


  /**
   * Affectation id à l'établissement
   *
   * @param CCDAPOCD_MT000040_CustodianOrganization $entite CCDAPOCD_MT000040_CustodianOrganization
   * @param CGroups                                 $etab   CGroups
   *
   * @return void
   */
  function setIdEtablissement($entite, $etab) {
    $factory = self::$cda_factory;

    // CDA structuré (1 seul ID) => soit root soit siret soit finess
    if ($factory->level == 3 && $factory->type_cda) {
      if ($etab->siret) {
        $ii = new CCDAII();
        $ii->setRoot("1.2.250.1.71.4.2.2");
        $ii->setExtension("3".$etab->siret);
        $ii->setAssigningAuthorityName("GIP-CPS");
        $entite->appendId($ii);
        return;
      }

      if ($etab->finess) {
        $ii = new CCDAII();
        $ii->setRoot("1.2.250.1.71.4.2.2");
        $ii->setExtension("1".$etab->finess);
        $ii->setAssigningAuthorityName("GIP-CPS");
        $entite->appendId($ii);
        return;
      }
    }

    // Pour SISRA, il faut mettre obligatoirement le FINESS
    if ($factory->xds_type == 'SISRA') {
        if (!$etab->finess) {
            self::$cda_factory->report->addData(
                CAppUI::tr('CGroups-msg-None finess'),
                CItemReport::SEVERITY_ERROR
            );
        }

        $ii = new CCDAII();
        $ii->setRoot("1.2.250.1.71.4.2.2");
        $ii->setExtension("1".$etab->finess);
        $ii->setAssigningAuthorityName("GIP-CPS");
        $entite->appendId($ii);

        return;
    }

    if (CAppUI::gconf('dmp general information_certificat', $etab->_id) == 'siret') {
      if (!$etab->siret) {
          self::$cda_factory->report->addData(
              CAppUI::tr('CGroups-msg-None siret'),
              CItemReport::SEVERITY_ERROR
          );
      }

      $ii = new CCDAII();
      $ii->setRoot("1.2.250.1.71.4.2.2");
      $ii->setExtension("3".$etab->siret);
      $ii->setAssigningAuthorityName("GIP-CPS");
      $entite->appendId($ii);
    }

    if (CAppUI::gconf('dmp general information_certificat', $etab->_id) == 'finess') {
      if (!$etab->finess) {
          self::$cda_factory->report->addData(
              CAppUI::tr('CGroups-msg-None finess'),
              CItemReport::SEVERITY_ERROR
          );
      }

      $ii = new CCDAII();
      $ii->setRoot("1.2.250.1.71.4.2.2");
      $ii->setExtension("1".$etab->finess);
      $ii->setAssigningAuthorityName("GIP-CPS");
      $entite->appendId($ii);
    }
  }

  /**
   * Création d'un ivl_ts avec une valeur basse et haute
   *
   * @param String  $low        String
   * @param String  $high       String
   * @param Boolean $nullFlavor false
   *
   * @return CCDAIVL_TS
   */
  function createIvlTs($low, $high, $nullFlavor = false) {
    $ivlTs = new CCDAIVL_TS();
    if ($nullFlavor && !$low && !$high) {
      $ivlTs->setNullFlavor("UNK");
      return $ivlTs;
    }

    $low = $this->getTimeToUtc($low);
    $high = $this->getTimeToUtc($high);

    $ivxbL = new CCDAIVXB_TS();
    $ivxbL->setValue($low);
    $ivlTs->setLow($ivxbL);
    $ivxbH = new CCDAIVXB_TS();
    $ivxbH->setValue($high);
    $ivlTs->setHigh($ivxbH);

    return $ivlTs;
  }
}
