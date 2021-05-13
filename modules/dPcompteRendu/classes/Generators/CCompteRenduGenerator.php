<?php
/**
 * @package Mediboard\CompteRendu
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\CompteRendu\Generators;

use DOMNode;
use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbObject;
use Ox\Core\CMbString;
use Ox\Core\CMbXMLDocument;
use Ox\Core\Generators\CObjectGenerator;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\CompteRendu\CTemplateManager;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\PlanningOp\Generators\CSejourGenerator;
use Ox\Mediboard\Sante400\CIdSante400;

/**
 * Description
 */
class CCompteRenduGenerator extends CObjectGenerator {
  CONST FILE_HEADER = "[HEADER_FILE]";

  static $mb_class = CCompteRendu::class;
  static $dependances = array(CSejour::class);

  protected $import_tag = "CCompteRenduGenerator";
  /** @var CCompteRendu */
  protected $object;

  /** @var CMbObject */
  protected $context;


  /**
   * @inheritdoc
   */
  function generate($type = "crh") {
    try {
      $modele = $this->getOrCreateModele($type);
    }
    catch (Exception $e) {
      CAppUI::setMsg($e->getMessage(), UI_MSG_WARNING);

      return null;
    }


    if ($this->force || !$this->context) {
      $this->context = (new CSejourGenerator())->generate();
    }

    $this->object      = $this->getCompteRenduFromModele($modele);
    $this->object->nom = CMbString::upper($type);

    if ($msg = $this->object->store()) {
      CAppUI::setMsg($msg, UI_MSG_WARNING);
    }
    else {
      $this->object->makePDFpreview();
      CAppUI::setMsg("CCompteRendu-msg-create", UI_MSG_OK);
    }

    return $this->object;
  }

  /**
   * Init the generator
   *
   * @param CMbObject $context Object to link CCompteRendu to
   *
   * @return $this
   */
  public function init($context) {
    $this->context = $context;

    return $this;
  }

  /**
   * @param CCompteRendu $compte_rendu Modele to get CCompteRendu from
   *
   * @return CCompteRendu
   */
  protected function getCompteRenduFromModele($compte_rendu) {
    $modele_id = $compte_rendu->_id;

    $compte_rendu->loadFile();
    $compte_rendu->loadContent();
    $compte_rendu->compte_rendu_id = null;
    $compte_rendu->function_id     = null;
    $compte_rendu->group_id        = null;
    $compte_rendu->object_id       = $this->context->_id;
    $compte_rendu->_ref_object     = null;
    $compte_rendu->modele_id       = $modele_id;
    $compte_rendu->content_id      = null;

    $header_id = null;
    $footer_id = null;

    // Utilisation des headers/footers
    if ($compte_rendu->header_id || $compte_rendu->footer_id) {
      $header_id = $compte_rendu->header_id;
      $footer_id = $compte_rendu->footer_id;
    }

    $compte_rendu->_source = $compte_rendu->generateDocFromModel(null, $header_id, $footer_id);
    $compte_rendu->updateFormFields();

    $ctx = $compte_rendu->loadTargetObject();

    // Use context to replace fields
    $templateManager = new CTemplateManager();
    $templateManager->isModele = false;
    $templateManager->document = $compte_rendu->_source;
    $ctx->fillTemplate($templateManager);


    $templateManager->applyTemplate($compte_rendu);

    $compte_rendu->_source = $templateManager->document;

    return $compte_rendu;
  }

  /**
   * @param string $type Type of CCompteRendu to create : cro|crh
   *
   * @return CCompteRendu|CMbObject|null
   * @throws Exception
   */
  protected function getOrCreateModele($type = "crh") {
    $idx = CIdSante400::getMatch("CCompteRendu", $this->import_tag, $type);
    if ($idx && $idx->_id) {
      $modele = $idx->loadTargetObject();
      if ($modele && $modele->_id) {
        return $modele;
      }
    }

    $modele_path = rtrim(CAppUI::conf('root_dir'), "\\/") . "/modules/populate/resources/cro_crh.xml";
    if (!file_exists($modele_path)) {
      CAppUI::stepAjax("Impossible d'importer les modèles", UI_MSG_WARNING);

      return null;
    }

    $doc         = file_get_contents($modele_path);
    $header_file = $this->importHeaderFile();
    if ($header_file && $header_file->_id) {
      $doc = str_replace(static::FILE_HEADER, $header_file->_guid, $doc);
    }

    $xml = new CMbXMLDocument(null);
    $xml->loadXML($doc);

    $root = $xml->firstChild->childNodes;

    $modeles_ids   = array();
    $modele_return = null;

    $group = CGroups::loadCurrent();
    /** @var DOMNode $_modele */
    foreach ($root as $_modele) {
      if ($_modele->attributes->getNamedItem("cr_type")->nodeValue == $type) {
        $modele = CCompteRendu::importModele($_modele, null, null, $group->_id, $modeles_ids);

        if ($modele && $modele->_id) {
          CAppUI::stepAjax($modele->nom . " - " . CAppUI::tr("CCompteRendu-msg-create"), UI_MSG_OK);

          if ($modele->type == "body") {
            $modele_return = $modele;

            $idx = new CIdSante400();
            $idx->setObject($modele);
            $idx->tag   = $this->import_tag;
            $idx->id400 = $type;

            $idx->store();
          }
        }
      }
    }

    return $modele_return;
  }

  /**
   * @return CFile|null
   */
  protected function importHeaderFile() {
    $file_path = rtrim(CAppUI::conf('root_dir'), "\\/") . "/modules/populate/resources/header_icon.png";

    if (!file_exists($file_path)) {
      return null;
    }

    $file = new CFile();
    $file->setObject(CGroups::loadCurrent());
    $file->file_name = "CCompteRenduGenerator-header";
    $file->loadMatchingObjectEsc();

    if ($file && $file->_id) {
      return $file;
    }

    $file->fillFields();
    $file->setContent(file_get_contents($file_path));
    if ($msg = $file->store()) {
      CAppUI::setMsg($msg, UI_MSG_WARNING);
    }

    return $file;
  }
}
