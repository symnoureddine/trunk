<?php
/**
 * @package Mediboard\CompteRendu
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\CompteRendu;

use CMb128BObject;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CMbString;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Core\Sessions\CSessionHandler;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * Gestion avancée de documents (destinataires, listes de choix, etc.)
 */
class CTemplateManager implements IShortNameAutoloadable {
  public $editor = "ckeditor";

  public $sections      = array();
  public $helpers       = array();
  public $allLists      = array();
  public $lists         = array();
  public $graphs        = array();
  public $textes_libres = array();

  public $template;
  public $document;
  public $usedLists     = array();
  public $isCourrier;

  public $valueMode     = true; // @todo : changer en applyMode
  public $isModele      = true;
  public $printMode     = false;
  public $simplifyMode  = false;
  public $messageMode   = false;
  public $parameters    = array();
  public $font;
  public $size;
  public $destinataires = array();

  private static $barcodeCache = array();

  /**
   * Constructeur
   *
   * @param array $parameters [optional]
   * @param bool  $valueMode Fill the fields with value
   */
  function __construct($parameters = array(), $valueMode = true) {
    $this->valueMode = $valueMode;
    $user = CMediusers::get();
    $user->loadRefSpecCPAM();
    $user->loadRefDiscipline();

    $this->parameters = $parameters;

    $courrier_section = CAppUI::tr('common-Mail');
    $formule_subItem = CAppUI::tr('CSalutation');
    $copy_subItem = CAppUI::tr('common-copy to');

    if ($this->getParameter("isBody", 1)) {
      $properties = array(
        "$formule_subItem - Début",
        "$formule_subItem - Fin",
        "$formule_subItem - " . CAppUI::tr('CSalutation-vous-te'),
        "$formule_subItem - " . CAppUI::tr('CSalutation-vous-t'),
        "$formule_subItem - " . CAppUI::tr('CSalutation-votre-ton'),
        "$formule_subItem - " . CAppUI::tr('CSalutation-votre-ta'),
        "$formule_subItem - " . CAppUI::tr('CSalutation-votre-accord genre patient'),
        CAppUI::tr('common-recipient name'),
        CAppUI::tr('common-recipient address'),
        CAppUI::tr('common-recipient cp city'),
        CAppUI::tr('common-brotherhood'),
        "$copy_subItem - ".CAppUI::tr('common-simple'),
        "$copy_subItem - ".CAppUI::tr('common-simple (multiline)'),
        "$copy_subItem - ".CAppUI::tr('common-full'),
        "$copy_subItem - ".CAppUI::tr('common-full (multiline)')
      );

      foreach ($properties as $_property) {
        $this->addProperty("$courrier_section - $_property", "[$courrier_section - $_property]");
      }
    }

    $general_section = CAppUI::tr('General');
    $now = CMbDT::dateTime();
    $this->addDateProperty("$general_section - ".CAppUI::tr('common-day date'), $now);
    $this->addLongDateProperty("$general_section - ".CAppUI::tr('common-date of the day (long)'), $now);
    $this->addLongDateProperty("$general_section - ".CAppUI::tr('common-date of the day (long, lowercase)'), $now, true);
    $this->addTimeProperty("$general_section - ".CAppUI::tr('common-current time'), $now);
    $this->addDateProperty("$general_section - ".CAppUI::tr('common-last day of the calendar year'), substr($now, 0, 4)."-12-31");

    $meta_section = CAppUI::tr('common-Meta Data|pl');
    $lock_subItem = CAppUI::tr('common-Lock date');
    $locker_subItem = CAppUI::tr('common-Locker');

    if ($this->getParameter("isModele")) {
      $this->addProperty("$meta_section - $lock_subItem - ".CAppUI::tr('common-Date'));
      $this->addProperty("$meta_section - $lock_subItem - ".CAppUI::tr('common-Hour'));
      $this->addProperty("$meta_section - $locker_subItem - ".CAppUI::tr('common-Name'));
      $this->addProperty("$meta_section - $locker_subItem - ".CAppUI::tr('CMediusers-_p_first_name'));
      $this->addProperty("$meta_section - $locker_subItem - ".CAppUI::tr('common-Initial|pl'));
    }

    // Connected user
    $user_complete = $user->_view;
    if ($user->isPraticien()) {
      if ($user->titres) {
        $user_complete .= "\n" . $user->titres;
      }
      if ($user->spec_cpam_id) {
        $spec_cpam = $user->loadRefSpecCPAM();
        $user_complete .= "\n" . $spec_cpam->text;
      }
      if ($user->adeli) {
        $user_complete .= "\nAdeli : " . $user->adeli;
      }
      if ($user->rpps) {
        $user_complete .= "\nRPPS : " . $user->rpps;
      }
      if ($user->_user_email) {
        $user_complete .= "\nE-mail : " . $user->_user_email;
      }
    }

    // Initials
    $elements_first_name = preg_split("/[ -]/", $user->_user_first_name);
    $initials_first_name = "";

    foreach ($elements_first_name as $_element) {
      $initials_first_name .= strtoupper(substr($_element, 0, 1));
    }

    $elements_last_name = preg_split("/[ -]/", $user->_user_last_name);
    $initials_last_name = "";

    foreach ($elements_last_name as $_element) {
      $initials_last_name .= strtoupper(substr($_element, 0, 1));
    }

    $redacteur_subItem = CAppUI::tr('common-editor');
    $redacteur_init_subItem = CAppUI::tr('common-editor (initial)');
    $this->addProperty("$general_section - $redacteur_subItem"        , $user->_shortview);
    $this->addProperty("$general_section - $redacteur_subItem - ".CAppUI::tr('CMediusers-_p_first_name'), $user->_user_first_name);
    $this->addProperty("$general_section - $redacteur_subItem - ".CAppUI::tr('common-name')  , $user->_user_last_name);
    $this->addProperty("$general_section - $redacteur_subItem - ".CAppUI::tr('CMedecin-titre')  , $user->titres);
    $this->addProperty("$general_section - $redacteur_subItem ".CAppUI::tr('common-full'), $user_complete);
    $this->addProperty("$general_section - $redacteur_init_subItem - ".CAppUI::tr('CMediusers-_p_first_name'), $initials_first_name);
    $this->addProperty("$general_section - $redacteur_init_subItem - ".CAppUI::tr('common-name'), $initials_last_name);
    $this->addProperty("$general_section - $redacteur_subItem - ".CAppUI::tr('CMediusers-discipline_id'), $user->_ref_discipline->_view);
    $this->addProperty("$general_section - $redacteur_subItem - ".CAppUI::tr('common-Speciality'), $user->_ref_spec_cpam->_view);
    $this->addProperty("$general_section - $redacteur_subItem - ".CAppUI::tr('CMedecin-adeli'), $user->adeli);
    $this->addBarcode("$general_section - $redacteur_subItem - ".CAppUI::tr('CMediusers-ADELI bar code'), $user->adeli, array("barcode" => array(
      "title" => CAppUI::tr("{$user->_class}-adeli")
    )));
    $this->addProperty("$general_section - $redacteur_subItem - ".CAppUI::tr('CMedecin-rpps'), $user->rpps);
    $this->addBarcode("$general_section - $redacteur_subItem - ".CAppUI::tr('CMedecin-RPPS bar code'), $user->rpps, array("barcode" => array(
      "title" => CAppUI::tr("{$user->_class}-rpps")
    )));
    $signature = $user->loadRefSignature();
    $this->addImageProperty("$general_section - $redacteur_subItem - ".CAppUI::tr('common-Signature'), $signature->_id, array("title" => "$general_section - $redacteur_subItem - ".CAppUI::tr('common-Signature')));

    if (CAppUI::pref("pdf_and_thumbs")) {
      $this->addProperty("$general_section - ".CAppUI::tr('common-page number'), "[$general_section - ".CAppUI::tr('common-page number')."]");
      $this->addProperty("$general_section - ".CAppUI::tr('common-number of page|pl'), "[$general_section - ".CAppUI::tr('common-number of page|pl')."]");
    }
  }

  /**
   * Retrouve un paramètre dans un tableau
   *
   * @param string $name    nom du paramètre
   * @param object $default [optional] valeur par défaut, si non retrouvé
   *
   * @return string
   */
  function getParameter($name, $default = null) {
    return CValue::read($this->parameters, $name, $default);
  }

  /**
   * Construit l'élément html pour les champs, listes de choix et textes libres.
   *
   * @param string $spanClass classe de l'élément
   * @param string $text      contenu de l'élément
   *
   * @return string
   */
  function makeSpan($spanClass, $text) {
    // Escape entities cuz CKEditor does so
    $text = CMbString::htmlEntities($text);

    // Keep backslashed double quotes instead of quotes
    // cuz CKEditor creates double quoted attributes
    return "<span class=\"{$spanClass}\">{$text}</span>";
  }

  /**
   * Ajoute un champ
   *
   * @param string  $field      nom du champ
   * @param string  $value      [optional]
   * @param array   $options    [optional]
   * @param boolean $htmlescape [optional]
   *
   * @return void
   */
  function addProperty($field, $value = null, $options = array(), $htmlescape = true) {
    if ($htmlescape) {
      $value = CMbString::htmlSpecialChars($value);
    }

    $sec = explode(' - ', $field, 3);
    switch (count($sec)) {
      case 3:
        $section  = $sec[0];
        $item     = $sec[1];
        $sub_item = $sec[2];
        break;
      case 2:
        $section  = $sec[0];
        $item     = $sec[1];
        $sub_item = "";
        break;
      default:
        trigger_error("Error while exploding the string", E_USER_ERROR);
        return;
    }

    if (!array_key_exists($section, $this->sections)) {
      $this->sections[$section] = array();
    }
    if ($sub_item !== "" && !array_key_exists($item, $this->sections[$section])) {
      $this->sections[$section][$item] = array();
    }

    $structure = array (
      "field"     => $field,
      "value"     => $value,
      "fieldHTML" => CMbString::htmlEntities("[{$field}]", ENT_QUOTES),
      "valueHTML" => $value,
      "options"   => $options
    );

    if ($sub_item === "") {
      $this->sections[$section][$field] = $structure;
    }
    else {
      $this->sections[$section][$item][$sub_item] = $structure;
    }

    // Barcode
    if (isset($options["barcode"])) {
      if ($sub_item) {
        $_field = &$this->sections[$section][$item][$sub_item];
      }
      else {
        $_field = &$this->sections[$section][$field];
      }

      if ($this->valueMode) {
        $src = $this->getBarcodeDataUri($_field['value'], $options["barcode"]);
      }
      else {
        $src = $_field['fieldHTML'];
      }

      $_field["valueHTML"] = "";

      if ($options["barcode"]["title"]) {
        $_field["valueHTML"] .= $options["barcode"]["title"]."<br />";
      }

      $_field["valueHTML"] .= "<img alt=\"$field\" src=\"$src\" ";

      foreach ($options["barcode"] as $name => $attribute) {
        $_field["valueHTML"] .= " $name=\"$attribute\"";
      }

      $_field["valueHTML"] .= "/>";
      $_field["fieldHTML"] = $_field["valueHTML"];
    }

    // Custom data
    if (isset($options["data"]) && empty($options["image"])) {
      $_field = &$this->sections[$section][$item][$sub_item];
      $data = $options["data"];

      if ($this->valueMode) {
        $view = $_field['value'];
      }
      else {
        $view = $_field['field'];
      }

      $_field["valueHTML"] = "[<span data-data='$data'>$view</span>]";
      $_field["fieldHTML"] = $_field["valueHTML"];
    }

    // Image (from a CFile object)
    if (isset($options["image"])) {
      if ($sub_item === "") {
        $_field = &$this->sections[$section][$field];
      }
      else {
        $_field = &$this->sections[$section][$item][$sub_item];
      }

      if ($this->valueMode) {
        $file = new CFile();
        $src = $_field['value'];
        // Ne charger le fichier que si c'est un id numérique
        if (is_numeric($_field['value'])) {
          $file->load($_field['value']);
          $src = $file->getThumbnailDataURI();
        }
      }
      else {
        if (isset($options["data"])) {
          $src = $options["data"];
        }
        else {
          $src = $_field['fieldHTML'];
        }
      }

      $attribute_names = array("width", "height", "title");

      $attributes = "";
      foreach ($attribute_names as $_name) {
        if (isset($options[$_name])) {
          $attributes .= " $_name=\"{$options[$_name]}\"";
        }
      }

      $_field["valueHTML"] = "<img src=\"".$src."\" $attributes/>";
      $_field["fieldHTML"] = $_field["valueHTML"];
    }
  }

  /**
   * Ajoute un champ de type date
   *
   * @param string $field nom du champ
   * @param string $value [optional]
   *
   * @return void
   */
  function addDateProperty($field, $value = null) {
    $value = $value ? CMbDT::format($value, CAppUI::conf("date")) : "";
    $this->addProperty($field, $value);
  }

  /**
   * Ajoute un champ au format Markdown
   *
   * @param string $field nom du champ
   * @param string $value [optional]
   *
   * @return void
   */
  function addMarkdown($field, $value = null) {
    $value = $value ? CMbString::markdown($value) : "";
    if (preg_match_all("/<p>/", $value) === 1) {
      $value = $value ? preg_replace("/^<p>(.*)<\/p>$/ms", "$1", $value) : "";
    }
    $this->addProperty($field, $value, array("markdown" => true), false);
  }

  /**
   * Ajoute un champ de type date longue
   *
   * @param string  $field     Nom du champ
   * @param string  $value     Valeur du champ
   * @param boolean $lowercase Champ avec des minuscules
   *
   * @return void
   */
  function addLongDateProperty($field, $value, $lowercase = false) {
    $value = $value ? ucfirst(CMbDT::format($value, CAppUI::conf("longdate"))) : "";
    $this->addProperty($field, $lowercase ? CMbString::lower($value) : $value);
  }

  /**
   * Ajoute un champ de type heure
   *
   * @param string $field Nom du champ
   * @param string $value Valeur du champ
   *
   * @return void
   */
  function addTimeProperty($field, $value = null) {
    $value = $value ? CMbDT::format($value, CAppUI::conf("time")) : "";
    $this->addProperty($field, $value);
  }

  /**
   * Ajoute un champ de type durée
   *
   * @param string $field Nom du champ
   * @param string $value Valeur du champ
   *
   * @return void
   */
  function addDurationProperty($field, $value = null) {
    $value = $value ? CMbDT::formatDuration($value) : "";
    $this->addProperty($field, $value);
  }

  /**
   * Ajoute un champ de type date et heure
   *
   * @param string $field Nom du champ
   * @param string $value Valeur du champ
   *
   * @return void
   */
  function addDateTimeProperty($field, $value = null) {
    $value = $value ? CMbDT::format($value, CAppUI::conf("datetime")) : "";
    $this->addProperty($field, $value);
  }

  /**
   * Ajoute un champ de type liste
   *
   * @param string  $field      Nom du champ
   * @param array   $items      Liste de valeurs
   * @param boolean $htmlescape [optional]
   * @param boolean $markdown   [optional]
   *
   * @return void
   */
  function addListProperty($field, $items = null, $htmlescape = true, bool $markdown = false) {
    $this->addProperty($field, $this->makeList($items, $htmlescape, 0, $markdown), null, false);
  }

  /**
   * Ajoute un champ de type image
   *
   * @param string $field   Nom du champ
   * @param int    $file_id Identifiant du fichier
   *
   * @return void
   */
  function addImageProperty($field, $file_id, $options = array()) {
    $options["image"] = 1;
    $this->addProperty($field, $file_id, $options, false);
  }

  /**
   * Génération de la source html pour la liste d'items
   *
   * @param array   $items       liste d'items
   * @param boolean $htmlescape  [optional]
   * @param integer $indentation Niveau d'indentation
   * @param boolean $markdown    [optional]
   *
   * @return string|null
   */
  function makeList($items, $htmlescape = true, $indentation = 0, $markdown = false) {
    if (!$items) {
      return null;
    }

    // Make a list out of a string
    if (!is_array($items)) {
      $items = array($items);
    }

    // Escape content
    if ($htmlescape) {
      $items = array_map("Ox\Core\CMbString::htmlEntities", $items);
    }

    if ($markdown) {
      foreach ($items as $_key => $_item) {
        $value = CMbString::markdown($_item);
        $value = preg_replace("/\n/", "", $value);

        if ($value && preg_match_all("/<p>/", $value) === 1) {
          $value = preg_replace("/^<p>(.*)<\/p>$/ms", "$1", $value);
        }
        $items[$_key] = $value;
      }
    }

    $indent = '';
    if ($indentation) {
      $indent = str_repeat('&emsp;', $indentation);
    }

    // HTML production
    switch ($default = CAppUI::pref("listDefault")) {
      case "ulli":
        $html = "<ul>";
        foreach ($items as $item) {
          $html .= "<li>$item</li>";
        }
        $html.= "</ul>";
        break;

      case "br":
        $html = "";
        $prefix = CAppUI::pref("listBrPrefix");
        foreach ($items as $item) {
          $html .= "<br />$indent$prefix $item";
        }
        break;

      case "inline":
        $separator = CAppUI::pref("listInlineSeparator");
        $html = $indent . implode(" $separator ", $items);
        break;

      default:
        $html = "";
        trigger_error("Default style for list is unknown '$default'", E_USER_WARNING);
        break;
    }

    return $html;
  }

  /**
   * Ajoute un champ de type graphique
   *
   * @param string $field   Champ
   * @param array  $data    Tableau de données
   * @param array  $options Options
   *
   * @return void
   */
  function addGraph($field, $data, $options = array()) {
    $this->graphs[$field] = array(
      "data" => $data,
      "options" => $options,
      "name" => $field
    );

    $this->addProperty($field, $field, null, false);
  }

  /**
   * Ajoute un champ de type code-barre
   *
   * @param string $field   Nom du champ
   * @param string $data    Code barre
   * @param array  $options Options
   *
   * @return void
   */
  function addBarcode($field, $data, $options = array()) {
    $options = array_replace_recursive(
      array(
        "barcode" => array(
          "width"  => 220,
          "height" => 60,
          "class"  => "barcode",
          "title"  => "",
        )
      ),
      $options
    );

    $this->addProperty($field, $data, $options, false);
  }

  /**
   * Ajoute un champ de type liste
   *
   * @param string $name Nom de la liste
   *
   * @return void
   */
  function addList($name) {
    $this->lists[$name] = array (
      "view" => $name,
      "item" => CMbString::htmlEntities("[Liste - {$name}]")
    );
  }

  /**
   * Ajoute une aide à la saisie au templateManager
   *
   * @param string $name Nom de l'aide à la saisie
   * @param string $text Texte de remplacement de l'aide
   *
   * @return void
   */
  function addHelper($name, $text) {
    $this->helpers[$name] = $text;
  }

  function addAdvancedData($name, $data, $value) {
    $options = array(
      "data" => $data
    );

    $this->addProperty($name, $value, $options, false);
  }

  /**
   * Applique les champs variable sur un document
   *
   * @param CCompteRendu|CPack $template TemplateManager sur lequel s'applique le document
   *
   * @return void
   */
  function applyTemplate($template) {
    assert($template instanceof CCompteRendu || $template instanceof CPack);

    if ($template instanceof CCompteRendu) {
      $this->font = $template->font ? CCompteRendu::$fonts[$template->font] : "";
      $this->size = $template->size;

      if (!$this->valueMode) {
        $this->setFields($template->object_class);
      }
    }

    $this->renderDocument($template->_source);
  }

  /**
   * Affiche l'éditeur de texte avec le contenu du document
   *
   * @return void
   */
  function initHTMLArea() {
    CSessionHandler::start();

    // Don't use CValue::setSession which uses $m
    $_SESSION["dPcompteRendu"]["templateManager"] = gzcompress(serialize($this));

    CSessionHandler::writeClose();

    $smarty = new CSmartyDP("modules/dPcompteRendu");
    $smarty->assign("templateManager", $this);
    $smarty->display("init_htmlarea");
  }

  /**
   * Applique les champs variable d'un objet
   *
   * @param string $modeleType classe de l'objet
   *
   * @return void
   */
  function setFields($modeleType) {
    if ($modeleType) {
      $object = new $modeleType;
      /** @var CMbObject $object */
      $object->fillTemplate($this);
    }
  }

  /**
   * Charge les listes de choix pour un utilisateur, ou la fonction et l'établissement de l'utilisateur connecté
   *
   * @param int $user_id         identifiant de l'utilisateur
   * @param int $compte_rendu_id identifiant du compte-rendu
   * @param bool $instance_mode  Flag pour ne prendre que les listes de choix d'instance
   *
   * @return void
   */
  function loadLists($user_id, $compte_rendu_id = 0, $instance_mode = false) {
    $where = array();
    $user = CMediusers::get($user_id);
    $user->loadRefFunction();
    if ($user_id) {
      $where[] = "(
        user_id = '$user->user_id' OR
        function_id = '$user->function_id' OR
        group_id = '{$user->_ref_function->group_id}'
      ) OR (user_id IS NULL AND function_id IS NULL AND group_id IS NULL)";
    }
    elseif ($instance_mode) {
      $where[] = "user_id IS NULL AND function_id IS NULL AND group_id IS NULL";
    }
    else {
      $compte_rendu = new CCompteRendu();
      $compte_rendu->load($compte_rendu_id);
      $where[] = "(
        function_id IN('$user->function_id', '$compte_rendu->function_id') OR
        group_id IN('{$user->_ref_function->group_id}', '$compte_rendu->group_id')
      ) OR (user_id IS NULL AND function_id IS NULL AND group_id IS NULL)";
    }

    $where[] = $user->getDS()->prepare("`compte_rendu_id` IS NULL OR compte_rendu_id = %", $compte_rendu_id);
    $order = "user_id, function_id, group_id, nom ASC";
    $lists = new CListeChoix();
    $this->allLists = $lists->loadList($where, $order);

    foreach ($this->allLists as $list) {
      /** @var CListeChoix $list */
      $this->addList($list->nom);
    }
  }

  /**
   * Charge les listes de choix d'une classe pour un utilisateur, sa fonction et son établissement
   *
   * @param int    $user_id           identifiant de l'utilisateur
   * @param string $modeleType        classe ciblée
   * @param string $other_function_id autre fonction
   *
   * @return void
   */
  function loadHelpers($user_id, $modeleType, $other_function_id = "") {
    $compte_rendu = new CCompteRendu();
    $ds = $compte_rendu->getDS();

    // Chargement de l'utilisateur courant
    $currUser = CMediusers::get($user_id);

    $order = "name";

    // Where user_id
    $whereUser = array();
    $whereUser["user_id"] = $ds->prepare("= %", $user_id);
    $whereUser["class"]   = $ds->prepare("= %", $compte_rendu->_class);

    // Where function_id
    $whereFunc = array();
    $whereFunc["function_id"] = $other_function_id ?
      "IN ($currUser->function_id, $other_function_id)" : $ds->prepare("= %", $currUser->function_id);
    $whereFunc["class"]       = $ds->prepare("= %", $compte_rendu->_class);

    // Where group_id
    $whereGroup = array();
    $group = CGroups::loadCurrent();
    $whereGroup["group_id"] = $ds->prepare("= %", $group->_id);
    $whereGroup["class"]       = $ds->prepare("= %", $compte_rendu->_class);

    // Chargement des aides
    $aide = new CAideSaisie();

    /** @var CAideSaisie $aidesUser */
    $aidesUser   = $aide->loadList($whereUser, $order, null, "aide_id");

    /** @var CAideSaisie $aidesFunc */
    $aidesFunc   = $aide->loadList($whereFunc, $order, null, "aide_id");

    /** @var CAideSaisie $aidesGroup */
    $aidesGroup  = $aide->loadList($whereGroup, $order, null, "aide_id");

    $this->helpers["Aide de l'utilisateur"] = array();
    foreach ($aidesUser as $aideUser) {
      if ($aideUser->depend_value_1 == $modeleType || $aideUser->depend_value_1 == "") {
        $this->helpers["Aide de l'utilisateur"][CMbString::htmlEntities($aideUser->name)] = CMbString::htmlEntities($aideUser->text);
      }
    }
    $this->helpers["Aide de la fonction"] = array();
    foreach ($aidesFunc as $aideFunc) {
      if ($aideFunc->depend_value_1 == $modeleType || $aideFunc->depend_value_1 == "") {
        $this->helpers["Aide de la fonction"][CMbString::htmlEntities($aideFunc->name)] = CMbString::htmlEntities($aideFunc->text);
      }
    }
    $this->helpers["Aide de l'&eacute;tablissement"] = array();
    foreach ($aidesGroup as $aideGroup) {
      if ($aideGroup->depend_value_1 == $modeleType || $aideGroup->depend_value_1 == "") {
        $this->helpers["Aide de l'&eacute;tablissement"][CMbString::htmlEntities($aideGroup->name)] =
          CMbString::htmlEntities($aideGroup->text);
      }
    }
  }

  /**
   * Get the data URI of a barcode
   *
   * @param string $code    Code
   * @param array  $options Options
   *
   * @return null|string
   */
  function getBarcodeDataUri($code, $options) {
    if (!$code) {
      return null;
    }

    $with_text = CMbArray::get($options, "with_text", true);

    $size = "{$options['width']}x{$options['width']}";

    if (isset(self::$barcodeCache[$code][$size])) {
      return self::$barcodeCache[$code][$size];
    }

    CMb128BObject::init();
    $bc_options = ($with_text ? (BCD_DEFAULT_STYLE | BCS_DRAW_TEXT) : BCD_DEFAULT_STYLE) & ~BCS_BORDER;
    $barcode = new CMb128BObject($options["width"] * 2, $options["height"] * 2, $bc_options, $code);

    $barcode->SetFont(7);
    $barcode->DrawObject(2);

    ob_start();
    $barcode->FlushObject();
    $image = ob_get_contents();
    ob_end_clean();

    $barcode->DestroyObject();

    $image = "data:image/png;base64,".urlencode(base64_encode($image));

    return self::$barcodeCache[$code][$size] = $image;
  }

  /**
   * Get the regex to replace data
   *
   * @param string $data     Data key
   * @param string $form_ctx Context for forms fields
   *
   * @return string
   */
  protected function getDataRegex($data, $form_ctx = '') {
    $data_re  = preg_quote($data, "/");
    $form_ctx = preg_quote(CMbString::htmlEntities($form_ctx), "/");

    return '/(\[<span data-data=["\']'.$data_re.'["\']>' . $form_ctx .'[^<]+<\/span>\])/ms';
  }

  /**
   * Applique les champs variables sur une source html
   *
   * @param string $_source source html
   *
   * @return void
   */
  function renderDocument($_source) {
    $fields = array();
    $values = array();

    $fields_regex = array();
    $values_regex = array();

    foreach ($this->sections as $type => $properties) {
      foreach ($properties as $key => $property) {
        $structure = strpos($key, " - ") === false ? $property : array($property);

        foreach ($structure as $_property) {
          if ($_property["valueHTML"] && isset($_property["options"]["barcode"])) {
            $image    = $this->getBarcodeDataUri($_property["value"], $_property["options"]["barcode"]);
            $fields[] = "src=\"[{$_property['field']}]\"";
            $values[] = "src=\"$image\"";
          }
          else if (isset($_property["options"]["data"]) && empty($_property["options"]["image"])) {
            $data           = $_property["options"]["data"];
            $form_ctx = '';
            if (strpos($data, 'CExObject') !== false) {
              $form_ctx = $type;
            }

            $fields_regex[] = $this->getDataRegex($data, $form_ctx);
            $values_regex[] = $_property["value"];
          }
          else if ($_property["valueHTML"] && isset($_property["options"]["image"])) {
            $src_src = isset($_property["options"]["data"]) ? $_property["options"]["data"] : "[{$_property['field']}]";
            $fields[] = "src=\"$src_src\"";

            if (is_numeric($_property['value'])) {
              $file = new CFile();
              $file->load($_property['value']);

              $src      = $file->getDataURI();
              $values[] = "src=\"$src\"";
            }
            else {
              $values[] = "src=\"" . $_property['value'] . "\"";
            }
          }
          else {
            $_property["fieldHTML"] = preg_replace("/'/", "&#039;", $_property["fieldHTML"]);

            $field = $_property["fieldHTML"];
            $value = $_property["valueHTML"];

            // Le markdown génère déjà des <br />, n'en ajoutons pas plus qu'il n'en faut...
            if (!isset($_property["options"]["markdown"])) {
              $value = nl2br($value);
            }

            $fields[] = $field;
            $values[] = $value;
          }
        }
      }
    }

    if (count($fields_regex)) {
      $_source = preg_replace($fields_regex, $values_regex, $_source);
    }

    if (count($fields)) {
      $_source = str_ireplace($fields, $values, $_source);
    }

    if (count($fields_regex) || count($fields)) {
      $this->document = $_source;
    }
  }

  /**
   * Obtention des listes utilisées dans le document
   *
   * @param CListeChoix[] $lists Listes de choix
   *
   * @return CListeChoix[]
   */
  function getUsedLists($lists) {
    $this->usedLists = array();

    // Les listes de choix peuvent contenir des caractères qui ne sont pas dans la table iso-8859-1
    // On change donc temporairement en windows-1252
    $actual_encoding = CApp::$encoding;
    CApp::$encoding = "windows-1252";

    foreach ($lists as $value) {
      $nom = CMbString::htmlEntities(stripslashes("[Liste - $value->nom]"), ENT_QUOTES);
      $pos = strpos($this->document, $nom);
      if ($pos !== false) {
        $this->usedLists[$pos] = $value;
      }
    }

    CApp::$encoding = $actual_encoding;

    ksort($this->usedLists);
    return $this->usedLists;
  }

  /**
   * Vérification s'il s'agit d'un courrier
   *
   * @return bool
   */
  function isCourrier() {
    return $this->isCourrier = strpos($this->document, "[Courrier -") !== false;
  }

  function makeFields($prefix, $with_separator = true, $check_modele = true, $with_bracket = true) {
    if ($check_modele && $this->isModele) {
      return true;
    }

    $needle = CMbString::htmlEntities(($with_bracket ? "[" : "") . $prefix . ($with_separator ? " - " : ""));
    return strpos($this->document, $needle) !== false;
  }
}
