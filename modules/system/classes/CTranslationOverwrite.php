<?php

/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System;

use Ox\Core\Cache;
use Ox\Core\CAppUI;
use Ox\Core\CMbObject;
use Ox\Core\CMbString;
use Ox\Core\CRequest;

/**
 * Class CTranslationOverwrite
 */
class CTranslationOverwrite extends CMbObject {
  public $translation_id;

  public $source;
  public $translation;
  public $language;
  public $_old_translation;

  public $_in_cache;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->table = "translation";
    $spec->key = "translation_id";
    $spec->uniques['trad'] = array('source', 'language');
    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props = parent::getProps();
    $props["source"]      = "str notNull";
    $props["language"]    = "enum notNull list|".implode('|', CAppUI::getAvailableLanguages())." default|fr";
    $props["translation"] = "text markdown notNull";
    return $props;
  }

  /**
   * Load the activated translation from mediboard (used to compare with the sql one)
   *
   * @param array $locales the locales array
   *
   * @return string
   */
  function loadOldTranslation($locales = array()) {
    if (!count($locales)) {
      $locales = array();
      $locale = CAppUI::pref("LOCALE", "fr");

      foreach (CAppUI::getLocaleFilesPaths($locale) as $_path) {
        include_once $_path;
      }
    }

    return $this->_old_translation = isset($locales[$this->source]) ? $locales[$this->source] : "";
  }

  /**
   * Check if the translation is cached
   *
   * @return bool
   */
  function checkInCache() {
    static $locales;

    if (!$locales) {
      $locales = CAppUI::flattenCachedLocales(CAppUI::$lang);
    }

    return $this->_in_cache = (isset($locales[$this->source]) && ($locales[$this->source] == $this->translation));
  }

  /**
   * @inheritdoc
   */
  function updatePlainFields() {
    parent::updatePlainFields();

    $this->translation = CMbString::purifyHTML($this->translation);
  }

  /**
   * Transform the mb locales with the overwrite system
   *
   * @param array       $locales  locales from mediboard
   * @param string|null $language language chosen, if not defined, use the preference.
   *
   * @return array $locales locales transformed
   */
  public function transformLocales($locales, $language = null) {
      $cache = new Cache('locales', $language . '-' . CAppUI::LOCALES_OVERWRITE, Cache::INNER_OUTER, 3600);
      if (!($overwrites = $cache->get())) {
          $ds = $this->_spec->ds;
          $where = [
              'language' => $ds->prepare('=%', $language ? $language : CAppUI::pref('LOCALE')),
          ];

          $query = new CRequest();
          $query->addSelect(['source', 'translation']);
          $query->addTable('translation');
          $query->addWhere($where);
          $overwrites = $cache->put($ds->loadList($query->makeSelect()));
      }

    foreach ($overwrites as $_overwrite) {
        if (isset($locales[$_overwrite['source']])) {
            $locales[$_overwrite['source']] = $_overwrite['translation'];
        }
    }

    return $locales;
  }
}
