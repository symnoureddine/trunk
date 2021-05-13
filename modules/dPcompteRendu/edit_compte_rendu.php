<?php
/**
 * @package Mediboard\CompteRendu
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\Cache;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Core\Module\CModule;
use Ox\Mediboard\Cabinet\CConsultAnesth;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\CompteRendu\CDestinataire;
use Ox\Mediboard\CompteRendu\CModeleToPack;
use Ox\Mediboard\CompteRendu\CPack;
use Ox\Mediboard\CompteRendu\CTemplateManager;
use Ox\Mediboard\Files\CFilesCategory;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Prescription\CElementPrescription;
use Ox\Mediboard\Prescription\CPrescription;
use Ox\Mediboard\Printing\CPrinter;
use Ox\Mediboard\System\CExchangeSource;

/**
 * Création / Modification d'un document (généré à partir d'un modèle)
 */
$compte_rendu_id = CView::get("compte_rendu_id", 'ref class|CCompteRendu');
$modele_id       = CView::get("modele_id"      , 'ref class|CCompteRendu');
$praticien_id    = CView::get("praticien_id"   , 'ref class|CMediusers');
$pack_id         = CView::get("pack_id"        , 'ref class|CPack');
$object_id       = CView::get("object_id"      , 'str');
$switch_mode     = CView::get("switch_mode"    , 'bool default|0');
$target_id       = CView::get("target_id"      , 'str');
$target_class    = CView::get("target_class"   , 'str');
$force_fast_edit = CView::get("force_fast_edit", 'bool default|0');
$store_headless  = CView::get("store_headless" , "bool default|0");
$ext_cabinet_id  = CView::get("ext_cabinet_id" , "num");

/* Optionnal fields */
$object_class = CView::get("object_class", 'str');
$object_guid  = CView::get("object_guid", 'str');
$unique_id    = CView::get("unique_id", 'str');
$reload_zones = CView::get("reloadzones", 'bool default|0');

CView::checkin();

// Faire ici le test des différentes variables dont on a besoin
$compte_rendu = new CCompteRendu();

$curr_user   = CMediusers::get();
$user_opener = new CMediusers();

// Modification d'un document
if ($compte_rendu_id) {
  $compte_rendu->load($compte_rendu_id);
  if (!$compte_rendu->_id) {
    CAppUI::stepAjax(CAppUI::tr("CCompteRendu-alert_doc_deleted"));
    CApp::rip();
  }
  $compte_rendu->loadContent();
  $compte_rendu->loadComponents();
  $compte_rendu->loadFile();

  $cache = new Cache(__FILE__, $compte_rendu->_guid, Cache::OUTER | Cache::DISTR, 1200);
  $user_opened_id = $cache->get();

  // Déjà ouvert par un autre utilisateur
  if ($user_opened_id && $curr_user->_id != $user_opened_id) {
    $user_opener->load($user_opened_id)->loadRefFunction();
  }
  // Premier accesseur
  elseif (!$cache->exists() || !$user_opened_id) {
    $cache->put($curr_user->_id);
  }
}
// Création à partir d'un modèle vide
else if ($modele_id == 0 && !$pack_id) {
  $compte_rendu->valueDefaults();
  $compte_rendu->object_id = $object_id;
  $compte_rendu->object_class = $target_class;
  $compte_rendu->_ref_object = new $target_class;
  $compte_rendu->_ref_object->load($object_id);
  $compte_rendu->updateFormFields();
}
// Création à partir d'un modèle
else {
  $compte_rendu->load($modele_id);
  $compte_rendu->loadFile();
  $compte_rendu->loadContent();
  $compte_rendu->_id = null;
  $compte_rendu->function_id = null;
  $compte_rendu->group_id = null;
  $compte_rendu->object_id = $object_id;
  $compte_rendu->_ref_object = null;
  $compte_rendu->modele_id = $modele_id;

  $header_id = null;
  $footer_id = null;
  
  // Utilisation des headers/footers
  if ($compte_rendu->header_id || $compte_rendu->footer_id) {
    $header_id = $compte_rendu->header_id;
    $footer_id = $compte_rendu->footer_id;
  }
  
  // On fournit la cible
  if ($target_id && $target_class) {
    $compte_rendu->object_id = $target_id;
    $compte_rendu->object_class = $target_class;
  }
  
  // A partir d'un pack
  if ($pack_id) {
    $pack = new CPack();
    $pack->load($pack_id);
    
    $pack->loadContent();
    $compte_rendu->nom = $pack->nom;
    $compte_rendu->object_class = $pack->object_class;
    $compte_rendu->file_category_id = $pack->category_id;
    $compte_rendu->fast_edit = $pack->fast_edit;
    $compte_rendu->fast_edit_pdf = $pack->fast_edit_pdf;
    $compte_rendu->_source = $pack->_source;
    $compte_rendu->modele_id = null;
    
    $pack->loadHeaderFooter();
    
    $header_id = $pack->_header_found->_id;
    $footer_id = $pack->_footer_found->_id;
    
    // Marges et format
    /** @var $links CModeleToPack[] */
    $links = $pack->_back['modele_links'];
    $first_modele = reset($links);
    $first_modele = $first_modele->_ref_modele;
    $compte_rendu->factory       = $first_modele->factory;
    $compte_rendu->margin_top    = $first_modele->margin_top;
    $compte_rendu->margin_left   = $first_modele->margin_left;
    $compte_rendu->margin_right  = $first_modele->margin_right;
    $compte_rendu->margin_bottom = $first_modele->margin_bottom;
    $compte_rendu->page_height   = $first_modele->page_height;
    $compte_rendu->page_width    = $first_modele->page_width;
    $compte_rendu->font          = $first_modele->font;
    $compte_rendu->size          = $first_modele->size;
    $compte_rendu->send          = $first_modele->send;
    $compte_rendu->signature_mandatory = $first_modele->signature_mandatory;
  }
  $compte_rendu->_source = $compte_rendu->generateDocFromModel(null, $header_id, $footer_id);
  $compte_rendu->updateFormFields();
}

$compte_rendu->loadRefsFwd();

$compte_rendu->loadRefPrinter();

$compte_rendu->_ref_object->loadRefsFwd();
$object =& $compte_rendu->_ref_object;

if (!$compte_rendu->_id) {
  if (!$compte_rendu->font) {
    $compte_rendu->font = array_search(CAppUI::conf("dPcompteRendu CCompteRendu default_font"), CCompteRendu::$fonts);
  }

  if (!$compte_rendu->size) {
    $compte_rendu->size = CAppUI::gconf("dPcompteRendu CCompteRendu default_size");
  }

  $compte_rendu->guessSignataire();
}
else {
  $compte_rendu->getDeliveryStatus();
}

// Calcul du user concerné
$user = $curr_user;

// Chargement dans l'ordre suivant pour les listes de choix si null :
// - user courant
// - anesthésiste
// - praticien de la consultation
if (!$user->isPraticien()) {
  $user = new CMediusers();
  $user_id = null;

  switch ($object->_class) {
    case "CConsultAnesth":
      /** @var $object CConsultAnesth */
      $operation = $object->loadRefOperation();
      $anesth = $operation->_ref_anesth;
      if ($operation->_id && $anesth->_id) {
        $user_id = $anesth->_id;
      }

      if ($user_id == null) {
        $user_id = $object->_ref_consultation->_praticien_id;
      }
      break;

    case "CConsultation":
      /** @var $object CConsultation */
      $user_id = $object->loadRefPraticien()->_id;
      break;

    case "CSejour":
      /** @var $object CSejour */
      $user_id = $object->praticien_id;
      break;

    case "COperation":
      /** @var $object COperation */
      $user_id = $object->chir_id;
      break;

    default:
      $user_id = $curr_user->_id;
  }

  $user->load($user_id);
}

$function = $user->loadRefFunction();

// Chargement des catégories
$listCategory = CFilesCategory::listCatClass($compte_rendu->object_class);
if ($compte_rendu->object_class === "CEvenementPatient" && CModule::getActive("oxCabinet")) {
    $compte_rendu->loadTargetObject();
    $categorie = CAppUI::gconf("oxCabinet CEvenementPatient categorie_{$compte_rendu->_ref_object->type}_default");
    $compte_rendu->file_category_id = $categorie;
}

// Décompte des imprimantes disponibles pour l'impression serveur
$nb_printers = $curr_user->loadRefFunction()->countBackRefs("printers");

// Gestion du template
$templateManager = new CTemplateManager();
$templateManager->isModele = false;
$templateManager->document = $compte_rendu->_source;
$object->fillTemplate($templateManager);
$templateManager->loadHelpers($user->_id, $compte_rendu->object_class, $curr_user->function_id);
$templateManager->loadLists($user->_id, $modele_id ? $modele_id : $compte_rendu->modele_id);

// Cas spécial des documents appliqués sur un protocole de prescription ou un élément de prescription : se comporte comme un modèle.
if (($object instanceof CPrescription && !$object->object_id) || $object instanceof CElementPrescription) {
  $templateManager->isModele = true;
  $templateManager->valueMode = false;
}
else {
  $templateManager->applyTemplate($compte_rendu);
}

if ($store_headless) {
  $compte_rendu->content_id = "";
  $compte_rendu->_source = $templateManager->document;

  $compte_rendu->store();

  echo $compte_rendu->_id;

  return;
}

$lists = $templateManager->getUsedLists($templateManager->allLists);

// Afficher le bouton correpondant si on détecte un élément de publipostage
$isCourrier = $templateManager->isCourrier();

$destinataires = array();
if ($isCourrier) {
  CDestinataire::makeAllFor($object);
  $destinataires = CDestinataire::$destByClass;
}

$can_lock      = $compte_rendu->canLock();
$can_unclock   = $compte_rendu->canUnlock();
$can_duplicate = $compte_rendu->canDuplicate();
$compte_rendu->isLocked();
$lock_bloked = $compte_rendu->_is_locked ? !$can_unclock : !$can_lock;
if ($compte_rendu->valide && !CAppUI::gconf("dPcompteRendu CCompteRendu unlock_doc")) {
  $lock_bloked = 1;
}
$compte_rendu->canDo();
$read_only = $compte_rendu->_is_locked || !$compte_rendu->_can->edit;

if ($compte_rendu->_is_locked) {
  $templateManager->printMode = true;
}
if ($compte_rendu->_id && !$compte_rendu->canEdit()) {
  $templateManager->printMode = true;
}

/* Set the object_class if not passed in the get parameters */
if (!$object_class) {
  $object_class = $compte_rendu->object_class;
}

$compte_rendu->_ext_cabinet_id = $ext_cabinet_id;

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("listCategory"  , $listCategory);
$smarty->assign("compte_rendu"  , $compte_rendu);
$smarty->assign("modele_id"     , $modele_id);
$smarty->assign("curr_user"     , $curr_user);
$smarty->assign("user_opener"   , $user_opener);
$smarty->assign("lists"         , $lists);
$smarty->assign("isCourrier"    , $isCourrier);
$smarty->assign("user_id"       , $user->_id);
$smarty->assign("user_view"     , $user->_view);
$smarty->assign("object_id"     , $object_id);
$smarty->assign('object_class'  , $object_class);
$smarty->assign("nb_printers"   , $nb_printers);
$smarty->assign("pack_id"       , $pack_id);
$smarty->assign("destinataires" , $destinataires);
$smarty->assign("lock_bloked"   , $lock_bloked);
$smarty->assign("can_duplicate" , $can_duplicate);
$smarty->assign("read_only"     , $read_only);
$smarty->assign("unique_id"     , $unique_id);

preg_match_all("/(:?\[\[Texte libre - ([^\]]*)\]\])/i", $compte_rendu->_source, $matches);

$templateManager->textes_libres = $matches[2];

// Suppression des doublons
$templateManager->textes_libres = array_unique($templateManager->textes_libres);

if (isset($compte_rendu->_ref_file->_id)) {
  $smarty->assign("file", $compte_rendu->_ref_file);
}

$smarty->assign("textes_libres", $templateManager->textes_libres);

$exchange_source = CExchangeSource::get("mediuser-".$curr_user->_id);
$smarty->assign("exchange_source", $exchange_source);

// Ajout d'entête / pied de page à la volée
$headers = array();
$footers = array();

if (CAppUI::gconf("dPcompteRendu CCompteRendu header_footer_fly")) {
  $headers = CCompteRendu::loadAllModelesFor($user->_id, "prat", $compte_rendu->object_class, "header");
  $footers = CCompteRendu::loadAllModelesFor($user->_id, "prat", $compte_rendu->object_class, "footer");
}

$smarty->assign("headers", $headers);
$smarty->assign("footers", $footers);

// Nettoyage des balises meta et link.
// Pose problème lors de la présence d'un entête et ou/pied de page
$source = &$templateManager->document;

$source = preg_replace("/<meta\s*[^>]*\s*[^\/]>/", '', $source);
$source = preg_replace("/(<\/meta>)+/i", '', $source);
$source = preg_replace("/<link\s*[^>]*\s*>/", '', $source);

$pdf_and_thumbs = CAppUI::pref("pdf_and_thumbs");

// Chargement du modèle
if ($compte_rendu->_id) {
  $compte_rendu->loadModele();
}

if ($reload_zones == 1) {
  $smarty->display("inc_zones_fields");
}
else if (!$compte_rendu_id && !$switch_mode
    && ($compte_rendu->fast_edit || $force_fast_edit || ($compte_rendu->fast_edit_pdf && $pdf_and_thumbs))
) {
  $printers = $function->loadBackRefs("printers") ?? [];

  /** @var $_printer CPrinter */
  foreach ($printers as $_printer) {
    $_printer->loadTargetObject();
  }

  $smarty->assign("_source"     , $templateManager->document);
  $smarty->assign("printers"    , $printers);
  $smarty->assign("object_guid" , $object_guid);

  $smarty->display("fast_mode");
}
else { 
  // Charger le document précédent et suivant
  $prevnext = array();
  if ($compte_rendu->_id) {
    $object->loadRefsDocs();
    $prevnext = CMbArray::getPrevNextKeys($object->_ref_documents, $compte_rendu->_id);
  }

  $templateManager->initHTMLArea();
  $smarty->assign("switch_mode"    , $switch_mode);
  $smarty->assign("templateManager", $templateManager);
  $smarty->assign("prevnext", $prevnext);
  $smarty->display("edit_compte_rendu");
}
