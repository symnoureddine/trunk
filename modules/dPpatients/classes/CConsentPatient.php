<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients;

use Ox\Core\CAppUI;
use Ox\Core\CMbObject;
use Ox\Core\Module\CModule;
use Ox\Mediboard\Admin\Rgpd\CRGPDConsent;
use Ox\Mediboard\Etablissement\CGroups;

/**
 * Consentement patient
 */
class CConsentPatient extends CMbObject {
  /** @var integer Primary key */
  public $consent_patient_id;

  public $tag;
  public $status;
  public $acceptance_datetime;
  public $refusal_datetime;
  public $object_id;
  public $object_class;
  public $group_id;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = "consent_patient";
    $spec->key   = "consent_patient_id";

    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props                        = parent::getProps();
    $props["tag"]                 = "num";
    $props["status"]              = "enum list|5|6";
    $props["acceptance_datetime"] = "dateTime";
    $props["refusal_datetime"]    = "dateTime";
    $props["object_id"]           = "ref notNull class|CPatient meta|object_class back|patient_consents";
    $props["object_class"]        = "enum list|CPatient notNull";
    $props["group_id"]            = "ref class|CGroups back|patient_consents";
    return $props;
  }

  /**
   * Enregistrement du consentement patient
   *
   * @param CPatient $patient
   *
   * @return string|null
   */
  public static function storeConsent(CPatient $patient) {
    if (!CModule::getActive("terreSante") || !CAppUI::gconf("terreSante CConsentPatient patient_consents")) {
      return null;
    }

    $consent = new static();
    $consent->object_class = $patient->_class;
    $consent->object_id = $patient->_id;
    $consent->group_id = CGroups::loadCurrent()->_id;
    $consent->tag = CRGPDConsent::TAG_TERRESANTE;

    $consent->loadMatchingObject();

    if ($patient->_consent) {
      if (!$consent->acceptance_datetime) {
        $consent->acceptance_datetime = "current";
        $consent->status = CRGPDConsent::STATUS_ACCEPTED;
        $consent->refusal_datetime = "";
      }
    }
    else {
      if (!$consent->refusal_datetime) {
        $consent->refusal_datetime = "current";
        $consent->status = CRGPDConsent::STATUS_REFUSED;
        $consent->acceptance_datetime = "";
      }
    }

    return $consent->store();
  }
}
