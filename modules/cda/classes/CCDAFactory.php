<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda;
use DOMElement;
use DOMNodeList;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Core\CMbXMLDocument;
use Ox\Core\CMbXPath;
use Ox\Interop\Cda\Datatypes\Base\CCDAII;
use Ox\Interop\Dmp\CDMPValueSet;
use Ox\Interop\Eai\CInteropReceiver;
use Ox\Interop\Eai\CReport;
use Ox\Interop\Xds\Structure\CXDSValueSet;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Cabinet\CConsultAnesth;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Permet de générer le CDA selon les champs générique
 */
class CCDAFactory implements IShortNameAutoloadable
{
    /** @var string */
    public static $vsm_code_jdv = "urn:asip:ci-sis:vsm:2012";
    /** @var string */
    public static $ldl_ees_code_jdv = "urn:asip:ci-sis:ldl-ees:2017";
    /** @var string */
    public static $ldl_ses_code_jdv = "urn:asip:ci-sis:ldl-ses:2017";
    /** @var string */
    public static $name_file_vsm = "SYNTH.XML";
    /** @var string */
    public static $type_doc_vsm = "1.2.250.1.213.1.1.4.12^SYNTH";
    /** @var string */
    public static $type_doc_ldl_ees = "2.16.840.1.113883.6.1^18761-7";
    /** @var string */
    public static $type_doc_ldl_ses = "2.16.840.1.113883.6.1^11490-0";
    /** @var string */
    public static $type_vsm = "VSM";
    /** @var string */
    public static $type_ldl_ees = "LDL-EES";
    /** @var string */
    public static $type_ldl_ses = "LDL-SES";

    public const NONE_ALLERGY          = 'NoneAllergy';
    public const NONE_TREATMENT        = 'NoneTreatment';
    public const NONE_PATHOLOGY        = 'NonePathology';
    public const STATUS_DOCUMENT       = 'statutDoc';
    public const TA_ASIP               = 'TA_ASIP';
    public const MODALITE_ENTREE       = 'modaliteE';
    public const MODALITE_SORTIE       = 'modaliteS';
    public const SYNTHESE              = 'synthese';
    public const RECHERCHE_MICRO_MULTI = 'RechercheMicroMulti';
    public const TRANSFU               = 'transfu';
    public const ADMI_SANG             = 'admiSang';

    /** @var string[] */
    public static $mapping_type_cda_with_metadatas = array(
        'LDL-EES' => array(
            'code_loinc'   => '18761-7',
            'file_name'    => 'Lettre de liaison à l\'entrée en établissement de santé',
            'file_name_mb' => 'LDLEES.XML',
            'type_doc'     => '2.16.840.1.113883.6.1^18761-7'
        ),
        'LDL-SES' => array(
            'code_loinc'   => '11490-0',
            'file_name'    => 'Lettre de liaison à la sortie de l\'établissement de santé',
            'file_name_mb' => 'LDLSES.XML',
            'type_doc'     => '2.16.840.1.113883.6.1^11490-0'
        ),
    );

    /** @var string[] */
    public static $referentielTemplateId = array(
        "VSM"     => array(
            array("root" => "1.2.250.1.213.1.1.1.13", "extension" => "Synthèse médicale"),
        ),
        'LDL-EES' => array(
            array('root' => '1.2.250.1.213.1.1.1.21', 'extension' => ''),
        ),
        'LDL-SES' => array(
            array('root' => '1.2.250.1.213.1.1.1.29', 'extension' => '2020.01'),
        )
    );

    /** @var string[] */
    public static $mapping_function_with_templatesId = array(
        "addAntecedentsMedicaux"     =>
            array("2.16.840.1.113883.10.20.1.27", "1.3.6.1.4.1.19376.1.5.3.1.4.5.1", "1.3.6.1.4.1.19376.1.5.3.1.4.5.2"),
        "addAntecedentsChirurgicaux" =>
            array('1.3.6.1.4.1.19376.1.5.3.1.4.19', '2.16.840.1.113883.10.20.1.29'),
        "addAllergies"               =>
            array('2.16.840.1.113883.10.20.1.27', '1.3.6.1.4.1.19376.1.5.3.1.4.5.1', '1.3.6.1.4.1.19376.1.5.3.1.4.5.3'),
        "addMedications"             =>
            array("2.16.840.1.113883.10.20.1.24", "1.3.6.1.4.1.19376.1.5.3.1.4.7"),
        "addTraitementAdmission"     =>
            array("2.16.840.1.113883.10.20.1.24", "1.3.6.1.4.1.19376.1.5.3.1.4.7", "1.2.250.1.213.1.1.3.42"),
        "addPathologiesActives"      =>
            array("2.16.840.1.113883.10.20.1.27", "1.3.6.1.4.1.19376.1.5.3.1.4.5.1", "1.3.6.1.4.1.19376.1.5.3.1.4.5.2"),
        "addStatusDocument"          => array("1.2.250.1.213.1.1.2.35"),
        "addMotifHospitalisation"    => array("1.3.6.1.4.1.19376.1.5.3.1.3.1"),
        "addMedicalSynthesis"        => array(
            "1.3.6.1.4.1.19376.1.7.3.1.1.13.7",
            "1.2.250.1.213.1.1.2.163",
            "1.2.250.1.213.1.1.2.163.1"
        ),
        "addTreatmentExit"           => array(
            "2.16.840.1.113883.10.20.1.24",
            "1.3.6.1.4.1.19376.1.5.3.1.4.7",
            "1.2.250.1.213.1.1.3.42"
        ),
        "addCommentaire"             => array("1.3.6.1.4.1.19376.1.4.1.2.16", "2.16.840.1.113883.10.12.201"),
        /*"addFacteursRisquesFamiliaux" => (
          array("2.16.840.1.113883.10.20.1.23", "1.3.6.1.4.1.19376.1.5.3.1.4.15")
        )*/
    );

    /** @var string[] */
    public static $mapping_mode_entree_jdv = array(
        '8' => 'ORG-068',
        '7' => 'ORG-069',
        'O' => 'ORG-069',
        '6' => 'GEN-092',
        'N' => 'GEN-092',
    );

    /** @var string[] */
    public static $mapping_mode_sortie_jdv = array(
        'normal'         => 'ORG-101',
        'transfert'      => 'ORG-073',
        'transfert_acte' => 'ORG-073',
        'mutation'       => 'GEN-092',
        'deces'          => 'GEN-092',
    );

    /** @var string[] */
    public static $check_creation_child_component = array(
        "1.2.250.1.213.1.1.2.31"            => "checkFacteursRisques",
        "1.2.250.1.213.1.1.2.32"            => "checkTraitementAuLongCours",
        "1.3.6.1.4.1.19376.1.5.3.1.3.16"    => "checkHabitus",
        "1.3.6.1.4.1.19376.1.5.3.1.3.19"    => "checkMedications",
        "1.3.6.1.4.1.19376.1.5.3.1.3.6"     => "checkPathologiesActives",
        "1.3.6.1.4.1.19376.1.5.3.1.1.5.3.1" => "checkFacteursRisquesProfessionels",
        "1.3.6.1.4.1.19376.1.5.3.1.3.15"    => "checkFacteursRisquesFamiliaux",
        "1.3.6.1.4.1.19376.1.5.3.1.3.12"    => "checkAntecedentsChirurgicaux",
        "1.3.6.1.4.1.19376.1.5.3.1.3.8"     => "checkAntecedentsMedicaux",
    );

    /** @var String */
    public $root;
    /** @var CMbObject */
    public $mbObject;
    /** @var COperation|CConsultAnesth|CConsultation|CSejour */
    public $targetObject;
    /** @var CPatient */
    public $patient;
    /** @var CUser|CMediusers */
    public $practicien;
    /** @var CCDADomDocument */
    public $dom_cda;
    /** @var  CInteropReceiver */
    public $receiver;

    public $level         = 1;
    public $version;
    public $mediaType;
    public $file;
    public $nom;
    public $id_cda;
    public $id_cda_lot;
    public $realm_code;
    public $langage;
    public $confidentialite;
    public $date_creation;
    public $code;
    public $date_author;
    public $industry_code;
    public $healt_care;
    public $service_event = array();
    public $templateId    = array();
    public $old_version;
    public $old_id;
    public $size;
    /** @var CReport */
    public $report;

  public $xds_type;
  public $type_cda;
  public $code_loinc_cda;
  public $_structure_cda = array();

  /** @var CXDSValueSet|CDMPValueSet */
  public $valueset_factory;

  /**
   * Création de la classe en fonction de l'objet passé
   *
   * @param CMbObject $mbObject objet mediboard
   * @param int       $level    level
   * @param string    $type_cda type cda
   *
   * @return CCDAFactory
   */
  static function factory($mbObject, $level = 1, $type_cda = null, $code_loinc_cda = null) {
    switch (get_class($mbObject)) {
      case CFile::class:
      case CCompteRendu::class:
        $class = new CCDAFactoryDocItem($mbObject);
        $class->level = $level;
        $class->type_cda = $type_cda;
        $class->code_loinc_cda = $code_loinc_cda;
        break;
      default:
        $class = new self($mbObject);
    }

    return $class;
  }

    /**
     * @param CMbObject $mbObject Object
     *
     * @see parent::__construct
     *
     */
    public function __construct(CMbObject $mbObject)
    {
        $this->mbObject = $mbObject;
        $this->report   = new CReport('Report CDA');
    }

  /**
   * Extraction des données pour alimenter le CDA
   *
   * @throws CMbException
   * @return void
   */
  function extractData() {
  }

  /**
   * Generation du CDA
   *
   * @return string
   * @throws CMbException
   */
  function generateCDA() {
    $this->extractData();
    $document_cda = new CCDADocumentCDA();
    $cda = $document_cda->generateCDA($this);
    $dom = $cda->toXML("ClinicalDocument", "urn:hl7-org:v3");

    // Ajout du lien de la feuille de style
    /*$base_url = CAppUI::conf("base_url");
    $res = explode("/", $base_url);*/
    // TODO : Gerer l'affichage d'un fichier VSM dans MB (bug avec le chemin de la feuille de style)
    //$xslt = $dom->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="cda_asip.xsl"');
    //$dom->insertBefore($xslt, $dom->documentElement);

    $dom->purgeEmptyElements();
    $this->dom_cda = $dom;

    return $dom->saveXML($dom);
  }

  /**
   * Création de templateId
   *
   * @param String $root      String
   * @param String $extension null
   *
   * @return CCDAII
   */
  function createTemplateID($root, $extension = null) {
    $ii = new CCDAII();
    $ii->setRoot($root);
    $ii->setExtension($extension);
    return $ii;
  }

  /**
   * Add templateId for sections
   *
   * @param string $type_cda type cda
   *
   * @return void
   */
  function mappingTypeCDAWithTemplateId($type_cda) {
    // Ajout des sections en fonction du type de CDA
    $template_Ids = CMbArray::get(CCDAFactory::$referentielTemplateId, $type_cda);
    foreach ($template_Ids as $_template_Id) {
      $this->templateId[] = $this->createTemplateID(
        CMbArray::get($_template_Id, "root"), CMbArray::get($_template_Id, "extension")
      );
    }
  }

  /**
   * Get XML structure
   *
   * @param string $type_cda type cda
   *
   * @return void
   */
  function getStructureCDAFromType($type_cda) {
    //On charge le XML contenant la structure du CDA
    if (!file_exists("modules/cda/resources/schemaCDA/$type_cda.xml")) {
      CAppUI::stepAjax("CDA-msg-Structure file does not exist", UI_MSG_ERROR, $type_cda);
    }

    $dom = new CMbXMLDocument("UTF-8");
    $dom->load("modules/cda/resources/schemaCDA/$type_cda.xml");

    $xpath = new CMbXPath($dom);
    $xpath->registerNamespace("ns4", "urn:hl7-org:v3");

    $components = $xpath->query("ns4:component/ns4:structuredBody/ns4:component");
    if (!$components) {
      CAppUI::stepAjax("CDA-msg-Impossible to get component", UI_MSG_ERROR, $type_cda);
    }

    $structure = array();
    $this->buildStructure($xpath, $components, $structure);
  }

  /**
   * Get Metadata for CDAr2
   *
   * @param string $type_ldl
   * @param string $metadata_name
   *
   * @return string
   */
  static function getMetadata($type_ldl, $metadata_name) {
    return CMbArray::get(CMbArray::get(CCDAFactory::$mapping_type_cda_with_metadatas, $type_ldl), $metadata_name);
  }

  /**
   * Build structure
   *
   * @param CMbXPath    $xpath            xpath
   * @param DOMNodeList $components_nodes components nodes
   * @param array       $structure        Structure
   *
   * @return void
   */
  function buildStructure(CMbXPath $xpath, DOMNodeList $components_nodes, &$structure) {
    /** @var DOMElement $_component_node */
    $compteur_component = 0;
    foreach ($components_nodes as $_component_node) {
      $compteur_component++;

      $sections_nodes = $xpath->query("ns4:section", $_component_node);
      // Initialisation du tableau des sections
      $compteur_section = 0;
      foreach ($sections_nodes as $_section_node) {
        $compteur_section++;

        $function_name = $_section_node->getAttribute("function");

        // Récupération des templatesId du component et donc de la section
        $templatesId_nodes = $xpath->query("ns4:templateId", $_section_node);
        $templatesId = array();
        $compteur_template = 0;
        foreach ($templatesId_nodes as $_templateId_node) {
          $compteur_template++;

          /** @var CCDAII $template_Id */
          $template_Id = $this->createTemplateID(
            $_templateId_node->getAttribute("root"), utf8_decode($_templateId_node->getAttribute("extension"))
          );
          $template_Id->_code_loinc = $_templateId_node->getAttribute("codeLoinc");
          $template_Id->_title      = utf8_decode($_templateId_node->getAttribute("title"));
          $templatesId["template_$compteur_template"] = $template_Id;
          /*array(
                      "root"      => $_templateId_node->getAttribute("root"),
                      "extension" => $_templateId_node->getAttribute("extension"),
                      "codeLoinc" => $_templateId_node->getAttribute("codeLoinc"),
                    );*/
        }

        // TODO : Faire les tests ici pour savoir sion met la section ou pas (check_functionName return bool)

        $structure["component_$compteur_component"]["section_$compteur_section"] = array(
          "templates" => $templatesId,
          "function"  => $function_name);

        $components_nodes_child = $xpath->query("ns4:component", $_section_node);
        // Si on a des component à l'intérieur du component parent, on descend récursivement
        if ($components_nodes_child->length > 0) {
          $this->buildStructure(
            $xpath,
            $components_nodes_child,
            $structure["component_$compteur_component"]["section_$compteur_section"]["components"]
          );
        }
      }
    }

    $this->_structure_cda = $structure;
  }
}
