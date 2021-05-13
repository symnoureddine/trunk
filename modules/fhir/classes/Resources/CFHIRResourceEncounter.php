<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Resources;

use Ox\Core\CMbObject;
use Ox\Core\CStoredObject;
use Ox\Interop\Eai\CDomain;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeCode;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeId;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeBackboneElement;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeCodeableConcept;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeIdentifier;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypePeriod;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeReference;
use Ox\Interop\Fhir\Exception\CFHIRExceptionNotFound;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * FIHR encounter resource
 */
class CFHIRResourceEncounter extends CFHIRResource
{
    /** @var string  */
    public const RESOURCE_TYPE = 'Encounter';

    /** @var int */
    public $id;

    /** @var CFHIRDataTypeIdentifier[] */
    public $identifier;

    /** @var CFHIRDataTypeCode */
    public $status;

    /** @var CFHIRDataTypeCodeableConcept */
    public $type;

    /** @var CFHIRDataTypeReference */
    public $subject;

    /** @var CFHIRDataTypeBackboneElement */
    public $participant;

    /** @var CFHIRDataTypePeriod */
    public $period;

    /**
     * @inheritdoc
     */
    public function getClass(): ?string
    {
        return CSejour::class;
    }

    /**
     * @inheritdoc
     */
    public function mapFrom(CMbObject $object): void
    {
        /** @var CSejour $sejour */
        $sejour = $object;
        parent::mapFrom($sejour);

        $domains = CDomain::loadDomainIdentifiers($sejour);

        foreach ($domains as $_domain) {
            if (empty($sejour->_returned_oids) || in_array($_domain->OID, $sejour->_returned_oids)) {
                $this->identifier[] = CFHIRDataTypeIdentifier::build(
                    [
                        "system" => "urn:oid:$_domain->OID",
                        "value"  => $_domain->_identifier->id400,
                    ]
                );
            }
        }

        $status = null;
        switch ($sejour->_etat) {
            case "preadmission":
                $status = "planned";
                break;
            case "encours":
                $status = "in-progress";
                break;
            case "cloture":
                $status = "finished";
                break;
            default:
        }
        if ($sejour->annule) {
            $status = "cancelled";
        }
        $this->status = new CFHIRDataTypeCode($status);

        $this->subject = CFHIRDataTypeReference::build(
            [
                "reference" => "Patient/$sejour->patient_id",
            ]
        );

        $this->participant = CFHIRDataTypeBackboneElement::build(
            [
                "type"       => "ADM",
                "individual" => CFHIRDataTypeReference::build(
                    [
                        "reference" => "Practitioner/$sejour->praticien_id",
                    ]
                ),
            ]
        );

        $this->period = CFHIRDataTypePeriod::build(
            $this->formatPeriod($sejour->entree, $sejour->sortie)
        );
    }
}
