<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_Component1;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_Component2;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_DocumentationOf;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_RelatedDocument;

/**
 * Classe regroupant les fonctions de type ActRelationship
 */
class CCDAActRelationshipCDA extends CCDADocumentCDA {

  /**
   * Création de l'actrelationship Component
   *
   * @return CCDAPOCD_MT000040_Component2
   */
  function setComponent2() {
    $component2 = new CCDAPOCD_MT000040_Component2();
    if (parent::$cda_factory->level == 1) {
      $component2->setNonXMLBody(parent::$act->setNonXMLBody());
    }
    else {
      $component2->setStructuredBody(parent::$act->setStructuredBody());
    }

    return $component2;
  }

  /**
   * Création componentOf
   *
   * @return CCDAPOCD_MT000040_Component1
   */
  function setComponentOf() {
    $componentOf = new CCDAPOCD_MT000040_Component1();
    $componentOf->setEncompassingEncounter(parent::$act->setEncompassingEncounter());
    return $componentOf;
  }

  /**
   * Création de documentationOf
   *
   * @return CCDAPOCD_MT000040_DocumentationOf
   */
  function setDocumentationOF() {
    $documentationOf = new CCDAPOCD_MT000040_DocumentationOf();
    $documentationOf->setServiceEvent(parent::$act->setServiceEvent());
    return $documentationOf;
  }

  /**
   * Création du relatedDocument
   *
   * @return CCDAPOCD_MT000040_RelatedDocument
   */
  function appendRelatedDocument() {
    $related = new CCDAPOCD_MT000040_RelatedDocument();
    if (parent::$cda_factory->old_version) {
      $related->setTypeCode("RPLC");
      $related->setParentDocument(parent::$act->setParentDocument());
    }
    return $related;
  }
}