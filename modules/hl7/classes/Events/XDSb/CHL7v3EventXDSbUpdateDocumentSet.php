<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7\Events\XDSb;

use Ox\Core\CMbException;
use Ox\Core\CMbSecurity;
use Ox\Interop\Cda\CCDAFactory;
use Ox\Interop\Xds\CXDSFactory;
use Ox\Interop\Xds\CXDSXmlDocument;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Files\CFile;

/**
 * CHL7v3EventXDSbUpdateDocumentSet
 * Update document set
 */
class CHL7v3EventXDSbUpdateDocumentSet extends CHL7v3EventXDSb implements CHL7EventXDSbUpdateDocumentSet {
  /** @var string */
  public $interaction_id = "UpdateDocumentSet";

  public $type;
  public $uuid;
  public $action;
  public $hide;
  public $metadata;

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

    $factory = CCDAFactory::factory($object);
    $factory->xds_type = $this->type;
    $cda     = $factory->generateCDA();
    /*try {
      CCdaTools::validateCDA($cda);
    }
    catch (CMbException $e) {
      throw $e;
    }*/

    // Cas d'archivage et dépublication
    $xml = new CXDSXmlDocument();

    $xds = CXDSFactory::factory($factory);
    $xds->type = $this->type;
    // Ajout de la taille du CDA pour les métadonnées
    $xds->size = strlen($cda);
    $xds->hash = sha1($cda);
    $xds->extractData();
    $header_xds = $xds->generateXDS57($this->uuid, $this->action, $this->hide, CMbSecurity::generateUUID()."2", $this->metadata);

    $xml->importDOMDocument($xml, $header_xds);
    // Pour la modif du masquage : On remplace le namespace dans le message puisqu'on a "copié/collé" celui de la réponse du registry
    $content = $this->action ? $xml->saveXML() : str_replace("ns4:", "rim:", $xml->saveXML());
    $this->message = $content;

    $this->updateExchange(false);
  }
}
