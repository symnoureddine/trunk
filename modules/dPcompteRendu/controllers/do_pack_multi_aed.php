<?php

/**
 * @package Mediboard\CompteRendu
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\CompteRendu\CModeleToPack;
use Ox\Mediboard\CompteRendu\CPack;
use Ox\Mediboard\CompteRendu\CTemplateManager;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * Pack multiple docs aed
 */
// Génération d'un document pour chaque modèle du pack
$pack_id         = CView::post("pack_id", "ref class|CPack");
$object_class    = CView::post("object_class", "str");
$object_id       = CView::post("object_id", "ref class|$object_class");
$ext_cabinet_id  = CView::post("_ext_cabinet_id", "num");
$callback        = CView::post("callback", "str");
$liste_cr        = CView::post("liste_cr", "str");

CView::checkin();

$user_id = CMediusers::get()->_id;

$pack = new CPack();
$pack->load($pack_id);

$modele_to_pack  = new CModeleToPack();
$modeles_to_pack = $modele_to_pack->loadAllModelesFor($pack_id);

/** @var $object CMbObject */
$object = new $object_class();
$object->load($object_id);

$cr_to_push = null;

// Sauvegarde du premier compte-rendu pour
// l'afficher dans la popup d'édition de compte-rendu
if (!$pack->is_eligible_selection_document) {
    $array_modeles = $modeles_to_pack;
    $first         = reset($modeles_to_pack);
    $modeles       = CMbObject::massLoadFwdRef($modeles_to_pack, "modele_id");
    CMbObject::massLoadFwdRef($modeles, "content_id");
} else {
    $array_modeles = $liste_cr;
    $first         = $liste_cr[0];
}
foreach ($array_modeles as $array_modele) {
    (!$pack->is_eligible_selection_document) ? $modele = $array_modele->loadRefModele(
    ) : $modele = CCompteRendu::findOrFail($array_modele);

    $modele->loadContent();

    $template           = new CTemplateManager();
    $template->isModele = false;

    $object->fillTemplate($template);

    $cr = new CCompteRendu();

    $cr->modele_id        = $modele->_id;
    $cr->object_class     = $object_class;
    $cr->object_id        = $object_id;
    $cr->author_id        = $user_id;
    $cr->nom              = $modele->nom;
    $cr->margin_right     = $modele->margin_right;
    $cr->margin_left      = $modele->margin_left;
    $cr->margin_top       = $modele->margin_top;
    $cr->margin_bottom    = $modele->margin_bottom;
    $cr->file_category_id = $modele->file_category_id;
    $cr->_ext_cabinet_id  = $ext_cabinet_id;
    if ($pack->merge_docs) {
        $cr->file_category_id = $pack->_ref_categorie->_id;
    }
    $cr->font         = $modele->font;
    $cr->size         = $modele->size;
    $cr->factory      = $modele->factory;
    $cr->send         = $modele->send;
    $cr->page_width   = $modele->page_width;
    $cr->page_height  = $modele->page_height;
    $cr->_orientation = "portrait";
    if ($cr->page_width > $cr->page_height) {
        $cr->_orientation = "landscape";
    }
    $cr->signature_mandatory = $modele->signature_mandatory;
    $cr->guessSignataire();

    $cr->loadContent(false);

    $cr->_source = $modele->generateDocFromModel();
    $template->applyTemplate($cr);
    $cr->_source = $template->document;

    if ($msg = $cr->store()) {
        CAppUI::setMsg($msg, UI_MSG_ERROR);
    }

    if ($array_modele === $first) {
        $cr_to_push = $cr;
    }
}

if ($callback && $cr_to_push) {
    $fields = $cr_to_push->getProperties();
    CAppUI::callbackAjax($callback, $cr_to_push->_id, $fields);
}

CAppUI::setMsg(CAppUI::tr("CPack-msg-create"), UI_MSG_OK);

echo CAppUI::getMsg();

CApp::rip();
