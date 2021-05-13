<?php
/**
 * @package Mediboard\fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Resources;

use Exception;
use Ox\Core\CMbObject;
use Ox\Core\CStoredObject;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeBase64Binary;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeCode;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeId;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeString;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeReference;
use Ox\Interop\Fhir\Event\CFHIREvent;
use Ox\Interop\Fhir\Exception\CFHIRExceptionNotFound;
use Ox\Mediboard\Files\CDocumentItem;
use Ox\Mediboard\Files\CDocumentReference;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Description
 */
class CFHIRResourceBinary extends CFHIRResource
{
    /** @var string  */
    public const RESOURCE_TYPE = 'Binary';

    /** @var CFHIRDataTypeId[] */
    public $id;

    /** @var CFHIRDataTypeCode */
    public $contentType;

    /** @var CFHIRDataTypeString */
    public $content;

    /** @var CFHIRDataTypeReference */
    public $securityContext;

    /** @var CFHIRDataTypeBase64Binary */
    public $data;

    /**
     * @inheritdoc
     */
    public function getClass(): ?string
    {
        return CDocumentReference::class;
    }

    /**
     * @inheritdoc
     */
    public function build(CMbObject $object, CFHIREvent $event)
    {
        parent::build($object, $event);

        $this->id = CFHIR::generateUUID();

        $this->contentType = $this->getContentType($object);
        $this->content     = $this->putContent($object);
    }

    /**
     * @param array       $data Data to handle
     *
     * @param string      $limit
     * @param string|null $offset
     *
     * @return CStoredObject[]
     * @throws Exception
     */
    public function specificSearch(array $data, string $limit, ?string $offset = null): array
    {
        /** @var CSejour $object */
        $object = $this->getObject();

        /** @var CDocumentReference[] $list */
        $list = $object->loadList(null, null, $limit);
        foreach ($list as $_list) {
            $_list->loadRefObject();
        }
        $total = $object->countList();

        return [$list, $total];
    }

    /**
     * @inheritdoc
     */
    public function mapFrom(CMbObject $object): void
    {
        /** @var CDocumentReference $document_reference */
        $document_reference = $object;

        $this->id[] = new CFHIRDataTypeId($document_reference->_id);

        $document_item = $document_reference->loadRefObject();

        $this->contentType = $this->getContentType($document_item);
        $this->content     = $this->putContent($document_item);
    }
}
