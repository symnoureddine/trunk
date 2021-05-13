<?php
/**
 * @package Mediboard\Xds
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Xds;

use DateTime;
use DateTimeZone;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CAppUI;
use Ox\Core\CMbException;
use Ox\Core\FileUtil\CCSVFile;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Patients\CPatient;

/**
 * Classe outils pour le module XDS
 */
class CXDSTools implements IShortNameAutoloadable {
  static $error = array();

  /**
   * Génération des jeux de valeurs en xml
   *
   * @return bool
   */
  static function generateXMLToJv() {
    $path = "modules/xds/resources/jeux_de_valeurs";
    $files = glob("$path/*.jv");

    foreach ($files as $_file) {
      self::jvToXML($_file, $path);
    }
    return true;
  }

  /**
   * Génére un xml d'après un jeu de valeurs
   *
   * @param String $file chemin du fichier
   * @param String $path Chemin du répertoire
   *
   * @return void
   */
  static function jvToXML($file, $path) {
    $name = self::deleteDate(basename($file));
    $csv = new CCSVFile($file);
    $csv->jumpLine(3);
    $xml = new CXDSXmlJvDocument();
    while ($line = $csv->readLine()) {
      [
        $oid,
        $code,
        $code_xds,
        ] = $line;
      $xml->appendLine($oid, $code, $code_xds);
    }
    $xml->save("$path/$name.xml");
  }

  /**
   * Supprime la date du nom des fichiers des jeux de valeurs
   *
   * @param String $name Nom du fichier
   *
   * @return string
   */
  static function deleteDate($name) {
    return substr($name, 0, strrpos($name, "_"));
  }

  /**
   * Retourne le datetime actuelle au format UTC
   *
   * @param String $date now
   *
   * @return string
   */
  static function getTimeUtc($date = "now") {
    $timezone_local = new DateTimeZone(CAppUI::conf("timezone"));
    $timezone_utc = new DateTimeZone("UTC");
    $date = new DateTime($date, $timezone_local);
    $date->setTimezone($timezone_utc);
    return $date->format("YmdHis");
  }

  /**
   * Retourne les informations de l'etablissement sous la forme HL7v2 XON
   *
   * @param String $libelle     Libelle
   * @param String $identifiant Identifiant
   * @param String $xds_type    Type du destinataire XDS
   *
   * @return string
   */
  static function getXONetablissement($libelle, $identifiant, $xds_type = null) {
    $comp1  = $libelle;
    $comp6  = "&1.2.250.1.71.4.2.2&ISO";
    $comp7 = null;
    if ($xds_type == "DMP" || $xds_type == "SISRA") {
      $comp7  = "IDNST";
    }
    $comp10 = $identifiant;
    $xon    = "$comp1^^^^^$comp6^$comp7^^^$comp10";

    return $xon;
  }

  /**
   * Retourne l'identifiant de l'établissement courant
   *
   * @param boolean $forPerson Identifiant concernant une personne
   * @param CGroups $group     etablissement
   *
   * @return null|string
   */
  static function getIdEtablissement($forPerson = false, $group = null, string $type = null) {
    $siret = "3";
    $finess = "1";

    if ($forPerson) {
      $siret = "5";
      $finess = "3";
    }

    // Pour SISRA, il faut obligatoirement le FINESS
    if ($type && $type == 'SISRA') {
        if (!$group->finess) {
            throw new CMbException("CGroups-msg-None finess");
        }
        return $finess.$group->finess;
    }

    if (CAppUI::gconf('dmp general information_certificat', $group->_id) == 'siret') {
      if (!$group->siret) {
        throw new CMbException("CGroups-msg-None siret");
      }
      return $siret.$group->siret;
    }

    if (CAppUI::gconf('dmp general information_certificat', $group->_id) == 'finess') {
      if (!$group->finess) {
        throw new CMbException("CGroups-msg-None finess");
      }
      return $finess.$group->finess;
    }

    return null;
  }

  /**
   * Retourne les informations du Mediuser sous la forme HL7v2 XCN
   *
   * @param String $identifiant Identifiant
   * @param String $lastname    Last name
   * @param String $firstname   First name
   *
   * @return string
   */
  static function getXCNMediuser($identifiant, $lastname, $firstname) {
    $comp1  = $identifiant;
    $comp2  = $lastname;
    $comp3  = $firstname;
    $comp9  = "&1.2.250.1.71.4.2.1&ISO";
    $comp10 = "D";
    $comp13 = "EI";

    return "$comp1^$comp2^$comp3^^^^^^$comp9^$comp10^^^$comp13";
  }

  /**
   * Retourne l'INS sous la forme HL7v2
   *
   * @param String $ins  INS
   * @param String $type Type d'INS
   *
   * @return string
   */
  static function getINSPatient($ins, $type) {
    $comp1 = $ins;
    $comp4 = "1.2.250.1.213.1.4.2";
    $comp5 = "INS-$type";

    return "$comp1^^^&$comp4&ISO^$comp5";
  }

  /**
   * Retourne l'INS sous la forme HL7v2
   *
   * @param CPatient $patient patient
   *
   * @return string
   */
  static function getNIRPatient(CPatient $patient) {
    $comp1 = $patient->matricule;
    $comp4 = CAppUI::conf("dmp NIR_OID");
    $comp5 = "NH";

    return "$comp1^^^&$comp4&ISO^$comp5";
  }
}
