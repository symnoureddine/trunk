<?php

/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda;
use Ox\Core\CMbException;
use Ox\Interop\Cda\Datatypes\Base\CCDACE;
use Ox\Interop\Cda\Datatypes\Base\CCDACS;
use Ox\Interop\Cda\Datatypes\Base\CCDAED;
use Ox\Interop\Cda\Datatypes\Base\CCDAII;
use Ox\Interop\Cda\Datatypes\Base\CCDAINT;
use Ox\Interop\Cda\Datatypes\Base\CCDAST;
use Ox\Interop\Cda\Datatypes\Base\CCDATS;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_ClinicalDocument;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_EncompassingEncounter;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_NonXMLBody;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_ParentDocument;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_Participant1;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_ServiceEvent;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_StructuredBody;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Loinc\CLoinc;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Classe regroupant les fonctions de type Act
 */
class CCDAActCDA extends CCDADocumentCDA {

  /**
   * Cr�ation d'un clinicalDocument
   *
   * @return CCDAPOCD_MT000040_ClinicalDocument
   */
  function setClinicalDocument() {
    $factory         = self::$cda_factory;
    $participation   = parent::$participation;
    $actRelationship = parent::$actRelationship;

    //d�claration du document
    $clinicaldocument = new CCDAPOCD_MT000040_ClinicalDocument();

    /**
     * Cr�ation de l'ent�te
     */

    //Cr�ation de l'Id du document
    $ii = new CCDAII();
    $ii->setRoot($factory->id_cda);
    $clinicaldocument->setId($ii);

    //cr�ation du typeId
    $clinicaldocument->setTypeId();

    //Ajout du realmCode FR
    $cs = new CCDACS();
    $cs->setCode($factory->realm_code);
    $clinicaldocument->appendRealmCode($cs);

    //Ajout du code langage fr-FR
    $cs = new CCDACS();
    $cs->setCode($factory->langage);
    $clinicaldocument->setLanguageCode($cs);

    //Ajout de la confidentialit� du document
    $confidentialite = $factory->confidentialite;
    $ce = new CCDACE();
    $ce->setCode($confidentialite["code"]);
    $ce->setCodeSystem($confidentialite["codeSystem"]);
    $ce->setDisplayName($confidentialite["displayName"]);

    $clinicaldocument->setConfidentialityCode($ce);

    //Ajout de la date de cr�ation du document
    $ts = new CCDATS();
    $ts->setValue($this->getTimeToUtc($factory->date_creation));
    $clinicaldocument->setEffectiveTime($ts);

    //Ajout du num�ro de version
    $int = new CCDAINT();
    $int->setValue($factory->version);
    $clinicaldocument->setVersionNumber($int);

    //Ajout de l'identifiant du lot
    $ii = new CCDAII();
    $ii->setRoot($factory->id_cda_lot);
    $clinicaldocument->setSetId($ii);

    //Ajout du nom du document
    $st = new CCDAST();
    $st->setData($factory->nom);
    $clinicaldocument->setTitle($st);

    //Ajout du code du document (Jeux de valeurs)
    $ce = new CCDACE();
    $code = $factory->code;
    $ce->setCode($code["code"]);
    $ce->setCodeSystem($code["codeSystem"]);
    $ce->setDisplayName($code["displayName"]);
    $clinicaldocument->setCode($ce);

    /**
     * D�claration Template
     */
    //conformit� HL7
    foreach ($factory->templateId as $_templateId) {
      $clinicaldocument->appendTemplateId($_templateId);
    }

    /**
     * Cr�ation des �l�ments obligatoire constituant le document
     */
    //Ajout des patients
    $clinicaldocument->appendRecordTarget($participation->setRecordTarget());
    //Ajout de l'�tablissement
    $clinicaldocument->setCustodian($participation->setCustodian());
    //Ajout des auteurs
    $clinicaldocument->appendAuthor($participation->setAuthor());
    //Ajout de l'auteur legal
    $clinicaldocument->setLegalAuthenticator($participation->setLegalAuthenticator());
      // Ajout du participant (m�decin traitant) obligatoire pour le VSM
      if ($factory->level == 3 && $factory->type_cda == "VSM") {
          $participant = new CCDAPOCD_MT000040_Participant1();
          $clinicaldocument->appendParticipant($participant->setParticipant($factory));
      }
    //Ajout des actes m�dicaux(ccam et cim10)
    $clinicaldocument->appendDocumentationOf($actRelationship->setDocumentationOF());
    //Ajout de la rencontre(Contexte : s�jour, consultation, op�ration)
    $clinicaldocument->setComponentOf($actRelationship->setComponentOf());
    //Ajout du document parent
    $clinicaldocument->appendRelatedDocument($actRelationship->appendRelatedDocument());

    /**
     * Cr�ation du corp du document
     */
    $clinicaldocument->setComponent($actRelationship->setComponent2());

    return $clinicaldocument;
  }

  /**
   * Cr�ation d'un corps non structur�
   *
   * @return CCDAPOCD_MT000040_NonXMLBody
   * @throws CMbException
   */
  function setNonXMLBody() {
    $file      = self::$cda_factory->file;
    $mediaType = self::$cda_factory->mediaType;
    $nonXMLBody = new CCDAPOCD_MT000040_NonXMLBody();

    $ed = new CCDAED();
    $ed->setMediaType($mediaType);
    $ed->setRepresentation("B64");
    if (!$file) {
      throw new CMbException("Aucun fichier renseign�");
    }
    $ed->setData(base64_encode(file_get_contents($file)));

    $nonXMLBody->setText($ed);
    return $nonXMLBody;
  }

  /**
   * Cr�ation encompassingEncounter
   *
   * @return CCDAPOCD_MT000040_EncompassingEncounter
   */
  function setEncompassingEncounter() {
    $encompassingEncounter = new CCDAPOCD_MT000040_EncompassingEncounter();
    /** @var CSejour|COperation|CConsultation $object CSejour*/
    $object = self::$cda_factory->targetObject;
    $ivl = "";
    switch (get_class($object)) {
      case CSejour::class:
        $low = $object->entree_reelle;
        if (!$low) {
          $low = $object->entree_prevue;
        }

        $high = $object->sortie_reelle;
        if (!$high) {
          $high = $object->sortie_prevue;
        }

        $ivl = $this->createIvlTs($low, $high);

        break;
      case COperation::class:
        $ivl = $this->createIvlTs($object->debut_op, $object->fin_op);
        $encompassingEncounter->setEffectiveTime($ivl);

        break;
      case CConsultation::class:
        $object->loadRefPlageConsult();
        $ivl = $this->createIvlTs($object->_datetime, $object->_date_fin, true);
        break;
      default:
    }
    $encompassingEncounter->setEffectiveTime($ivl);

    $encompassingEncounter->setLocation(parent::$participation->setLocation());

    return $encompassingEncounter;
  }

  /**
   * Cr�ation de service event
   *
   * @return CCDAPOCD_MT000040_ServiceEvent
   */
  function setServiceEvent() {
    $service_event = self::$cda_factory->service_event;

    $serviceEvent = new CCDAPOCD_MT000040_ServiceEvent();
    $ce           = new CCDACE();
    $time_start   = $service_event["time_start"];
    $time_stop    = $service_event["time_stop"];
    $ivl = parent::createIvlTs($time_start, $time_stop);
    $serviceEvent->setEffectiveTime($ivl);
    if ($service_event["nullflavor"]) {
      $ce->setNullFlavor($service_event["nullflavor"]);
    }
    else {
      if (self::$cda_factory->level == 3
        && (self::$cda_factory->type_cda == CCDAFactory::$type_ldl_ees || self::$cda_factory->type_cda == CCDAFactory::$type_ldl_ses)
      ) {
        $ce->setCode($service_event["code"]);
        $ce->setDisplayName($service_event["libelle"]);
        $ce->setCodeSystem($service_event["oid"]);
        $ce->setCodeSystemName("HL7:ActCode");
      }
      else {
        $code = $service_event["code"];

        // Est-ce que c'est un code LOINC (cas CDA structur�)
        if (self::$cda_factory->level == 3) {
          $code_loinc = CLoinc::get($code);
          if ($code_loinc && $code_loinc->_id) {
            $ce->setCode($service_event["code"]);
            $ce->setDisplayName($service_event["libelle"]);
            $ce->setCodeSystem(CLoinc::$oid_loinc);
            $ce->setCodeSystemName("LOINC");
          }
        }
        else {
          $ce->setCode($service_event["code"]);
          $ce->setCodeSystem($service_event["oid"]);
        }
      }
    }
    $serviceEvent->appendPerformer(parent::$participation->setPerformer($service_event["executant"]));
    $serviceEvent->setCode($ce);

    return $serviceEvent;
  }

  /**
   * Cr�ation du document parent
   *
   * @return CCDAPOCD_MT000040_ParentDocument
   */
  function setParentDocument() {
    $parent = new CCDAPOCD_MT000040_ParentDocument();
    $ii = new CCDAII();
    $ii->setRoot(parent::$cda_factory->old_version);
    $parent->appendId($ii);
    return $parent;
  }

  /**
   * Cr�ation d'un contenu structur�
   *
   * @return CCDAPOCD_MT000040_StructuredBody
   */
  function setStructuredBody() {
    $structured = new CCDAPOCD_MT000040_StructuredBody();
    $factory    = self::$cda_factory;

    // On parcourt tous les components, sections, templatesId  qui ont �t� ajout�es dans extractData()
    /** @var CCDAII $_templateId */
    foreach ($factory->_structure_cda as $_component) {
      $structured->createStructure($_component, $factory);
    }

    return $structured;
  }
}
