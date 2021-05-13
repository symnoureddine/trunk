<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Core\CView;
use Ox\Mediboard\Cabinet\CActeNGAP;
use Ox\Mediboard\Ccam\CCodageCCAM;
use Ox\Mediboard\Ccam\CModelCodage;
use Ox\Mediboard\SalleOp\CActeCCAM;

set_time_limit(300);

CCanDo::checkEdit();

$model_codage_id = CView::post('model_codage_id', 'ref class|CModelCodage');
$apply = CView::post('apply', 'bool default|1');
$export = CView::post('export', 'bool default|0');
$object_class = CView::post('object_class', 'enum list|COperation|CSejour-seances');

CView::checkin();

$model = new CModelCodage();
$model->load($model_codage_id);

$model->loadRefPraticien();
$model->loadRefsActesCCAM();
$model->loadRefsActesNGAP();
$model->loadRefsCodagesCCAM();
/** @var CCodageCCAM[] $model_codages */
$model_codages = $model->_ref_codages_ccam[$model->praticien_id];
$objects = $model->loadObjects();

$model_acts = [
  'ccam' => [],
  'ngap' => []
];

$model_codes = explode('|', $model->codes_ccam);

foreach ($model->_ref_actes_ccam as $_act) {
  $key = "$_act->code_acte-$_act->code_activite-$_act->code_phase";
  if (!array_key_exists($key, $model_acts)) {
    $model_acts['ccam'][$key] = array();
  }

  $model_acts['ccam'][$key][] = $_act;
}

foreach ($model->_ref_actes_ngap as $_act) {
  $model_acts['ngap'][] = $_act;
}

$count_operations = 0;

if ($apply) {
  foreach ($objects as $_object) {
    $_error = 0;
    $_object->loadRefsCodagesCCAM();
    $_object->loadRefsActesCCAM();
    $_object->getActeExecution();
    $_model_acts = $model_acts;

    $_object_codes = $_object->codes_ccam != '' ? explode('|', $_object->codes_ccam) : array();

    $_codes_ccam = array();
    $_diff = array_diff($_object_codes, $model_codes);
    if (empty($_object_codes) || empty($_diff)) {
      $_codes_ccam = $model_codes;
    }
    else {
      $_codes_ccam = array_merge($model_codes, $_diff);
    }
    $_object->codes_ccam = implode('|', $_codes_ccam);
    $_object->_codes_ccam = $_codes_ccam;

    if ($_object->_class == 'COperation') {
      $msg = $_object->store(false);
    }
    else {
      $msg = $_object->store();
    }

    if ($msg) {
      continue;
    }

    /* Vérification de l'affectation des dépassements d'honoraires */
    $depassement_affecte = false;
    $depassement_anesth_affecte = false;
    foreach ($_object->_ref_actes_ccam as $_act) {
      if ($_act->code_activite == 1 && $_act->montant_depassement) {
        $depassement_affecte = true;
      }
      elseif ($_act->code_activite == 4 && $_act->montant_depassement) {
        $depassement_anesth_affecte = true;
      }
    }

    switch ($_object->_class) {
      case 'COperation':
        $_date = $_object->date;
        break;
      case 'CConsultation':
        $_object->loadRefPlageConsult();
        $_date = $_object->_date;
        break;
      default:
        $_date = CMbDT::date();
    }

    foreach ($model_codages as $_model_codage) {
      $_codage = new CCodageCCAM();
      $_codage->codable_class = $_object->_class;
      $_codage->codable_id = $_object->_id;
      $_codage->praticien_id = $_model_codage->praticien_id;
      $_codage->activite_anesth = $_model_codage->activite_anesth;
      $_codage->date = $_date;

      $_codage->loadMatchingObject();

      $_codage->association_mode = $_model_codage->association_mode;
      $_codage->association_rule = $_model_codage->association_rule;

      if ($_error = $_codage->store()) {
        break;
      }
    }

    if ($_error) {
      continue;
    }

    foreach ($_object->_ref_actes_ccam as $_act) {
      $key = "$_act->code_acte-$_act->code_activite-$_act->code_phase";

      if ($_act->executant_id != $model->praticien_id) {
        continue;
      }

      if (!array_key_exists($key, $_model_acts['ccam'])) {
        if ($_act->montant_depassement && $_act->code_activite == 1) {
          $depassement_affecte = false;
        }
        elseif ($_act->montant_depassement && $_act->code_activite == 4) {
          $depassement_anesth_affecte = false;
        }

        $_act->delete();
        continue;
      }

      /** @var CActeCCAM $_model_act */
      $_model_act = reset($_model_acts['ccam'][$key]);

      $_act->code_association = $_model_act->code_association;
      $_act->code_extension = $_model_act->code_extension;

      /* Afftectation des dépassement en priorité aux actes existants */
      if (!$depassement_affecte && $_act->code_activite == 1) {
        if ($_object->_acte_depassement) {
          $_act->montant_depassement = $_object->_acte_depassement;
          $depassement_affecte = true;
        }
        elseif ($_model_act->montant_depassement) {
          $_act->montant_depassement = $_model_act->montant_depassement;
          $depassement_affecte = true;
        }
      }
      elseif (!$depassement_anesth_affecte && $_act->code_activite == 4) {
        if ($_object->_acte_depassement_anesth) {
          $_act->montant_depassement = $_object->_acte_depassement_anesth;
          $depassement_anesth_affecte = true;
        }
        elseif ($_model_act->montant_depassement) {
          $_act->montant_depassement = $_model_act->montant_depassement;
          $depassement_anesth_affecte = true;
        }
      }

      $_act->motif_depassement = $_model_act->motif_depassement;
      $_act->facturable = $_model_act->facturable;
      $_act->extension_documentaire = $_model_act->extension_documentaire;
      $_act->rembourse = $_model_act->rembourse;
      $_act->execution = $_object->_acte_execution;
      $_act->modificateurs = $_model_act->modificateurs;
      $_act->position_dentaire = $_model_act->position_dentaire;
      $_act->commentaire = $_model_act->commentaire;
      $_act->precodeModifiers();

      if ($_error = $_act->store()) {
        break;
      }

      if (count($_model_acts['ccam'][$key]) == 1) {
        unset($_model_acts['ccam'][$key]);
      }
      else {
        unset($_model_acts['ccam'][$key][0]);
      }
    }

    if ($_error) {
      continue;
    }

    foreach ($_model_acts['ccam'] as $code => $_acts) {
      foreach ($_acts as $_model_act) {
        $_act = new CActeCCAM();
        $_act->object_class = $_object->_class;
        $_act->object_id = $_object->_id;
        $_act->code_acte = $_model_act->code_acte;
        $_act->code_activite = $_model_act->code_activite;
        $_act->code_phase = $_model_act->code_phase;
        $_act->code_extension = $_model_act->code_extension;
        $_act->executant_id = $_model_act->executant_id;
        $_act->code_association = $_model_act->code_association;

        /* Afftectation des dépassement */
        if (!$depassement_affecte && $_act->code_activite == 1) {
          if ($_object->_acte_depassement) {
            $_act->montant_depassement = $_object->_acte_depassement;
            $depassement_affecte = true;
          }
          elseif ($_model_act->montant_depassement) {
            $_act->montant_depassement = $_model_act->montant_depassement;
            $depassement_affecte = true;
          }
        }
        elseif (!$depassement_anesth_affecte && $_act->code_activite == 4) {
          if ($_object->_acte_depassement_anesth) {
            $_act->montant_depassement = $_object->_acte_depassement_anesth;
            $depassement_anesth_affecte = true;
          }
          elseif ($_model_act->montant_depassement) {
            $_act->montant_depassement = $_model_act->montant_depassement;
            $depassement_anesth_affecte = true;
          }
        }

        $_act->motif_depassement = $_model_act->motif_depassement;
        $_act->facturable = $_model_act->facturable;
        $_act->extension_documentaire = $_model_act->extension_documentaire;
        $_act->rembourse = $_model_act->rembourse;
        $_act->execution = $_object->_acte_execution;
        $_act->modificateurs = $_model_act->modificateurs;
        $_act->position_dentaire = $_model_act->position_dentaire;
        $_act->commentaire = $_model_act->commentaire;
        $_act->precodeModifiers();

        if ($_act->code_activite == 4) {
          if (!$_act->extension_documentaire) {
            $_act->extension_documentaire = $_object->getExtensionDocumentaire($_act->executant_id);
          }

          /* Dans le cas des actes d'activité 4, la date d'execution est la même que l'activité 1 si celle est codée */
          $acte_chir = $_act->loadActeActiviteAssociee();
          if ($acte_chir->_id) {
            $_act->execution = $acte_chir->execution;
            if ($acte_chir->code_extension) {
              $_act->code_extension = $acte_chir->code_extension;
            }
          }
        }

        if ($_error = $_act->store()) {
          break;
        }
      }
    }

    foreach ($_model_acts['ngap'] as $_model_act) {
      $_act                       = new CActeNGAP();
      $_act->object_class         = $_object->_class;
      $_act->object_id            = $_object->_id;
      $_act->code                 = $_model_act->code;
      $_act->quantite             = $_model_act->quantite;
      $_act->coefficient          = $_model_act->coefficient;
      $_act->montant_depassement  = $_model_act->montant_depassement;
      $_act->montant_base         = $_model_act->montant_base;
      $_act->demi                 = $_model_act->demi;
      $_act->complement           = $_model_act->complement;
      $_act->executant_id         = $_model_act->executant_id;
      $_act->lettre_cle           = $_model_act->lettre_cle;
      $_act->facturable           = $_model_act->facturable;
      $_act->lieu                 = $_model_act->lieu;
      $_act->exoneration          = $_model_act->exoneration;
      $_act->gratuit              = $_model_act->gratuit;
      $_act->qualif_depense       = $_model_act->qualif_depense;
      $_act->accord_prealable     = $_model_act->accord_prealable;
      $_act->date_demande_accord  = $_model_act->date_demande_accord;
      $_act->reponse_accord       = $_model_act->reponse_accord;
      $_act->execution            = $_object->_acte_execution;

      if ($_error = $_act->store()) {
        break;
      }
    }

    if ($_error) {
      continue;
    }

    if ($export) {
      $_codage->locked = 1;
      $_codage->store();

      $_object->facture = 1;
      $_object->_force_sent = true;
      $_object->loadLastLog();

      try {
        $_object->store();

        $_object->_ref_actes_ccam = null;
        $_object->loadRefsCodagesCCAM();
        $_object->loadRefsActesCCAM();

        foreach ($_object->_ref_actes_ccam as $_act) {
          $_act->sent = 1;
          $_act->store();
        }

        $finished = true;

        foreach ($_object->_ref_codages_ccam as $_codage_by_prat) {
          foreach ($_codage_by_prat as $_codage) {
            if (!$_codage->locked) {
              $finished = false;
              break 2;
            }
          }
        }

        if (!$finished) {
          $_object->facture = 0;
          $_object->_no_synchro_eai = true;
          $_object->store(false);
        }
      }
      catch(CMbException $e) {
        // Cas d'erreur on repasse la facturation à l'état précédent
        $_object->facture = 0;
        $_object->store();
        $_error = 1;
      }
    }

    if (!$_error) {
      $count_operations++;
    }
  }
}

$model->delete();

$object_traduction = 'interventions';
if ($object_class == 'CSejours-seances') {
  $object_traduction = 'séances';
}

if (!$apply && !$export) {
  CAppUI::stepAjax('Codage en masse annulé', UI_MSG_OK);
}
elseif ($count_operations == count($objects)) {
  CAppUI::stepAjax("Le codage a été appliqué avec succès à $count_operations $object_traduction", UI_MSG_OK);
}
else {
  $errors = count($objects) - $count_operations;
  CAppUI::stepAjax(
    "Le codage a été appliqué avec succès à $count_operations $object_traduction, $errors $object_traduction marquées en erreurs.",
    UI_MSG_WARNING
  );
}