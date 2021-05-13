<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Resources;
use Ox\Core\CMbObject;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeCode;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeUnsignedInt;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeUri;
use Ox\Interop\Fhir\Event\CFHIREvent;

/**
 * FIHR patient resource
 */
class CFHIRResourceBundle extends CFHIRResource {
    /** @var string  */
  public const RESOURCE_TYPE = 'Bundle';

  public $id;

  public $meta;

  /** @var CFHIRDataTypeCode */
  public $type;

  /** @var CFHIRDataTypeUnsignedInt */
  public $total;

  /** @var CFHIRDataTypeUri[] */
  public $link = array();

  /** @var CFHIRResourceBundleEntry[] */
  public $entry = array();

  /**
   * @inheritdoc
   */
  function build(CMbObject $object, CFHIREvent $event) {
    parent::build($object, $event);

    $this->id   = CFHIR::generateUUID();
    $this->meta = $this->addMetaData($event->tag_profile.$event->code);
    $this->type = $this->addTypeCode($event->type);
  }
}
