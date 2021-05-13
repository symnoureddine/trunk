<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System;
use Ox\Core\CMbArray;
use Ox\Core\CMbObject;
use Ox\Core\FieldSpecs\CColorSpec;

class CTag extends CMbObject {
  public $tag_id;

  public $parent_id;
  public $object_class;
  public $name;
  public $color;

  public $_font_color;

  /** @var self */
  public $_ref_parent;

  /** @var CTagItem[] */
  public $_ref_items;
  public $_nb_items;

  /** @var CTag[] */
  public $_ref_children;
  public $_nb_children;

  public $_deepness;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec                  = parent::getSpec();
    $spec->table           = "tag";
    $spec->key             = "tag_id";
    $spec->uniques["name"] = array("parent_id", "object_class", "name");

    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props                 = parent::getProps();
    $props["parent_id"]    = "ref class|CTag autocomplete|name dependsOn|object_class back|children";
    $props["object_class"] = "str class";
    $props["name"]         = "str notNull seekable";
    $props["color"]        = "color";
    $props["_nb_items"]    = "num";
    $props['_font_color']  = 'color';

    return $props;
  }

  /**
   * @inheritdoc
   */
  function updateFormFields() {
    parent::updateFormFields();

    $parent      = $this->loadRefParent();
    $this->_view = ($parent->_id ? "$parent->_view &raquo; " : "") . $this->name;

    $this->_font_color = "000000";

    if ($this->color && (CColorSpec::get_text_color($this->color) < 130)) {
      $this->_font_color = "ffffff";
    }

    $this->color       = ($this->color) ?: $parent->color;
    $this->_font_color = ($this->_font_color) ?: $parent->_font_color;
  }

  /**
   * @inheritdoc
   */
  function getPerm($permType) {
    $class = $this->object_class;

    if ($class) {
      $context = new $class();
      return $context->getPerm($permType);
    }
    else {
      return parent::getPerm($permType);
    }
  }

  /**
   * Load tag items
   *
   * @return CTagItem[]
   */
  function loadRefItems() {
    return $this->_ref_items = $this->loadBackRefs("items");
  }

  /**
   * Count items related to this
   *
   * @return int
   */
  function countRefItems() {
    return $this->_nb_items = $this->countBackRefs("items");
  }

  /**
   * Load children
   *
   * @return self[]
   */
  function loadRefChildren() {
    return $this->_ref_children = $this->loadBackRefs("children");
  }

  /**
   * Count children tags
   *
   * @return int
   */
  function countChildren() {
    return $this->_nb_children = $this->countBackRefs("children");
  }

  /**
   * Load parent tag
   *
   * @return self
   */
  function loadRefParent() {
    return $this->_ref_parent = $this->loadFwdRef("parent_id", true);
  }

  /**
   * @inheritdoc
   */
  function check() {
    if ($msg = parent::check()) {
      return $msg;
    }

    if (!$this->parent_id) {
      return null;
    }

    $tag = $this;
    while ($tag->parent_id) {
      $parent = $tag->loadRefParent();
      if ($parent->_id == $this->_id) {
        return "Récursivité détectée, un des ancêtres du tag est lui-même";
      }
      $tag = $parent;
    }

    return null;
  }

  /**
   * Get objects matching the keywords having the current tag
   *
   * @param string $keywords Keywords
   *
   * @return CMbObject[]
   */
  function getObjects($keywords = "") {
    if (!$keywords) {
      $items = $this->loadRefItems();
    }
    else {
      $where = array(
        "tag_id"       => "= '$this->_id'",
        "object_class" => "= 'object_class'",
      );
      $item  = new CTagItem;
      $items = $item->seek($keywords, $where, 10000);
    }

    CMbArray::invoke($items, "loadTargetObject");

    return CMbArray::pluck($items, "_ref_object");
  }

  /**
   * @inheritdoc
   */
  function getAutocompleteList($keywords, $where = null, $limit = null, $ljoin = null, $order = null, $group_by = null, bool $strict = true) {
    $list = array();

    if ($keywords === "%" || $keywords == "") {
      $tree = self::getTree($this->object_class);
      self::appendItemsRecursive($list, $tree);

      foreach ($list as $_tag) {
        $_tag->_view = $_tag->name;
      }
    }
    else {
      $list = parent::getAutocompleteList($keywords, $where, $limit, $ljoin, $order, $group_by, $strict);
    }

    return $list;
  }

  /**
   * @param self[] $list
   * @param array  $tree
   */
  private static function appendItemsRecursive(&$list, $tree) {
    if ($tree["parent"]) {
      $list[] = $tree["parent"];
    }

    foreach ($tree["children"] as $_child) {
      self::appendItemsRecursive($list, $_child);
    }
  }

  /**
   * @param int $d
   *
   * @return int
   */
  function getDeepness($d = 0) {
    if ($this->parent_id) {
      $d++;
      $d = $this->loadRefParent()->getDeepness($d);
    }

    return $this->_deepness = $d;
  }

  /**
   * @param string $object_class
   * @param CTag   $parent
   * @param array  $tree
   *
   * @return array
   */
  static function getTree($object_class, CTag $parent = null, &$tree = array()) {
    $tag   = new self;
    $where = array(
      "object_class" => "= '$object_class'",
      "parent_id"    => (($parent && $parent->_id) ? "= '{$parent->_id}'" : "IS NULL"),
    );

    $tree["parent"]   = $parent;
    $tree["children"] = array();

    /** @var self[] $tags */
    $tags = $tag->loadList($where, "name");

    foreach ($tags as $_tag) {
      $_tag->getDeepness();
      self::getTree($object_class, $_tag, $sub_tree);
      $tree["children"][] = $sub_tree;
    }

    return $tree;
  }
}
