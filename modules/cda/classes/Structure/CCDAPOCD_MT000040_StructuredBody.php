<?php

/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Structure;

use Ox\Cli\SvnClient\Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Interop\Cda\CCDAFactory;
use Ox\Interop\Cda\Datatypes\Base\CCDABL;
use Ox\Interop\Cda\Datatypes\Base\CCDACD;
use Ox\Interop\Cda\Datatypes\Base\CCDACE;
use Ox\Interop\Cda\Datatypes\Base\CCDACS;
use Ox\Interop\Cda\Datatypes\Base\CCDAED;
use Ox\Interop\Cda\Datatypes\Base\CCDAEN;
use Ox\Interop\Cda\Datatypes\Base\CCDAII;
use Ox\Interop\Cda\Datatypes\Base\CCDAIVL_TS;
use Ox\Interop\Cda\Datatypes\Base\CCDAIVXB_TS;
use Ox\Interop\Cda\Datatypes\Base\CCDAPQ;
use Ox\Interop\Cda\Datatypes\Base\CCDAST;
use Ox\Interop\Cda\Datatypes\Base\CCDATEL;
use Ox\Interop\Cda\Datatypes\Datatype\CCDAIVL_PQ;
use Ox\Interop\Cda\Datatypes\Datatype\CCDAIVXB_PQ;
use Ox\Interop\Cda\Datatypes\Datatype\CCDAPIVL_TS;
use Ox\Interop\Cda\Datatypes\Voc\CCDAActClass;
use Ox\Interop\Cda\Datatypes\Voc\CCDAActClinicalDocument;
use Ox\Interop\Cda\Datatypes\Voc\CCDAActMood;
use Ox\Interop\Cda\Rim\CCDARIMAct;
use Ox\Interop\Dmp\CDMPValueSet;
use Ox\Interop\InteropResources\CInteropResources;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Ccam\CDatedCodeCCAM;
use Ox\Mediboard\Cim10\CCodeCIM10;
use Ox\Mediboard\Loinc\CLoinc;
use Ox\Mediboard\Patients\CAntecedent;
use Ox\Mediboard\Patients\CPathologie;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Mpm\CPrescriptionLineMedicament;
use Ox\Mediboard\Snomed\CSnomed;

/**
 * POCD_MT000040_StructuredBody Class
 */
class CCDAPOCD_MT000040_StructuredBody extends CCDARIMAct {

  /**
   * @var CCDAPOCD_MT000040_Component3
   */
  public $component = array();

  /**
   * Setter confidentialityCode
   *
   * @param CCDACE $inst CCDACE
   *
   * @return void
   */
  function setConfidentialityCode(CCDACE $inst) {
    $this->confidentialityCode = $inst;
  }

  /**
   * Getter confidentialityCode
   *
   * @return CCDACE
   */
  function getConfidentialityCode() {
    return $this->confidentialityCode;
  }

  /**
   * Setter languageCode
   *
   * @param CCDACS $inst CCDACS
   *
   * @return void
   */
  function setLanguageCode(CCDACS $inst) {
    $this->languageCode = $inst;
  }

  /**
   * Getter languageCode
   *
   * @return CCDACS
   */
  function getLanguageCode() {
    return $this->languageCode;
  }

  /**
   * Ajoute l'instance spécifié dans le tableau
   *
   * @param CCDAPOCD_MT000040_Component3 $inst CCDAPOCD_MT000040_Component3
   *
   * @return void
   */
  function appendComponent(CCDAPOCD_MT000040_Component3 $inst) {
    array_push($this->component, $inst);
  }

  /**
   * Efface le tableau
   *
   * @return void
   */
  function resetListComponent() {
    $this->component = array();
  }

  /**
   * Getter component
   *
   * @return CCDAPOCD_MT000040_Component3[]
   */
  function getComponent() {
    return $this->component;
  }

  /**
   * Assigne classCode à DOCBODY
   *
   * @return void
   */
  function setClassCode() {
    $actClass = new CCDAActClass();
    $actClass->setData("DOCBODY");
    $this->classCode = $actClass;
  }

  /**
   * Getter classCode
   *
   * @return CCDAActClass
   */
  function getClassCode() {
    return $this->classCode;
  }

  /**
   * Assigne moodCode à EVN
   *
   * @return void
   */
  function setMoodCode() {
    $actMood = new CCDAActMood();
    $actMood->setData("EVN");
    $this->moodCode = $actMood;
  }

  /**
   * Getter moodCode
   *
   * @return CCDAActMood
   */
  function getMoodCode() {
    return $this->moodCode;
  }

  /**
   * Create component element
   *
   * @return CCDAPOCD_MT000040_Component3
   */
  function createComponent3() {
    return new CCDAPOCD_MT000040_Component3();
  }

  /**
   * Create component element
   *
   * @return CCDAPOCD_MT000040_Component5
   */
  function createComponent5() {
    return new CCDAPOCD_MT000040_Component5();
  }

  /**
   * Create section element
   *
   * @return CCDAPOCD_MT000040_Section
   */
  function createSection() {
    return new CCDAPOCD_MT000040_Section();
  }

  /**
   * Create elements component, section and templateId
   *
   * @param array                     $component      component
   * @param CCDAFactory               $factory        factory
   * @param CCDAPOCD_MT000040_Section $section_parent section
   *
   * @return void
   */
  function createStructure($component, CCDAFactory $factory, $section_parent = null) {
    // Création de l'élément component
    $comp = $section_parent ? $this->createComponent5() : $this->createComponent3();

    // Parcours des sections
    foreach ($component as $_section) {
      // Création de l'élément section
      $sec  = $this->createSection();

      // Ajout Id
      $root = new CCDAII();
      $root->setRoot(CCDAActClinicalDocument::generateUUID());
      $sec->setId($root);

      $templates = CMbArray::get($_section, "templates");
      // Ajout des templates sur la section
      if ($templates) {
        foreach ($templates as $_template) {

          // Vérification si on doit mettre component/section ou non
          $function_check_name = CMbArray::get(CCDAFactory::$check_creation_child_component, $_template->root->data);
          if ($function_check_name && method_exists($this, $function_check_name) && !$this->$function_check_name($sec, $factory)) {
            continue 2;
          }

          // Création de l'élement templateId
          $sec->appendTemplateId($_template);

          $text_content = null;

          switch ($_template->root->data) {
            // TODO : Pour le moment on ne remplit pas cette partie là
            case "1.2.250.1.213.1.1.2.31":
              $text_content = "<table><tbody><tr><td>Aucun facteur de risque pour le patient</td></tr></tbody></table>";
              break;

            // TODO : Pour le moment on ne remplit pas cette partie là
            case "1.3.6.1.4.1.19376.1.5.3.1.3.27":
              $text_content = "<table><tbody><tr><td>Aucun point de vigilance pour le patient</td></tr></tbody></table>";
              break;

            // Raison de la recommandation
            case "1.3.6.1.4.1.19376.1.5.3.1.3.1":
              $reason = $factory->targetObject instanceof CConsultation ? $factory->targetObject->motif : $factory->targetObject->libelle;
              $text_content = "<table><tbody><tr><td>$reason</td></tr></tbody></table>";
              break;

            // TODO : Trouver une solution pour ne pas mettre les OIDs en dur
            // (ajout d'un espace pour avoir texte vide mais qu'il ne passe pas dans le purgeEmpty
            case "1.2.250.1.213.1.1.2.29":
              $text_content = " ";
              break;

            case "1.2.250.1.213.1.1.2.30":
              $text_content = $this->checkNecessaryTextForSection($factory->patient)
                ? "Aucune pathologie, aucun antécédent et aucune allergie"
                : null;

            default;
          }

          if ($text_content) {
            $text_observation = new CCDAED();
            $text_observation->setData($text_content);
            $sec->setText($text_observation);
          }
        }

        $first_template = reset($templates);
        // On s'assure d'avoir un attribut extension sur notre templateId
        if ($first_template instanceof CCDAII && $first_template->extension) {
          $this->addTitle($sec, $first_template->_title ? $first_template->_title : $first_template->extension->data);
          if ($first_template->_code_loinc) {
            $loinc = CLoinc::get($first_template->_code_loinc);
            $this->addCodeCE($sec, $loinc->code, CLoinc::$oid_loinc, $loinc->composant_fr, CLoinc::$name_loinc);
          }
        }
      }

      // Création des entry et text pour les sections
      $function_name = CMbArray::get($_section, "function");
      if ($function_name && method_exists($this, $function_name)) {
        $this->$function_name($sec, $factory);
      }

      $comp->setSection($sec);

      // Si la section contient d'autres components, on les crée récursivement
      $components_child = CMbArray::get($_section, "components");
      if ($components_child) {

        $child_build = true;

        // Verification si il faut bien créer les components child (par exemple cas de
        // la section Traitement au long cours si le patient n'a pas de traitement on ne crée pas les fils et on met une balise text)
        if ($templates) {
          foreach ($templates as $_template) {
            if (!$child_build) {
              continue;
            }

            $function_check_name = CMbArray::get(CCDAFactory::$check_creation_child_component, $_template->root->data);

            // Si on a pas de test à faire pour cette fonction, on continue
            if (!$function_check_name) {
              continue;
            }

            // Si la méthode n'existe pas, on continue
            if (!method_exists($this, $function_check_name)) {
              continue;
            }

            $child_build = $this->$function_check_name($sec, $factory);
          }
        }

        if ($child_build) {
          foreach ($components_child as $_component_child) {
            $this->createStructure($_component_child, $factory, $sec);
          }
        }
      }
    }

    $section_parent ? $section_parent->appendComponent($comp) : $this->appendComponent($comp);
  }

  /**
   * Check if necessary to put balise <text> or not
   *
   * @param CCDAPOCD_MT000040_Section $section
   * @param CCDAFactory               $factory
   *
   * @return bool
   */
  function checkNecessaryTextForSection($patient) {
    // Section avec texte obligatoire si aucune des 4 sous sections fille n'est présente
    // Vérification patho active, ATCD médical, ATDC chir, allergie

    $dossier_medical = $patient->loadRefDossierMedical();
    if (!$dossier_medical || !$dossier_medical->_id) {
      return true;
    }

    // 1er check : les pathologies
    $pathologies = $dossier_medical->loadRefsPathologies();
    /** @var CPathologie $_pathology */
    foreach ($pathologies as $_pathology) {
      // Pas de code CIM10 => on prend pas
      if (!$_pathology->code_cim10) {
        continue;
      }
      return false;
    }

    // 2ème check : les antécédents médicaux
    $antecedents = $dossier_medical->loadRefsAntecedentsOfType("med");
    /** @var CAntecedent $_antecedent */
    foreach ($antecedents as $_antecedent) {
      // Récupération des codes Snomed sur l'antécédent (on prend le premier code Snomed) => si on en a pas, next
      $codes_snomed = $_antecedent->loadBackRefs("atcd_snomed");
      if (!$_antecedent->loadRefsCodesSnomed()) {
        continue;
      }
      return false;
    }

    // 3ème check : les antécédents chirurgicaux
    $antecedents = $dossier_medical->loadRefsAntecedentsOfType("chir");
    /** @var CAntecedent $_antecedent */
    foreach ($antecedents as $_antecedent) {
      // On ajoute que les antécédents qui ont une date
      if (!$_antecedent->date) {
        continue;
      }

      // On prend les antécédents qui ont un code CCAM dans leur libellé
      if (!$this->checkNameCodeCCAM($_antecedent)) {
        continue;
      }
      return false;
    }

    // 4ème check : les allergies
    $allergies = $dossier_medical->loadRefsAntecedentsOfType("alle");

    /** @var CAntecedent $_antecedent */
    foreach ($allergies as $_allergy) {
      // On ajoute que les antécédents qui ont une date de début et une date de fin
      if (!$_allergy->date) {
        continue;
      }
      return false;
    }

    return true;
  }

  /**
   * Check if childs can be build for section "Pathologies actives"
   *
   * @param CCDAPOCD_MT000040_Section $section section
   * @param CCDAFactory               $factory factory
   *
   * @return bool
   */
  function checkPathologiesActives(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    $patient         = $factory->patient;
    $dossier_medical = $patient->loadRefDossierMedical();
    if (!$dossier_medical || !$dossier_medical->_id) {
      return false;
    }

    $pathologies = $dossier_medical->loadRefsPathologies();
    /** @var CPathologie $_pathology */
    foreach ($pathologies as $_pathology) {
      // Pas de code CIM10 => on prend pas
      if (!$_pathology->code_cim10) {
        continue;
      }
      return true;
    }
    return false;
  }

  /**
   * Check if childs can be build for section "Traitement au Long cours"
   *
   * @param CCDAPOCD_MT000040_Section $section section
   * @param CCDAFactory               $factory factory
   *
   * @return bool
   */
  function checkTraitementAuLongCours(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    // Section obligatoire pour le VSM
    return true;
  }

  /**
   * Check if we need to create section "Antécédents médicaux"
   *
   * @param CCDAPOCD_MT000040_Section $section
   * @param CCDAFactory               $factory
   *
   * @return bool
   */
  function checkAntecedentsMedicaux(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    $patient         = $factory->patient;
    $dossier_medical = $patient->loadRefDossierMedical();
    if (!$dossier_medical || !$dossier_medical->_id) {
      return false;
    }

    $antecedents = $dossier_medical->loadRefsAntecedentsOfType("med");
    /** @var CAntecedent $_antecedent */
    foreach ($antecedents as $_antecedent) {
      // Récupération des codes Snomed sur l'antécédent (on prend le premier code Snomed) => si on en a pas, next
      $codes_snomed = $_antecedent->loadBackRefs("atcd_snomed");
      if (!$_antecedent->loadRefsCodesSnomed()) {
        continue;
      }

      return true;
    }

    return false;
  }

  /**
   * Check if we need to create section "Antécédents chirurgicaux"
   *
   * @param CCDAPOCD_MT000040_Section $section
   * @param CCDAFactory               $factory
   *
   * @return bool
   */
  function checkAntecedentsChirurgicaux(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    $patient         = $factory->patient;
    $dossier_medical = $patient->loadRefDossierMedical();
    if (!$dossier_medical || !$dossier_medical->_id) {
      return false;
    }

    $antecedents = $dossier_medical->loadRefsAntecedentsOfType("chir");
    /** @var CAntecedent $_antecedent */
    foreach ($antecedents as $_antecedent) {
      // On ajoute que les antécédents qui ont une date
      if (!$_antecedent->date) {
        continue;
      }

      // On prend les antécédents qui ont un code CCAM dans leur libellé
      if (!$this->checkNameCodeCCAM($_antecedent)) {
        continue;
      }

      return true;
    }

    return false;
  }

  /**
   * Check if childs can be build for section "Facteurs de risques familiaux"
   *
   * @param CCDAPOCD_MT000040_Section $section section
   * @param CCDAFactory               $factory factory
   *
   * @return bool
   */
  function checkFacteursRisquesFamiliaux(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    return false;
  }

  /**
   * Check if childs can be build for section "Facteurs de risques professionnels"
   *
   * @param CCDAPOCD_MT000040_Section $section section
   * @param CCDAFactory               $factory factory
   *
   * @return bool
   */
  function checkFacteursRisquesProfessionels(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    return false;
  }

  /**
   * Check if childs can be build for section "Mode de vie"
   *
   * @param CCDAPOCD_MT000040_Section $section section
   * @param CCDAFactory               $factory factory
   *
   * @return bool
   */
  function checkMedications(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    // Section optionnelle pour la VSM
    $patient         = $factory->patient;
    $dossier_medical = $patient->loadRefDossierMedical();
    if (!$dossier_medical || !$dossier_medical->_id) {
      return false;
    }

    $prescription = $dossier_medical->loadRefPrescription();

    // Il nous faut que des traitements au long cours
    if ($prescription->_ref_prescription_lines) {
      /** @var CPrescriptionLineMedicament $_prescription_line */
      foreach ($prescription->_ref_prescription_lines as $_prescription_line) {
        $_prescription_line->loadRefsPrises();

        if (!$_prescription_line->long_cours) {
          continue;
        }
        return true;
      }
    }

    return false;
  }

  /**
   * Check if childs can be build for section "Mode de vie"
   *
   * @param CCDAPOCD_MT000040_Section $section section
   * @param CCDAFactory               $factory factory
   *
   * @return bool
   */
  function checkHabitus(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    return false;
  }

  /**
   * Check if childs can be build for section "Facteurs de risque"
   *
   * @param CCDAPOCD_MT000040_Section $section section
   * @param CCDAFactory               $factory factory
   *
   * @return bool
   */
  function checkFacteursRisques(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    // On met "true" puisque la section est obligatoire pour le VSM
    return true;
  }

  /**
   * Add content for section "Traitement au long cours"
   *
   * @param CCDAPOCD_MT000040_Section $section section
   * @param CCDAFactory               $factory factory
   *
   * @return void
   */
  function addTraitementAuLongCours(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    // On est obligé de mettre du texte s'il n'y a pas de sous section
    $patient         = $factory->patient;
    $dossier_medical = $patient->loadRefDossierMedical();
    if (!$dossier_medical || !$dossier_medical->_id) {
      $this->addText($section, 'Aucun traitement');
    }

    $prescription = $dossier_medical->loadRefPrescription();
    if (!$prescription->_ref_prescription_lines) {
      $this->addText($section, 'Aucun traitement');
    }
  }

  /**
   * Add content for section "Traitement à l'admission"
   *
   * @param CCDAPOCD_MT000040_Section $section section
   * @param CCDAFactory               $factory factory
   *
   * @return void
   */
  function addTraitementAdmission(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    $this->addTextSectionTraitement($section, $factory);
    $this->addMedicationEntries($section, $factory, __FUNCTION__);
  }

  function addTextSectionTraitement(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    $patient         = $factory->patient;
    $dossier_medical = $patient->loadRefDossierMedical();
    if (!$dossier_medical || !$dossier_medical->_id) {
      return;
    }

    $prescription = $dossier_medical->loadRefPrescription();

    // Il nous faut que des traitements au long cours
    $none_traitement = true;
    $text_content = '';
    if ($prescription->_ref_prescription_lines) {
      /** @var CPrescriptionLineMedicament $_prescription_line */
      foreach ($prescription->_ref_prescription_lines as $_prescription_line) {
        $_prescription_line->loadRefsPrises();

        if (!$none_traitement) {
          continue;
        }

        if (!$_prescription_line->long_cours) {
          continue;
        }

        $none_traitement = false;
      }

      $text_content = "<table><thead><tr><th>Date de début</th><th>Date de fin</th><th>Médicament</th><th>Posologie</th></tr></thead>";

      /** @var CPrescriptionLineMedicament $_prescription_line */
      foreach ($prescription->_ref_prescription_lines as $_prescription_line) {
        // A laisser sur une ligne pour eviter des retours à la ligne dans le CDA
        $text_content = $text_content . "<tbody><tr><td>$_prescription_line->debut</td><td>$_prescription_line->fin</td><td><content ID='{$_prescription_line->_guid}'>{$_prescription_line->_ref_produit->ucd_view}</content></td>";

        $prise_resume = "";
        foreach ($_prescription_line->_ref_prises as $_prise) {
          $prise_resume = $prise_resume == "" ? $prise_resume . $_prise : $prise_resume . ", " . $_prise;
        }
        $text_content = $text_content . "<td>{$prise_resume}</td></tr></tbody>";
      }

      $text_content = $text_content . "</table>";
    }

    $text_observation = new CCDAED();
    $text_observation->setData(
      $none_traitement === true ? "<table><tbody><tr><td><content ID='".CCDAFactory::NONE_TREATMENT."'>Aucun traitement au long cours pour le patient</content></td></tr></tbody></table>" : $text_content
    );

    $section->setText($text_observation);
  }

  /**
   * Add medication
   *
   * @param CCDAPOCD_MT000040_Section $section section
   * @param CCDAFactory               $factory factory
   *
   * @return void
   */
  function addMedications(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    // Ajout du code
    $this->addCodeCE($section, "10160-0", CLoinc::$oid_loinc, "Historique de la prise médicamenteuse", CLoinc::$name_loinc);
    // Ajout du title
    $this->addTitle($section, "Traitement");

    $this->addMedicationEntries($section, $factory, __FUNCTION__);
  }

  function addMedicationEntries(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory, $function_name) {
    $patient         = $factory->patient;
    $dossier_medical = $patient->loadRefDossierMedical();
    if (!$dossier_medical || !$dossier_medical->_id) {
      return;
    }

    // Ajout des entry
    $prescription = $dossier_medical->loadRefPrescription();
    if ($prescription->_ref_prescription_lines) {
      /** @var CPrescriptionLineMedicament $_prescription_line */
      foreach ($prescription->_ref_prescription_lines as $_prescription_line) {
        $_prescription_line->loadRefsPrises();

        // Ajout du text
        $text_content = "<table><thead><tr><th>Date de début</th><th>Date de fin</th><th>Médicament</th><th>Posologie</th></tr></thead>";

        // A laisser sur une ligne pour eviter des retours à la ligne dans le CDA
        $text_content = $text_content . "<tbody><tr><td>$_prescription_line->debut</td><td>$_prescription_line->fin</td><td><content ID='{$_prescription_line->_guid}'>{$_prescription_line->_ref_produit->ucd_view}</content></td>";

        $prise_resume = "";
        foreach ($_prescription_line->_ref_prises as $_prise) {
          $prise_resume = $prise_resume == "" ? $prise_resume . $_prise : $prise_resume . ", " . $_prise;
        }
        $text_content = $text_content . "<td>{$prise_resume}</td></tr></tbody></table>";

        $this->addText($section, $text_content);

        $entry = new CCDAPOCD_MT000040_Entry();
        $entry->setTypeCode("DRIV");

        // Ajout substanceAdministration
        $substanceAdministration = new CCDAPOCD_MT000040_SubstanceAdministration();
        $substanceAdministration->setClassCode();
        $substanceAdministration->setMoodCode("EVN");

        // Ajout des templatesId
        $templatesId = CMbArray::get(CCDAFactory::$mapping_function_with_templatesId, $function_name);
        if ($templatesId) {
          foreach ($templatesId as $_templateId) {
            $template = $factory->createTemplateID($_templateId);
            $substanceAdministration->appendTemplateId($template);
          }
        }

        // Ajout du templateId en fonction du mode d'administration
        $template = new CCDAII();
        $template->setRoot("1.3.6.1.4.1.19376.1.5.3.1.4.7.1");
        $substanceAdministration->appendTemplateId($template);

        // Ajout id sur substanceAdministration
        $this->addId($substanceAdministration);

        // Ajout element
        $this->addTextWithReference($substanceAdministration, "#".$_prescription_line->_guid);

        // StatusCode
        $this->addStatusCode($substanceAdministration, "completed");

        // EffectiveTime (début et fin de traitement)
        if (!$_prescription_line->debut || !$_prescription_line->fin) {
          $this->addNullFlavorEffectiveTime($substanceAdministration);
        }
        else {
          $this->appendLowAndHighTime(
            $substanceAdministration,
            $this->getDateForAntecedent($_prescription_line->debut),
            $this->getDateForAntecedent($_prescription_line->fin)
          );
        }

        // EffectiveTime (fréquence d'administration) and Dose quantity
        $this->addDoseAndFrequenceAdministration($substanceAdministration, $_prescription_line);

        // Add consumable
        $this->addConsumable($substanceAdministration, $_prescription_line);

        $entry->setSubstanceAdministration($substanceAdministration);
        $section->appendEntry($entry);
      }
    }
    // Entry Aucun Traitement personnel
    else {
      $this->addNoneTraitement($section, $factory, $function_name, $dossier_medical->absence_traitement);
    }
  }

  /**
   * Add content for section "Etat d'achevenment"
   *
   * @param CCDAPOCD_MT000040_Section $section section
   * @param CCDAFactory               $factory factory
   *
   * @return void
   */
  function addStatusDocument(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    if ($factory->targetObject instanceof CSejour) {
      $stateDoc = $factory->targetObject->_etat == 'cloture' ? 'Validé' : 'En cours';
    }
    elseif ($factory->targetObject instanceof CConsultation) {
      $stateDoc = $factory->targetObject->chrono == 64 ? 'Validé' : 'En cours';
    }
    else {
      $stateDoc = 'Validé';
    }

    // Ajout du texte
    $this->addText($section, '<table><tbody><tr><td>Statut du document</td><td><content ID="'.CCDAFactory::STATUS_DOCUMENT.'">'.$stateDoc.'</content></td></tr></tbody></table>');

    // Ajout des templatesId
    $templatesId = CMbArray::get(CCDAFactory::$mapping_function_with_templatesId, __FUNCTION__);
    if ($templatesId) {
      $this->addTemplatesId($section, $templatesId, $factory);
    }

    // Ajout entry
    $entry = new CCDAPOCD_MT000040_Entry();

    // Ajout simple observation
    $this->addSimpleObservation($entry, $factory,
      array('1.3.6.1.4.1.19376.1.5.3.1.4.13', '1.2.250.1.213.1.1.3.48', '1.2.250.1.213.1.1.3.48.16', '1.2.250.1.213.1.1.3.48.16.1'),
      'GEN-065', 'Statut du document', '1.2.250.1.213.1.1.4.322', CCDAFactory::TA_ASIP, "#".CCDAFactory::STATUS_DOCUMENT, 'completed',
      $stateDoc == 'En cours' ? 'GEN-066' : 'GEN-068', '1.2.250.1.213.1.1.4.322', $stateDoc, CCDAFactory::TA_ASIP, CMbDT::format(CMbDT::dateTime(), "%Y%m%d"));

    $section->appendEntry($entry);
  }

  /**
   * Add simple observation
   *
   * @param CCDAPOCD_MT000040_Entry $entry
   * @param CCDAFactory             $factory
   * @param array                   $templatesId
   * @param string                  $code
   * @param string                  $displayName
   * @param string                  $codeSystem
   * @param string                  $codeSystemName
   * @param string                  $referenceValue
   * @param string                  $statusCode
   * @param string                  $codeValue
   * @param string                  $valueCodeSystem
   * @param string                  $valueDisplayName
   * @param string                  $valueCodeSystemName
   * @param string                  $effectiveTime
   * @param string                  $valueText
   *
   * @return void
   */
  function addSimpleObservationWithValueText(CCDAPOCD_MT000040_Entry $entry, CCDAFactory $factory, $templatesId, $code, $displayName, $codeSystem,
                                $codeSystemName, $referenceValue, $statusCode, $effectiveTime, $valueText) {
    $observation = new CCDAPOCD_MT000040_Observation();
    $observation->setClassCode('OBS');
    $observation->setMoodCode('EVN');

    // Ajout des templatesId
    if ($templatesId) {
      foreach ($templatesId as $_templateId) {
        $template = $factory->createTemplateID($_templateId);
        $observation->appendTemplateId($template);
      }
    }

    // Ajout id
    $this->addId($observation);

    // Ajout code
    $this->addCodeCD($observation, $code, $codeSystem, $displayName, $codeSystemName);

    // Ajout texte
    $this->addTextWithReference($observation, $referenceValue);

    // Ajout statusCode
    $this->addStatusCode($observation, $statusCode);

    // Ajout effectiveTime
    $this->addEffectiveTIme($observation, $effectiveTime);

    // Ajout value text
    $text_value = new CCDAST();
    $text_value->setData($valueText);
    $observation->appendValue($text_value);


    $entry->setObservation($observation);
  }

    /**
     * Add simple observation
     *
     * @param CCDAPOCD_MT000040_Entry $entry
     * @param CCDAFactory             $factory
     * @param array                   $templatesId
     * @param string                  $code
     * @param string                  $displayName
     * @param string                  $codeSystem
     * @param string                  $codeSystemName
     * @param string                  $referenceValue
     * @param string                  $statusCode
     * @param string                  $codeValue
     * @param string                  $valueCodeSystem
     * @param string                  $valueDisplayName
     * @param string                  $valueCodeSystemName
     * @param string                  $effectiveTime
     * @param string                  $valueText
     *
     * @return void
     */
    public function addSimpleObservationWithValueBL(
        CCDAPOCD_MT000040_Entry $entry,
        CCDAFactory $factory,
        $templatesId,
        $code,
        $displayName,
        $codeSystem,
        $codeSystemName,
        $referenceValue,
        $statusCode,
        $effectiveTime,
        $valueBL
    ) {
        $observation = new CCDAPOCD_MT000040_Observation();
        $observation->setClassCode('OBS');
        $observation->setMoodCode('EVN');

        // Ajout des templatesId
        if ($templatesId) {
            foreach ($templatesId as $_templateId) {
                $template = $factory->createTemplateID($_templateId);
                $observation->appendTemplateId($template);
            }
        }

        // Ajout id
        $this->addId($observation);

        // Ajout code
        $this->addCodeCD($observation, $code, $codeSystem, $displayName, $codeSystemName);

        // Ajout texte
        $this->addTextWithReference($observation, $referenceValue);

        // Ajout statusCode
        $this->addStatusCode($observation, $statusCode);

        // Ajout effectiveTime
        $this->addEffectiveTIme($observation, $effectiveTime);

        // Ajout value text
        $text_value = new CCDABL();
        $text_value->setValue($valueBL);
        $observation->appendValue($text_value);

        $entry->setObservation($observation);
    }

  /**
   * Add simple observation
   *
   * @param CCDAPOCD_MT000040_Entry $entry
   * @param CCDAFactory             $factory
   * @param array                   $templatesId
   * @param string                  $code
   * @param string                  $displayName
   * @param string                  $codeSystem
   * @param string                  $codeSystemName
   * @param string                  $referenceValue
   * @param string                  $statusCode
   * @param string                  $codeValue
   * @param string                  $valueCodeSystem
   * @param string                  $valueDisplayName
   * @param string                  $valueCodeSystemName
   * @param string                  $effectiveTime
   *
   * @return void
   */
  function addSimpleObservation(CCDAPOCD_MT000040_Entry $entry, CCDAFactory $factory, $templatesId, $code, $displayName, $codeSystem,
      $codeSystemName, $referenceValue, $statusCode, $codeValue, $valueCodeSystem, $valueDisplayName, $valueCodeSystemName, $effectiveTime, $cd = true) {
    $observation = new CCDAPOCD_MT000040_Observation();
    $observation->setClassCode('OBS');
    $observation->setMoodCode('EVN');

    // Ajout des templatesId
    if ($templatesId) {
      foreach ($templatesId as $_templateId) {
        $template = $factory->createTemplateID($_templateId);
        $observation->appendTemplateId($template);
      }
    }

    // Ajout id
    $this->addId($observation);

    // Ajout code
    $this->addCodeCD($observation, $code, $codeSystem, $displayName, $codeSystemName);

    // Ajout texte
    $this->addTextWithReference($observation, $referenceValue);

    // Ajout statusCode
    $this->addStatusCode($observation, $statusCode);

    // Ajout effectiveTime
    $this->addEffectiveTIme($observation, $effectiveTime);

    // Ajout value
    $this->addValueCodeCIM10($observation, $codeValue, $valueCodeSystem, $valueDisplayName, true, null, $valueCodeSystemName, $cd);

    $entry->setObservation($observation);
  }

  /**
   * Add content for section "Motif d'hospitalisation" (raison de la recommandation)
   *
   * @param CCDAPOCD_MT000040_Section $section section
   * @param CCDAFactory               $factory factory
   *
   * @return void
   */
  function addMotifHospitalisation(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    // Ajout des templatesId
    $templatesId = CMbArray::get(CCDAFactory::$mapping_function_with_templatesId, __FUNCTION__);
    if ($templatesId) {
      $this->addTemplatesId($section, $templatesId, $factory);
    }

    $motif = '';
    if ($factory->targetObject instanceof CSejour) {
      $motif = $factory->targetObject->libelle;
    }
    elseif ($factory->targetObject instanceof CConsultation) {
      $motif = $factory->targetObject->motif;
    }

    // Ajout du texte
    $this->addText($section, '<table><tbody><tr><td>'.$motif.'</td></tr></tbody></table>');
  }

  /**
   * Add content for section "Evènements observés"
   *
   * @param CCDAPOCD_MT000040_Section $section section
   * @param CCDAFactory               $factory factory
   *
   * @return void
   */
  function addMedicalSynthesis(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    // Ajout des templatesId
    $templatesId = CMbArray::get(CCDAFactory::$mapping_function_with_templatesId, __FUNCTION__);
    if ($templatesId) {
      $this->addTemplatesId($section, $templatesId, $factory);
    }

    $sejour = $factory->targetObject;

    $text = '<table><thead><tr><th>Synthèse médicale du séjour</th></tr></thead>';
    $text .= '<tbody><tr><td>Modalité et date d\'entrée en hospitalisation</td><td><content ID="'.CCDAFactory::MODALITE_ENTREE.'">'
      .CMbDT::format($sejour->_entree, "%d/%m/%Y").', '
      .CAppUI::tr('CSejour.mode_entree.'.$sejour->mode_entree).'</content></td></tr>';

    $text .= '<tr><td>Modalité et date de sortie d\'hospitalisation</td><td><content ID="'.CCDAFactory::MODALITE_SORTIE.'">'
      .CMbDT::format($sejour->_sortie, "%d/%m/%Y").', '
      .CAppUI::tr('CSejour.mode_sortie.'.$sejour->mode_sortie).'</content></td></tr>';

    $text .= '<tr><td>Synthèse médicale</td><td><content ID="'.CCDAFactory::SYNTHESE.'">'.$sejour->libelle.'</content></td></tr> ';

    $text .= '<tr><td>Recherche de microorganismes multi-résistants ou émergents effectuée</td><td><content ID="'.CCDAFactory::RECHERCHE_MICRO_MULTI.'">Non</content></td></tr> ';

    $text .= '<tr><td>Transfusion de produits sanguins</td><td><content ID="'.CCDAFactory::TRANSFU.'">Non</content></td></tr> ';

    $text .= '<tr><td>Administration de dérivés du sang</td><td><content ID="'.CCDAFactory::ADMI_SANG.'">Non</content></td></tr> ';

    $text = $text.'</tbody></table>';

    // Ajout du texte
    $this->addText($section, $text);

    // Ajout entry Modalité d'entrée
    $values_mode_entree = CInteropResources::loadEntryJV(
      CMbArray::get(CDMPValueSet::$JDV, "modaliteEntree"),
      $sejour->mode_entree ? CMbArray::get(CCDAFactory::$mapping_mode_entree_jdv, $sejour->mode_entree) : 'GEN-092',
      CDMPValueSet::$type
    );

    $entry = new CCDAPOCD_MT000040_Entry();
    $this->addSimpleObservation($entry, $factory,
      array('1.3.6.1.4.1.19376.1.5.3.1.4.13', '1.2.250.1.213.1.1.3.48', '1.2.250.1.213.1.1.3.48.6'),
      'ORG-070', 'Modalité d\'entrée', '1.2.250.1.213.1.1.4.322', CCDAFactory::TA_ASIP, "#".CCDAFactory::MODALITE_ENTREE, 'completed',
      CMbArray::get($values_mode_entree, 'code'), CMbArray::get($values_mode_entree, 'codeSystem'),
      CMbArray::get($values_mode_entree, 'displayName'), CCDAFactory::TA_ASIP, CMbDT::format($sejour->_entree, "%Y%m%d"), false
      );

    $section->appendEntry($entry);

    // Ajout entry Modalité de sortie
    $values_mode_sortie = CInteropResources::loadEntryJV(
      CMbArray::get(CDMPValueSet::$JDV, "modaliteSortie"),
      $sejour->mode_sortie ? CMbArray::get(CCDAFactory::$mapping_mode_sortie_jdv, $sejour->mode_sortie) : 'GEN-092',
      CDMPValueSet::$type
    );

    $entry = new CCDAPOCD_MT000040_Entry();
    $this->addSimpleObservation($entry, $factory,
      array('1.3.6.1.4.1.19376.1.5.3.1.4.13', '1.2.250.1.213.1.1.3.48', '1.2.250.1.213.1.1.3.48.7'),
      'ORG-074', 'Modalité de sortie', '1.2.250.1.213.1.1.4.322', CCDAFactory::TA_ASIP, "#".CCDAFactory::MODALITE_SORTIE, 'completed',
      CMbArray::get($values_mode_sortie, 'code'), CMbArray::get($values_mode_sortie, 'codeSystem'),
      CMbArray::get($values_mode_sortie, 'displayName'), CCDAFactory::TA_ASIP, CMbDT::format($sejour->_sortie, "%Y%m%d"), false
    );

    $section->appendEntry($entry);

    // Ajout entry Synthèse médicale
    $entry = new CCDAPOCD_MT000040_Entry();
    $this->addSimpleObservationWithValueText(
      $entry, $factory,
      array('1.3.6.1.4.1.19376.1.5.3.1.4.13', '1.2.250.1.213.1.1.3.48', '1.2.250.1.213.1.1.3.48.9'),
      'MED-142', 'Synthèse médicale', '1.2.250.1.213.1.1.4.322', CCDAFactory::TA_ASIP, "#".CCDAFactory::SYNTHESE, 'completed',
      CMbDT::format(CMbDT::dateTime(), "%Y%m%d"), $factory->targetObject->libelle.". ". $factory->targetObject->rques
    );

    $section->appendEntry($entry);

    // TODO : A dev
    // Ajout entry Recherche-de-micro-organismes
      $entry = new CCDAPOCD_MT000040_Entry();
      $this->addSimpleObservationWithValueBL(
          $entry, $factory,
          array('1.3.6.1.4.1.19376.1.5.3.1.4.13', '1.2.250.1.213.1.1.3.48', '1.2.250.1.213.1.1.3.48.8'),
          'MED-309', 'Recherche de microorganismes multi-résistants ou émergents effectuée', '1.2.250.1.213.1.1.4.322', CCDAFactory::TA_ASIP, "#".CCDAFactory::RECHERCHE_MICRO_MULTI, 'completed',
          CMbDT::format(CMbDT::dateTime(), "%Y%m%d"), "false"
      );

      $section->appendEntry($entry);


      // TODO : A dev
      // Ajout entry Transfusion-de-produits-sanguins
      $entry = new CCDAPOCD_MT000040_Entry();
      $this->addSimpleObservationWithValueBL(
          $entry, $factory,
          array('1.3.6.1.4.1.19376.1.5.3.1.4.13', '1.2.250.1.213.1.1.3.48', '1.2.250.1.213.1.1.3.48.10'),
          'MED-145', 'Transfusion de produits sanguins', '1.2.250.1.213.1.1.4.322', CCDAFactory::TA_ASIP, "#".CCDAFactory::TRANSFU, 'completed',
          CMbDT::format(CMbDT::dateTime(), "%Y%m%d"), "false"
      );

      $section->appendEntry($entry);

      // TODO : A dev
      // Ajout entry Accidents-transfusionnels
      $entry = new CCDAPOCD_MT000040_Entry();
      $this->addSimpleObservationWithValueBL(
          $entry, $factory,
          array('1.3.6.1.4.1.19376.1.5.3.1.4.13', '1.2.250.1.213.1.1.3.48', '1.2.250.1.213.1.1.3.48.2'),
          'MED-147', 'Administration de dérivés du sang', '1.2.250.1.213.1.1.4.322', CCDAFactory::TA_ASIP, "#".CCDAFactory::ADMI_SANG, 'completed',
          CMbDT::format(CMbDT::dateTime(), "%Y%m%d"), "false"
      );

      $section->appendEntry($entry);
  }

  /**
   * Add content for section 'Traitements à la sortie'
   *
   * @param CCDAPOCD_MT000040_Section $section
   * @param CCDAFactory               $factory
   */
  function addTreatmentExit(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    $patient         = $factory->patient;
    $dossier_medical = $patient->loadRefDossierMedical();
    if (!$dossier_medical || !$dossier_medical->_id) {
      $this->addText($section, CAppUI::tr('CDA-msg-None treatment exit'));
      return;
    }

    $prescription = $dossier_medical->loadRefPrescription();
    if (!$prescription->_ref_prescription_lines) {
      $this->addText($section, "<table><tbody><tr><td><content ID='".CCDAFactory::NONE_TREATMENT."'>Aucun traitement à la sortie</content></td></tr></tbody></table>");
      $this->addNoneTraitement($section, $factory, 'addTraitementAdmission', $dossier_medical->absence_traitement);
      return;
    }

    $text_header = '<table border="1"><thead><tr><th>Date de début</th><th>Date de fin</th><th>Médicament</th><th>Posologie</th>'
      .'</tr></thead><tbody>';

    $treatment = false;

    // Il nous faut que des traitements qui ont commencé le jour de la sortie du séjour ou les TP commencés avant le séjour et qui continuent après la sortie
    if ($prescription->_ref_prescription_lines) {
      /** @var CPrescriptionLineMedicament $_prescription_line */
      foreach ($prescription->_ref_prescription_lines as $_prescription_line) {
        if ($_prescription_line->debut && $_prescription_line->debut == CMbDT::format($factory->targetObject->_sortie, '%Y-%m-%d')
          || ($_prescription_line->debut < CMbDT::format($factory->targetObject->_entree, '%Y-%m-%d') && $_prescription_line->fin > CMbDT::format($factory->targetObject->_sortie, '%Y-%m-%d'))) {

          $treatment = true;

          $text_header .= '<tr><td>'.CMbDT::format($_prescription_line->debut, '%Y-%m-%d').'</td>';
          $text_header .= '<td>'.CMbDT::format($_prescription_line->fin, '%Y-%m-%d').'</td>';
          $text_header .= "<td><content ID='{$_prescription_line->_guid}'>{$_prescription_line->_ref_produit->ucd_view}</content></td>";

          $prise_resume = "";
          foreach ($_prescription_line->loadRefsPrises() as $_prise) {
            $prise_resume = $prise_resume == "" ? $prise_resume . $_prise : $prise_resume . ", " . $_prise;
          }

          $text_header .= "<td>{$prise_resume}</td></tr>";
        }
      }
    }

    $text_header .= '</tbody></table>';

    $this->addText($section, $treatment ? $text_header : CAppUI::tr('CDA-msg-None treatment exit'));

    // Ajout des entry
    if ($treatment) {
      foreach ($prescription->_ref_prescription_lines as $_prescription_line) {
        if ($_prescription_line->debut && $_prescription_line->debut == CMbDT::format($factory->targetObject->_sortie, '%Y-%m-%d')
          || ($_prescription_line->debut < CMbDT::format($factory->targetObject->_entree, '%Y-%m-%d') && $_prescription_line->fin > CMbDT::format($factory->targetObject->_sortie, '%Y-%m-%d'))) {


          $entry = new CCDAPOCD_MT000040_Entry();
          $substanceAdministration = new CCDAPOCD_MT000040_SubstanceAdministration();
          $substanceAdministration->setClassCode('SBADM');
          $substanceAdministration->setMoodCode('EVN');

          // Ajout des templatesId
          $templatesId = CMbArray::get(CCDAFactory::$mapping_function_with_templatesId, __FUNCTION__);
          if ($templatesId) {
            foreach ($templatesId as $_templateId) {
              $template = $factory->createTemplateID($_templateId);
              $substanceAdministration->appendTemplateId($template);
            }
          }

          // Ajout du templateId en fonction du mode d'administration
          $template = new CCDAII();
          $template->setRoot("1.3.6.1.4.1.19376.1.5.3.1.4.7.1");
          $substanceAdministration->appendTemplateId($template);

          // Ajout id sur substanceAdministration
          $this->addId($substanceAdministration);

          // Ajout element
          $this->addTextWithReference($substanceAdministration, "#".$_prescription_line->_guid);

          // StatusCode
          $this->addStatusCode($substanceAdministration, "completed");

          // EffectiveTime (début et fin de traitement)
          if (!$_prescription_line->debut || !$_prescription_line->fin) {
            $this->addNullFlavorEffectiveTime($substanceAdministration);
          }
          else {
            $this->appendLowAndHighTime(
              $substanceAdministration,
              $this->getDateForAntecedent($_prescription_line->debut),
              $this->getDateForAntecedent($_prescription_line->fin)
            );
          }

          // EffectiveTime (fréquence d'administration) and Dose quantity
          $this->addDoseAndFrequenceAdministration($substanceAdministration, $_prescription_line);

          // Add consumable
          $this->addConsumable($substanceAdministration, $_prescription_line);

          $entry->setSubstanceAdministration($substanceAdministration);

          $section->appendEntry($entry);
        }
      }
    }
    else {
      $this->addNoneTraitement($section, $factory, 'addTraitementAdmission', $dossier_medical->absence_traitement);
    }
  }

  /**
   * Add entry 'Aucun traitement à l'admission'
   *
   * @param CCDAPOCD_MT000040_Section $section
   * @param CCDAFactory               $factory
   * @param string                    $function_name
   * @param bool                      $absence_traitement
   */
  function addNoneTraitement(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory, $function_name, $absence_traitement) {
    // Si absence traitement == 0 => On ne sait pas s'il y a ou pas des traitements (utiliser nullFlavoir à 'NA')
    // Si absence traitement == 1 => On sait que le patient n'a pas de traitement (utiliser nullFlavoir à 'NI')
    $entry = new CCDAPOCD_MT000040_Entry();
    $entry->setTypeCode("DRIV");

    // Ajout substanceAdministration
    $substanceAdministration = new CCDAPOCD_MT000040_SubstanceAdministration();
    $substanceAdministration->setClassCode();
    $substanceAdministration->setMoodCode("EVN");

    // Ajout des templatesId
    $templatesId = CMbArray::get(CCDAFactory::$mapping_function_with_templatesId, $function_name);
    if ($templatesId) {
      foreach ($templatesId as $_templateId) {
        $template = $factory->createTemplateID($_templateId);
        $substanceAdministration->appendTemplateId($template);
      }
    }

    // Ajout du templateId en fonction du mode d'administration
    $template = new CCDAII();
    $template->setRoot("1.3.6.1.4.1.19376.1.5.3.1.4.7.1");
    $substanceAdministration->appendTemplateId($template);

    // Ajout id sur substanceAdministration
    $this->addId($substanceAdministration);

    // Ajout du code MED-273 si on sait qu'il n'y aucun traitement
    if ($absence_traitement) {
      $code = new CCDACD();
      $code->setCode('MED-273');
      $code->setCodeSystem("1.2.250.1.213.1.1.4.322");
      $code->setDisplayName('Aucun traitement');
      $code->setCodeSystemName("TA-ASIP");
      $substanceAdministration->setCode($code);
    }

    // StatusCode
    $this->addStatusCode($substanceAdministration, "completed");

    // Text with reference
    $this->addTextWithReference($substanceAdministration, "#". CCDAFactory::NONE_TREATMENT);

    // Add consumable
    $this->addConsumableEmpty($substanceAdministration, $absence_traitement);

    $entry->setSubstanceAdministration($substanceAdministration);
    $section->appendEntry($entry);
  }

  /**
   * Add code CCAM on element
   *
   * @param object $element               element
   * @param object $produit               produit
   * @param string $content_original_text content original text
   *
   * @return void
   */
  function addCodeCIS($element, $produit, $content_original_text = null) {
    $code = new CCDACE();
    $code->setCode($produit->code_cis);
    $code->setCodeSystem("1.2.250.1.213.2.3.1");
    $code->setDisplayName($produit->libelle);
    $code->setCodeSystemName("CIS");

    if ($content_original_text) {
      $text_observation = new CCDAED();
      $text_reference_observation = new CCDATEL();
      $text_reference_observation->setValue($content_original_text);
      $text_observation->setReference($text_reference_observation);
      $code->setOriginalText($text_observation);
    }

    $element->setCode($code);
  }

  /**
   * Add consumable element
   *
   * @param CCDAPOCD_MT000040_SubstanceAdministration $element           element
   * @param CPrescriptionLineMedicament               $prescription_line prescription line
   *
   * @return void
   */
  function addConsumable(CCDAPOCD_MT000040_SubstanceAdministration $element, CPrescriptionLineMedicament $prescription_line) {
    $consumable = new CCDAPOCD_MT000040_Consumable();

    $manufacturedProduct = new CCDAPOCD_MT000040_ManufacturedProduct();

    $templatesId = array("1.3.6.1.4.1.19376.1.5.3.1.4.7.2", "2.16.840.1.113883.10.20.1.53");

    foreach ($templatesId as $_templateId) {
      $templateId = new CCDAII();
      $templateId->setRoot($_templateId);
      $manufacturedProduct->appendTemplateId($templateId);
    }

    $manufacturedMaterial = new CCDAPOCD_MT000040_Material();
    $produit = $prescription_line->loadRefProduit();
    $this->addCodeCIS($manufacturedMaterial, $produit, "#$prescription_line->_guid");

    $name = new CCDAEN();
    $name->setData($produit->ucd_view);
    $manufacturedMaterial->setName($name);

    $manufacturedProduct->setManufacturedMaterial($manufacturedMaterial);
    $consumable->setManufacturedProduct($manufacturedProduct);
    $element->setConsumable($consumable);
  }

  /**
   * Add consumable element empty
   *
   * @param CCDAPOCD_MT000040_SubstanceAdministration $element            element
   * @param bool                                      $absence_traitement absence traitement
   *
   * @return void
   */
    public function addConsumableEmpty(
        CCDAPOCD_MT000040_SubstanceAdministration $element,
        ?bool $absence_traitement
    ): void {
        $consumable = new CCDAPOCD_MT000040_Consumable();

        $manufacturedProduct = new CCDAPOCD_MT000040_ManufacturedProduct();

        $templatesId = array("1.3.6.1.4.1.19376.1.5.3.1.4.7.2", "2.16.840.1.113883.10.20.1.53");

        foreach ($templatesId as $_templateId) {
            $templateId = new CCDAII();
            $templateId->setRoot($_templateId);
            $manufacturedProduct->appendTemplateId($templateId);
        }

        $manufacturedMaterial = new CCDAPOCD_MT000040_Material();

        $code_empty = new CCDACE();
        $code_empty->setCode('MED-273');
        $code_empty->setCodeSystem("1.2.250.1.213.1.1.4.322");
        $code_empty->setDisplayName('Aucun traitement');
        $code_empty->setCodeSystemName("TA-ASIP");

        $text_observation           = new CCDAED();
        $text_reference_observation = new CCDATEL();
        $text_reference_observation->setValue('#' . CCDAFactory::NONE_TREATMENT);
        $text_observation->setReference($text_reference_observation);
        $code_empty->setOriginalText($text_observation);

        $manufacturedMaterial->setCode($code_empty);

        $manufacturedProduct->setManufacturedMaterial($manufacturedMaterial);
        $consumable->setManufacturedProduct($manufacturedProduct);
        $element->setConsumable($consumable);
    }

  /**
   * Add frequency administration
   *
   * @param CCDAPOCD_MT000040_SubstanceAdministration $element           element
   * @param CPrescriptionLineMedicament               $prescription_line prescription line
   *
   * @return void
   */
  function addDoseAndFrequenceAdministration(
    CCDAPOCD_MT000040_SubstanceAdministration $element, CPrescriptionLineMedicament $prescription_line
  ) {
    $frequence          = false;
    $moment_nb          = 0;
    $frequence_nb_fois  = 0;
    $frequence_quantity = 0;
    $unite_frequence    = "";
    $unit               = null;
    foreach ($prescription_line->_ref_prises as $_prise) {
      if ($_prise->unite_fois) {
        $frequence = true;

        $frequence_nb_fois = $frequence_nb_fois + $_prise->nb_fois;
        $frequence_quantity = $frequence_quantity + $_prise->quantite;
        $unite_frequence = $_prise->unite_fois;
      }
      else {
        $moment_nb = $moment_nb + $_prise->quantite;
      }

      // On récupère l'unité de la dernière poso (c'est sensé être la même pour toutes les posologies)
      $unit = $_prise->_libelle_unite_prescription;
    }

    $pivlTs = new CCDAPIVL_TS();
    // On met à la position 1 pour pouvoir valuer correctement le xsi:type lors de la génération du XML
    $pivlTs->position = 1;

    // Si on a la date de début, on met la phase
    if ($prescription_line->debut) {
      $ivlTs = new CCDAIVL_TS();

      // Low time
      $ivxbL = new CCDAIVXB_TS();
      $ivxbL->setValue($this->getDateForAntecedent($prescription_line->debut));
      $ivlTs->setLow($ivxbL);

      $pivlTs->setPhase($ivlTs);
    }

    // Ajout de la periode
    $cdaPQ = new CCDAPQ();

    if ($frequence) {
      $cdaPQ->setValue($frequence_nb_fois);
      $cdaPQ->setUnit($unite_frequence == "semaine" ? "wk" : "d");
    }
    else {
      $cdaPQ->setValue($moment_nb);
      $cdaPQ->setUnit("d");
    }
    $pivlTs->setPeriod($cdaPQ);

    $pivlTs->setOperator("A");
    $element->appendEffectiveTime($pivlTs);

    // Ajout de la dose
    $ivlPQ = new CCDAIVL_PQ();
    $min_dose = $max_dose = $frequence ? $frequence_quantity : $moment_nb;

    if ($unit) {
      if ($unit == "comprimés") {
        // basé sur UCUM
        $unit = "tbl";
      }
      // pour les unités dénombrables => pas d'unité à mettre
      elseif ($unit != "ml" || $unit != "mg") {
        $unit = null;
      }
    }

    // Low dose and unit
    $ivxbL = new CCDAIVXB_PQ();
    $ivxbL->setValue($min_dose);
    $ivxbL->setUnit($unit);
    $ivlPQ->setLow($ivxbL);

    // High dose and unit
    $ivxbL = new CCDAIVXB_PQ();
    $ivxbL->setValue($max_dose);
    $ivxbL->setUnit($unit);
    $ivlPQ->setHigh($ivxbL);

    $element->setDoseQuantity($ivlPQ);
  }

  /**
   * Add NullFlavor for datetime
   *
   * @param object $element                 element
   * @param bool   $append                  append
   * @param string $value                   value
   * @param bool   $nullFlavorEffectiveTime nullFlavorEffectiveTime
   *
   * @return void
   */
  function addNullFlavorLowTime($element, $append = true, $value = "UNK", $nullFlavorEffectiveTime = false) {
    // Low time
    $ivlTs = new CCDAIVL_TS();
    $ivxbL = new CCDAIVXB_TS();
    $ivxbL->setNullFlavor($value);
    $ivlTs->setLow($ivxbL);

    if ($nullFlavorEffectiveTime) {
      $ivlTs->setNullFlavor($value);
    }

    $append ? $element->appendEffectiveTime($ivlTs) : $element->setEffectiveTime($ivlTs);
  }

  /**
   * Add NullFlavor for datetime
   *
   * @param object $element element
   * @param bool   $append  append
   *
   * @return void
   */
  function addNullFlavorEffectiveTime($element, $null_flavor = 'UNK', $append = true) {
    // Low time
    $ivlTs = new CCDAIVL_TS();
    $ivxbL = new CCDAIVXB_TS();
    $ivxbL->setNullFlavor($null_flavor);
    $ivlTs->setLow($ivxbL);

    // High time
    $ivxbL = new CCDAIVXB_TS();
    $ivxbL->setNullFlavor($null_flavor);
    $ivlTs->setHigh($ivxbL);

    $append ? $element->appendEffectiveTime($ivlTs) : $element->setEffectiveTime($ivlTs);
  }

  /**
   * Add content for section "Pathologies actives"
   *
   * @param CCDAPOCD_MT000040_Section $section section
   * @param CCDAFactory               $factory factory
   *
   * @throws Exception
   *
   * @return void
   */
  function addPathologiesActives(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    $patient         = $factory->patient;
    $dossier_medical = $patient->loadRefDossierMedical();
    if (!$dossier_medical || !$dossier_medical->_id) {
      return;
    }

    $pathologies = $dossier_medical->loadRefsPathologies();
    // Ajout du text
    $pathologies_vsm = array();
    $content = "<list>";

    /** @var CPathologie $_pathology */
    foreach ($pathologies as $_pathology) {
      // Pas de code CIM10 => on prend pas
      if (!$_pathology->code_cim10) {
        continue;
      }

      $content = $content. "<item>{$_pathology->debut} : <content ID='{$_pathology->_guid}'>{$_pathology->_view}</content></item>";
      $pathologies_vsm[] = $_pathology;
    }
    $content = $content. "</list>";

    $this->addText($section, $content);

    // Construction de l'entry (1 entry avec plusieurs entryRelationShip
    $entry = new CCDAPOCD_MT000040_Entry();
    $act   = new CCDAPOCD_MT000040_Act();
    $act->setClassCode("ACT");
    $act->setMoodCode("EVN");
    // On ne sait pas si l'état clinique existe ou pas
    $act->setNegationInd("true");

    // Ajout des templatesId
    $templatesId = CMbArray::get(CCDAFactory::$mapping_function_with_templatesId, __FUNCTION__);
    if ($templatesId) {
      $this->addTemplatesId($act, $templatesId, $factory);
    }

    // Ajout Id
    $this->addId($act);

    // Ajout code (fixé à NullFlavor="NA")
    $this->addCodeNullFlavor($act);

    // Ajout statut entrée
    $value_set = new CDMPValueSet();
    $statusCode = $this->getStatusEntranceMB($factory->targetObject);
    $this->addStatusCode($act, CMbArray::get($value_set->getStatusEntrance($statusCode), "code"));

    // Ajout date entrée
    // Dans ce cas, on met Low et High
    if ($statusCode == "completed" || $statusCode == "aborted") {
      $this->addLowAndHighTime(
        $act, CMbDT::format(CMbArray::get($factory->service_event, "time_start"), "%Y%m%d"),
        CMbDT::format(CMbArray::get($factory->service_event, "time_stop"), "%Y%m%d")
      );
    }
    // Dans ce cas, on met que Low
    else {
      $this->addLowTime($act, CMbDT::format(CMbArray::get($factory->service_event, "time_start"), "%Y%m%d"));
    }

    // ajout des entryRelationShip
    foreach ($pathologies_vsm as $_pathology) {
      $entryRelationShip = new CCDAPOCD_MT000040_EntryRelationship();
      $entryRelationShip->setTypeCode("SUBJ");
      $entryRelationShip->setNegationInd("false");
      $entryRelationShip->setInversionInd("false");

      $observation = new CCDAPOCD_MT000040_Observation();
      $observation->setClassCode("OBS");
      $observation->setMoodCode("EVN");
      // Signifie que l'élément observé a eu lieu
      $observation->setNegationInd("false");

      // Ajout des templatesId
      $this->addTemplatesId($observation, array("2.16.840.1.113883.10.20.1.28", "1.3.6.1.4.1.19376.1.5.3.1.4.5"), $factory);

      // Ajout ID
      $this->addId($observation);

      // Ajout Code
      $code = $_pathology->type == "pathologie" ? "G-1009" : "F-01000";
      $data_code = $value_set->getProblemCode($code);
      $this->addCodeSnomed(
        $observation, $code, CMbArray::get($data_code, "codeSystem"), CMbArray::get($data_code, "displayName"), false
      );

      // Ajout text
      $this->addTextWithReference($observation, "#$_pathology->_guid");

      // Ajout status code
      $this->addStatusCode($observation, "completed");

      // Ajout effectiveTime
      $_pathology->debut ?
        $this->addLowTime($observation, $this->getDateForAntecedent($_pathology->debut))
        : $this->addNullFlavorLowTime($observation, false);

      // Ajout code CIM10
      $code_cim_10 = CCodeCIM10::get($_pathology->code_cim10);
      if ($code_cim_10) {
        $this->addValueCodeCIM10(
          $observation, $_pathology->code_cim10, CCodeCIM10::$OID, $code_cim_10->libelle, true, "#$_pathology->_guid"
        );
      }

      $entryRelationShip->setObservation($observation);
      $act->appendEntryRelationship($entryRelationShip);
    }

    $entry->setAct($act);
    $section->appendEntry($entry);
  }

  /**
   * Add code snomed in element like Observation
   *
   * @param object $element               element
   * @param string $code                  code
   * @param string $codeSystem            code system
   * @param string $displayName           display name
   * @param bool   $append                append | set code
   * @param string $content_original_text content original text
   * @param string $codeSystemName        code system name
   *
   * @return void
   */
  function addValueCodeCIM10(
    $element, $code, $codeSystem, $displayName, $append = true, $content_original_text = null, $codeSystemName = "CIM10", $cd = true
  ) {

    $code_element = $cd ? new CCDACD() : new CCDACE();
    $code_element->setCode($code);
    $code_element->setCodeSystem($codeSystem);
    $code_element->setDisplayName($displayName);
    $code_element->setCodeSystemName($codeSystemName);

    if ($content_original_text) {
      $text_observation = new CCDAED();
      $text_reference_observation = new CCDATEL();
      $text_reference_observation->setValue($content_original_text);
      $text_observation->setReference($text_reference_observation);
      $code_element->setOriginalText($text_observation);
    }

    $append ? $element->appendValue($code_element) : $element->setCode($code_element);
  }

  /**
   * Get status entrance
   *
   * @param CMbObject $target target
   *
   * @return string
   */
  function getStatusEntranceMB($target) {
    // ATTENTION : On peut utiliser que "active", "suspended", "aborted" ou "completed" comme valeur du JDV
    $status = null;
    switch ($target->_class) {
      case "CConsultation":
        switch ($target->_etat) {
          case "Ann.":
            $status = "cancelled";
            break;
          case "Plan.":
            $status = "held";
            break;
          case "En cours":
            $status = "active";
            break;
          case "Term.":
            $status = "completed";
            break;
          default:
            $status = "active";
        }
        break;
      case "CConsultAnesth":
        $consultation = $target->loadRefConsultation();
        switch ($consultation->_etat) {
          case "Ann.":
            $status = "cancelled";
            break;
          case "Plan.":
            $status = "held";
            break;
          case "En cours":
            $status = "active";
            break;
          case "Term.":
            $status = "completed";
            break;
          default:
            $status = "active";
        }
        break;
      case "COperation":
        if ($target->debut_op && $target->fin_op) {
          $status = "completed";
        }
        elseif ($target->debut_op && !$target->fin_op) {
          $status = "active";
        }
        else {
          $status = "held";
        }

        break;
      case "CSejour":
        switch ($target->_etat) {
          case "preadmission":
            $status = "active";
            break;
          case "encours":
            $status = "active";
            break;
          case "cloture":
            $status = "completed";
            break;
          default:
            $status = "completed";
        }

        if ($target->annule) {
          $status = "cancelled";
        }
        break;
      default;
    }

    return $status;
  }

  /**
   * Add templatesId on element
   *
   * @param object      $element      element
   * @param array       $templates_Id templatesId
   * @param CCDAFactory $factory      factory
   *
   * @return void
   */
  function addTemplatesId($element, $templates_Id, CCDAFactory $factory) {
    foreach ($templates_Id as $_templateId) {
      $template = $factory->createTemplateID($_templateId);
      $element->appendTemplateId($template);
    }
  }

  /**
   * Add section commentaire
   *
   * @param CCDAPOCD_MT000040_Section $section
   * @param CCDAFactory               $factory
   *
   * @return void
   */
  function addCommentaire(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {

      // Ajout des templatesId
      $templatesId = CMbArray::get(CCDAFactory::$mapping_function_with_templatesId, __FUNCTION__);
      if ($templatesId) {
          $this->addTemplatesId($section, $templatesId, $factory);
      }

    $comment = $factory->patient->rques ? $factory->patient->rques : 'Aucune autre information utile';
    $text    = '<table><thead><tr><th colspan="3">Commentaires</th></tr></thead><tbody><tr><td><content ID="commentUtile">'.$comment.'</content></td></tr></tbody></table>';

    $this->addText($section, $text);

    $entry       = new CCDAPOCD_MT000040_Entry();
    $observation = new CCDAPOCD_MT000040_Observation();

    $observation->setClassCode("OBS");
    $observation->setMoodCode("EVN");

    // Ajout des templatesId
    $this->addTemplatesId($observation, array("1.3.6.1.4.1.19376.1.5.3.1.4.13"), $factory);

    // Ajout ID
    $this->addId($observation);

    // Ajout du code
    $this->addCodeCD($observation, 'GEN-089', '1.2.250.1.213.1.1.4.322', 'Description', CCDAFactory::TA_ASIP);

    // Ajout du texte
    $this->addTextWithReference($observation, 'commentUtile');

    // Ajout statusCode
    $this->addStatusCode($observation, 'completed');

    // Ajout effectiveTime (nullFlavor parce qu'on a pas la valeur dans notre système)
    $this->addEffectiveTIme($observation, null, 'NA');

    // Ajout value
    $value_object = new CCDAST();
    $value_object->setData($comment);
    $observation->appendValue($value_object);

    $entry->setObservation($observation);
    $section->appendEntry($entry);
  }

  /**
   * Add content for section "Antécédents médicaux"
   *
   * @param CCDAPOCD_MT000040_Section $section section
   * @param CCDAFactory               $factory factory
   *
   * @throws Exception
   * @return void
   */
  function addAntecedentsMedicaux(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    $patient         = $factory->patient;
    $dossier_medical = $patient->loadRefDossierMedical();
    if (!$dossier_medical || !$dossier_medical->_id) {
      return;
    }

    $antecedents = $dossier_medical->loadRefsAntecedentsOfType("med");
    $antecedents_vsm = array();
    /** @var CAntecedent $_antecedent */
    foreach ($antecedents as $_antecedent) {
      // Récupération des codes Snomed sur l'antécédent (on prend le premier code Snomed) => si on en a pas, next
      $codes_snomed = $_antecedent->loadBackRefs("atcd_snomed");
      if (!$_antecedent->loadRefsCodesSnomed()) {
        continue;
      }

      $antecedents_vsm[] = $_antecedent;
    }

    $text = "<list>";
    // Ajout du texte sur la section
    foreach ($antecedents_vsm as $_antecedent) {
      $text = $text. "<item>". $_antecedent->date. "<content ID='".$_antecedent->_guid."'>$_antecedent->rques</content></item>";
    }

    $text = $text."</list>";

    $this->addText($section, $text);

    // Ajout de l'entry et de l'act
    $entry = new CCDAPOCD_MT000040_Entry();
    $ac = new CCDAPOCD_MT000040_Act();
    $ac->setClassCode("ACT");
    $ac->setMoodCode("EVN");
    $ac->setNegationInd("true");

    // Ajout des templatesId
    $templatesId = CMbArray::get(CCDAFactory::$mapping_function_with_templatesId, __FUNCTION__);
    if ($templatesId) {
      $this->addTemplatesId($ac, $templatesId, $factory);
    }

    // Ajout de l'ID
    $this->addId($ac);

    // Ajout du code nullFlavor
    $this->addCodeNullFlavor($ac);

    // Ajout statut entrée
    $value_set = new CDMPValueSet();
    $statusCode = $this->getStatusEntranceMB($factory->targetObject);
    $this->addStatusCode($ac, CMbArray::get($value_set->getStatusEntrance($statusCode), "code"));

    // Ajout effectiveTime (date entrée)
    if ($statusCode == "completed" || $statusCode == "aborted") {
      $this->addLowAndHighTime(
        $ac, CMbDT::format(CMbArray::get($factory->service_event, "time_start"), "%Y%m%d"),
        CMbDT::format(CMbArray::get($factory->service_event, "time_stop"), "%Y%m%d")
      );
    }
    else {
      $this->addLowTime($ac, CMbDT::format(CMbArray::get($factory->service_event, "time_start"), "%Y%m%d"));
    }


    /** @var CAntecedent $_antecedent */
    foreach ($antecedents_vsm as $_antecedent) {
      // Ajout entryRelationShip pour chaque antécédent
      $entry_relation_ship = new CCDAPOCD_MT000040_EntryRelationship();
      $entry_relation_ship->setInversionInd("false");
      $entry_relation_ship->setTypeCode("SUBJ");
      $ac->appendEntryRelationship($entry_relation_ship);

      // Ajout observation sur entryRelationShip
      $observation = new CCDAPOCD_MT000040_Observation();
      $observation->setClassCode("OBS");
      $observation->setMoodCode("EVN");
      $observation->setNegationInd("false");

      // Ajout du templateId sur l'observation
      $template = $factory->createTemplateID("2.16.840.1.113883.10.20.1.28");
      $observation->appendTemplateId($template);
      $template = $factory->createTemplateID("1.3.6.1.4.1.19376.1.5.3.1.4.5");
      $observation->appendTemplateId($template);

      // Ajout de l'ID sur l'observation
      $this->addId($observation);

      // Ajout du code sur l'observation (pour des ATCD, on force le code Snomed à "Diagnostic" dans le JDV "JDV_ProblemCodes-CISIS")
      $this->addCodeCD($observation, "G-1009", "1.2.250.1.213.2.12", "Diagnostic", "SNOMED 3.5");

      // Ajout du text sur l'observation
      $this->addTextWithReference($observation, "#$_antecedent->_guid");

      // Ajout du StatusCode sur l'observation (valeur fixée à 'completed')
      $this->addStatusCode($observation, "completed");

      // Ajout effectiveTime sur l'observation
      $date_antecedent     = $this->getDateForAntecedent($_antecedent->date);
      $date_end_antecedent = $this->getDateForAntecedent($_antecedent->date_fin);
      if ($date_antecedent) {
        $this->addLowAndHighTime($observation, $date_antecedent, $date_end_antecedent ? $date_end_antecedent : null);
      }

      $_antecedent->date ?
        $this->addLowTime($observation, $this->getDateForAntecedent($_antecedent->date))
        : $this->addNullFlavorLowTime($observation, false);

      // Ajout du code Snomed (il en faut qu'un => on prend le premier)
      /** @var CSnomed $code_snomed */
      $code_snomed = reset($_antecedent->_ref_codes_snomed);
      if ($code_snomed && $code_snomed->_id) {
        $this->addCodeSnomed(
          $observation, $code_snomed->code, CSnomed::$oid_snomed, $code_snomed->libelle, true, "#$_antecedent->_guid"
        );
      }

      $entry_relation_ship->setObservation($observation);
    }

    $entry->setAct($ac);
    $section->appendEntry($entry);
  }

  /**
   * Add content for section "Antécédents chirurgicaux"
   *
   * @param CCDAPOCD_MT000040_Section $section section
   * @param CCDAFactory               $factory factory
   *
   * @return void
   */
  function addAntecedentsChirurgicaux(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    $patient         = $factory->patient;
    $dossier_medical = $patient->loadRefDossierMedical();
    if (!$dossier_medical || !$dossier_medical->_id) {
      return;
    }

    // Ajout d'un template
    $section->appendTemplateId($factory->createTemplateID("1.3.6.1.4.1.19376.1.5.3.1.3.11"));
    $section->appendTemplateId($factory->createTemplateID("2.16.840.1.113883.10.20.1.12"));

    $antecedents = $dossier_medical->loadRefsAntecedentsOfType("chir");
    $antecedents_vsm = array();
    /** @var CAntecedent $_antecedent */
    foreach ($antecedents as $_antecedent) {
      // On ajoute que les antécédents qui ont une date
      if (!$_antecedent->date) {
        continue;
      }

      // On prend les antécédents qui ont un code CCAM dans leur libellé
      if (!$this->checkNameCodeCCAM($_antecedent)) {
        continue;
      }

      $antecedents_vsm[] = $_antecedent;
    }

    $text = "<list>";
    // Ajout du texte sur la section
    foreach ($antecedents_vsm as $_antecedent) {
      $text = $text. "<item>". $_antecedent->date. "<content ID='$_antecedent->_guid'>$_antecedent->rques</content></item>";
    }

    $text = $text."</list>";
    $this->addText($section, $text);

    foreach ($antecedents_vsm as $_antecedent) {
      $entry = new CCDAPOCD_MT000040_Entry();
      $procedure = new CCDAPOCD_MT000040_Procedure();
      $procedure->setClassCode("PROC");
      $procedure->setMoodCode("EVN");

      // Ajout des templatesId
      $templatesId = CMbArray::get(CCDAFactory::$mapping_function_with_templatesId, __FUNCTION__);
      if ($templatesId) {
        $this->addTemplatesId($procedure, $templatesId, $factory);
      }

      // Ajout ID
      $root = $this->addId($procedure);

      // Code (Code CCAM)
      $this->addCodeCCAM($procedure, $_antecedent->_ref_code_ccam);

      // Text
      $this->addTextWithReference($procedure, "#$_antecedent->_guid");

      // StatusCode
      $this->addStatusCode($procedure, $_antecedent->annule ? "cancelled" : "completed");

      // effectiveTime
      $date_start_antecedent = $this->getDateForAntecedent($_antecedent->date);
      $date_end_antecedent   = $this->getDateForAntecedent($_antecedent->date_fin);
      if ($date_end_antecedent && $date_end_antecedent > $date_start_antecedent) {
          $this->addLowAndHighTime($procedure, $date_start_antecedent, $date_end_antecedent);
      }
      else {
          $this->addLowTime($procedure, $date_start_antecedent);
      }

      // Ajout du motif dd l'acte
      $this->addMotifActe($procedure, $_antecedent, $factory, $root);

      $entry->setProcedure($procedure);
      $section->appendEntry($entry);
    }
  }


  /**
   * Add content for section "Antécédents chirurgicaux"
   *
   * @param CCDAPOCD_MT000040_Section $section section
   * @param CCDAFactory               $factory factory
   *
   * @throws Exception
   * @return void
   */
  function addAllergies(CCDAPOCD_MT000040_Section $section, CCDAFactory $factory) {
    $patient         = $factory->patient;
    $dossier_medical = $patient->loadRefDossierMedical();
    if (!$dossier_medical || !$dossier_medical->_id) {
      return;
    }

    // Ajout du template sur la section des allergies
    $template = $factory->createTemplateID('2.16.840.1.113883.10.20.1.2');
    $section->appendTemplateId($template);

    $allergies                  = $dossier_medical->loadRefsAntecedentsOfType("alle");
    $allergies_vsm              = array();
    $last_update_list_allergies = null;

    /** @var CAntecedent $_antecedent */
    foreach ($allergies as $_allergy) {
      // On ajoute que les antécédents qui ont une date de début et une date de fin
      if (!$_allergy->date) {
        continue;
      }

      $last_update = $_allergy->loadLastLog();
      if (!$last_update_list_allergies || ($last_update_list_allergies < $last_update->date)) {
        $last_update_list_allergies = $last_update->date;
      }

      $allergies_vsm[] = $_allergy;
    }

    if ($allergies_vsm) {
      $text = "<table><thead><tr><th>Date</th><th>Allergie</th></tr></thead><tbody>";
      // Ajout du texte sur la section
      foreach ($allergies_vsm as $_allergy) {
        $text = $text. "<tr><td>$_allergy->date</td><td><content ID='$_allergy->_guid'>$_allergy->rques</content></td></tr>";
      }

      $text = $text."</tbody></table>";
    }
    else {
      $text = "<content ID='".CCDAFactory::NONE_ALLERGY."'>Aucune allergie / intolérance / réaction adverse</content>";
    }

    $this->addText($section, $text);

    if ($allergies_vsm) {
      $this->addAllergiesEntries($section, $factory, $allergies_vsm, $last_update_list_allergies, __FUNCTION__);
    }
    else {
      $this->addAllergieEmpty($section, $factory, __FUNCTION__, $dossier_medical->absence_allergie);
    }
  }

    /**
     * Add templateId for Observation in allergy
     *
     * @param CCDAFactory                   $factory
     * @param CCDAPOCD_MT000040_Observation $observation
     *
     * @return void
     */
    public function addTemplateObservationAllergy(
        CCDAFactory $factory,
        CCDAPOCD_MT000040_Observation $observation
    ): void {
        $template = $factory->createTemplateID("2.16.840.1.113883.10.20.1.28");
        $observation->appendTemplateId($template);
        $template = $factory->createTemplateID("2.16.840.1.113883.10.20.1.18");
        $observation->appendTemplateId($template);
        $template = $factory->createTemplateID("1.3.6.1.4.1.19376.1.5.3.1.4.6");
        $observation->appendTemplateId($template);
        $template = $factory->createTemplateID("1.3.6.1.4.1.19376.1.5.3.1.4.5");
        $observation->appendTemplateId($template);
    }

    /**
     * Add entry allergie empty
     *
     * @param CCDAPOCD_MT000040_Section $section
     * @param CCDAFactory               $factory
     * @param string                    $function_name
     * @param bool                      $absence_allergie
     *
     * @return void
     */
    public function addAllergieEmpty(
        CCDAPOCD_MT000040_Section $section,
        CCDAFactory $factory,
        string $function_name,
        bool $absence_allergie
    ): void {

        // Ajout de l'entry et de l'act
        $entry = new CCDAPOCD_MT000040_Entry();
        $act   = new CCDAPOCD_MT000040_Act();
        $act->setClassCode("ACT");
        $act->setMoodCode("EVN");

        // Ajout des templatesId
        $templatesId = CMbArray::get(CCDAFactory::$mapping_function_with_templatesId, $function_name);
        if ($templatesId) {
            foreach ($templatesId as $_templateId) {
                $template = $factory->createTemplateID($_templateId);
                $act->appendTemplateId($template);
            }
        }

        // Ajout Id
        $this->addId($act);

        // Code
        $this->addCodeNullFlavor($act);

        // StatusCode Ajout statut entrée
        $value_set  = new CDMPValueSet();
        $statusCode = $this->getStatusEntranceMB($factory->targetObject);
        $this->addStatusCode($act, CMbArray::get($value_set->getStatusEntrance($statusCode), "code"));

        // Ajout effectiveTime
        $this->addLowTime($act, CMbDT::format(CMbArray::get($factory->service_event, "time_start"), "%Y%m%d"));

        // Add entryRelationShip
        $entryRelationShip = new CCDAPOCD_MT000040_EntryRelationship();
        $entryRelationShip->setTypeCode("SUBJ");
        $entryRelationShip->setInversionInd("false");

        // Création de l'observation à ajouter sur l'entryRelationShip
        $observation = new CCDAPOCD_MT000040_Observation();
        $observation->setClassCode("OBS");
        $observation->setMoodCode("EVN");
        $observation->setNegationInd("false");

        // Ajout des templateId sur l'observation
        $this->addTemplateObservationAllergy($factory, $observation);

        // Ajout Id
        $this->addId($observation);

        // Ajout code
        $code_info = CInteropResources::loadEntryJV("JDV_HL7_ObservationIntoleranceType-CISIS", "ALG", "ASIP");
        if ($code_info) {
            $this->addCodeCD(
                $observation,
                CMbArray::get($code_info, "code"),
                CMbArray::get($code_info, "codeSystem"),
                CMbArray::get($code_info, "displayName"),
                "ObservationIntoleranceType"
            );
        }

        // Ajout text
        $this->addTextWithReference($observation, "#" . CCDAFactory::NONE_ALLERGY);

        // Ajout StatusCode sur l'observation
        $this->addStatusCode($observation, "completed");

        // Ajout effectiveTime
        $this->addLowTime($observation, CMbDT::transform(null, CMbDT::date(), "%Y%m%d"));

        // Ajout value
        $this->addValueOriginalText(
            $observation,
            "#" . CCDAFactory::NONE_ALLERGY,
            'MED-274',
            'Aucune allergie, intolérance, ni réaction adverse',
            '1.2.250.1.213.1.1.4.322',
            'TA-ASIP'
        );

        $entryRelationShip->setObservation($observation);
        $act->appendEntryRelationship($entryRelationShip);

        $entry->setAct($act);
        $section->appendEntry($entry);
    }

    /**
     * Add entry for allergies
     *
     * @param CCDAPOCD_MT000040_Section $section
     * @param CCDAFactory               $factory
     * @param array                     $allergies_vsm
     * @param string                    $last_update_list_allergies
     * @param string                    $function_name
     *
     * @return void
     * @throws \Exception
     *
     */
    public function addAllergiesEntries(
        CCDAPOCD_MT000040_Section $section,
        CCDAFactory $factory,
        array $allergies_vsm,
        string $last_update_list_allergies,
        string $function_name
    ): void {

        // Ajout de l'entry et de l'act
        $entry = new CCDAPOCD_MT000040_Entry();
        $act   = new CCDAPOCD_MT000040_Act();
        $act->setClassCode("ACT");
        $act->setMoodCode("EVN");

        // Ajout des templatesId
        $templatesId = CMbArray::get(CCDAFactory::$mapping_function_with_templatesId, $function_name);
        if ($templatesId) {
            foreach ($templatesId as $_templateId) {
                $template = $factory->createTemplateID($_templateId);
                $act->appendTemplateId($template);
            }
        }

        // Ajout Id
        $this->addId($act);

        // Code
        $this->addCodeNullFlavor($act);

        // StatusCode Ajout statut entrée
        $value_set = new CDMPValueSet();
        // On fixe le statusCode à "active"
        $statusCode = 'active';
        $this->addStatusCode($act, CMbArray::get($value_set->getStatusEntrance($statusCode), "code"));

        // Ajout effectiveTime (date de dernière mise à jour des allergies)
        $last_update_list_allergies = $last_update_list_allergies
            ? CMbDT::transform(null, CMbDT::date($last_update_list_allergies), "%Y%m%d")
            : CMbDT::transform(null, CMbDT::date(), "%Y%m%d");

        if ($statusCode == "completed" || $statusCode == "aborted") {
            $this->addLowAndHighTime($act, CMbDT::date());
        } else {
            $this->addLowTime($act, $last_update_list_allergies);
        }

        /** @var CAntecedent $_allergy */
        foreach ($allergies_vsm as $_allergy) {
            // Add entryRelationShip
            $entryRelationShip = new CCDAPOCD_MT000040_EntryRelationship();

            $entryRelationShip->setTypeCode("SUBJ");
            $entryRelationShip->setInversionInd("false");

            // Création de l'observation à ajouter sur l'entryRelationShip
            $observation = new CCDAPOCD_MT000040_Observation();
            $observation->setClassCode("OBS");
            $observation->setMoodCode("EVN");
            $observation->setNegationInd("false");

            // Ajout des templateId sur l'observation
            $this->addTemplateObservationAllergy($factory, $observation);

            // Ajout Id
            $this->addId($observation);

            // Ajout code
            $code_info = CInteropResources::loadEntryJV("JDV_HL7_ObservationIntoleranceType-CISIS", "ALG", "ASIP");
            if ($code_info) {
                $this->addCodeCD(
                    $observation,
                    CMbArray::get($code_info, "code"),
                    CMbArray::get($code_info, "codeSystem"),
                    CMbArray::get($code_info, "displayName"),
                    "ObservationIntoleranceType"
                );
            }

            // Ajout text
            $this->addTextWithReference($observation, "#$_allergy->_guid");

            // EffectiveTime with LowTime
            $date_allergy     = $this->getDateForAntecedent($_allergy->date);
            $date_end_allergy = $this->getDateForAntecedent($_allergy->date_fin);
            if ($date_end_allergy && $date_end_allergy > $date_allergy) {
                $this->addLowAndHighTime($observation, $date_allergy, $date_end_allergy);
            } else {
                $this->addLowTime($observation, $date_allergy);
            }

            // Ajout StatusCode sur l'observation
            $this->addStatusCode($observation, "completed");

            // Ajout de la value sur l'observation
            $this->addValueOriginalText($observation, "#$_allergy->_guid");

            $entryRelationShip->setObservation($observation);
            $act->appendEntryRelationship($entryRelationShip);
        }

        $entry->setAct($act);
        $section->appendEntry($entry);
    }

    /**
     * Add value element and originalText element
     *
     * @param object $element               element
     * @param string $content_original_text value
     *
     * @return void
     */
    public function addValueOriginalText(
        $element,
        $content_original_text,
        $code_value = null,
        $displayName = null,
        $codeSystem = null,
        $codeSystemName = null
    ): void {
        $code = new CCDACD();

        $code->setCode($code_value);
        $code->setDisplayName($displayName);
        $code->setCodeSystem($codeSystem);
        $code->setCodeSystemName($codeSystemName);

        if ($content_original_text) {
            $text_observation           = new CCDAED();
            $text_reference_observation = new CCDATEL();
            $text_reference_observation->setValue($content_original_text);
            $text_observation->setReference($text_reference_observation);
            $code->setOriginalText($text_observation);
        }

        $element->appendValue($code);
    }

    /**
     * Add code on element
     *
     * @param object $element element
     *
     * @return void
     */
    public function addCodeNullFlavor($element): void
    {
        $cd = new CCDACD();
        $cd->setNullFlavor("NA");
        $element->setCode($cd);
    }

    /**
     * Add motif acte
     *
     * @param object      $element    element
     * @param CAntecedent $antecedent antecedent
     * @param CCDAFactory $factory    factory
     * @param string      $root       root
     *
     * @return void
     */
    public function addMotifActe($element, CAntecedent $antecedent, CCDAFactory $factory, $root): void
    {
        $entryRelationShip = new CCDAPOCD_MT000040_EntryRelationship();
        $entryRelationShip->setTypeCode("RSON");
        $entryRelationShip->setInversionInd("false");

        $act = new CCDAPOCD_MT000040_Act();
        $act->setClassCode("ACT");
        $act->setMoodCode("EVN");

        // Ajout du template sur l'acte
        $template = $factory->createTemplateID("1.3.6.1.4.1.19376.1.5.3.1.4.4.1");
        $act->appendTemplateId($template);

        // Ajout de l'identifiant sur l'acte
        // Ajout de l'ID
        $ii = new CCDAII();
        $ii->setRoot($root);
        $act->appendId($ii);

        // Ajout du code CCAM
        $this->addCodeCCAM($act, $antecedent->_ref_code_ccam, "#$antecedent->_guid");

        $entryRelationShip->setAct($act);
        $element->appendEntryRelationship($entryRelationShip);
    }

    /**
     * Add code CCAM on element
     *
     * @param object         $element               element
     * @param CDatedCodeCCAM $code_ccam             code ccam
     * @param string         $content_original_text content original text
     *
     * @return void
     */
    public function addCodeCCAM($element, CDatedCodeCCAM $code_ccam, $content_original_text = null): void
    {
        $code = new CCDACE();
        $code->setCode($code_ccam->code);
        $code->setCodeSystem("1.2.250.1.213.2.5");
        $code->setDisplayName($code_ccam->libelleLong);
        $code->setCodeSystemName("CCAM");

        if ($content_original_text) {
            $text_observation           = new CCDAED();
            $text_reference_observation = new CCDATEL();
            $text_reference_observation->setValue($content_original_text);
            $text_observation->setReference($text_reference_observation);
            $code->setOriginalText($text_observation);
        }

        $element->setCode($code);
    }

    /**
     * @param CAntecedent $antecedent antecedent
     *
     * @return bool
     */
    public function checkNameCodeCCAM(CAntecedent $antecedent): bool
    {
        $explodes = explode(" ", $antecedent->rques);
        foreach ($explodes as $_explode) {
            if (preg_match("/^[A-Z]{4}[0-9]{3}(-[0-9](-[0-9])?)?$/i", $_explode)) {
                $code_ccam = new CDatedCodeCCAM($_explode);
                $code_ccam->load();

                if ($code_ccam->code != $_explode) {
                    continue;
                }

                $antecedent->_ref_code_ccam = $code_ccam;

                return true;
            }
        }

        return false;
    }

    /**
     * Add element text and element reference
     *
     * @param object $element    element
     * @param string $text_value text value
     *
     * @return void
     */
    public function addTextWithReference($element, $text_value): void
    {
        $text_observation           = new CCDAED();
        $text_reference_observation = new CCDATEL();
        $text_reference_observation->setValue($text_value);
        $text_observation->setReference($text_reference_observation);
        $element->setText($text_observation);
    }

    /**
     * Add effectiveTime on element
     *
     * @param object $element    element
     * @param string $value      value
     * @param string $nullFlavor nullFlavor
     *
     * @return void
     */
    public function addEffectiveTIme($element, $value, $nullFlavor = null): void
    {
        $tl = new CCDAIVL_TS();
        $tl->setValue($value);
        if ($nullFlavor) {
            $tl->setNullFlavor($nullFlavor);
        }
        $element->setEffectiveTime($tl);
    }

  /**
   * Add statusCode on element
   *
   * @param object $element element
   * @param string $value   value
   *
   * @return void
   */
  function addStatusCode($element, $value) {
    $status = new CCDACS();
    $status->setCode($value);
    $element->setStatusCode($status);
  }

  /**
   * Add ID on element
   *
   * @param object $element element
   *
   * @return string
   */
  function addId($element) {
    $ii = new CCDAII();
    $root = CCDAActClinicalDocument::generateUUID();
    $ii->setRoot($root);
    $element->appendId($ii);
    return $root;
  }

  /**
   * Formate date antecedent
   *
   * @param string $date date
   *
   * @return string
   */
  function getDateForAntecedent($date) {
    if (!$date) {
      return null;
    }

    $year = $mounth = $day = "00";
    list($year, $mounth, $day) = explode("-", $date);

    if ($mounth == "00") {
      $mounth = "01";
    }
    if ($day == "00") {
      $day = "01";
    }

    return "$year$mounth$day";
  }

  /**
   * Add low time in effectiveTime element
   *
   * @param object $element element
   * @param string $lowTime low time
   * @param bool   $append  append
   *
   * @return void
   */
  function addLowTime($element, $lowTime, $append = false) {
    $ivlTs = new CCDAIVL_TS();
    $ivxbL = new CCDAIVXB_TS();
    $ivxbL->setValue($lowTime);
    $ivlTs->setLow($ivxbL);
    if ($append) {
        $element->appendEffectiveTime($ivlTs);
    }
    else {
        $element->setEffectiveTime($ivlTs);
    }
  }

  /**
   * Add high time in effectiveTime element
   *
   * @param object $element element
   * @param string $lowTime low time
   *
   * @return void
   */
  function addHighTime($element, $lowTime) {
    $ivlTs = new CCDAIVL_TS();
    $ivxbL = new CCDAIVXB_TS();
    $ivxbL->setValue($lowTime);
    $ivlTs->setHigh($ivxbL);
    $element->setEffectiveTime($ivlTs);
  }

  /**
   * Add low and high time in effectiveTime element
   *
   * @param object $element    element
   * @param string $date_start date start
   * @param string $date_end   date end
   *
   * @return void
   */
  function addLowAndHighTime($element, $date_start, $date_end = null) {
    $ivlTs = new CCDAIVL_TS();
    $ivxbL = new CCDAIVXB_TS();
    $ivxbL->setValue($date_start);
    $ivlTs->setLow($ivxbL);
    $ivxbL = new CCDAIVXB_TS();
    $ivxbL->setValue($date_end ? $date_end : $date_start);
    $ivlTs->setHigh($ivxbL);
    $element->setEffectiveTime($ivlTs);
  }

  /**
   * Add low and high time in effectiveTime element
   *
   * @param object $element    element
   * @param string $date_start date start
   * @param string $date_end   date end
   *
   * @return void
   */
  function addLowAndHighTimeNullFlavor($element, $nullFlavor) {
    $ivlTs = new CCDAIVL_TS();
    $ivxbL = new CCDAIVXB_TS();
    $ivxbL->setNullFlavor($nullFlavor);
    $ivlTs->setLow($ivxbL);
    $ivxbL = new CCDAIVXB_TS();
    $ivxbL->setNullFlavor($nullFlavor);
    $ivlTs->setHigh($ivxbL);

    $ivlTs->setNullFlavor($nullFlavor);
    $element->appendEffectiveTime($ivlTs);
  }

  /**
   * Add low and high time in effectiveTime element
   *
   * @param object $element   element
   * @param string $date_low  date low
   * @param string $date_high date high
   *
   * @return void
   */
  function appendLowAndHighTime($element, $date_low, $date_high = null) {
    $ivlTs = new CCDAIVL_TS();

    // Low time
    $ivxbL = new CCDAIVXB_TS();
    $ivxbL->setValue($date_low);
    $ivlTs->setLow($ivxbL);

    // High time
    $ivxbL = new CCDAIVXB_TS();
    $ivxbL->setValue($date_high ? $date_high : $date_low);
    $ivlTs->setHigh($ivxbL);

    $element->appendEffectiveTime($ivlTs);
  }

  /**
   * Add element code in section
   *
   * @param object $element        element
   * @param string $code_loinc     code loinc
   * @param string $codeSystem     code system
   * @param string $displayName    display name
   * @param string $codeSystemName code system name
   *
   * @return void
   */
  function addCodeCE($element, $code_loinc, $codeSystem, $displayName, $codeSystemName) {
    $code = new CCDACE();
    $code->setCode($code_loinc);
    $code->setCodeSystem($codeSystem);
    $code->setDisplayName($displayName);
    $code->setCodeSystemName($codeSystemName);
    $element->setCode($code);
  }

  /**
   * Add element code in section
   *
   * @param object $element        element
   * @param string $code_loinc     code loinc
   * @param string $codeSystem     code system
   * @param string $displayName    display name
   * @param string $codeSystemName code system name
   *
   * @return void
   */
  function addCodeCD($element, $code_loinc, $codeSystem, $displayName, $codeSystemName) {
    $code = new CCDACD();
    $code->setCode($code_loinc);
    $code->setCodeSystem($codeSystem);
    $code->setDisplayName($displayName);
    $code->setCodeSystemName($codeSystemName);
    $element->setCode($code);
  }

  /**
   * Add code snomed in element like Observation
   *
   * @param object $element               element
   * @param string $code                  code
   * @param string $codeSystem            code system
   * @param string $displayName           display name
   * @param bool   $append                append | set code
   * @param string $content_original_text content original text
   * @param string $codeSystemName        code system name
   *
   * @return void
   */
  function addCodeSnomed(
    $element, $code, $codeSystem, $displayName, $append = true, $content_original_text = null, $codeSystemName = "SNOMED 3.5"
  ) {
    $code_element = new CCDACD();
    $code_element->setCode($code);
    $code_element->setCodeSystem($codeSystem);
    $code_element->setDisplayName($displayName);
    $code_element->setCodeSystemName($codeSystemName);

    if ($content_original_text) {
      $text_observation = new CCDAED();
      $text_reference_observation = new CCDATEL();
      $text_reference_observation->setValue($content_original_text);
      $text_observation->setReference($text_reference_observation);
      $code_element->setOriginalText($text_observation);
    }

    $append ? $element->appendValue($code_element) : $element->setCode($code_element);
  }

  /**
   * Add element title in section
   *
   * @param CCDAPOCD_MT000040_Section $section    section
   * @param string                    $data_title data title
   *
   * @return void
   */
  function addTitle(CCDAPOCD_MT000040_Section $section, $data_title) {
    $title = new CCDAST();
    $title->setData($data_title);
    $section->setTitle($title);
  }

  /**
   * Add element text in section
   *
   * @param CCDAPOCD_MT000040_Section|CCDAPOCD_MT000040_SubstanceAdministration $element section
   * @param string                                                              $content content
   *
   * @return void
   */
  function addText($element, $content) {
    $text = new CCDAED();
    $text->setData($content);
    $element->setText($text);
  }

  /**
   * Retourne les propriétés
   *
   * @return array
   */
  function getProps() {
    $props = parent::getProps();
    $props["typeId"]              = "CCDAPOCD_MT000040_InfrastructureRoot_typeId xml|element max|1";
    $props["confidentialityCode"] = "CCDACE xml|element max|1";
    $props["languageCode"]        = "CCDACS xml|element max|1";
    $props["component"]           = "CCDAPOCD_MT000040_Component3 xml|element min|1";
    $props["classCode"]           = "CCDAActClass xml|attribute fixed|DOCBODY";
    $props["moodCode"]            = "CCDAActMood xml|attribute fixed|EVN";
    return $props;
  }

  /**
   * Fonction permettant de tester la classe
   *
   * @return array
   */
  function test() {
    $tabTest = parent::test();

    /**
     * Test avec un component3 correct
     */

    $comp = new CCDAPOCD_MT000040_Component3();
    $sec = new CCDAPOCD_MT000040_Section();
    $sec->setClassCode();
    $comp->setSection($sec);
    $this->appendComponent($comp);
    $tabTest[] = $this->sample("Test avec un component correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un classCode correct
     */

    $this->setClassCode();
    $tabTest[] = $this->sample("Test avec un classCode correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un moodCode correct
     */

    $this->setMoodCode();
    $tabTest[] = $this->sample("Test avec un moodCode correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un languageCode incorrect
     */

    $cs = new CCDACS();
    $cs->setCode(" ");
    $this->setLanguageCode($cs);
    $tabTest[] = $this->sample("Test avec un languageCode incorrect", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un languageCode correct
     */

    $cs->setCode("TEST");
    $this->setLanguageCode($cs);
    $tabTest[] = $this->sample("Test avec un languageCode correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un confidentialityCode incorrect
     */

    $ce = new CCDACE();
    $ce->setCode(" ");
    $this->setConfidentialityCode($ce);
    $tabTest[] = $this->sample("Test avec un confidentialityCode incorrect", "Document invalide");

    /*-------------------------------------------------------------------------------------*/

    /**
     * Test avec un confidentialityCode correct
     */

    $ce->setCode("TEST");
    $this->setConfidentialityCode($ce);
    $tabTest[] = $this->sample("Test avec un confidentialityCode correct", "Document valide");

    /*-------------------------------------------------------------------------------------*/

    return $tabTest;
  }
}
