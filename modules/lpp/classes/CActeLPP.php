<?php
/**
 * @package Mediboard\Lpp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Lpp;

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CMbFieldSpec;
use Ox\Core\Module\CModule;
use Ox\Mediboard\Ccam\CActe;
use Ox\Mediboard\Ccam\CCodable;
use Ox\Mediboard\Facturation\CFacture;
use Ox\Mediboard\Facturation\CFactureItem;
use Ox\Mediboard\OxPyxvital\CPyxvitalFSE;
use Ox\Mediboard\OxPyxvital\CPyxvitalFSEAct;

/**
 * Description
 */
class CActeLPP extends CActe {
  /**
   * @var integer Primary key
   */
  public $acte_lpp_id;

  /** @var string Le code de prestation associ� au code LPP */
  public $code_prestation;

  /** @var string Le code LPP */
  public $code;

  /** @var string Le type de prestation */
  public $type_prestation;

  /** @var string Le n� SIRET du fabirquant ou de l'importateur (non utilis� dans la nomenclature pour le moment) */
  public $siret;

  /** @var string Date d'achat, de d�livrance ou de d�but de location ou de service */
  public $date;

  /** @var string Date de fin de location ou de service */
  public $date_fin;

  /** @var integer Le nombre d'unit� fournies, pour les locations, nombre de jours/semaines/mois de location */
  public $quantite;

  /** @var float Tarif de r�f�rence x quantit� */
  public $montant_total;

  /** @var float Tarif (TTC) factur� apr�s une �ventuelle remise */
  public $montant_final;

  /** @var string Le qualificatif de la d�pense */
  public $qualif_depense;

  /** @var bool Indique si une demande d'accord pr�alable a �t� faite */
  public $accord_prealable;

  /** @var string Date de la DEP */
  public $date_demande_accord;

  /** @var string R�ponse de la DEP */
  public $reponse_accord;

  /** @var bool Indique si l'acte concerne l'ALD du patient */
  public $concerne_ald;

  /** @var bool Indique la n�c�ssit� d'une DEP */
  public $_dep = false;

  /** @var array La liste des qualificatifs de d�penses */
  public $_qual_depense = array('d', 'e', 'f', 'g', 'n', 'a', 'b');

  /** @var array La liste des qualificatifs de d�penses interdits pour ce code */
  public $_unauthorized_qual_depense = array();

  /**
   * Initialize the class specifications
   *
   * @return CMbFieldSpec
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = "actes_lpp";
    $spec->key   = "acte_lpp_id";

    return $spec;
  }

  /**
   * Get the properties of our class as strings
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();

    $props["object_id"]          .= " back|actes_lpp";
    $props["executant_id"]       .= " back|actes_lpp_executes";
    $props['code_prestation']     = 'str maxLength|3 minLength|3 notNull';
    $props['code']                = 'str maxLength|7 minLength|7 notNull';
    $props['type_prestation']     = 'enum list|A|E|L|P|S|R|V notNull';
    $props['siret']               = 'str maxLength|14 minLength|14';
    $props['date']                = 'date notNull';
    $props['date_fin']            = 'date';
    $props['quantite']            = 'num min|1 default|1';
    $props['montant_total']       = 'float';
    $props['montant_final']       = 'float';
    $props['qualif_depense']      = 'enum list|d|e|f|g|n|a|b';
    $props['accord_prealable']    = 'bool default|0';
    $props['date_demande_accord'] = 'date';
    $props['reponse_accord']      = 'enum list|no_answer|accepted|emergency|refused';
    $props['concerne_ald']        = 'bool default|0';

    return $props;
  }

  /**
   * @see parent::updateFormFields
   */
  public function updateFormFields() {
    if ($this->code) {
      $code = CLPPCode::load($this->code);
      $code->loadLastPricing($this->date);
      $this->_dep = $code->_last_pricing->dep;

      $this->_unauthorized_qual_depense = $code->getQualificatifsDepense();
    }

    parent::updateFormFields();
  }

  /**
   * @see parent::store()
   */
  public function store() {
    // Chargement du oldObject
    $oldObject = new CActeLPP();
    $oldObject->load($this->_id);

    if ($msg = parent::store()) {
      return $msg;
    }

    /* We create a link between the act and the fse in creation for the linked consultation */
    if (CModule::getActive('oxPyxvital') && $this->object_class == 'CConsultation' && !$oldObject->_id) {
      $this->loadTargetObject();
      $fses = CPyxvitalFSE::loadForConsult($this->_ref_object);

      foreach ($fses as $_fse) {
        if ($_fse->state == 'creating') {
          $_link            = new CPyxvitalFSEAct();
          $_link->fse_id    = $_fse->_id;
          $_link->act_class = $this->_class;
          $_link->act_id    = $this->_id;

          if ($msg = $_link->store()) {
            return $msg;
          }
        }
      }
    }

    return null;
  }

  /**
   * @see parent::delete()
   */
  public function delete() {
    /* We delete the links between the act and the fse that are in creation or cancelled */
    if (CModule::getActive('oxPyxvital') && $this->object_class == 'CConsultation') {
      /** @var CPyxvitalFSEAct[] $fse_links */
      $fse_links = $this->loadBackRefs('fse_links');
      if ($fse_links) {
        foreach ($fse_links as $_link) {
          $_link->loadRefFSE();
          if ($_link->_ref_fse->state == 'creating' || $_link->_ref_fse->state == 'cancelled') {
            if ($msg = $_link->delete()) {
              return $msg;
            }
          }
        }
      }
    }

    return parent::delete();
  }

  /**
   * @see parent::canDeleteEx()
   */
  function canDeleteEx() {
    if ($msg = $this->checkCoded()) {
      return $msg;
    }

    $msg = parent::canDeleteEx();

    if ($msg) {
      return $msg;
    }

    if (CModule::getActive('oxPyxvital') && $this->object_class == 'CConsultation') {
      /** @var CPyxvitalFSEAct[] $fse_links */
      $fse_links = $this->loadBackRefs('fse_links');
      if ($fse_links) {
        foreach ($fse_links as $_link) {
          $_link->loadRefFSE();
          if ($_link->_ref_fse->state != 'creating' && $_link->_ref_fse->state != 'cancelled') {
            $msg = CAppUI::tr('CMbObject-msg-nodelete-backrefs') . ': ' . count($fse_links) . ' ' . CAppUI::tr("CActe-back-fse_links");
          }
        }
      }
    }

    return $msg;
  }

  /**
   * Create a CFactureItem
   *
   * @param CFacture $facture la facture
   *
   * @return string|null
   */
  function creationItemsFacture($facture) {
    $_code = CLPPCode::load($this->code);

    $item                      = new CFactureItem();
    $item->libelle             = $_code->name;
    $item->code                = "{$this->code} {$this->code_prestation}";
    $item->type                = $this->_class;
    $item->object_id           = $facture->_id;
    $item->object_class        = $facture->_class;
    $item->date                = CMbDT::date($this->execution);
    $item->montant_base        = $this->montant_final;
    $item->montant_depassement = $this->montant_depassement;
    $item->quantite            = $this->quantite;
    $item->coeff               = 0;

    return $item->store();
  }

  /**
   * @see parent::makeFullCode()
   */
  public function makeFullCode() {
    return $this->_full_code = "{$this->quantite}-{$this->code}-{$this->code_prestation}"
      . "-{$this->type_prestation}-{$this->montant_base}-" . str_replace('-', '*', $this->montant_depassement);
  }

  /**
   * @param string $code
   *
   * @see parent::setFullCode()
   */
  public function setFullCode($code) {
    $details = explode('-', $code);

    $this->quantite            = $details[0];
    $this->code                = $details[1];
    $this->code_prestation     = $details[2];
    $this->type_prestation     = $details[3];
    $this->montant_base        = $details[4];
    $this->montant_depassement = $details[5];
    $this->montant_final       = intval($this->quantite) * floatval($this->montant_base);

    $this->updateFormFields();
  }

  /**
   * @see parent::getPrecodeReady()
   */
  function getPrecodeReady() {
    return $this->quantite && $this->code && $this->code_prestation;
  }

  /**
   * Create an empty act for the given codable
   *
   * @param CCodable $codable The codable
   *
   * @return CActeLPP
   */
  public static function createFor($codable) {
    $act = new self;

    $act->setObject($codable);
    $act->quantite = 1;
    $act->loadExecution();
    $act->guessExecutant();
    $act->date = CMbDT::date(null, $act->execution);

    return $act;
  }
}
