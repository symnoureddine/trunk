<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Structure;

use Ox\Interop\Cda\Datatypes\Base\CCDACE;
use Ox\Interop\Cda\Datatypes\Base\CCDACS;
use Ox\Interop\Cda\Datatypes\Base\CCDAED;
use Ox\Interop\Cda\Datatypes\Base\CCDAII;
use Ox\Interop\Cda\Datatypes\Base\CCDAINT;
use Ox\Interop\Cda\Datatypes\Base\CCDAIVL_TS;
use Ox\Interop\Cda\Datatypes\Base\CCDAIVXB_TS;
use Ox\Interop\Cda\Datatypes\Base\CCDAST;
use Ox\Interop\Cda\Datatypes\Base\CCDATS;
use Ox\Interop\Cda\Datatypes\Voc\CCDAActClinicalDocument;
use Ox\Interop\Cda\Datatypes\Voc\CCDAActMood;
use Ox\Interop\Cda\Rim\CCDARIMDocument;

/**
 * POCD_MT000040_ClinicalDocument Class
 */
class CCDAPOCD_MT000040_ClinicalDocument extends CCDARIMDocument {

  /** @var CCDAPOCD_MT000040_RecordTarget */
  public $recordTarget = array();
  /** @var CCDAPOCD_MT000040_Author */
  public $author = array();
  /** @var CCDAPOCD_MT000040_DataEnterer */
  public $dataEnterer;
  /** @var CCDAPOCD_MT000040_Informant12 */
  public $informant = array();
  /** @var CCDAPOCD_MT000040_Custodian */
  public $custodian;
  /** @var CCDAPOCD_MT000040_InformationRecipient */
  public $informationRecipient = array();
  /** @var CCDAPOCD_MT000040_LegalAuthenticator */
  public $legalAuthenticator;
  /** @var CCDAPOCD_MT000040_Authenticator */
  public $authenticator = array();
  /** @var CCDAPOCD_MT000040_Participant1 */
  public $participant = array();
  /** @var CCDAPOCD_MT000040_InFulfillmentOf */
  public $inFulfillmentOf = array();
  /** @var CCDAPOCD_MT000040_DocumentationOf */
  public $documentationOf = array();
  /** @var CCDAPOCD_MT000040_RelatedDocument */
  public $relatedDocument = array();
  /** @var CCDAPOCD_MT000040_Authorization */
  public $authorization = array();
  /** @var CCDAPOCD_MT000040_Component1 */
  public $componentOf;
  /** @var CCDAPOCD_MT000040_Component2 */
  public $component;


  /**
   * Setter id
   *
   * @param CCDAII $inst CCDAII
   *
   * @return void
   */
  function setId(CCDAII $inst) {
    $this->id = $inst;
  }

  /**
   * Getter id
   *
   * @return CCDAII
   */
  function getId() {
    return $this->id;
  }

  /**
   * Setter code
   *
   * @param CCDACE $inst CCDACE
   *
   * @return void
   */
  function setCode(CCDACE $inst) {
    $this->code = $inst;
  }

  /**
   * Getter code
   *
   * @return CCDACE
   */
  function getCode() {
    return $this->code;
  }

  /**
   * Setter title
   *
   * @param CCDAST $inst CCDAST
   *
   * @return void
   */
  function setTitle(CCDAST $inst) {
    $this->title = $inst;
  }

  /**
   * Getter title
   *
   * @return CCDAST
   */
  function getTitle() {
    return $this->title;
  }

  /**
   * Setter effectiveTime
   *
   * @param CCDATS $inst CCDATS
   *
   * @return void
   */
  function setEffectiveTime(CCDATS $inst) {
    $this->effectiveTime = $inst;
  }

  /**
   * Getter effectiveTime
   *
   * @return CCDATS
   */
  function getEffectiveTime() {
    return $this->effectiveTime;
  }

  /**
   * Setter confidentialityCode
   *
   * @param CCDACE $inst CCDACE
   *
   * @return void
   */
  function setConfidentialityCode(CCDACE $inst) {
    $this->confidentialityCode = $inst;
  }

  /**
   * Getter confidentialityCode
   *
   * @return CCDACE
   */
  function getConfidentialityCode() {
    return $this->confidentialityCode;
  }

  /**
   * Setter languageCode
   *
   * @param CCDACS $inst CCDACS
   *
   * @return void
   */
  function setLanguageCode(CCDACS $inst) {
    $this->languageCode = $inst;
  }

  /**
   * Getter languageCode
   *
   * @return CCDACS
   */
  function getLanguageCode() {
    return $this->languageCode;
  }

  /**
   * Setter setId
   *
   * @param CCDAII $inst CCDAII
   *
   * @return void
   */
  function setSetId(CCDAII $inst) {
    $this->setId = $inst;
  }

  /**
   * Getter setId
   *
   * @return CCDAII
   */
  function getSetId() {
    return $this->setId;
  }

  /**
   * Setter versionNumber
   *
   * @param CCDAINT $inst CCDAINT
   *
   * @return void
   */
  function setVersionNumber(CCDAINT $inst) {
    $this->versionNumber = $inst;
  }

  /**
   * Getter versionNumber
   *
   * @return CCDAINT
   */
  function getVersionNumber() {
    return $this->versionNumber;
  }

  /**
   * Setter copyTime
   *
   * @param CCDATS $inst CCDATS
   *
   * @return void
   */
  function setCopyTime(CCDATS $inst) {
    $this->copyTime = $inst;
  }

  /**
   * Getter copyTime
   *
   * @return CCDATS
   */
  function getCopyTime() {
    return $this->copyTime;
  }

  /**
   * Ajoute l'instance spécifié dans le tableau
   *
   * @param CCDAPOCD_MT000040_RecordTarget $inst CCDAPOCD_MT000040_RecordTarget
   *
   * @return void
   */
  function appendRecordTarget(CCDAPOCD_MT000040_RecordTarget $inst) {
    array_push($this->recordTarget, $inst);
  }

  /**
   * Efface le tableau
   *
   * @return void
   */
  function resetListRecordTarget() {
    $this->recordTarget = array();
  }

  /**
   * Getter recordTarget
   *
   * @return CCDAPOCD_MT000040_RecordTarget[]
   */
  function getRecordTarget() {
    return $this->recordTarget;
  }

  /**
   * Ajoute l'instance spécifié dans le tableau
   *
   * @param CCDAPOCD_MT000040_Author $inst CCDAPOCD_MT000040_Author
   *
   * @return void
   */
  function appendAuthor(CCDAPOCD_MT000040_Author $inst) {
    array_push($this->author, $inst);
  }

  /**
   * Efface le tableau
   *
   * @return void
   */
  function resetListAuthor() {
    $this->author = array();
  }

  /**
   * Getter author
   *
   * @return CCDAPOCD_MT000040_Author[]
   */
  function getAuthor() {
    return $this->author;
  }

  /**
   * Setter dataEnterer
   *
   * @param CCDAPOCD_MT000040_DataEnterer $inst CCDAPOCD_MT000040_DataEnterer
   *
   * @return void
   */
  function setDataEnterer(CCDAPOCD_MT000040_DataEnterer $inst) {
    $this->dataEnterer = $inst;
  }

  /**
   * Getter dataEnterer
   *
   * @return CCDAPOCD_MT000040_DataEnterer
   */
  function getDataEnterer() {
    return $this->dataEnterer;
  }

  /**
   * Ajoute l'instance spécifié dans le tableau
   *
   * @param CCDAPOCD_MT000040_Informant12 $inst CCDAPOCD_MT000040_Informant12
   *
   * @return void
   */
  function appendInformant(CCDAPOCD_MT000040_Informant12 $inst) {
    array_push($this->informant, $inst);
  }

  /**
   * Efface le tableau
   *
   * @return void
   */
  function resetListInformant() {
    $this->informant = array();
  }

  /**
   * Getter informant
   *
   * @return CCDAPOCD_MT000040_Informant12[]
   */
  function getInformant() {
    return $this->informant;
  }

  /**
   * Setter custodian
   *
   * @param CCDAPOCD_MT000040_Custodian $inst CCDAPOCD_MT000040_Custodian
   *
   * @return void
   */
  function setCustodian(CCDAPOCD_MT000040_Custodian $inst) {
    $this->custodian = $inst;
  }

  /**
   * Getter custodian
   *
   * @return CCDAPOCD_MT000040_Custodian
   */
  function getCustodian() {
    return $this->custodian;
  }

  /**
   * Ajoute l'instance spécifié dans le tableau
   *
   * @param CCDAPOCD_MT000040_InformationRecipient $inst CCDAPOCD_MT000040_InformationRecipient
   *
   * @return void
   */
  function appendInformationRecipient(CCDAPOCD_MT000040_InformationRecipient $inst) {
    array_push($this->informationRecipient, $inst);
  }

  /**
   * Efface le tableau
   *
   * @return void
   */
  function resetListInformationRecipient() {
    $this->informationRecipient = array();
  }

  /**
   * Getter informationRecipient
   *
   * @return CCDAPOCD_MT000040_InformationRecipient[]
   */
  function getInformationRecipient() {
    return $this->informationRecipient;
  }

  /**
   * Setter legalAuthenticator
   *
   * @param CCDAPOCD_MT000040_LegalAuthenticator $inst CCDAPOCD_MT000040_LegalAuthenticator
   *
   * @return void
   */
  function setLegalAuthenticator(CCDAPOCD_MT000040_LegalAuthenticator $inst) {
    $this->legalAuthenticator = $inst;
  }

  /**
   * Getter legalAuthenticator
   *
   * @return CCDAPOCD_MT000040_LegalAuthenticator
   */
  function getLegalAuthenticator() {
    return $this->legalAuthenticator;
  }

  /**
   * Ajoute l'instance spécifié dans le tableau
   *
   * @param CCDAPOCD_MT000040_Authenticator $inst CCDAPOCD_MT000040_Authenticator
   *
   * @return void
   */
  function appendAuthenticator(CCDAPOCD_MT000040_Authenticator $inst) {
    array_push($this->authenticator, $inst);
  }

  /**
   * Efface le tableau
   *
   * @return void
   */
  function resetListAuthenticator() {
    $this->authenticator = array();
  }

  /**
   * Getter authenticator
   *
   * @return CCDAPOCD_MT000040_Authenticator[]
   */
  function getAuthenticator() {
    return $this->authenticator;
  }

  /**
   * Ajoute l'instance spécifié dans le tableau
   *
   * @param CCDAPOCD_MT000040_Participant1 $inst CCDAPOCD_MT000040_Participant1
   *
   * @return void
   */
  function appendParticipant(?CCDAPOCD_MT000040_Participant1 $inst) {
    array_push($this->participant, $inst);
  }

  /**
   * Efface le tableau
   *
   * @return void
   */
  function resetListParticipant() {
    $this->participant = array();
  }

  /**
   * Getter participant
   *
   * @return CCDAPOCD_MT000040_Participant1[]
   */
  function getParticipant() {
    return $this->participant;
  }

  /**
   * Ajoute l'instance spécifié dans le tableau
   *
   * @param CCDAPOCD_MT000040_InFulfillmentOf $inst CCDAPOCD_MT000040_InFulfillmentOf
   *
   * @return void
   */
  function appendInFulfillmentOf(CCDAPOCD_MT000040_InFulfillmentOf $inst) {
    array_push($this->inFulfillmentOf, $inst);
  }

  /**
   * Efface le tableau
   *
   * @return void
   */
  function resetListInFulfillmentOf() {
    $this->inFulfillmentOf = array();
  }

  /**
   * Getter inFulfillmentOf
   *
   * @return CCDAPOCD_MT000040_InFulfillmentOf[]
   */
  function getInFulfillmentOf() {
    return $this->inFulfillmentOf;
  }

  /**
   * Ajoute l'instance spécifié dans le tableau
   *
   * @param CCDAPOCD_MT000040_DocumentationOf $inst CCDAPOCD_MT000040_DocumentationOf
   *
   * @return void
   */
  function appendDocumentationOf(CCDAPOCD_MT000040_DocumentationOf $inst) {
    array_push($this->documentationOf, $inst);
  }

  /**
   * Efface le tableau
   *
   * @return void
   */
  function resetListDocumentationOf() {
    $this->documentationOf = array();
  }

  /**
   * Getter documentationOf
   *
   * @return CCDAPOCD_MT000040_DocumentationOf[]
   */
  function getDocumentationOf() {
    return $this->documentationOf;
  }

  /**
   * Ajoute l'instance spécifié dans le tableau
   *
   * @param CCDAPOCD_MT000040_RelatedDocument $inst CCDAPOCD_MT000040_RelatedDocument
   *
   * @return void
   */
  function appendRelatedDocument(CCDAPOCD_MT000040_RelatedDocument $inst) {
    array_push($this->relatedDocument, $inst);
  }

  /**
   * Efface le tableau
   *
   * @return void
   */
  function resetListRelatedDocument() {
    $this->relatedDocument = array();
  }

  /**
   * Getter relatedDocument
   *
   * @return CCDAPOCD_MT000040_RelatedDocument[]
   */
  function getRelatedDocument() {
    return $this->relatedDocument;
  }

  /**
   * Ajoute l'instance spécifié dans le tableau
   *
   * @param CCDAPOCD_MT000040_Authorization $inst CCDAPOCD_MT000040_Authorization
   *
   * @return void
   */
  function appendAuthorization(CCDAPOCD_MT000040_Authorization $inst) {
    array_push($this->authorization, $inst);
  }

  /**
   * Efface le tableau
   *
   * @return void
   */
  function resetListAuthorization() {
    $this->authorization = array();
  }

  /**
   * Getter authorization
   *
   * @return CCDAPOCD_MT000040_Authorization[]
   */
  function getAuthorization() {
    return $this->authorization;
  }

  /**
   * Setter componentOf
   *
   * @param CCDAPOCD_MT000040_Component1 $inst CCDAPOCD_MT000040_Component1
   *
   * @return void
   */
  function setComponentOf(CCDAPOCD_MT000040_Component1 $inst) {
    $this->componentOf = $inst;
  }

  /**
   * Getter componentOf
   *
   * @return CCDAPOCD_MT000040_Component1
   */
  function getComponentOf() {
    return $this->componentOf;
  }

  /**
   * Setter component
   *
   * @param CCDAPOCD_MT000040_Component2 $inst CCDAPOCD_MT000040_Component2
   *
   * @return void
   */
  function setComponent(CCDAPOCD_MT000040_Component2 $inst) {
    $this->component = $inst;
  }

  /**
   * Getter component
   *
   * @return CCDAPOCD_MT000040_Component2
   */
  function getComponent() {
    return $this->component;
  }

  /**
   * Assigne classCode à DOCCLIN
   *
   * @return void
   */
  function setClassCode() {
    $actClinic = new CCDAActClinicalDocument();
    $actClinic->setData("DOCCLIN");
    $this->classCode = $actClinic;
  }

  /**
   * Getter classCode
   *
   * @return CCDAActClinicalDocument
   */
  function getClassCode() {
    return $this->classCode;
  }

  /**
   * Assigne moodCode à EVN
   *
   * @return void
   */
  function setMoodCode() {
    $mood = new CCDAActMood();
    $mood->setData("EVN");
    $this->moodCode = $mood;
  }

  /**
   * Getter moodCode
   *
   * @return CCDAActMood
   */
  function getMoodCode() {
    return $this->moodCode;
  }

  /**
   * Retourne les propriétés
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["typeId"]               = "CCDAPOCD_MT000040_InfrastructureRoot_typeId xml|element required";
    $props["id"]                   = "CCDAII xml|element required";
    $props["code"]                 = "CCDACE xml|element required";
    $props["title"]                = "CCDAST xml|element max|1";
    $props["effectiveTime"]        = "CCDATS xml|element required";
    $props["confidentialityCode"]  = "CCDACE xml|element required";
    $props["languageCode"]         = "CCDACS xml|element max|1";
    $props["setId"]                = "CCDAII xml|element max|1";
    $props["versionNumber"]        = "CCDAINT xml|element max|1";
    $props["copyTime"]             = "CCDATS xml|element max|1";
    $props["recordTarget"]         = "CCDAPOCD_MT000040_RecordTarget xml|element min|1";
    $props["author"]               = "CCDAPOCD_MT000040_Author xml|element min|1";
    $props["dataEnterer"]          = "CCDAPOCD_MT000040_DataEnterer xml|element max|1";
    $props["informant"]            = "CCDAPOCD_MT000040_Informant12 xml|element";
    $props["custodian"]            = "CCDAPOCD_MT000040_Custodian xml|element required";
    $props["informationRecipient"] = "CCDAPOCD_MT000040_InformationRecipient xml|element";
    $props["legalAuthenticator"]   = "CCDAPOCD_MT000040_LegalAuthenticator xml|element max|1";
    $props["authenticator"]        = "CCDAPOCD_MT000040_Authenticator xml|element";
    $props["participant"]          = "CCDAPOCD_MT000040_Participant1 xml|element";
    $props["inFulfillmentOf"]      = "CCDAPOCD_MT000040_InFulfillmentOf xml|element";
    $props["documentationOf"]      = "CCDAPOCD_MT000040_DocumentationOf xml|element";
    $props["relatedDocument"]      = "CCDAPOCD_MT000040_RelatedDocument xml|element";
    $props["authorization"]        = "CCDAPOCD_MT000040_Authorization xml|element";
    $props["componentOf"]          = "CCDAPOCD_MT000040_Component1 xml|element max|1";
    $props["component"]            = "CCDAPOCD_MT000040_Component2 xml|element required";
    $props["classCode"]            = "CCDAActClinicalDocument xml|attribute fixed|DOCCLIN";
    $props["moodCode"]             = "CCDAActMood xml|attribute fixed|EVN";
    return $props;
  }

  /**
   * Fonction permettant de tester la classe
   *
   * @return array
   */
  function test() {
    $tabTest = parent::test();

    /**
     * Test avec un Id incorrect
     */

    $ii = new CCDAII();
    $ii->setRoot("4TESTTEST");
    $this->setId($ii);
    $tabTest[] = $this->sample("Test avec un Id incorrect", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un Id correct
     */

    $ii->setRoot("1.2.250.1.213.1.1.9");
    $this->setId($ii);
    $tabTest[] = $this->sample("Test avec un Id correct", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un code incorrect
     */

    $ce = new CCDACE();
    $ce->setCode(" ");
    $this->setCode($ce);
    $tabTest[] = $this->sample("Test avec un code incorrect", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un code correct
     */

    $ce->setCode("SYNTH");
    $this->setCode($ce);
    $tabTest[] = $this->sample("Test avec un code correct", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec deux templateId correcte
     */

    $ii = new CCDAII();
    $ii->setRoot("1.2.5");
    $this->appendTemplateId($ii);
    $tabTest[] = $this->sample("Test avec deux templateId correct", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec trois templateId correcte
     */

    $ii = new CCDAII();
    $ii->setRoot("1.2.5.6");
    $this->appendTemplateId($ii);
    $tabTest[] = $this->sample("Test avec trois templateId correct", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un author correcte
     */

    $auth = new CCDAPOCD_MT000040_Author();
    $ts = new CCDATS();
    $ts->setValue("24141331462095.812975314545697850652375076363185459409261232419230495159675586");
    $auth->setTime($ts);

    $assigned = new CCDAPOCD_MT000040_AssignedAuthor();
    $ii = new CCDAII();
    $ii->setRoot("1.2.5");
    $assigned->appendId($ii);
    $auth->setAssignedAuthor($assigned);
    $this->appendAuthor($auth);
    $tabTest[] = $this->sample("Test avec un author correct", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un custodian correcte
     */

    $custo = new CCDAPOCD_MT000040_Custodian();
    $assign = new CCDAPOCD_MT000040_AssignedCustodian();
    $custoOrg = new CCDAPOCD_MT000040_CustodianOrganization();
    $ii = new CCDAII();
    $ii->setRoot("1.25.2");
    $custoOrg->appendId($ii);
    $assign->setRepresentedCustodianOrganization($custoOrg);
    $custo->setAssignedCustodian($assign);
    $this->setCustodian($custo);
    $tabTest[] = $this->sample("Test avec un custodian correct", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un recordTarget correcte
     */

    $reco = new CCDAPOCD_MT000040_RecordTarget();
    $rolePatient = new CCDAPOCD_MT000040_PatientRole();
    $ii = new CCDAII();
    $ii->setRoot("1.2.250.1.213.1.1.9");
    $rolePatient->appendId($ii);
    $reco->setPatientRole($rolePatient);
    $this->appendRecordTarget($reco);
    $tabTest[] = $this->sample("Test avec un recordTarget correct", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un effectiveTime incorrect
     */

    $ts = new CCDATS();
    $ts->setValue("TEST");
    $this->setEffectiveTime($ts);
    $tabTest[] = $this->sample("Test avec un effectiveTime incorrect", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un effectiveTime correct
     */

    $ts->setValue("75679245900741.869627871786625715081550660290154484483335306381809807748522068");
    $this->setEffectiveTime($ts);
    $tabTest[] = $this->sample("Test avec un effectiveTime correct", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un confidentialityCode incorrect
     */

    $ce = new CCDACE();
    $ce->setCode(" ");
    $this->setConfidentialityCode($ce);
    $tabTest[] = $this->sample("Test avec un confidentialityCode incorrect", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un confidentialityCode correct
     */

    $ce->setCode("TEST");
    $this->setConfidentialityCode($ce);
    $tabTest[] = $this->sample("Test avec un confidentialityCode correct", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un component correcte
     */

    $comp = new CCDAPOCD_MT000040_Component2();
    $nonXML = new CCDAPOCD_MT000040_NonXMLBody();
    $ed = new CCDAED();
    $ed->setLanguage("TEST");
    $nonXML->setText($ed);
    $comp->setNonXMLBody($nonXML);
    $this->setComponent($comp);
    $tabTest[] = $this->sample("Test avec un component correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un classCode correcte
     */

    $this->setClassCode();
    $tabTest[] = $this->sample("Test avec un classCode correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un moodCode correcte
     */

    $this->setMoodCode();
    $tabTest[] = $this->sample("Test avec un moodCode correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un title correct
     */

    $st = new CCDAST();
    $st->setData("TEST");
    $this->setTitle($st);
    $tabTest[] = $this->sample("Test avec un title correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un languageCode incorrect
     */

    $cs = new CCDACS();
    $cs->setCode(" ");
    $this->setLanguageCode($cs);
    $tabTest[] = $this->sample("Test avec un languageCode incorrect", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un languageCode correct
     */

    $cs->setCode("TEST");
    $this->setLanguageCode($cs);
    $tabTest[] = $this->sample("Test avec un languageCode correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un setId incorrect
     */

    $ii = new CCDAII();
    $ii->setRoot("4TESTTEST");
    $this->setSetId($ii);
    $tabTest[] = $this->sample("Test avec un setId incorrect", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un setId correct
     */

    $ii->setRoot("1.25.4");
    $this->setSetId($ii);
    $tabTest[] = $this->sample("Test avec un setId correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un versionNumber incorrect
     */

    $int = new CCDAINT();
    $int->setValue("10.25");
    $this->setVersionNumber($int);
    $tabTest[] = $this->sample("Test avec un versionNumber incorrect", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un versionNumber correct
     */

    $int->setValue("10");
    $this->setVersionNumber($int);
    $tabTest[] = $this->sample("Test avec un versionNumber correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un copyTime incorrect
     */

    $ts = new CCDATS();
    $ts->setValue("TEST");
    $this->setCopyTime($ts);
    $tabTest[] = $this->sample("Test avec un copyTime incorrect", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un copyTime correct
     */

    $ts->setValue("75679245900741.869627871786625715081550660290154484483335306381809807748522068");
    $this->setCopyTime($ts);
    $tabTest[] = $this->sample("Test avec un copyTime correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un dataEnterer correct
     */

    $data = new CCDAPOCD_MT000040_DataEnterer();
    $assign = new CCDAPOCD_MT000040_AssignedEntity();
    $ii = new CCDAII();
    $ii->setRoot("1.2.5");
    $assign->appendId($ii);
    $data->setAssignedEntity($assign);
    $this->setDataEnterer($data);
    $tabTest[] = $this->sample("Test avec un dataEnterer correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un informant correct
     */

    $infor = new CCDAPOCD_MT000040_Informant12();
    $assigned = new CCDAPOCD_MT000040_AssignedEntity();
    $ii = new CCDAII();
    $ii->setRoot("1.2.5");
    $assigned->appendId($ii);
    $infor->setAssignedEntity($assigned);
    $this->appendInformant($infor);
    $tabTest[] = $this->sample("Test avec un informant correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un informationRecipient correct
     */

    $inforReci = new CCDAPOCD_MT000040_InformationRecipient();
    $inten = new CCDAPOCD_MT000040_IntendedRecipient();
    $inten->setTypeId();
    $inforReci->setIntendedRecipient($inten);
    $this->appendInformationRecipient($inforReci);
    $tabTest[] = $this->sample("Test avec un informationRecipient correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un legalAuthenticator correct
     */

    $legal = new CCDAPOCD_MT000040_LegalAuthenticator();
    $ts = new CCDATS();
    $ts->setValue("24141331462095.812975314545697850652375076363185459409261232419230495159675586");
    $legal->setTime($ts);

    $cs = new CCDACS();
    $cs->setCode("TEST");
    $legal->setSignatureCode($cs);

    $assigned = new CCDAPOCD_MT000040_AssignedEntity();
    $ii = new CCDAII();
    $ii->setRoot("1.2.5");
    $assigned->appendId($ii);
    $legal->setAssignedEntity($assigned);
    $tabTest[] = $this->sample("Test avec un legalAuthenticator correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un authenticator correct
     */

    $authen = new CCDAPOCD_MT000040_Authenticator();
    $ts = new CCDATS();
    $ts->setValue("24141331462095.812975314545697850652375076363185459409261232419230495159675586");
    $authen->setTime($ts);

    $cs = new CCDACS();
    $cs->setCode("TEST");
    $authen->setSignatureCode($cs);

    $assigned = new CCDAPOCD_MT000040_AssignedEntity();
    $ii = new CCDAII();
    $ii->setRoot("1.2.5");
    $assigned->appendId($ii);
    $authen->setAssignedEntity($assigned);
    $tabTest[] = $this->sample("Test avec un authenticator correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un participant correct
     */

    $part = new CCDAPOCD_MT000040_Participant1();
    $associated = new CCDAPOCD_MT000040_AssociatedEntity();
    $associated->setClassCode("RoleClassPassive");
    $part->setAssociatedEntity($associated);

    $part->setTypeCode("CST");
    $this->appendParticipant($part);
    $tabTest[] = $this->sample("Test avec un participant correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un inFulfillmentOf correct
     */

    $inFull = new CCDAPOCD_MT000040_InFulfillmentOf();
    $ord = new CCDAPOCD_MT000040_Order();
    $ii = new CCDAII();
    $ii->setRoot("1.2.5");
    $ord->appendId($ii);
    $inFull->setOrder($ord);
    $this->appendInFulfillmentOf($inFull);
    $tabTest[] = $this->sample("Test avec un inFulfillmentOf correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un documentationOf correct
     */

    $doc = new CCDAPOCD_MT000040_DocumentationOf();
    $event = new CCDAPOCD_MT000040_ServiceEvent();
    $event->setMoodCode();
    $doc->setServiceEvent($event);
    $this->appendDocumentationOf($doc);
    $tabTest[] = $this->sample("Test avec un documentationOf correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un relatedDocument correct
     */

    $rela = new CCDAPOCD_MT000040_RelatedDocument();
    $parent = new CCDAPOCD_MT000040_ParentDocument();
    $ii = new CCDAII();
    $ii->setRoot("1.2.5");
    $parent->appendId($ii);
    $rela->setParentDocument($parent);
    $rela->setTypeCode("RPLC");
    $this->appendRelatedDocument($rela);
    $tabTest[] = $this->sample("Test avec un relatedDocument correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un authorization correct
     */

    $autho = new CCDAPOCD_MT000040_Authorization();
    $pocConsent = new CCDAPOCD_MT000040_Consent();
    $cs = new CCDACS();
    $cs->setCode(" ");
    $pocConsent->setStatusCode($cs);
    $autho->setConsent($pocConsent);
    $autho->setTypeCode();

    $cs->setCode("TEST");
    $pocConsent->setStatusCode($cs);
    $autho->setConsent($pocConsent);
    $this->appendAuthorization($autho);
    $tabTest[] = $this->sample("Test avec un authorization correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un componentOf correct
     */

    $compOf = new CCDAPOCD_MT000040_Component1();
    $encou = new CCDAPOCD_MT000040_EncompassingEncounter();
    $ivl_ts = new CCDAIVL_TS();
    $hi = new CCDAIVXB_TS();
    $hi->setValue("75679245900741.869627871786625715081550660290154484483335306381809807748522068");
    $ivl_ts->setHigh($hi);
    $encou->setEffectiveTime($ivl_ts);
    $compOf->setEncompassingEncounter($encou);
    $this->setComponentOf($compOf);
    $tabTest[] = $this->sample("Test avec un componentOf correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
