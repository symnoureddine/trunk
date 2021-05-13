<?php
/**
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Files;

use Ox\Core\CMbObject;
use Ox\Core\CStoredObject;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Patients\CCorrespondantPatient;
use Ox\Mediboard\Patients\CMedecin;
use Ox\Mediboard\Patients\CPatient;

/**
 * Description
 */
class CDestinataireItem extends CStoredObject {
  /**
   * @var integer Primary key
   */
  public $destinataire_item_id;

  // DB fields
  public $dest_class;
  public $dest_id;
  public $tag;
  public $docitem_id;
  public $docitem_class;

  // References
  public $_ref_docitem;
  public $_ref_destinataire;
  public $_ref_dispatches;

  // Form fields
  public $_nom;
  public $_adresse;
  public $_cp;
  public $_ville;
  public $_pays;
  public $_email;
  public $_email_apicrypt;
  public $_tag;

  public $_docitem_guid;
  public $_destinataire_guid;

  public static $tags = [
    'assurance', 'assure', 'autre', 'correspondant', 'employeur', 'ìnconnu',
    'other_prat', 'patient', 'praticien', 'prevenir', 'traitant'
  ];

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->table  = "destinataire_item";
    $spec->key    = "destinataire_item_id";
    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props = parent::getProps();
    $props["dest_class"]   = "enum list|CCorrespondantPatient|CMedecin|CPatient notNull";
    $props["dest_id"]      = "ref notNull class|CMbObject meta|dest_class back|dest_items";
    $props["tag"]          = "enum list|assurance|assure|autre|correspondant|employeur|ìnconnu|other_prat|patient|praticien|prevenir|traitant";
    $props["docitem_class"] = "enum list|CCompteRendu|CFile notNull";
    $props["docitem_id"]    = "ref notNull class|CMbObject meta|docitem_class back|destinataires cascade";
    return $props;
  }

  /**
   * @see parent::updateFormFields()
   */
  function updateFormFields() {
    parent::updateFormFields();

    $this->_docitem_guid = "$this->docitem_class-$this->docitem_id";
    $this->_destinataire_guid = "$this->dest_class-$this->dest_id";
  }

  /**
   * Set the receiver and the tag according to the receiver's class
   *
   * @param CMbObject $receiver The receiver
   * @param string    $tag      An optional tag (if not given, the tag will be set according to the class of the receiver)
   *
   * @return void
   */
  public function setReceiver(CMbObject $receiver, $tag = null) {
    $this->dest_class = $receiver->_class;
    $this->dest_id = $receiver->_id;

    if ($tag) {
      $this->tag = $tag;
    }
    else {
      switch ($receiver->_class) {
        case 'CMedecin':
          $this->tag = 'other_prat';
          break;
        case 'CPatient':
          $this->tag = 'patient';
          break;
        case 'CCorrespondantPatient':
          if (in_array($receiver->relation, self::$tags)) {
            $this->tag = $receiver->relation;
          }
          else {
            $this->tag = 'autre';
          }
          break;
        default:
          $this->tag = 'autre';
      }
    }
  }

  /**
   * Charge le destinataire
   *
   * @return CCorrespondantPatient|CMedecin|CPatient
   */
  function loadRefDestinataire() {
    return $this->_ref_destinataire = $this->loadFwdRef("dest_id", true);
  }

  /**
   * Charge l'item documentaire
   *
   * @return CCompteRendu|CFile
   */
  function loadRefDocItem() {
    return $this->_ref_docitem = $this->loadFwdRef("docitem_id", true);
  }

  /**
   * Charge les infos (adresse, nom) en fonction du destinataire
   */
  function loadInfos() {
    $destinataire = $this->loadRefDestinataire();

    switch ($destinataire->_class) {
      case "CCorrespondantPatient":
      case "CMedecin":
        $this->_nom     = "$destinataire->prenom $destinataire->nom";
        $this->_adresse = $destinataire->adresse;
        $this->_cp      = $destinataire->cp;
        $this->_ville   = $destinataire->ville;
        $this->_email   = $destinataire->email;
        if ($this->_class == "CMedecin") {
          $this->_email_apicrypt = $destinataire->email_apicrypt;
        }

        switch ($this->tag) {
          case "prevenir":
            $this->_tag = "Personne à prévenir";
            break;
        }

        if ($destinataire instanceof CMedecin) {
          $this->_email_apicrypt   = $destinataire->email_apicrypt;
          $this->_tag = $this->tag == "traitant" ? "Médecin traitant" :  "Médecin correspondant";
        }
        break;
      case "CPatient":
        if ($this->tag == "assure") {
          $this->_nom     = "$destinataire->assure_prenom $destinataire->assure_nom";
          $this->_adresse = $destinataire->assure_adresse;
          $this->_cp      = $destinataire->assure_cp;
          $this->_ville   = $destinataire->assure_ville;
          $this->_pays    = $destinataire->assure_pays;
          $this->_tag     = "Assuré";
        }
        else {
          $this->_nom     = "$destinataire->prenom $destinataire->nom";
          $this->_adresse = $destinataire->adresse;
          $this->_cp      = $destinataire->cp;
          $this->_ville   = $destinataire->ville;
          $this->_email   = $destinataire->email;
          $this->_pays    = $destinataire->pays;
          $this->_tag     = "Patient";
        }
    }
  }

  /**
   * Charge les envois précédents pour ce destinataire
   */
  function loadRefsDispatches() {
    $this->_ref_dispatches = $this->loadBackRefs("links_dispatches", "link_dest_dispatch.link_dest_dispatch_id DESC");

    if (count($this->_ref_dispatches)) {
      /** @var CLinkDestDispatch $_dispatch */
      foreach ($this->_ref_dispatches as $_dispatch) {
        $_dispatch->loadRefDispatch();
      }
    }
  }
}
