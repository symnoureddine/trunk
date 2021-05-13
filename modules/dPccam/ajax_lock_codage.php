<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbException;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Ccam\CCodageCCAM;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkEdit();

$codable_class    = CView::post('codable_class', 'str');
$codable_id       = CView::post('codable_id', 'ref meta|codable_class');
$praticien_id     = CView::post('praticien_id', 'ref class|CMediusers');
$date             = CView::post('date', 'date');
$user_password    = CView::post('user_password', 'str');
$lock_all_codages = CView::post('lock_all_codages', 'bool default|0');
$lock             = CView::post('lock', 'bool default|1');
$export           = CView::post('export', 'bool default|0');

CView::checkin();

$codage = new CCodageCCAM();
$codage->praticien_id = $praticien_id;
$codage->codable_class = $codable_class;
$codage->codable_id = $codable_id;
if ($date && !$lock_all_codages) {
  $codage->date = $date;
}

/** @var CCodageCCAM[] $codages */
$codages = $codage->loadMatchingList();
$user      = CMediusers::get();
$praticien = CMediusers::get($praticien_id);

if (CAppUI::gconf("dPccam codage lock_codage_ccam") != 'password'
    || (CAppUI::gconf("dPccam codage lock_codage_ccam") == 'password'
    && ($user->_id === $praticien->_id || CUser::checkPassword($praticien->_user_username, $user_password)))
) {
  $object = null;
  foreach ($codages as $_codage) {
    $_codage->locked = $lock;
    $result = $_codage->store();

    if (!$result) {
      $_codage->loadActesCCAM();
      $object = $_codage->loadCodable();

      foreach ($_codage->_ref_actes_ccam as $_act) {
        $_act->signe = $lock;
        $_act->_no_synchro_eai = true;
        $_act->store();
      }
    }
  }

  /* Export des actes */
  if ($export && $lock) {
    $is_factured = $object->facture;
    /* If the object is already factured, we must set the field facture to 0, before set it back to 1,
     * because the acts are only exported if the field factured is modified to 1
     */
    if ($is_factured) {
      $object->facture = '0';
      $object->_no_synchro_eai = true;
      $object->store(false);
    }

    $object->_force_sent = true;
    $object->_no_synchro_eai = false;
    $object->facture = '1';
    $object->loadLastLog();

    try {
      $_msg = $object->store(false);

      $object->loadRefsActesCCAM();
      foreach ($codages as $_codage) {
        foreach ($_codage->_ref_actes_ccam as $_act) {
          $_act->_no_synchro_eai = true;
          $_act->sent = 1;
          $_act->store();
        }
      }

      $object->loadRefsCodagesCCAM();
      $finished = true;
      foreach ($object->_ref_codages_ccam as $_codage_by_prat) {
        foreach ($_codage_by_prat as $_codage) {
          if (!$_codage->locked) {
            $finished = false;
          }
        }
      }

      if (!$finished) {
        $object->facture = '0';
        $object->_no_synchro_eai = true;
        $object->_force_sent = false;
        $object->store(false);
      }
    }
    catch(CMbException $e) {
      // Cas d'erreur on repasse la facturation à l'état précédent
      if (!$is_factured) {
        $object->facture = '0';
        $object->_no_synchro_eai = true;
        $object->_force_sent = false;
        $object->store(false);
      }
    }
  }
  elseif (!$lock && $object->facture) {
    $object->facture = 0;
    $object->_ref_actes_ccam = null;
    $object->loadRefsActesCCAM();

    foreach ($object->_ref_actes_ccam as $_act) {
      $_act->sent = 0;
      $_act->_no_synchro_eai = true;
      $_act->store();
    }
  }

  $msg = $lock ? 'CCodageCCAM-msg-codage_locked' : 'CCodageCCAM-msg-codage_unlocked';
  CAppUI::setMsg($msg, UI_MSG_OK);
  echo CAppUI::getMsg();
}
elseif (CAppUI::gconf("dPccam codage lock_codage_ccam") == 'password' && $user->_id !== $praticien->_id
    && !CUser::checkPassword($praticien->_user_username, $user_password)
) {
  CAppUI::setMsg("CUser-user_password-nomatch", UI_MSG_ERROR);
  echo CAppUI::getMsg();
}
