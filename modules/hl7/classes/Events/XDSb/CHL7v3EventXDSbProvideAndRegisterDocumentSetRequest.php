<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7\Events\XDSb;

use Ox\Core\CAppUI;
use Ox\Core\CMbException;
use Ox\Interop\Cda\CCDAFactory;
use Ox\Interop\Cda\CCdaTools;
use Ox\Interop\Eai\CEAIHandler;
use Ox\Interop\Eai\CItemReport;
use Ox\Interop\Eai\CReport;
use Ox\Interop\Xds\CXDSFactory;
use Ox\Interop\Xds\CXDSXmlDocument;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Files\CFileTraceability;

/**
 * CHL7v3EventXDSbProvideAndRegisterDocumentSetRequest
 * Provide and register document set request
 */
class CHL7v3EventXDSbProvideAndRegisterDocumentSetRequest
  extends CHL7v3EventXDSb implements CHL7EventXDSbProvideAndRegisterDocumentSetRequest {
  /** @var string */
  public $interaction_id = "ProvideAndRegisterDocumentSetRequest";
  public $_event_name    = "DocumentRepository_ProvideAndRegisterDocumentSet-b";
  public $old_version;
  public $old_id;
  public $type;
  public $uuid;
  public $hide;
  public $xcn_mediuser;
  public $xon_etablissement;
  public $specialty;
  public $pratice_setting;
  public $healtcare;

  public $sign = true;
  public $add_doctors = false;

  public $sign_now;
  public $passphrase_certificate;
  public $path_certificate;

  /** @var CReport */
  public $report;
  /** @var CFileTraceability */
  public $file_traceability;

  /**
   * Build ProvideAndRegisterDocumentSetRequest event
   *
   * @param CCompteRendu|CFile $object compte rendu
   *
   * @see parent::build()
   *
   * @throws CMbException
   * @return void
   */
  function build($object) {
    parent::build($object);

    $xml = new CXDSXmlDocument();
    $message = $xml->createDocumentRepositoryElement($xml, "ProvideAndRegisterDocumentSetRequest");

    $factory = CCDAFactory::factory($object);
    $factory->old_version = $this->old_version;
    $factory->old_id      = $this->old_id;
    $factory->receiver    = $this->_receiver;
    $factory->xds_type    = $this->type;

    $cda = $factory->generateCDA();
    // La validation du CDA
    try {
      CCdaTools::validateCDA($cda);
    }
    catch (CMbException $e) {
      throw $e;
    }

      if ($factory->report && $factory->report->getItems()) {
          $this->report = $factory->report;
      }

    $xds           = CXDSFactory::factory($factory);
    $xds->type     = $this->type;
    $xds->doc_uuid = $this->uuid;

    switch ($this->hide) {
      case "0":
        $xds->hide_ps = true;
        break;
      case "1":
        $xds->hide_patient = true;
        break;
      case "2":
        $xds->hide_representant = true;
        break;
      default:
        $xds->hide_patient = false;
    }

    $xds->extractData();
    $xds->xcn_mediuser         = $this->xcn_mediuser      ? $this->xcn_mediuser      : $xds->xcn_mediuser;
    $xds->xon_etablissement    = $this->xon_etablissement ? $this->xon_etablissement : $xds->xon_etablissement;
    $xds->specialty            = $this->specialty         ? $this->specialty         : $xds->specialty;
    $xds->practice_setting     = $this->pratice_setting   ? $this->pratice_setting   : $xds->practice_setting;
    $xds->health_care_facility = $this->healtcare         ? $this->healtcare         : $xds->health_care_facility;
    // Ajout de la taille du CDA
    $xds->size = strlen($cda);
    $xds->hash = sha1($cda);

    $header_xds = $xds->generateXDS41();
    $xml->importDOMDocument($message, $header_xds);

    // ajout d'un document
    $document = $xml->createDocumentRepositoryElement($message, "Document");
    $xml->addAttribute($document, "id", $xds->uuid["extrinsic"]);
    /* @todo on peut faire un base64_encode du document */
    $document->nodeValue = base64_encode($cda);

    // ajout de la balise <signature> en auth. directe et indirecte mais on signe la canonisation qu'en indirecte (DMP)
    // ajout des médecins pour SISRA
    if ($this->sign || $this->add_doctors) {
      CEAIHandler::notify("AfterBuild", $this, $xml, $factory, $xds);
    }

    $this->message = $xml->saveXML($message);
    $this->updateExchange(false);
  }
}
