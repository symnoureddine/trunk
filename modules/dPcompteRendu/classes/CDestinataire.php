<?php
/**
 * @package Mediboard\CompteRendu
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\CompteRendu;

use Exception;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CMbObject;
use Ox\Mediboard\Cabinet\CConsultAnesth;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CMedecin;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Prescription\CPrescription;

/**
 * Permet une forme de publi-postage pour les documents produits dans Mediboard
 * Cette classe n'est pas un MbObject et les objets ne sont pas enregistrés en base
 */
class CDestinataire implements IShortNameAutoloadable {
  public $nom;
  public $adresse;
  public $cpville;
  public $email;
  public $confraternite;      // used for medecin
  public $tag;
  public $civilite_nom;
  public $object_guid;

  /** @var string Current user starting formula */
  public $starting_formula;

  /** @var string Current user closing formula */
  public $closing_formula;

  public $tutoiement;

  static $destByClass = array();
  static $_patient = null;

  /**
   * Constructeur standard
   *
   * @param string $tag Tag par défaut, optionnel
   */
  function __construct($tag = null) {
    $this->tag = $tag;
  }

  /**
   * Construit les destinataires pour un MbObject
   *
   * @param CMbObject &$mbObject    L'objet en question
   * @param string    $tag          [optionnel] tag par défaut
   * @param CMbObject $object       L'objet auquel le praticien est associé pour récupérer les formules de politesses
   * @param string    $address_type Type d'adresse voulue (mail, apicrypt, mssante)
   *
   * @return void
   */
  static function makeFor(CMbObject &$mbObject, $tag = null, $object = null, $address_type = 'mail') {
    if (!$mbObject->_id) {
      return;
    }

    if ($mbObject instanceof CPatient && $address_type == 'mail') {
      $patient = $mbObject;
      $patient->loadRefsCorrespondantsPatient();

      // Patient
      $dest                                   = new static($tag);
      $dest->tag                              = "patient";
      $dest->nom                              = "$patient->nom $patient->prenom";
      $dest->adresse                          = $patient->adresse;
      $dest->cpville                          = "$patient->cp $patient->ville";
      $dest->email                            = $patient->email;
      $dest->civilite_nom                     = ucfirst($patient->_civilite_long) . " $patient->nom $patient->prenom";
      $dest->object_guid                      = $patient->_guid;
      self::$destByClass[$mbObject->_class][] = $dest;

      // Assuré
      $dest                                   = new static($tag);
      $dest->tag                              = "assure";
      $dest->nom                              = "$patient->assure_nom $patient->assure_prenom";
      $dest->adresse                          = $patient->assure_adresse;
      $dest->cpville                          = "$patient->assure_cp $patient->assure_ville";
      $dest->civilite_nom                     = $patient->_assure_civilite_long . " $patient->assure_nom $patient->assure_prenom";
      $dest->object_guid                      = $patient->_guid;
      $dest->email                            = "";
      self::$destByClass[$mbObject->_class][] = $dest;

      // Personne à prévenir et employeur
      foreach ($patient->_ref_correspondants_patient as $_corres) {
        if ($_corres->relation == "confiance") {
          continue;
        }
        $dest                                   = new static($tag);
        $dest->tag                              = $_corres->relation;
        $dest->nom                              = $_corres->nom;
        $dest->adresse                          = $_corres->adresse;
        $dest->cpville                          = "$_corres->cp $_corres->ville";
        $dest->civilite_nom                     = "$_corres->nom";
        $dest->email                            = $_corres->email;
        $dest->object_guid                      = $_corres->_guid;
        self::$destByClass[$mbObject->_class][] = $dest;
      }
    }
    elseif ($mbObject instanceof CMedecin) {
      /** @var CMedecin $medecin */
      $medecin = $mbObject;

      // Chargement des formules de politesse selon le praticien du contexte
      $user_id = CMediusers::get()->_id;
      if ($object) {
        switch ($object->_class) {
          case 'CConsultation':
            $user_id = $object->loadRefPraticien()->_id;
            break;

          case 'CConsultAnesth':
          case 'COperation':
            $user_id = $object->chir_id;
            break;

          case 'CSejour':
            $user_id = $object->praticien_id;
            break;

          default:
        }
      }

      $medecin->loadSalutations($user_id);

      $dest                = new static($tag);
      $dest->confraternite = $medecin->_confraternite;
      $dest->nom           = $medecin->_view;
      $dest->adresse       = $medecin->adresse;
      $dest->cpville       = "$medecin->cp $medecin->ville";
      $dest->civilite_nom  = $medecin->_longview;
      $dest->object_guid   = $medecin->_guid;

      $dest->starting_formula = $medecin->_starting_formula;
      $dest->closing_formula  = $medecin->_closing_formula;
      $dest->tutoiement       = $medecin->_tutoiement;

      switch ($address_type) {
        case 'apicrypt':
          $dest->email = $medecin->email_apicrypt;
          break;
        case 'mssante':
          $dest->email = $medecin->mssante_address;
          break;
        case 'mail':
        default:
          $dest->email = $medecin->email;
      }

      self::$destByClass[$mbObject->_class][$medecin->_id] = $dest;
    }
    elseif ($mbObject instanceof CMediusers) {
      $dest               = new static("praticien");
      $dest->nom          = "Dr " . $mbObject->_user_last_name . " " . $mbObject->_user_first_name;
      $dest->object_guid  = $mbObject->_guid;

      $mbObject->loadRefFunction();
      $dest->adresse      = $mbObject->_ref_function->adresse;
      $dest->cpville      = "{$mbObject->_ref_function->cp} {$mbObject->_ref_function->ville}";

      switch ($address_type) {
        case 'apicrypt':
          $dest->email = $mbObject->mail_apicrypt;
          break;
        case 'mssante':
          $dest->email = $mbObject->mssante_address;
          break;
        case 'mail':
        default:
          $dest->email = $mbObject->_user_email;
      }

      self::$destByClass[$mbObject->_class][$mbObject->_id] = $dest;
    }
  }

  /**
   * Construit les destinataires pour un MbObject et ses dépendances
   *
   * @param CMbObject $mbObject     L'objet en question
   * @param Object    $object       Objet secondaire
   * @param string    $address_type Type d'adresse voulue (mail, apicrypt, mssante)
   *
   * @return void
   * @throws Exception
   */
  static function makeAllFor(CMbObject &$mbObject, $object = null, $address_type = 'mail') {
    self::$destByClass = array();
    if ($mbObject instanceof CPatient) {
      $patient = $mbObject;
      // Garder une référence vers le patient pour l'ajout de correspondants
      // en modale dans la popup d'édition de document

      self::$_patient = $patient;
      self::makeFor($patient, null, $object, $address_type);

      $patient->loadRefsCorrespondants();

      self::makeFor($patient->_ref_medecin_traitant, "traitant", $object, $address_type);

      foreach ($patient->_ref_medecins_correspondants as &$corresp) {
        self::makeFor($corresp->_ref_medecin, "correspondant", $object, $address_type);
      }
    }
    elseif ($mbObject instanceof CPrescription) {
      self::makeAllFor($mbObject->loadRefPatient(), $object, $address_type);
    }
    elseif ($mbObject instanceof CConsultation) {
      $consult = $mbObject;

      $consult->loadRefPatient();
      $consult->loadRefPraticien();
      self::makeAllFor($consult->_ref_patient, $object, $address_type);
      self::makeFor($consult->_ref_praticien, $object, $address_type);
    }
    elseif ($mbObject instanceof CConsultAnesth) {
      $consultAnesth = $mbObject;

      $consultAnesth->loadRefConsultation();
      self::makeAllFor($consultAnesth->_ref_consultation, $object, $address_type);
    }
    elseif ($mbObject instanceof CSejour) {
      $sejour = $mbObject;

      $sejour->loadRefPatient();
      $sejour->loadRefPraticien();
      self::makeAllFor($sejour->_ref_patient, $object, $address_type);
      self::makeFor($sejour->_ref_praticien, $object, $address_type);
    }
    elseif ($mbObject instanceof COperation) {
      $operation = $mbObject;

      $operation->loadRefSejour();
      self::makeAllFor($operation->_ref_sejour, $object, $address_type);
    }
  }
}
