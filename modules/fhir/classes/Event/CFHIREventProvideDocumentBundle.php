<?php
/**
 * @package Mediboard\fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Event;

use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Interop\Fhir\Exception\CFHIRException;
use Ox\Interop\Fhir\Resources\CFHIRResource;
use Ox\Interop\Fhir\Resources\CFHIRResourceBundle;

/**
 * Description
 */
class CFHIREventProvideDocumentBundle extends CFHIREvent
{
    /**
     * Construct
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->code = "iti-65";
        $this->type = "transaction";
    }

    /**
     * Build event
     *
     * @param CMbObject $object Object
     *
     * @return CFHIRResourceBundle
     * @throws CFHIRException
     * @see parent::build()
     *
     */
    public function build(CMbObject $object): CFHIRResource
    {
        // Construction du Bundle
        $bundle = new CFHIRResourceBundle();
        $bundle->build($object, $this);

        // Construction du DocumentManifest
        $manifest = $this->addDocumentManifest($object, $bundle);
        // Construction du DocumentReference
        $this->addDocumentReference($object, $bundle, $manifest);
        $this->addBinaryReference($object, $bundle);

        return $bundle;
    }
}
