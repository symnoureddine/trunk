<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ccam;

use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\Cache;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CMbObjectSpec;
use Ox\Core\Module\CModule;
use Ox\Core\CObject;
use Ox\Core\CSQLDataSource;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;

/**
 * Classe pour gérer le mapping avec la base de données CCAM
 */
class CDatedCodeCCAM implements IShortNameAutoloadable {
  public $date;          // Date de référence
  public $_date;         // date au style CCAM
  public $code;          // Code de l'acte
  public $chapitres;     // Chapitres de la CCAM concernes
  public $chap;          // nom du Chapitre de la CCAM concernes
  public $libelleCourt;  // Libelles
  public $libelleLong;
  public $place;         // Place dans la CCAM
  public $remarques;     // Remarques sur le code
  public $type;          // Type d'acte (isolé, procédure ou complément)
  public $activites = array(); // Activites correspondantes
  public $phases    = array(); // Nombre de phases par activités
  public $incomps   = array(); // Incompatibilite
  public $assos     = array(); // Associabilite
  public $procedure;     // Procedure
  public $remboursement; // Remboursement
  public $entente_prealable; // Nécessité d'une entente préalable
  public $forfait;       // Forfait spécifique (SEH1, SEH2, SEH3, SEH4, SEH5)
  public $couleur;       // Couleur du code par rapport à son chapitre
  /** @var CExtensionPMSI[] */
  public $extensions;
  /* Liste des catégories de spécialités autorisées
   * (TS: Toutes Spécialités, SF: Sages-Femmes, D1: Dentistes, D2: OrthoDontistes, CD: Chirugiens Dentistes)
   */
  public $specialites = array();

  // Variable calculées
  public $_code7;        // Possibilité d'ajouter le modificateur 7 (0: non, 1 : oui)
  public $_default;
  public $_sorted_tarif; // Phases classées par ordre de tarif brut
  public $occ;
  public $_count_activite;

  // Code CCAM de référence
  /** @var  CCodeCCAM */
  public $_ref_code_ccam;

  // Distant field
  public $class;
  public $favoris_id;
  public $_ref_favori;

  // Activités et phases recuperées depuis le code CCAM
  public $_activite;
  public $_phase;

  /** @var CMbObjectSpec */
  public $_spec;

  public $_couleursChap = array(
    0  => "ffffff",
    1  => "669966",
    2  => "6666cc",
    3  => "6699ee",
    4  => "cc6633",
    5  => "ee6699",
    6  => "ff66ee",
    7  => "33cc33",
    8  => "66cc99",
    9  => "99ccee",
    10 => "cccc33",
    11 => "eecc99",
    12 => "ffccee",
    13 => "33ff33",
    14 => "66ff99",
    15 => "99ffee",
    16 => "ccff33",
    17 => "eeff99",
    18 => "ffffee",
    19 => "cccccc",
  );

  /**
   * Constructeur à partir du code CCAM
   *
   * @param string $code Le code CCAM
   * @param string $date Date de référence
   *
   * @return self
   */
  function __construct($code = null, $date = null) {
    if (CModule::getActive('oxPyxvital') && CAppUI::gconf('pyxVital General mode') == 'test') {
      $this->date = CMbDT::date();

      if (CAppUI::gconf('pyxVital General date_ccam')) {
        $this->date = CAppUI::gconf('pyxVital General date_ccam');
      }
    }
    else {
      $this->date = CMbDT::date($date);
    }

    if (!$code || strlen($code) > 7) {
      if (!preg_match("/^[A-Z]{4}[0-9]{3}(-[0-9](-[0-9])?)?$/i", $code)) {
         return "Le code $code n'est pas formaté correctement";
      }

      // Cas ou l'activite et la phase sont indiquées dans le code (ex: BFGA004-1-0)
      $detailCode = explode("-", $code);
      $this->code = strtoupper($detailCode[0]);
      $this->_activite = $detailCode[1];
      if (count($detailCode) > 2) {
        $this->_phase = $detailCode[2];
      }
    }
    else {
      $this->code = strtoupper($code);
    }

    return null;
  }

  static $cache_layers = Cache::OUTER;

  /**
   * Chargement optimisé des codes CCAM
   *
   * @param string $code Code CCAM
   * @param string $date Date de référence
   *
   * @return CDatedCodeCCAM
   */
  static function get($code, $date = null) {

    // Cache by copy needed : OUTER
    $date = CMbDT::date($date);
    $cache = new Cache(__METHOD__, array($code, $date), self::$cache_layers);
    if ($cache->exists()) {
      return $cache->get();
    }

    $code_ccam = new CDatedCodeCCAM($code, $date);
    $code_ccam->load();

    return $cache->put($code_ccam, true);
  }

  /**
   * Chargement complet d'un code
   * en fonction du niveau de profondeur demandé
   *
   * @return bool
   */
  function load() {
    $this->_ref_code_ccam = CCodeCCAM::get($this->code);
    $this->_date = CMbDT::format($this->date, "%Y%m%d");

    if (!$this->getLibelles()) {
      return false;
    }
    $this->getTarification();
    $this->getForfaitSpec();

    $this->getChaps();
    $this->getRemarques();
    $this->getActivites();

    $this->getActesAsso();
    $this->getActesIncomp();
    $this->getProcedure();
    $this->getActivite7();
    $this->getExtensions();
    $this->getSpecialites();

    return true;
  }

  /**
   * @inheritdoc
   */
  function __sleep() {
    $vars = get_object_vars($this);
    unset($vars["_ref_code_ccam"]);
    return array_keys($vars);
  }

  /**
   * @inheritdoc
   */
  function __wakeup() {
    $this->_ref_code_ccam = CCodeCCAM::get($this->code);
  }

  /**
   * Récuparation des libellés du code
   *
   * @return bool etat de validité de l'acte cherché
   */
  function getLibelles() {
    // Vérification que le code est actif à la date donnée
    if ($this->_ref_code_ccam->date_fin != "00000000" && $this->_ref_code_ccam->date_fin < $this->_date) {
      $this->code = "-";
      //On rentre les champs de la table actes
      $this->libelleCourt = "Acte inconnu ou supprimé";
      $this->libelleLong = "Acte inconnu ou supprimé";
      $this->_code7 = 1;
      return false;
    }

    $this->libelleCourt = $this->_ref_code_ccam->libelle_court;
    $this->libelleLong  = $this->_ref_code_ccam->libelle_long;
    $this->type         = $this->_ref_code_ccam->type_acte;
    return true;
  }

  /**
   * Vérification de l'existence du moficiateur 7 pour l'acte
   *
   * @return void
   */
  function getActivite7() {
    $this->_code7 = 0;
    foreach ($this->activites as $activite) {
      foreach ($activite->modificateurs as $modificateur) {
        if ($modificateur->code == "7") {
          $this->_code7 = 1;
        }
      }
    }
  }

  /**
   * Récupération de la possibilité de remboursement de l'acte et la nécéssité d'une entente préalable
   *
   * @return int l'admission au remboursement
   */
  function getTarification() {
    foreach ($this->_ref_code_ccam->_ref_infotarif as $dateeffet => $infotarif) {
      if ($this->_date >= $dateeffet) {
        $this->remboursement = $infotarif->admission_rbt;
        $this->entente_prealable = $infotarif->entente == 'O';
        return $this->remboursement;
      }
    }
    return 0;
  }

  /**
   * Récupération du type de forfait de l'acte
   * (forfait spéciaux des listes SEH)
   *
   * @return void
   */
  function getForfaitSpec() {
    $this->forfait = $this->_ref_code_ccam->_forfait;
  }

  /**
   * Chargement des chapitres de l'acte
   *
   * @return void
   */
  function getChaps() {
    if ($this->place) {
      return;
    }
    $this->couleur = $this->_couleursChap[intval($this->_ref_code_ccam->arborescence[1]["db"])];
    $this->chapitres[0]["db"]   = $this->_ref_code_ccam->arborescence[1]["db"];
    $this->place = $this->chapitres[0]["rang"] = $this->_ref_code_ccam->arborescence[1]["rang"];
    $this->chapitres[0]["code"] = $this->_ref_code_ccam->arborescence[1]["code"];
    $this->chapitres[0]["nom"]  = $this->_ref_code_ccam->arborescence[1]["nom"];
    $this->chapitres[0]["rq"]   = $this->_ref_code_ccam->arborescence[1]["rq"];
    if (isset($this->_ref_code_ccam->arborescence[2]["rang"])) {
      $this->chapitres[1]["db"]   = $this->_ref_code_ccam->arborescence[2]["db"];
      $this->place = $this->chapitres[1]["rang"] = $this->_ref_code_ccam->arborescence[2]["rang"];
      $this->chapitres[1]["code"] = $this->_ref_code_ccam->arborescence[2]["code"];
      $this->chapitres[1]["nom"]  = $this->_ref_code_ccam->arborescence[2]["nom"];
      $this->chapitres[1]["rq"]   = $this->_ref_code_ccam->arborescence[2]["rq"];
    }
    if (isset($this->_ref_code_ccam->arborescence[3]["rang"])) {
      $this->chapitres[2]["db"]   = $this->_ref_code_ccam->arborescence[3]["db"];
      $this->place = $this->chapitres[2]["rang"] = $this->_ref_code_ccam->arborescence[3]["rang"];
      $this->chapitres[2]["code"] = $this->_ref_code_ccam->arborescence[3]["code"];
      $this->chapitres[2]["nom"]  = $this->_ref_code_ccam->arborescence[3]["nom"];
      $this->chapitres[2]["rq"]   = $this->_ref_code_ccam->arborescence[3]["rq"];
    }
    if (isset($this->_ref_code_ccam->arborescence[4]["rang"])) {
      $this->chapitres[3]["db"]   = $this->_ref_code_ccam->arborescence[4]["db"];
      $this->place = $this->chapitres[3]["rang"] = $this->_ref_code_ccam->arborescence[4]["rang"];
      $this->chapitres[3]["code"] = $this->_ref_code_ccam->arborescence[4]["code"];
      $this->chapitres[3]["nom"]  = $this->_ref_code_ccam->arborescence[4]["nom"];
      $this->chapitres[3]["rq"]   = $this->_ref_code_ccam->arborescence[4]["rq"];
    }
  }

  /**
   * Chargement des remarques sur l'acte
   *
   * @return void
   */
  function getRemarques() {
    $this->remarques = array();
    foreach ($this->_ref_code_ccam->_ref_notes as $note) {
      $this->remarques[] = str_replace("¶", "\n", $note->texte);
    }
  }

  /**
   * Chargement des activités de l'acte
   *
   * @return array La liste des activités
   */
  function getActivites() {
    $this->getChaps();
    foreach ($this->_ref_code_ccam->_ref_activites as $activite) {
      $datedActivite = new CObject();
      $datedActivite->numero  = $activite->code_activite;
      $datedActivite->type    = $activite->_libelle_activite;
      $datedActivite->libelle = "";
      // On ne met pas l'activité 1 pour les actes du chapitre 18.01
      if ($this->chapitres[0]["db"] != "000018" || $this->chapitres[1]["db"] != "000001" || $datedActivite->numero != "1") {
        $this->activites[$datedActivite->numero] = $datedActivite;
      }
    }
    // Libellés des activités
    foreach ($this->remarques as $remarque) {
      $match = null;
      if (preg_match("/Activité (\d) : (.*)/i", $remarque, $match)) {
        $this->activites[$match[1]]->libelle = $match[2];
      }
    }
    // Détail des activités
    foreach ($this->activites as &$activite) {
      $this->getPhasesFromActivite($activite);
      $this->getModificateursFromActivite($activite);
    }
    // Test de la présence d'activité virtuelle
    /*
    if (isset($this->activites[1]) && isset($this->activites[4])) {
      if (isset($this->activites[1]->phases[0]) && isset($this->activites[4]->phases[0])) {
        if ($this->activites[1]->phases[0]->tarif && !$this->activites[4]->phases[0]->tarif) {
          unset($this->activites[4]);
        }
        if (!$this->activites[1]->phases[0]->tarif && $this->activites[4]->phases[0]->tarif) {
          unset($this->activites[1]);
        }
      }
    }
    */
    $this->_default = reset($this->activites);
    if (isset($this->_default->phases[0])) {
      $this->_default = $this->_default->phases[0]->tarif;
    }
    else {
      $this->_default = 0;
    }

    return $this->activites;
  }

  /**
   * Récupération des modificateurs d'une activité
   *
   * @param object $activite Activité concernée
   *
   * @return void
   */
  function getModificateursFromActivite(&$activite) {
    // Extraction des modificateurs
    $activite->modificateurs = [];
    $listModificateurs = [];
    $selected_effect_date = null;
    ksort($this->_ref_code_ccam->_ref_activites[$activite->numero]->_ref_modificateurs);
    foreach ($this->_ref_code_ccam->_ref_activites[$activite->numero]->_ref_modificateurs as $dateEffet => $liste) {
      if ($this->_date >= $dateEffet) {
        $selected_effect_date = $dateEffet;
      }
    }

    $modificateurs_actifs = CCodeCCAM::getModificateursActifs($this->_date);
    if (array_key_exists($selected_effect_date, $this->_ref_code_ccam->_ref_activites[$activite->numero]->_ref_modificateurs)) {
      // Ajout des modificateurs normaux
      foreach ($this->_ref_code_ccam->_ref_activites[$activite->numero]->_ref_modificateurs[$selected_effect_date] as $modificateur) {
        // Cas d'un modificateur de convergence
        if (strpos($modificateurs_actifs, $modificateur->modificateur) !== false) {
          $_modif                       = new CObject();
          $_modif->code                 = $modificateur->modificateur;
          $_modif->libelle              = $modificateur->_libelle;
          $_modif->_checked             = null;
          $_modif->_state               = null;
          $_modif->_double              = "1";
          $activite->modificateurs[$_modif->code] = $_modif;
        }
      }

      foreach ($activite->phases as $_phase) {
        // Ajout des modificateurs pour les phases dont le tarif existe
        $_phase->_modificateurs = [];
        /* Les modificateurs  doivent être clonés pour éviter les problèmes posés par la copie par référence dans le cas ou il y a plusieurs phases pour une activité */
        foreach ($activite->modificateurs as $code => $modificateur) {
          $_phase->_modificateurs[$code] = clone $modificateur;
        }
      }
    }
  }

  /**
   * Récupération des phases d'une activité
   *
   * @param array $activite Activité concernée
   *
   * @return void
   */
  function getPhasesFromActivite($activite) {
    $activite->phases = array();
    $phases =& $activite->phases;
    $infoPhase = null;
    foreach ($this->_ref_code_ccam->_ref_activites[$activite->numero]->_ref_phases as $phase) {
      foreach ($phase->_ref_classif as $dateEffet => $info) {
        if ($dateEffet <= $this->_date) {
          $infoPhase = $info;
          break;
        }
      }
      $datedPhase               = new CObject();
      $datedPhase->phase        = $phase->code_phase;
      $datedPhase->libelle      = "Phase Principale";
      $datedPhase->nb_dents     = intval($phase->nb_dents);
      $datedPhase->dents_incomp = $phase->_ref_dents_incomp;
      if ($infoPhase) {
        $datedPhase->tarif_g01  = floatval($infoPhase->prix_unitaire_g01) / 100;
        $datedPhase->tarif_g02  = floatval($infoPhase->prix_unitaire_g02) / 100;
        $datedPhase->tarif_g03  = floatval($infoPhase->prix_unitaire_g03) / 100;
        $datedPhase->tarif_g04  = floatval($infoPhase->prix_unitaire_g04) / 100;
        $datedPhase->tarif_g05  = floatval($infoPhase->prix_unitaire_g05) / 100;
        $datedPhase->tarif_g06  = floatval($infoPhase->prix_unitaire_g06) / 100;
        $datedPhase->tarif_g07  = floatval($infoPhase->prix_unitaire_g07) / 100;
        $datedPhase->tarif_g08  = floatval($infoPhase->prix_unitaire_g08) / 100;
        $datedPhase->tarif_g09  = floatval($infoPhase->prix_unitaire_g09) / 100;
        $datedPhase->tarif_g10  = floatval($infoPhase->prix_unitaire_g10) / 100;
        $datedPhase->tarif_g11  = floatval($infoPhase->prix_unitaire_g11) / 100;
        $datedPhase->tarif_g12  = floatval($infoPhase->prix_unitaire_g12) / 100;
        $datedPhase->tarif_g13  = floatval($infoPhase->prix_unitaire_g13) / 100;
        $datedPhase->tarif_g14  = floatval($infoPhase->prix_unitaire_g14) / 100;
        $datedPhase->tarif_g15  = floatval($infoPhase->prix_unitaire_g15) / 100;
        $datedPhase->tarif_g16  = floatval($infoPhase->prix_unitaire_g16) / 100;
        $datedPhase->tarif      = $datedPhase->tarif_g14;
        $datedPhase->charges    = floatval($infoPhase->charge_cab) / 100;
        $datedPhase->coeff_dom = $infoPhase->coeff_dom;
      }
      else {
        $datedPhase->tarif_g01  = 0;
        $datedPhase->tarif_g02  = 0;
        $datedPhase->tarif_g03  = 0;
        $datedPhase->tarif_g04  = 0;
        $datedPhase->tarif_g05  = 0;
        $datedPhase->tarif_g06  = 0;
        $datedPhase->tarif_g07  = 0;
        $datedPhase->tarif_g08  = 0;
        $datedPhase->tarif_g09  = 0;
        $datedPhase->tarif_g10  = 0;
        $datedPhase->tarif_g11  = 0;
        $datedPhase->tarif_g12  = 0;
        $datedPhase->tarif_g13  = 0;
        $datedPhase->tarif_g14  = 0;
        $datedPhase->tarif_g15  = 0;
        $datedPhase->tarif_g16  = 0;
        $datedPhase->tarif      = 0;
        $datedPhase->charges    = 0;
        $datedPhase->coeff_dom  = [];
      }

      /* Ordre des tarifs décroissants pour l'activité 1
       * On utilise le tarif de la grille 03 car il ne sera pas égal à 0 à partir du 01/01/18
       */
      if ($activite->numero == "1") {
        if ($datedPhase->tarif_g03 != 0) {
          $this->_sorted_tarif = 1 / $datedPhase->tarif_g03;
        }
        else {
          $this->_sorted_tarif = 1;
        }
      }
      elseif ($this->_sorted_tarif === null) {
        $this->_sorted_tarif = 2;
      }

      // Ajout de la phase
      $phases[$phase->code_phase] = $datedPhase;
    }

    // Libellés des phases
    foreach ($this->remarques as $remarque) {
      if (preg_match("/Phase (\d) : (.*)/i", $remarque, $match)) {
        if (isset($phases[$match[1]])) {
          $phases[$match[1]]->libelle = $match[2];
        }
      }
    }
  }

  /**
   * Récupération des codes associés d'une activité
   *
   * @param array  $activite Activité concernée
   * @param string $code     Chaine de caractère à trouver dans les résultats
   * @param int    $limit    Nombre max de codes retournés
   *
   * @return void
   */
  function getAssoFromActivite(&$activite, $code = null, $limit = null) {
    // Extraction des phases
    $assos = array();
    $anesth_comp = '';
    if ($this->type == 2) {
      $activite->assos = $assos;
      $activite->anesth_comp = $anesth_comp;
      return;
    }
    $listeAsso = array();
    foreach ($this->_ref_code_ccam->_ref_activites[$activite->numero]->_ref_associations as $dateEffet => $liste) {
      if ($dateEffet <= $this->_date) {
        $listeAsso = $liste;
        break;
      }
    }
    /** @var CActiviteAssociationCCAM $asso */
    foreach ($listeAsso as $asso) {
      $assos[$asso->acte_asso]["code"]  = $asso->_ref_code["CODE"];
      $assos[$asso->acte_asso]["texte"] = $asso->_ref_code["LIBELLELONG"];
      $assos[$asso->acte_asso]["type"]  = $asso->_ref_code["TYPE"];

      /* Vérification si l'un des codes associés est une anesthésie complémentaire */
      if (in_array($asso->acte_asso, array('ZZLP008', 'ZZLP012', 'ZZLP025', 'ZZLP030', 'ZZLP042', 'ZZLP054' ))) {
        $anesth_comp = $asso->acte_asso;
      }
    }
    $this->assos = array_merge($this->assos, $assos);
    $activite->assos = $assos;
    $activite->anesth_comp = $anesth_comp;
  }

  /**
   * Récupération des actes associés (compléments / suppléments)
   *
   * @param string $code  Chaine de caractère à trouver dans les résultats
   * @param int    $limit Nombre max de codes retournés
   *
   * @return void
   */
  function getActesAsso($code = null, $limit = null) {
    foreach ($this->activites as &$activite) {
      $this->getAssoFromActivite($activite, $code, $limit);
    }
  }

  /**
   * Récupération de la liste des actes incompatibles à l'acte
   *
   * @return void
   */
  function getActesIncomp() {
    $incomps    = array();
    $listIncomp = array();
    foreach ($this->_ref_code_ccam->_ref_incompatibilites as $dateEffet => $liste) {
      if ($dateEffet <= $this->_date) {
        $listIncomp = array_merge($listIncomp, $liste);
      }
    }
    /** @var $incomp CIncompatibiliteCCAM */
    foreach ($listIncomp as $incomp) {
      $incomps[$incomp->code_incomp]["code"]  = $incomp->_ref_code["CODE"];
      $incomps[$incomp->code_incomp]["texte"] = $incomp->_ref_code["LIBELLELONG"];
      $incomps[$incomp->code_incomp]["type"]  = $incomp->_ref_code["TYPE"];
    }

    $this->incomps = $incomps;
  }

  /**
   * Récupération de la première procédure liée à l'acte
   *
   * @return void
   */
  function getProcedure() {
    $listProc = array();
    foreach ($this->_ref_code_ccam->_ref_procedures as $dateEffet => $liste) {
      if ($dateEffet <= $this->_date) {
        $listProc = $liste;
        break;
      }
    }
    if (count($listProc)) {
      $procedure = reset($listProc);
      $this->procedure["code"]  = $procedure->_ref_code["CODE"];
      $this->procedure["texte"] = $procedure->_ref_code["LIBELLELONG"];
      $this->procedure["type"]  = $procedure->_ref_code["TYPE"];
    }
    else {
      $this->procedure["code"]  = "";
      $this->procedure["texte"] = "";
      $this->procedure["type"]  = "";
    }
  }

  /**
   * Récupération du forfait d'un modificateur
   *
   * @param string $modificateur Lettre clé du modificateur
   * @param string $grille       La grille de tarif a utiliser
   * @param string $date         Date de référence
   *
   * @return array forfait et coefficient
   */
  function getForfait($modificateur, $grille = '14', $date = null) {
    return CCodeCCAM::getForfait($modificateur, $grille, $date);
  }

  /**
   * Récupération du coefficient d'association
   *
   * @param string $code Code d'association
   *
   * @return float
   */
  function getCoeffAsso($code) {
    return CCodeCCAM::getCoeffAsso($code);
  }

  /**
   * Load the PMSI extensions
   *
   * @return CExtensionPMSI[]
   */
  public function getExtensions() {
    return $this->extensions = CExtensionPMSI::loadList($this->code, $this->date);
  }

  /**
   * Charge la liste des spécialités autorisées pour ce code
   *
   * @return array
   */
  public function getSpecialites() {
    $this->specialites = array();

    $specialites = array();
    $selected_effect_date = null;

    /** @var CInfoTarifCCAM $info_tarif */
    ksort($this->_ref_code_ccam->_ref_infotarif);
    foreach ($this->_ref_code_ccam->_ref_infotarif as $effect_date => $info_tarif) {
      if ($this->_date >= $info_tarif->date_effet) {
        $selected_effect_date = $effect_date;
      }

      $specialites[$effect_date] = array();
      foreach ($info_tarif->prescripteur as $item) {
        if ($item['db'] != '') {
          $specialites[$effect_date][] = $item['db'];
        }
      }
    }

    if (array_key_exists($selected_effect_date, $specialites)) {
      $this->specialites = $specialites[$selected_effect_date];
    }

    return $this->specialites;
  }

  /**
   * @param CMediusers $user    The user to get the facturation context
   * @param CPatient   $patient The patient to get the facturation context
   * @param string     $date    The date
   *
   * @return void
   */
  public function getPrice($user = null, $patient = null, $date = null) {
    if (!$user) {
      return;
    }

    if (!$patient) {
      $patient = new CPatient();
    }

    $date = CMbDT::date($date);

    $field = 'tarif_g' . CContexteTarifaireCCAM::getPriceGrid($user, $patient, $date);
    $dom_code = CContexteTarifaireCCAM::getDOMCode($user);

    foreach ($this->activites as $activite) {
      foreach ($activite->phases as $phase) {
        $phase->tarif = $phase->$field;

        /* Application du coefficient pour les DOM/TOM */
        if ($dom_code && array_key_exists($dom_code, $phase->coeff_dom)) {
          $phase->tarif *= $phase->coeff_dom[$dom_code];
        }
      }
    }
  }

  /**
   * Check wether an acte is a complement or not
   *
   * @return bool
   */
  function isComplement() {
    $this->getChaps();
    return (isset($this->chapitres[1]) && $this->chapitres[1]['rang'] == '18.02.') || $this->type == '2';
  }

  /**
   * Check wether an acte is a supplement or not
   *
   * @return bool
   */
  function isSupplement() {
    $this->getChaps();
    return isset($this->chapitres[1]) && $this->chapitres[1]['rang'] == '19.02.';
  }

  /**
   * Check wether an acte is inclued in 'acte d'imagerie pour acte de radiologie interventionnelle
   * ou cardiologie interventionnelle'
   *
   * @return bool
   */
  function isRadioCardioInterv() {
    $this->getChaps();
    return isset($this->chapitres[3]) && $this->chapitres[3]['rang'] == '19.01.09.02.';
  }

  /**
   * Recherche de codes CCAM
   *
   * @param string $code       Codes partiels à chercher
   * @param string $keys       Mot clés à chercher
   * @param int    $max_length Longueur maximum du code
   * @param string $where      Autres paramètres where
   *
   * @return array Tableau d'actes
   */
  function findCodes($code='', $keys='', $max_length = null, $where = null) {
    $checkDateFin = "(`DATEFIN` = '00000000' OR `DATEFIN` >= '" . CMbDT::format($this->date, '%Y%m%d') . "')";
    if ($where) {
      $where .= " AND $checkDateFin";
    }
    else {
      $where = $checkDateFin;
    }

    return CCodeCCAM::findCodes($code, $keys, $max_length, $where);
  }

  /**
   * Chargement des actes voisins
   *
   * @return array|CDatedCodeCCAM
   */
  function loadActesVoisins() {
    $query = "SELECT CODE
    FROM p_acte
    WHERE DATEFIN = '00000000' ";
    foreach ($this->chapitres as $_key => $_chapitre ) {
      $chapitre_db = $_chapitre["db"];
      switch ($_key) {
        case "0":
          $query .= " AND ARBORESCENCE1 = '$chapitre_db'";
          break;

        case "1":
          $query .= " AND ARBORESCENCE2 = '$chapitre_db'";
          break;

        case "2":
          $query .= " AND ARBORESCENCE3 = '$chapitre_db'";
          break;

        case "3":
          $query .= " AND ARBORESCENCE4 = '$chapitre_db'";
          break;

        default:
      }
    }
    $query .= " ORDER BY CODE LIMIT 0 , 100";
    $acte_voisins = array();

    $ds = CSQLDataSource::get("ccamV2");
    $result       = $ds->exec($query);
    while ($row = $ds->fetchArray($result)) {
      $acte_voisin = CDatedCodeCCAM::get($row["CODE"]);
      $acte_voisin->_ref_code_ccam->date_creation = preg_replace(
        '/^(\d{4})(\d{2})(\d{2})$/', '\\3/\\2/\\1', $acte_voisin->_ref_code_ccam->date_creation
      );
      $acte_voisins[] = $acte_voisin;
    }

    return $acte_voisins;
  }

  /**
   * Check if the code is allowed for the given user (based on the specialty of the user)
   *
   * @param CMediusers $user The user
   *
   * @return boolean
   */
  public function isCodeAllowedForUSer($user) {
    $is_allowed = false;

    switch ($user->spec_cpam_id) {
      case 21:
        $is_allowed = in_array('SF', $this->specialites);
        break;
      case 19:
      case 53:
      case 54:
        $is_allowed = in_array('D1', $this->specialites);
        break;
      case 36:
        $is_allowed = in_array('D2', $this->specialites);
        break;
      default:
        $is_allowed = in_array('TS', $this->specialites) || (empty($this->specialites) || !isset($this->specialites));
    }

    return $is_allowed;
  }

  /**
   * Change date format yyyyddmm at yyyy/mm/dd
   *
   * @param string $dateFrom Date
   *
   * @return string format yyyy/mm/dd
   */
  static function mapDateFrom($dateFrom) {
    return preg_replace('/^(\d{4})(\d{2})(\d{2})$/', '\\3/\\2/\\1', $dateFrom);
  }

  /**
   * Change date format yyyy/mm/dd at yyyymmdd
   *
   * @param string $dateTo Date
   *
   * @return string format yyyymmdd
   */
  static function mapDateToSlash($dateTo) {
    $date = explode("/", $dateTo);
    return $date[2].$date[1].$date[0];
  }

  /**
   * Change date format yyyy-mm-dd at yyyymmdd
   *
   * @param string $dateTo Date
   *
   * @return string format yyyymmdd
   */
  static function mapDateToDash($dateTo) {
    $date = explode("-", $dateTo);
    return $date[0].$date[1].$date[2];
  }

  /**
   * Change date format yyyymmdd at yyyy-mm-dd
   *
   * @param string $dateTo Date
   *
   * @return string format yyyy-mm-dd
   */
  static function mapDateFromToDash($dateTo) {
    return preg_replace('/^(\d{4})(\d{2})(\d{2})$/', '\\1-\\2-\\3', $dateTo);
  }

  /**
   * Change date format yyyy/mm/dd at yyyy-mm-dd
   *
   * @param string $dateTo Date
   *
   * @return string format yyyy-mm-dd
   */
  static function mapSlashToDash($dateTo) {
    $date = explode("/", $dateTo);

    return $date[2] ."-". $date[1]. "-" . $date[0]. "T00:00:00";
  }
}
