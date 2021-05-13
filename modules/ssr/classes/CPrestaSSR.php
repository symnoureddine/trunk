<?php
/**
 * @package Mediboard\Ssr
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ssr;

use Ox\Core\CMbArray;
use Ox\Core\CMbObject;
use Ox\Core\Module\CModule;
use Ox\Core\CSQLDataSource;
use Ox\Interop\Opale\CPrefixePresta;

/**
 * Prestation SSR
 */
class CPrestaSSR extends CMbObject {
  // DB Fields
  public $prestation_id;
  public $type;
  public $code;
  public $libelle;
  public $description;
  public $tarif;
  public $type_tarif;

  // Form fields
  public $_refs_prestations_ssr;
  public $_count_prestations_ssr;
  public $_prefixe;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->dsn   = 'presta_ssr';
    $spec->table = "prestation";
    $spec->key   = "prestation_id";

    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props                = parent::getProps();
    $props["type"]        = "enum list|dietetique|neuropsychologie|logopedie|ergotherapie|physiotherapie|psychotherapie|psychomotricite|sport|artherapie|massotherapie";
    $props["code"]        = "str";
    $props["libelle"]     = "str";
    $props["description"] = "text";
    $props["tarif"]       = "float";
    $props["type_tarif"]  = "enum list|points|francs";

    return $props;
  }

  /**
   * @inheritdoc
   */
  function updateFormFields() {
    parent::updateFormFields();
    $this->_view = $this->code;
  }

  /**
   * Charge une prestaton SSR par le code
   *
   * @param string $code Code prestation SSR
   *
   * @return self
   */
  static function get($code) {
    $presta       = new self();
    $presta->code = $code;
    $presta->loadMatchingObjectEsc();

    return $presta->_id ? $presta : null;
  }

  /**
   * Load the prestations SSR
   *
   * @param array  $where Tableau de clauses WHERE MYSQL
   * @param string $order paramètre ORDER SQL
   * @param string $limit paramètre LIMIT SQL
   *
   * @return array
   */
  function loadRefsPrestationsSSR($where = null, $order = null, $limit = null) {
    return $this->_refs_prestations_ssr = $this->loadList($where, $order, $limit);
  }

  /**
   * Count the prestations SSR
   *
   * @param array $where Tableau de clauses WHERE MYSQL
   *
   * @return int
   */
  function countPrestationsSSR($where) {
    return $this->_count_prestations_ssr = $this->countList($where);
  }

  /**
   * Load the prefixes matching a collection of prestations
   *
   * @param self[] $prestas_ssr List of prestations
   *
   * @return void
   */
  public static function massGetPrefixes($prestas_ssr) {
    if (!count($prestas_ssr) || !CModule::getActive("opale")) {
      return;
    }

    $codes = CMbArray::pluck($prestas_ssr, "code");

    $prefixe_presta   = new CPrefixePresta();
    $prefixes_prestas = $prefixe_presta->loadList(array("code" => CSQLDataSource::prepareIn($codes)));

    foreach ($prefixes_prestas as $_prefixe_presta) {
      if (false !== $key = array_search($_prefixe_presta->code, $codes)) {
        $prestas_ssr[$key]->_prefixe = $_prefixe_presta->prefixe;
      }
    }
  }

  /**
   * Load the matching prefix of the prestation
   *
   * @return void
   */
  function getPrefixe() {
    if (!CModule::getActive("opale")) {
      return;
    }
    $prefixe_presta       = new CPrefixePresta();
    $prefixe_presta->code = $this->code;
    $prefixe_presta->loadMatchingObject();
    $this->_prefixe = $prefixe_presta->prefixe;
  }
}
