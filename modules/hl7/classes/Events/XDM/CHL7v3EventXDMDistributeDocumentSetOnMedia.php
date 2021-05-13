<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7\Events\XDM;

use Ox\Core\CMbException;
use Ox\Interop\Cda\CCDAFactory;
use Ox\Interop\Eai\CEAIHandler;
use Ox\Interop\Eai\CReport;
use Ox\Interop\Hl7\Events\XDSb\CHL7v3EventXDSb;
use Ox\Interop\Xds\CXDSFactory;
use Ox\Interop\Xds\CXDSXmlDocument;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Files\CFile;

/**
 * CHL7v3EventXDSbProvideAndRegisterDocumentSetRequest
 * Provide and register document set request
 */
class CHL7v3EventXDMDistributeDocumentSetOnMedia extends CHL7v3EventXDSb implements CHL7EventXDMDistributeSetOnMedia
{
    /** @var string */
    public $interaction_id = "DistributeDocumentSetOnMedia";
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
    public $type_cda;
    public $code_loinc_cda;
    public $content_cda;
    /** @var CReport */
    public $report;

    /**
     * Build ProvideAndRegisterDocumentSetRequest event
     *
     * @param CCompteRendu|CFile $object compte rendu
     *
     * @return void
     * @throws CMbException
     * @see parent::build()
     *
     */
    public function build($object): void
    {
        parent::build($object);

        $xml     = new CXDSXmlDocument();
        $message = $xml->documentElement;

        $factory              = CCDAFactory::factory($object, 3, $this->type_cda, $this->code_loinc_cda);
        $factory->old_version = $this->old_version;
        $factory->old_id      = $this->old_id;
        $factory->receiver    = $this->_receiver;
        $factory->xds_type    = $this->type;

        $this->content_cda = $factory->generateCDA();
        $this->report      = $factory->report;

        // En cas d'erreur sur le CDA, on ne va pas plus loin
        if ($this->report) {
            return;
        }
        // La validation du CDA
        /*try {
          CCdaTools::validateCDA($cda);
        }
        catch (CMbException $e) {
          throw $e;
        }*/

        $xds           = CXDSFactory::factory($factory);
        $xds->type     = $this->type;
        $xds->doc_uuid = $this->uuid;
        $xds->uri      = $object->file_name;

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
        $xds->xcn_mediuser         = $this->xcn_mediuser ? $this->xcn_mediuser : $xds->xcn_mediuser;
        $xds->xon_etablissement    = $this->xon_etablissement ? $this->xon_etablissement : $xds->xon_etablissement;
        $xds->specialty            = $this->specialty ? $this->specialty : $xds->specialty;
        $xds->practice_setting     = $this->pratice_setting ? $this->pratice_setting : $xds->practice_setting;
        $xds->health_care_facility = $this->healtcare ? $this->healtcare : $xds->health_care_facility;
        // Ajout de la taille du CDA
        $xds->size = strlen($this->content_cda);
        $xds->hash = sha1($this->content_cda);

        $header_xds = $xds->generateXDS32("urn:oasis:names:tc:ebxml-regrep:StatusType:Approved");
        $xml->importDOMDocument($xml, $header_xds);

        $this->msg_hl7 = $xml->saveXML($message);
    }
}
