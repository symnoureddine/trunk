<?php
/**
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Urgences;

use Ox\Core\CMbObject;
use Ox\Mediboard\Patients\CConstantesMedicales;

/**
 * Echelle de tri
 */
class CEchelleTri extends CMbObject {
  public $echelle_tri_id;

  // DB Fields
  public $rpu_id;

  // Form fields
  public $proteinurie;
  public $liquide;
  public $antidiabet_use;
  public $anticoagul_use;
  public $anticoagulant;
  public $antidiabetique;
  public $pupille_droite;
  public $pupille_gauche;
  public $reactivite_droite;
  public $reactivite_gauche;
  public $ouverture_yeux;
  public $rep_verbale;
  public $rep_motrice;
  public $enceinte;
  public $semaine_grossesse;
  public $signe_clinique;
  public $ccmu_manuel;

  /** @var CRPU */
  public $_ref_rpu;

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = 'echelle_tri';
    $spec->key   = 'echelle_tri_id';

    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props                      = parent::getProps();
    $props["rpu_id"]            = "ref class|CRPU notNull cascade back|echelle_tri";
    $props["proteinurie"]       = "enum list|positive|negative";
    $props["liquide"]           = "enum list|meconial|teinte";
    $props["pupille_droite"]    = "num notNull min|0 max|3 default|0";
    $props["pupille_gauche"]    = "num notNull min|0 max|3 default|0";
    $props["antidiabet_use"]    = "enum list|NP|oui|non default|NP";
    $props["anticoagul_use"]    = "enum list|NP|oui|non default|NP";
    $props["antidiabetique"]    = "enum list|oral|insuline|oral_insuline";
    $props["anticoagulant"]     = "enum list|sintrom|other";
    $props["ouverture_yeux"]    = "enum list|jamais|douleur|bruit|spontane";
    $props["rep_verbale"]       = "enum list|aucune|incomprehensible|inapproprie|confuse|oriente";
    $props["rep_motrice"]       = "enum list|rien|decerebration|decortication|evitement|oriente|obeit";
    $props["reactivite_droite"] = "enum list|reactif|non_reactif";
    $props["reactivite_gauche"] = "enum list|reactif|non_reactif";
    $props["enceinte"]          = "enum list|1|0";
    $props["semaine_grossesse"] = "num min|0 max|45";
    $props["signe_clinique"]    = "text";
    $props["ccmu_manuel"]       = "bool default|0";

    return $props;
  }

  /**
   * Chargement du PRU
   *
   * @param bool $cache cache
   *
   * @return CRPU
   */
  function loadRefRPU($cache = true) {
    return $this->_ref_rpu = $this->loadFwdRef("rpu_id", $cache);
  }

  /**
   * @see parent::store()
   */
  function store() {

    if (!$this->_id && $this->rpu_id) {
      $echelle         = new self;
      $echelle->rpu_id = $this->rpu_id;
      $echelle->loadMatchingObject();
      $this->_id = $echelle->_id;
    }

    $glasgow = null;
    if ($this->fieldModified("ouverture_yeux") || $this->fieldModified("rep_motrice") || $this->fieldModified("rep_verbale")) {
      $glasgow = $this->calculGlasgow();
    }

    // Standard Store
    if ($msg = parent::store()) {
      return $msg;
    }

    if ($glasgow != null) {
      $sejour                               = $this->loadRefRPU()->loadRefSejour();
      $constante                            = new CConstantesMedicales();
      $constante->_new_constantes_medicales = 1;
      $constante->patient_id                = $sejour->loadRefPatient()->_id;
      $constante->context_class             = $sejour->_class;
      $constante->context_id                = $sejour->_id;
      $constante->glasgow                   = $glasgow;
      $constante->datetime                  = 'now';
      if ($msg = $constante->store()) {
        return $msg;
      }
    }

    return null;
  }

  function calculGlasgow() {
    $glasgow = 0;
    if ($this->ouverture_yeux) {
      $glasgow += array_search($this->ouverture_yeux, $this->_specs['ouverture_yeux']->_list) + 1;
    }
    if ($this->rep_motrice) {
      $glasgow += array_search($this->rep_motrice, $this->_specs['rep_motrice']->_list) + 1;
    }
    if ($this->rep_verbale) {
      $glasgow += array_search($this->rep_verbale, $this->_specs['rep_verbale']->_list) + 1;
    }

    return $glasgow;
  }
}