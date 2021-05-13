<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core;

use Exception;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\Composer\CComposerScript;
use Ox\Core\FieldSpecs\CHtmlSpec;
use Ox\Core\FieldSpecs\CRefSpec;
use Ox\Core\FieldSpecs\CStrSpec;
use Ox\Core\FieldSpecs\CTextSpec;
use Ox\Core\Handlers\Traits\SubjectTrait;
use Ox\Core\Kernel\Routing\CRouter;
use Ox\Core\Module\CModule;
use Ox\Mediboard\System\Forms\CExObject;
use ReflectionClass;
use ReflectionException;
use Throwable;

/**
 * - Metamodel: properties, class, validation
 */
abstract class CModelObject implements IShortNameAutoloadable {
  /** @var string */
  public const RESOURCE_NAME = 'undefined';

  /** @var string */
  public const FIELDSET_DEFAULT = 'default';

  /** @var string */
  public const FIELDSET_EXTRA = 'extra';

  /** @var array */
  public const RELATIONS_DEFAULT = [];

  use SubjectTrait;

  /** @var string The object's class name */
  public $_class;

  /** @var integer The object ID */
  public $_id;

  /** @var string The object GUID ("_class-_id") */
  public $_guid;

  /** @var string The universal object view */
  public $_view = '';

  /**@var string The universal object shortview */
  public $_shortview = '';

  /** @var CMbObjectSpec The class specification */
  public $_spec;

  /** @var CMbFieldSpec[] Properties specifications as objects */
  public $_specs = array();

  /** @var array Properties specifications as string */
  public $_props = array();

  /** @var CMbBackSpec[] Back reference specification as objects */
  public $_backSpecs = array();

  /** @var array Back reference specification as string */
  public $_backProps = array();

  /** @var array Object configs */
  public $_configs = array();

  /** @var array General purpose data store */
  protected $_data_store = array();

  /** @var CMbObjectSpec[] */
  static $spec = array();

  /** @var string[] */
  static $props = array();

  /** @var CMbFieldSpec[] */
  static $specs = array();

  /** @var string[] */
  static $backProps = array();

  /** @var CMbBackSpec[] */
  static $backSpecs = array();

  /** @var array */
  static $module_name = array();

  private static $sortField = null;

  /** @var CModule Parent module */
  public $_ref_module;

  /** @var bool true if object is locked */
  public $_locked;

  /**
   * Tell wether class exists
   *
   * @param string $class String name
   *
   * @return bool
   */
  static function classExists($class) {
    $cache = new Cache(__METHOD__, func_get_args(), Cache::INNER);

    if ($cache->exists()) {
      return $cache->get();
    }

    $value = class_exists($class);

    return $cache->put($value);
  }

  /**
   * Make an instance of a ModelObject
   *
   * @param string $class Object class to get an instance of
   *
   * @return null|self
   */
  static function getInstance($class) {
    $object = CExObject::getValidObject($class);

    if (!$object) {
      // Non existing class
      if (!self::classExists($class)) {
        return null;
      }

      // Check class is instanciable
      try {
        $obj = new $class();
      }
      catch (Throwable $e) {
        return null;
      }

      return $obj;
    }

    return $object;
  }

  /**
   * Construct
   *
   * @return void
   * @throws Exception
   */
  function __construct() {
    $this->initialize();
  }

  /**
   * Pre-serialize magic method
   *
   * @return array Property keys to be serialized
   */
  function __sleep() {
    $vars = get_object_vars($this);
    unset($vars["_class"]);
    unset($vars["_spec"]);
    unset($vars["_props"]);
    unset($vars["_specs"]);
    unset($vars["_backProps"]);
    unset($vars["_backSpecs"]);
    unset($vars["_ref_module"]);
    unset($vars["_data_store"]);
    // Non strict value Removing would purge empty arrays
    CMbArray::removeValue(null, $vars, true);
    CMbArray::removeValue("", $vars, true);

    return array_keys($vars);
  }

  /**
   * Post-unserialize magic method
   *
   * @return void
   * @throws Exception
   */
  function __wakeup() {
    $this->initialize();
  }

  /**
   * To string magic method
   *
   * @return string
   */
  function __toString() {
    return strip_tags($this->_view);
  }

  /**
   * Initialization factorisation for construction and unserialization
   *
   * @return void
   * @throws Exception
   */
  function initialize() {
    $class_name = get_class($this);
    $class_map  = CClassMap::getInstance();
    $map        = $class_map->getClassMap($class_name);
    $class      = $map->short_name;

    $in_cache = isset(self::$spec[$class]);

    if (!$in_cache) {
      $spec = $this->getSpec();
      $spec->init();
      self::$spec[$class]        = $spec;
      self::$module_name[$class] = self::getModuleName($map->file);
    }

    $this->_class = $class;
    $this->_spec  =& self::$spec[$class];

    if ($key = $this->_spec->key) {
      $this->_id =& $this->$key;
    }

    if (!$in_cache) {
      self::$props[$class] = $this->getProps();
      $this->_props        =& self::$props[$class];

      self::$specs[$class] = $this->getSpecs();
      $this->_specs        =& self::$specs[$class];

      self::$backProps[$class] = $this->getBackProps();
      $this->_backProps        =& self::$backProps[$class];

      // Not prepared since it depends on many other classes
      // Has to be done as a second pass
      self::$backSpecs[$class] = array();
    }

    $this->_props     =& self::$props[$class];
    $this->_specs     =& self::$specs[$class];
    $this->_backProps =& self::$backProps[$class];
    $this->_backSpecs =& self::$backSpecs[$class];

    $this->_guid = $this->_id ? "$this->_class-$this->_id" : "$this->_class-none";

    // @todo Move up to CStoredObject
    $this->loadRefModule(self::$module_name[$class]);
  }

  /**
   * Get the module name corresponding to given path
   *
   * @param string $path Path name
   *
   * @return string Module name
   */
  public static function getModuleName($path) {
    // Handle linux and windows paths
    if (preg_match('@(/|\\\)modules(\\\|/)(?P<module_name>[^\\\|/]+)(\\\|/)@', $path, $matches) && isset($matches['module_name'])) {
      return $matches['module_name'];
    }

    if ("classes" === basename($path = dirname($path))) {
      $path = dirname($path);
    }

    return basename($path);
  }

  /**
   * @param null $prefix
   *
   * @return array
   * @throws ReflectionException
   * @example FOO > FOO_BAR, FOO_TOTO
   */
  public static function getConstants($prefix = null): array {
    // static cache
    $cache = new Cache(static::class, __FUNCTION__ . '_' . $prefix, Cache::INNER);

    if ($cache->exists()) {
      return $cache->get();
    }

    $constants = (new ReflectionClass(static::class))->getConstants();
    if ($prefix) {
      foreach ($constants as $const_name => $const_value) {
        if (strpos($const_name, $prefix . '_') !== 0) {
          unset($constants[$const_name]);
        }
      }
    }

    return $cache->put($constants);
  }

  /**
   * Initialize object specification
   *
   * @return CMbObjectSpec the spec
   */
  function getSpec() {
    return new CMbObjectSpec();
  }

  /**
   * @return string|null
   */
  function getPrimaryKey(): ?string {
    return $this->getSpec()->key;
  }

  /**
   * Get properties specifications as strings
   *
   * @return array
   */
  function getProps() {
    $props               = array();
    $props["_shortview"] = "str";
    $props["_view"]      = "str";

    return $props;
  }

  /**
   * Get backward reference specifications
   *
   * @return array Array of form "collection-name" => "class join-field"
   * @throws Exception
   */
  final public function getBackProps(): array {
    // todo remove when ref initialize (construct CModelObject call getBackProps)
    if (CComposerScript::$is_running) {
      return [];
    }

    $current_class = static::class;

    // TODO Replace with Cache::INNER_OUTER
    $cache = new Cache(__METHOD__, $current_class, Cache::INNER);
    if ($cache->exists()) {
      return $cache->get();
    }

    return $cache->put($this->getBackPropsFromClassRef());
  }

  /**
   * @param string $backname
   *
   * @return mixed
   * @throws CMbException
   */
  public function getBackProp($backname) {
    $backprops = $this->getBackProps();
    if (!array_key_exists($backname, $backprops)) {
      throw new CMbException("Invalid backname '{$backname}' in class '" . static::class . "'");
    }

    return $backprops[$backname];
  }

  /**
   * Get backward reference specifications
   *
   * @param bool $ignore_undefined
   *
   * @return array Array of form "collection-name" => "class join-field"
   * @throws Exception
   */
  private function getBackPropsFromClassRef(): array {
    $current_class = static::class;

    // We have to concat layers backprops
    $layers = class_parents($current_class);

    // Because children can overrides backprops
    $layers = array_reverse($layers);

    // Add current class
    $layers[] = $current_class;

    // Init
    $classmap  = CClassMap::getInstance();
    $backprops = [];

    foreach ($layers as $class_name) {
      $refs = $classmap->getClassRef($class_name);
      foreach ($refs->back as $back_name => $backpros) {

        // CExObject, ModelObjectAbstract ...
        if (strpos($back_name, 'undefined_') === 0) {
          continue;
        }

        [$back_class, $backfield] = explode(' ', $backpros);

        $backprops[$back_name] = $back_class . ' ' . $backfield;
      }
    }

    // keep only one combinaison (class + field)
    return array_unique($backprops);
  }

  /**
   * Get the backrefs to export when using CMbObjecExport
   *
   * @return array
   * @todo   Should move back to CStoredObject
   */
  function getExportedBackRefs() {
    return array();
  }

  /**
   * Convert string back specifications to objet specifications
   *
   * @param string $backName The name of the back reference
   *
   * @return CMbBackSpec The back reference specification, null if undefined
   */
  function makeBackSpec($backName) {
    if (array_key_exists($backName, $this->_backSpecs)) {
      return $this->_backSpecs[$backName];
    }

    if ($backSpec = CMbBackSpec::make($this->_class, $backName, $this->_backProps[$backName])) {
      return $this->_backSpecs[$backName] = $backSpec;
    }

    return null;
  }

  /**
   * Makes all the back specs
   *
   * @return void
   */
  function makeAllBackSpecs() {
    foreach ($this->_backProps as $backName => $backProp) {
      $this->makeBackSpec($backName);
    }
  }

  /**
   * Converts properties string specifications to object specifications
   * Optimized version
   *
   * @return CMbFieldSpec[]
   * @throws Exception
   */
  function getSpecs() {
    $specs = array();
    foreach ($this->_props as $name => $prop) {
      $specs[$name] = CMbFieldSpecFact::getSpec($this, $name, $prop);
    }

    return $specs;
  }

  /**
   * @param array|string $fieldsets
   *
   * @return CMbFieldSpec[]
   */
  public function getFieldsSpecsByFieldsets($fieldsets) {
    $fieldsets = is_array($fieldsets) ? $fieldsets : [$fieldsets];

    // static cache todo outer cache ?
    $cache = new Cache(static::class, __FUNCTION__ . '_' . implode('_', $fieldsets), Cache::INNER);

    if ($cache->exists()) {
      return $cache->get();
    }

    $fileds = [];

    foreach ($fieldsets as $fieldset) {
      foreach ($this->_specs as $field_name => $spec) {
        if (isset($spec->fieldset) && $spec->fieldset === $fieldset) {
          $fileds[$field_name] = $spec;
        }
      }
    }

    return $cache->put($fileds);
  }

    /**
     * @param array|null $fieldsets
     *
     * @return array
     */
    public function getSchema(?array $fieldsets)
    {
        if (empty($fieldsets)) {
            $fieldsets[] = self::FIELDSET_DEFAULT;
        }

        /** @var CModelObject $instance */
        $fieldsspecs = $this->getFieldsSpecsByFieldsets($fieldsets);

        $datas = [];
        foreach ($fieldsspecs as $spec) {
            $spec_transformed = $spec->transform();
            // Description
            $spec_transformed['libelle']     = utf8_encode(CAppUI::tr($spec->className . '-' . $spec->fieldName));
            $spec_transformed['label']       = utf8_encode(
                CAppUI::tr($spec->className . '-' . $spec->fieldName . '-court')
            );
            $spec_transformed['description'] = utf8_encode(
                CAppUI::tr($spec->className . '-' . $spec->fieldName . '-desc')
            );

            $datas[] = $spec_transformed;
        }

        return $datas;
    }

  /**
   * Decode all string fields (str, text, html)
   *
   * @return void
   */
  function decodeUtfStrings() {
    foreach ($this->_specs as $name => $spec) {
      if (in_array(get_class($spec), array(CStrSpec::class, CHtmlSpec::class, CTextSpec::class))) {
        if (null !== $this->$name) {
          $this->$name = utf8_decode($this->$name);
        }
      }
    }
  }

  /**
   * Set default values to properties
   *
   * @return void
   * @throws Exception
   */
  function valueDefaults() {
    $specs = $this->getSpecs();

    $fields = $this->getPlainFields();
    unset($fields[$this->_spec->key]);
    unset($fields["object_id"]);
    foreach ($fields as $_name => $_value) {
      $this->$_name = $specs[$_name]->default;
    }
  }

  /**
   * Check a property against its specification
   *
   * @param string $name Name of the property
   *
   * @return string Store-like error message
   */
  function checkProperty($name) {
    $spec = $this->_specs[$name];

    return $spec->checkPropertyValue($this);
  }

  /**
   * Check confidential
   *
   * @param array $specs Specs
   *
   * @return void
   */
  function checkConfidential($specs = null) {
    static $confidential = null;

    if ($confidential === null) {
      $confidential = CAppUI::conf("hide_confidential") == 1;
    }

    if (!$confidential) {
      return;
    }

    if ($specs == null) {
      $specs = $this->_specs;
    }

    foreach ($specs as $name => $_spec) {
      $value =& $this->$name;
      if ($value !== null && $this->_specs[$name]) {
        $this->_specs[$name]->checkConfidential($this);
      }
    }
  }

  /**
   * Get object properties, i.e. having specs
   *
   * @param bool $nonEmpty Filter non empty values
   *
   * @return array Associative array
   */
  function getProperties($nonEmpty = false, bool $only_showable = false) {
    $values = array();

    foreach ($this->_specs as $key => $_spec) {
      $value = $this->$key;
      if ( (!$nonEmpty || ($value !== null && $value !== "")) && (!$only_showable || $_spec->show !== '0') ) {
        $values[$key] = $value;
      }
    }

    return $values;
  }

  /**
   * Returns the field's formatted value
   *
   * @param string $field   Field name
   * @param array  $options Format options
   *
   * @return string The field's formatted value
   */
  function getFormattedValue($field, $options = array()) {
    return $this->_specs[$field]->getValue($this, $options);
  }

  /**
   * Returns the field's html value
   *
   * @param string $field   Field name
   * @param array  $options Format options
   *
   * @return string The field's formatted value
   */
  function getHtmlValue($field, $options = array()) {
    return $this->_specs[$field]->getHtmlValue($this, $options);
  }

  /**
   * Returns the field's HTML label element
   *
   * @param string $field   Field name
   * @param array  $options Format options
   *
   * @return string The field's formatted value
   */
  function getLabelElement($field, $options = array()) {
    return $this->_specs[$field]->getLabelElement($this, $options);
  }

  /**
   * Returns the field's main locale
   *
   * @param string $field Field name
   *
   * @return string The locale
   */
  function getLocale($field) {
    return CAppUI::tr("$this->_class-$field");
  }

  /**
   * Trigger a warning with appropriate locale and variatic i18n parameters
   *
   * @param string $suffix Locale suffix
   *
   * @return void
   */
  static function warning($suffix/*, ... */) {
    $args = func_get_args();
    unset($args[0]);
    $backtrace = debug_backtrace();
    $class     = $backtrace[1]["class"];
    $message   = CAppUI::tr("$class-warning-$suffix", $args);
    trigger_error($message, E_USER_WARNING);
  }

  /**
   * Trigger an error with appropriate locale and variatic i18n parameters
   *
   * @param string $suffix Locale suffix
   *
   * @return void
   */
  static function error($suffix/*, ... */) {
    $args = func_get_args();
    unset($args[0]);
    $backtrace = debug_backtrace();
    $class     = $backtrace[1]["class"];
    $message   = CAppUI::tr("$class-warning-$suffix", $args);
    trigger_error($message, E_USER_ERROR);
  }

  /**
   * Bind an object with an array
   * @deprecated Do not use this method with user data
   *
   * @param array $hash  associative array of values to match with
   * @param bool  $strip true to strip slashes
   *
   * @return bool
   */
  function bind($hash, $strip = true) {
    bindHashToObject($strip ? stripslashes_deep($hash) : $hash, $this);

    return true;
  }

  /**
   * Update form (derived) fields from plain fields
   *
   * @return void
   */
  function updateFormFields() {
    $this->_guid      = "$this->_class-$this->_id";
    $this->_view      = CAppUI::tr($this->_class) . " " . $this->_id;
    $this->_shortview = "#$this->_id";
  }

  /**
   * Get DB fields and there values
   *
   * @return array Associative array
   */
  function getPlainFields() {
    $result = array();
    $vars   = get_object_vars($this);
    foreach ($vars as $name => $value) {
      if ($name[0] !== '_') {
        $result[$name] = $value;
      }
    }

    return $result;
  }

  /**
   * Get the exportables DB fields and their values
   *
   * @param bool $trads Put trads instead of values
   *
   * @return array
   */
  function getExportableFields($trads = false) {
    $fields = $this->getPlainFields();

    if ($trads) {
      foreach ($fields as $_field => &$_value) {
        $_value = CAppUI::tr($this->_class . '-' . $_field);
      }
    }

    return $fields;
  }

  /**
   * Update the plain fields from the form fields
   *
   * @return void
   */
  function updatePlainFields() {
    $specs  = $this->_specs;
    $fields = $this->getPlainFields();

    foreach ($fields as $name => $value) {
      if ($value !== null) {
        $this->$name = $specs[$name]->filter($value);
      }
    }
  }

  /**
   * Merges the fields of an array of objects to $this
   *
   * @param CModelObject[] $objects       An array of CModelObject
   * @param bool           $getFirstValue Get first value ?
   *
   * @return void
   */
  function mergePlainFields($objects, $getFirstValue = false) {
    $fields = $this->getPlainFields();
    $diffs  = $fields;
    foreach ($diffs as &$diff) {
      $diff = false;
    }

    foreach ($objects as &$object) {
      foreach ($fields as $name => $value) {
        // Assign the value of the first object
        if ($getFirstValue) {
          if ($this->$name === null) {
            $this->$name = $object->$name;
          }
          continue;
        }

        // Try to assign the first not null value among objects
        if ($this->$name === null && !$diffs[$name]) {
          $this->$name = $object->$name;
          continue;
        }

        // In case we have different values, rather nullify
        if ($this->$name != $object->$name) {
          $diffs[$name] = true;
          $this->$name  = null;
        }
      }
    }
  }

  /**
   * Nullify object fields that are empty strings
   *
   * @return void
   * @todo   Rename to plainFields
   */
  function nullifyEmptyFields() {
    foreach ($this->getPlainFields() as $name => $value) {
      if ($value === "") {
        $this->$name = null;
      }
    }
  }

  /**
   * Nullify object all properties
   *
   * @return void
   */
  function nullifyProperties() {
    foreach ($this->getProperties() as $name => $value) {
      $this->$name = null;
    }
  }

  /**
   * Assign multiple properties from values array()
   *
   * @param array $values name-value pairs
   *
   * @return void
   */
  function setProperties($values) {
    foreach ($values as $_name => $_value) {
      $this->$_name = $_value;
    }
  }

  /**
   * Extends object properties with target object (of the same class) properties
   *
   * @param CModelObject $object Object to extend with
   * @param bool         $gently Gently preserve existing non-empty values
   *
   * @return void
   */
  function extendsWith(CModelObject $object, $gently = false) {
    if ($this->_class !== $object->_class) {
      trigger_error(
        printf("Target object has not the same class (%s) as this (%s)", $object->_class, $this->_class),
        E_USER_WARNING
      );

      return;
    }

    foreach ($object->getProperties() as $name => $value) {
      if ($value !== null && $value != "") {
        if (!$gently || $this->$name === null || $this->$name === "") {
          $this->$name = $value;
        }
      }
    }
  }

  /**
   * Clone object
   *
   * @param CModelObject $object Object to clone
   *
   * @return void
   */
  function cloneFrom(CModelObject $object) {
    $this->extendsWith($object);
    $this->_id = null;
  }

  /**
   * Get CSV values for object, i.e. db fields, references excepted
   *
   * @return array Associative array of values
   */
  function getCSVFields() {
    $fields = array();
    foreach ($this->getPlainFields() as $name => $value) {
      if (!$this->_specs[$name] instanceof CRefSpec) {
        $fields[$name] = $value;
      }
    }

    return $fields;
  }

  /**
   * Comparison callback for natural sorting
   *
   * @param CModelObject $a Object having a self::$sortField property
   * @param CModelObject $b Object having a self::$sortField property
   *
   * @return int Comparison result
   */
  protected static function _cmpFieldNatural($a, $b) {
    $sort_field = self::$sortField;

    return strnatcasecmp($a->$sort_field, $b->$sort_field);
  }

  /**
   * Diacritic insensitive comparison callback for natural sorting
   *
   * @param CModelObject $a Object having a self::$sortField property
   * @param CModelObject $b Object having a self::$sortField property
   *
   * @return int Comparison result
   */
  protected static function _cmpFieldNaturalAccentsDiacritics($a, $b) {
    $sort_field = self::$sortField;

    return strnatcasecmp(CMbString::removeDiacritics($a->$sort_field), CMbString::removeDiacritics($b->$sort_field));
  }

  /**
   * Collection natural sort utility with diacritic sensitiveness options
   *
   * @param CModelObject[] $objects    Object collection to be sorted
   * @param string[]       $fields     Fields to sort on
   * @param bool           $diacritics Take diacritics (accents and more) into account
   *
   * @return array
   */
  public static function naturalSort($objects, $fields, $diacritics = false) {
    if (empty($objects)) {
      return $objects;
    }

    $callback = $diacritics ? "_cmpFieldNaturalAccentsDiacritics" : "_cmpFieldNatural";

    foreach ($fields as $field) {
      self::$sortField = $field;
      usort($objects, array(__CLASS__, $callback));
    }

    // Restore original keys
    return array_combine(CMbArray::pluck($objects, "_id"), $objects);
  }

  /**
   * Add data to the data store
   *
   * @param string $key  The key to store to
   * @param mixed  $data The data to store
   *
   * @return mixed
   */
  public function addToStore($key, $data) {
    return $this->_data_store[$key] = $data;
  }

  /**
   * Get an item from the store
   *
   * @param string $key The key to get
   *
   * @return mixed|null
   */
  public function getFromStore($key) {
    if (!array_key_exists($key, $this->_data_store)) {
      return null;
    }

    return $this->_data_store[$key];
  }

  /**
   * @return bool
   */
  public function isModelObjectAbstract() {
    return !$this->_spec || !($this->_spec->table && $this->_spec->key);
  }

  /**
   * Return the sefl api link of a resoucre (use CRouter::generate)
   *
   * @return string|null
   */
  public function getApiLink(): ?string {
    return null;
  }

  /**
   * Return the schema api link of a resource (expected fieldsets)
   *
   * @param array|null $filedsets
   *
   * @return string|null
   */
  public function getApiSchemaLink(array $filedsets = null): ?string {
    $parameters                  = [];
    $parameters['resource_name'] = $this::RESOURCE_NAME;
    if (is_array($filedsets)) {
      $parameters['fieldsets'] = implode(',', $filedsets);
    }

    return CRouter::getInstance()
      ->generate('system_shemas_models', $parameters);
  }


  /**
   * Return the history api link of a resource
   *
   * @return string|null
   */
  public function getApiHistoryLink(): ?string {
    $parameters = [
      'resource_name' => $this::RESOURCE_NAME,
      'resource_id'   => (int)$this->_id
    ];

    return CRouter::getInstance()
      ->generate('system_history_list', $parameters);
  }

  /**
   * @param string $fieldname
   * @param string $option fieldset|back|...
   *
   * @return string|null
   */
  public function getPropsWitouthOption(string $fieldname, string $option): ?string {
    $spec = $this->_specs[$fieldname];

    return str_replace(" $option|" . $spec->$option, "", $spec->prop);
  }

  /**
   * @param string $fieldname
   *
   * @return string|null
   */
  public function getPropsWitouthFieldset(string $fieldname): ?string {
    return $this->getPropsWitouthOption($fieldname, "fieldset");
  }


  /**
   * @param string $resource_name
   *
   * @return mixed
   * @throws Exception
   */
  public static function getClassNameByResourceName($resource_name) {
    $cache = new Cache(__METHOD__, $resource_name, Cache::INNER);
    if ($cache->exists()) {
      return $cache->get();
    }

    $models = CClassMap::getInstance()->getClassChildren(self::class);

    $class_name = null;
    foreach ($models as $model) {
      if ($model::RESOURCE_NAME === $resource_name) {
        $class_name = $model;
        break;
      }
    }

    if ($class_name === null) {
      throw new CMbException("Ressource '{$resource_name}' not found in CModelObject.");
    }

    return $cache->put($class_name);
  }

    /**
     * @return bool
     */
    public function mustUseAntiCsrf(): bool
    {
        return $this->_spec->mustUseAntiCsrf();
    }
}
