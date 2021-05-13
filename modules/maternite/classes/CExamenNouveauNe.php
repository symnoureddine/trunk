<?php

/**
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Maternite;

use DateTime;
use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CMbObjectSpec;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\PlanSoins\CAdministration;
use Ox\Mediboard\Prescription\CPrescriptionLineElement;

/**
 * Suivi des examens des nouveaux n�s
 */
class CExamenNouveauNe extends CMbObject
{
    // DB Table key
    /** @var int */
    public $examen_nouveau_ne_id;

    /** @var int */
    public $grossesse_id;
    /** @var int */
    public $examinateur_id;
    /** @var int */
    public $naissance_id;

    /** @var string */
    public $date;
    /** @var int */
    public $poids;
    /** @var float */
    public $taille;
    /** @var float */
    public $pc;
    /** @var int */
    public $bip;

    // Inspection
    /** @var string */
    public $coloration_globale;
    /** @var string */
    public $revetement_cutane;
    /** @var string */
    public $etat_trophique;

    // Examen cardio-pulmonaire
    /** @var string */
    public $auscultation;
    /** @var string */
    public $pouls_femoraux;
    /** @var string */
    public $ta;

    // T�te
    /** @var string */
    public $crane;
    /** @var string */
    public $face_yeux;
    /** @var string */
    public $cavite_buccale;
    /** @var string */
    public $fontanelles;
    /** @var string */
    public $sutures;
    /** @var string */
    public $cou;

    // Abdomen
    /** @var string */
    public $foie;
    /** @var string */
    public $rate;
    /** @var string */
    public $reins;
    /** @var string */
    public $ombilic;
    /** @var string */
    public $orifices_herniaires;
    /** @var string */
    public $ligne_mediane_posterieure;
    /** @var string */
    public $region_sacree;
    /** @var string */
    public $anus;

    // Organes g�nitaux externes
    /** @var string */
    public $jet_mictionnel;

    // Examen orthop�dique
    /** @var string */
    public $clavicules;
    /** @var string */
    public $hanches;
    /** @var string */
    public $mains;
    /** @var string */
    public $pieds;

    // Examen neurologique
    /** @var string */
    public $cri;
    /** @var string */
    public $reactivite;
    /** @var string */
    public $tonus_axial;
    /** @var string */
    public $tonus_membres;
    /** @var string */
    public $reflexes_archaiques;

    // Divers
    /** @var string */
    public $test_audition;
    /** @var string */
    public $oreille_droite; //OEA
    /** @var string */
    public $oreille_gauche; //OEA
    /** @var string */
    public $rdv_orl;

    /** @var DateTime */
    public $guthrie_datetime;
    /** @var int */
    public $guthrie_user_id;
    /** @var string */
    public $guthrie_envoye;

    // Estimation du d�veloppement foetal
    /** @var int */
    public $est_age_gest;
    /** @var string */
    public $dev_ponderal;
    /** @var string */
    public $croiss_ponderale;
    /** @var string */
    public $croiss_staturale;

    /** @var array */
    public $_oea_exam;

    /** @var CGrossesse */
    public $_ref_grossesse;
    /** @var CNaissance */
    public $_ref_naissance;
    /** @var CMediusers */
    public $_ref_guthrie_user_id;

    /** @var int */
    public $_jours;

    /**
     * @see parent::getSpec()
     */
    public function getSpec(): CMbObjectSpec
    {
        $spec        = parent::getSpec();
        $spec->table = 'examen_nouveau_ne';
        $spec->key   = 'examen_nouveau_ne_id';

        return $spec;
    }

    /**
     * @see parent::getProps()
     */
    public function getProps(): array
    {
        $props                   = parent::getProps();
        $props["grossesse_id"]   = "ref notNull class|CGrossesse back|examens_nouveau_ne";
        $props["examinateur_id"] = "ref notNull class|CMediusers back|exams_bebes";
        $props["naissance_id"]   = "ref notNull class|CNaissance back|exams_bebe";
        $props["date"]           = "date";

        $props["poids"]  = "num";
        $props["taille"] = "float";
        $props["pc"]     = "float";
        $props["bip"]    = "num";

        $props["coloration_globale"] = "text";
        $props["revetement_cutane"]  = "text";
        $props["etat_trophique"]     = "text";

        $props["auscultation"]   = "text";
        $props["pouls_femoraux"] = "text";
        $props["ta"]             = "text";

        $props["crane"]          = "text";
        $props["face_yeux"]      = "text";
        $props["cavite_buccale"] = "text";
        $props["fontanelles"]    = "text";
        $props["sutures"]        = "text";
        $props["cou"]            = "text";

        $props["foie"]                      = "text";
        $props["rate"]                      = "text";
        $props["reins"]                     = "text";
        $props["ombilic"]                   = "text";
        $props["orifices_herniaires"]       = "text";
        $props["ligne_mediane_posterieure"] = "text";
        $props["region_sacree"]             = "text";
        $props["anus"]                      = "text";

        $props["jet_mictionnel"] = "text";

        $props["clavicules"] = "text";
        $props["hanches"]    = "text";
        $props["mains"]      = "text";
        $props["pieds"]      = "text";

        $props["cri"]                 = "text";
        $props["reactivite"]          = "text";
        $props["tonus_axial"]         = "text";
        $props["tonus_membres"]       = "text";
        $props["reflexes_archaiques"] = "text";

        $props["test_audition"]  = "text";
        $props["oreille_droite"] = "enum list|positif|negatif";
        $props["oreille_gauche"] = "enum list|positif|negatif";
        $props["rdv_orl"]        = "date";

        $props["guthrie_datetime"] = "dateTime";
        $props["guthrie_user_id"]  = "ref class|CMediusers back|guthrie_exams";
        $props["guthrie_envoye"]   = "bool";

        $props["est_age_gest"]     = "num";
        $props["dev_ponderal"]     = "enum list|hypo|eutro|hyper";
        $props["croiss_ponderale"] = "enum list|rest|norm|exces";
        $props["croiss_staturale"] = "enum list|rest|norm|exces";

        $props["_jours"] = "num";

        return $props;
    }

    /**
     * Chargement de la grossesse
     *
     * @return CGrossesse
     * @throws Exception
     */
    public function loadRefGrossesse(): CGrossesse
    {
        return $this->_ref_grossesse = $this->loadFwdRef("grossesse_id", true);
    }

    /**
     * Chargement de la naissance
     *
     * @return CNaissance
     * @throws Exception
     */
    public function loadRefNaissance(): CNaissance
    {
        return $this->_ref_naissance = $this->loadFwdRef("naissance_id", true);
    }

    /**
     * Load the Guthrie user
     *
     * @return CMediusers
     * @throws Exception
     */
    public function loadRefGuthrieUser(): CMediusers
    {
        return $this->_ref_guthrie_user_id = $this->loadFwdRef("guthrie_user_id", true);
    }

    /**
     * Calcul du nombre de jours du nouveau n� � l'examen
     *
     * @return int
     * @throws Exception
     */
    public function getJours(): int
    {
        $this->loadRefNaissance();

        //Rajoute de 1 pour que le jour de naissance du nouveau n� soit J0
        return $this->_jours = CMbDT::daysRelative($this->_ref_naissance->date_time, $this->date) + 1;
    }

    /**
     * Get the information of the Oto Emissions Acoustiques exam
     * TODO : Lier les administrations/prescription � l'examen (ajout attribut- ref fonction - update template
     *
     * @return array
     * @throws Exception
     */
    public function getOEAExam(): array
    {
        $naissance     = $this->loadRefNaissance();
        $sejour        = $naissance->loadRefSejourEnfant();
        $prescription  = $sejour->loadRefPrescriptionSejour();
        $exam_audition = [];

        if (!$prescription->_id) {
            return $exam_audition;
        }

        $elt_oea_id = explode(":", CAppUI::gconf("maternite CNaissance elt_oea"))[0];

        $where                            = [];
        $where["prescription_id"]         = " = '$prescription->_id'";
        $where["element_prescription_id"] = " = '$elt_oea_id'";

        $line_element = new CPrescriptionLineElement();

        $lines_element = $line_element->loadList($where);

        if (count($lines_element)) {
            foreach ($lines_element as $_line_element) {
                $administrations = $_line_element->loadRefsAdministrations(
                    [CMbDT::date($sejour->entree), CMbDT::date($sejour->sortie)]
                );

                foreach ($administrations as $_administration) {
                    $_administration->loadRefAdministrateur();

                    $exam_audition[$_administration->_id]["datetime"]               = $_administration->dateTime;
                    $exam_audition[$_administration->_id]["administrateur"]["guid"] =
                        $_administration->_ref_administrateur->_guid;
                    $exam_audition[$_administration->_id]["administrateur"]["view"] =
                        $_administration->_ref_administrateur->_view;
                    $exam_audition[$_administration->_id]["oreille_droite"]         = "";
                    $exam_audition[$_administration->_id]["oreille_gauche"]         = "";
                    $exam_audition[$_administration->_id]["rdv_orl"]                = "";

                    $date_adm = CMbDT::date($_administration->dateTime);

                    $this->date         = $date_adm;
                    $this->naissance_id = $naissance->_id;
                    $this->loadMatchingObject();

                    if ($this->_id) {
                        $exam_audition[$_administration->_id]["oreille_droite"] = $this->oreille_droite;
                        $exam_audition[$_administration->_id]["oreille_gauche"] = $this->oreille_gauche;
                        $exam_audition[$_administration->_id]["rdv_orl"]        = $this->rdv_orl;
                    }
                }
            }
        }

        return $this->_oea_exam = $exam_audition;
    }

    /**
     * Check the Guthrie exam
     *
     * @return void
     * @throws Exception
     */
    public function checkGuthrieExam(): void
    {
        $naissance    = $this->loadRefNaissance();
        $sejour       = $naissance->loadRefSejourEnfant();
        $prescription = $sejour->loadRefPrescriptionSejour();

        if ($prescription->_id) {
            $elt_guthrie_id = explode(":", CAppUI::gconf("maternite CNaissance elt_guthrie"))[0];

            $where                            = [];
            $where["prescription_id"]         = " = '$prescription->_id'";
            $where["element_prescription_id"] = " = '$elt_guthrie_id'";

            $line_element = new CPrescriptionLineElement();
            $line_element->loadObject($where);

            if ($line_element->_id) {
                $administrations     = $line_element->loadRefsAdministrations(
                    [CMbDT::date($sejour->entree), CMbDT::date($sejour->sortie)],
                    null,
                    "dateTime ASC"
                );
                $last_administration = end($administrations);

                /** @var CAdministration $last_administration */
                if ($last_administration && $last_administration->_id) {
                    $last_administration->loadRefAdministrateur();

                    if (!$this->guthrie_datetime && !$this->guthrie_user_id) {
                        $this->guthrie_datetime = $last_administration->dateTime;
                        $this->guthrie_user_id  = $last_administration->_ref_administrateur->_id;
                        $this->store();
                    }
                }
            }
        }
    }
}
