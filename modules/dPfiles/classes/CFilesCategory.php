<?php
/**
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Files;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbObject;
use Ox\Core\Module\CModule;
use Ox\Core\CStoredObject;
use Ox\Interop\Dmp\CDMPTools;
use Ox\Interop\Eai\CInteropReceiver;
use Ox\Interop\Sas\CSAS;
use Ox\Interop\Sisra\CSisraTools;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Sante400\CIdSante400;

/**
 * The CFilesCategory class
 */
class CFilesCategory extends CMbObject
{
    public const RESOURCE_NAME = 'files_category';

    // Static fields
    private static $fields_etiq = [
        "CODE BARRE COURT",
        "LIBELLE",
    ];

    // DB Table key
    public $file_category_id;

    // DB Fields
    public $nom;
    public $nom_court;
    public $class;
    public $group_id;
    public $importance;
    public $send_auto;
    public $eligible_file_view;
    public $medicale;
    public $color;
    public $type_doc_dmp;
    public $type_doc_sisra;

    // Form fields
    public $_count_documents;
    public $_count_files;
    public $_count_doc_items;
    public $_tag_association_code;

    public $_count_unsent_documents;
    public $_count_unsent_files;
    public $_count_unsent_doc_items;
    public $_count_receivers;

    public $_nb_files_read;

    // References
    /** @var CGroups */
    public $_ref_group;
    /** @var CInteropReceiver[] */
    public $_ref_receivers;
    /** @var CIdSante400 */
    public $_ref_idex_association_code;

    /**
     * @see parent::getSpec()
     */
    function getSpec()
    {
        $spec             = parent::getSpec();
        $spec->table      = 'files_category';
        $spec->key        = 'file_category_id';
        $spec->merge_type = 'fast';

        return $spec;
    }

    /**
     * @see parent::getProps()
     */
    function getProps()
    {
        $props                       = parent::getProps();
        $props["nom"]                = "str notNull seekable fieldset|default";
        $props["nom_court"]          = "str fieldset|default";
        $props["class"]              = "str fieldset|default";
        $props["group_id"]           = "ref class|CGroups back|files_categories fieldset|default";
        $props["importance"]         = "enum list|normal|high default|normal fieldset|extra";
        $props["send_auto"]          = "bool fieldset|extra";
        $props["eligible_file_view"] = "bool notNull default|0 fieldset|extra";
        $props["medicale"]           = "bool default|0 fieldset|extra";
        $props["color"]              = "color fieldset|default";
        $type_doc_dmp                = "";
        if (CModule::getActive("dmp")) {
            $type_doc_dmp = CDMPTools::getTypesDoc();
        }
        $props["type_doc_dmp"] = (empty($type_doc_dmp) ? "str" : "enum list|$type_doc_dmp");
        $sisra_types           = "";
        if (CModule::getActive("sisra")) {
            $sisra_types = CSisraTools::getSisraTypeDocument();
            $sisra_types = implode("|", $sisra_types);
        }
        $props["type_doc_sisra"] = (empty($sisra_types) ? "str" : "enum list|$sisra_types");

        return $props;
    }

    /**
     * @see parent::updateFormFields()
     */
    function updateFormFields()
    {
        parent::updateFormFields();
        $this->_view = $this->nom;
    }

    /**
     * @see parent::countDocItems()
     */
    function countDocItems($permType = null)
    {
        $this->_count_documents = $this->countBackRefs("categorized_documents");
        $this->_count_files     = $this->countBackRefs("categorized_files");
        $this->_count_doc_items = $this->_count_documents + $this->_count_files;
    }

    /**
     * Count unsent document items
     *
     * @return void
     */
    function countUnsentDocItems()
    {
        $where["file_category_id"] = "= '$this->_id'";
        $where["etat_envoi"]       = "!= 'oui'";
        $where["object_id"]        = "IS NOT NULL";

        $file                      = new CFile();
        $this->_count_unsent_files = $file->countList($where);

        $document                      = new CCompteRendu();
        $this->_count_unsent_documents = $document->countList($where);
        $this->_count_unsent_doc_items = $this->_count_unsent_documents + $this->_count_unsent_files;
    }

    /**
     * @param null $user_id
     *
     * @return null
     */
    function countReadFiles($user_id = null)
    {
        if (!$this->eligible_file_view) {
            return $this->_nb_files_read = null;
        }
        $user_id = $user_id ? $user_id : CMediusers::get()->_id;
        $where   = [
            "file_category_id"        => " = '$this->_id' ",
            "files_user_view.user_id" => " = '$user_id' ",
        ];
        $ljoin   = [
            "files_mediboard" => "files_mediboard.file_id = files_user_view.file_id",
        ];
        $file    = new CFileUserView();

        return $this->_nb_files_read = $file->countList($where, null, $ljoin);
    }

    /**
     * Load categories by class
     *
     * @return self[]
     */
    static function loadListByClass($unset_no_class = true)
    {
        $category = new self();

        $where = [
            "group_id IS NULL OR group_id = '" . CGroups::loadCurrent()->_id . "'",
        ];

        /** @var self[] $categories */
        $categories = $category->loadListWithPerms(PERM_READ, $where, "nom");

        $catsByClass = [];
        foreach ($categories as $_category) {
            $catsByClass[$_category->class][$_category->_id] = $_category;
        }
        if ($unset_no_class) {
            unset($catsByClass[""]);
        }

        return $catsByClass;
    }

    /**
     * Get the list of categories for a specific class
     *
     * @param string $class Class name
     *
     * @return self[]
     */
    static function listCatClass($class = null)
    {
        $instance = new self();
        $where    = [
            $instance->getDS()->prepare("class IS NULL OR class = %", $class),
            $instance->getDS()->prepare("group_id IS NULL OR group_id = %", CGroups::loadCurrent()->_id),
        ];

        return $instance->loadListWithPerms(PERM_READ, $where, "nom");
    }

    /**
     * Get the importants categories
     *
     * @return self[]
     */
    static function getImportantCategories()
    {
        $cat             = new self;
        $cat->importance = "high";

        return $cat->loadMatchingList();
    }

    /**
     * Get the medical categories
     *
     * @return self[]
     */
    static function getMedicalCategories()
    {
        $cat           = new self;
        $cat->medicale = 1;

        return $cat->loadMatchingList();
    }

    /**
     * Load the group linked to the category
     *
     * @return CGroups
     * @throws Exception
     */
    function loadRefGroup()
    {
        return $this->_ref_group = $this->loadFwdRef("group_id", true);
    }

    /**
     * Count receivers
     *
     * @param array $where Where clause
     *
     * @return int
     * @throws Exception
     */
    function countRelatedReceivers($where = [])
    {
        return $this->_count_receivers = $this->countBackRefs("related_receivers", $where);
    }

    /**
     * Load receivers
     *
     * @param array $where Where clause
     *
     * @return CInteropReceiver[]|CStoredObject[]
     * @throws Exception
     */
    function loadRefRelatedReceivers($where = [])
    {
        if ($this->_ref_receivers) {
            return $this->_ref_receivers;
        }

        return $this->_ref_receivers = $this->loadBackRefs(
            "related_receivers",
            null,
            null,
            null,
            null,
            null,
            null,
            $where
        );
    }

    public function loadIdexAssociationCode($group_id = null)
    {
        if (!$group_id) {
            $group_id = CGroups::loadCurrent()->_id;
        }

        return $this->_ref_idex_association_code = CIdSante400::getMatchFor($this, CSAS::getFilesCategoryAssociationTag($group_id));
    }

    /**
     * @see parent::completeLabelFields()
     */
    function completeLabelFields(&$fields, $params)
    {
        $fields["CODE BARRE COURT"] = "@BARCODE_" . (CModule::getActive("barcodeDoc") && CAppUI::gconf(
                "barcodeDoc general module_actif"
            ) ?
                CAppUI::gconf("barcodeDoc general prefix_CAT") :
                "") . $this->nom_court . "@";
        $fields["LIBELLE"]          = $this->nom;
    }

    /**
     * Retourne la cat�gorie par d�faut pour un utilisateur
     * Recherche au niveau de l'utilisateur puis au niveau de la fonction
     *
     * @param string $user_id Identifiant de l'utilisateur
     *
     * @return CFilesCategory
     * @throws Exception
     */
    static function getDefautCat($user_id = null, $object_class = "")
    {
        $user = CMediusers::get($user_id);

        $function = $user->loadRefFunction();

        $files_cat        = new self();
        $file_default_cat = new CFilesCatDefault();

        $owners = [$user, $function];
        foreach ($owners as $_owner) {
            $where = [
                "owner_class" => "= '$_owner->_class'",
                "owner_id"    => "= '$_owner->_id'",
            ];

            if ($object_class) {
                $where["object_class"] = "= '$object_class'";
            }

            if ($file_default_cat->loadObject($where)) {
                return $files_cat->load($file_default_cat->file_category_id);
            }

            if ($object_class) {
                $where["object_class"] = "IS NULL";
                if ($file_default_cat->loadObject($where)) {
                    return $files_cat->load($file_default_cat->file_category_id);
                }
            }
        }

        return $files_cat;
    }

    /**
     * Returns all categories of an establishment (for configs)
     *
     * @return CStoredObject[]|CFilesCategory[]
     * @throws Exception
     */
    public static function getFileCategories()
    {
        $file_category = new CFilesCategory();
        $ds            = $file_category->getDS();

        return $file_category->loadList($ds->prepare("group_id is null or group_id = ?", CGroups::get()->_id), "nom");
    }

    /**
     * Getter to fields_etiq variale
     *
     * @return array
     * @throws Exception
     */
    public static function getFieldsEtiq()
    {
        return self::$fields_etiq;
    }
}
