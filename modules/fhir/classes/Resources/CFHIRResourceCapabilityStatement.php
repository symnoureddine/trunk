<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Resources;

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeCode;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeDate;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeDateTime;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeString;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeBackboneElement;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeReference;

/**
 * FIHR CapabilityStatement resource
 */
class CFHIRResourceCapabilityStatement extends CFHIRResource
{
    /** @var string */
    public const RESOURCE_TYPE = 'CapabilityStatement';

    /** @var CFHIRDataTypeString */
    public $version;

    /** @var CFHIRDataTypeCode */
    public $status;

    /** @var CFHIRDataTypeDateTime */
    public $date;

    /** @var CFHIRDataTypeString */
    public $publisher;

    /** @var CFHIRDataTypeCode */
    public $kind;

    /** @var CFHIRDataTypeBackboneElement */
    public $software;

    /** @var CFHIRDataTypeCode */
    public $fhirVersion;

    /** @var CFHIRDataTypeCode */
    public $acceptUnknown;

    /** @var CFHIRDataTypeCode[] */
    public $format;

    /** @var CFHIRDataTypeBackboneElement[] */
    public $rest;

    /** @var CFHIRDataTypeBackboneElement[] */
    public $resource;

    /**
     *
     */
    function interaction_capabilities($data)
    {
        $applicationVersion = CApp::getReleaseInfo();

        $this->status = new CFHIRDataTypeCode("active");

        $this->date = new CFHIRDataTypeDate(CMbArray::get($applicationVersion, "releaseDate"));

        $this->publisher = new CFHIRDataTypeString("Not provided");

        $this->kind = new CFHIRDataTypeCode("instance");

        $this->software = CFHIRDataTypeBackboneElement::build(
            [
                "name"        => new CFHIRDataTypeString(CAppUI::conf("product_name")),
                "version"     => new CFHIRDataTypeString(
                    CMbArray::get(CMbArray::get($applicationVersion, "version"), "string")
                ),
                "releaseDate" => new CFHIRDataTypeDate(CMbArray::get($applicationVersion, "releaseDate")),
            ]
        );

        $this->fhirVersion = new CFHIRDataTypeCode(CAppUI::conf("fhir version"));

        $this->acceptUnknown = new CFHIRDataTypeCode("no");

        $this->format[] = [
            new CFHIRDataTypeCode("application/fhir+xml"),
            new CFHIRDataTypeCode("application/fhir+json"),
        ];

        $resources = [];
        foreach (CFHIR::getResources() as $_resource_type => $_interactions) {
            if ($_resource_type == "CapabilityStatement") {
                continue;
            }
            $interactions = [];
            foreach ($_interactions as $_interaction) {
                if ($_interaction == "search") {
                    $_interaction = "search-type";
                }
                if ($_interaction == "history") {
                    $_interaction = "history-instance";
                }
                $interactions[] = CFHIRDataTypeBackboneElement::build(
                    [
                        "code" => new CFHIRDataTypeCode($_interaction),
                    ]
                );
            }

            $resources[] = CFHIRDataTypeBackboneElement::build(
                [
                    "type"        => new CFHIRDataTypeString($_resource_type),
                    "profile"     => CFHIRDataTypeReference::build(
                        [
                            "reference" => new CFHIRDataTypeString("http://hl7.org/fhir/Profile/$_resource_type"),
                        ]
                    ),
                    "interaction" => $interactions,
                ]
            );
        }

        $this->rest[] = CFHIRDataTypeBackboneElement::build(
            [
                "mode"     => new CFHIRDataTypeCode("server"),
                "resource" => $resources,
            ]
        );
    }
}
