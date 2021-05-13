<?php
/**
 * @package Mediboard\SalleOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

$dPconfig["dPsalleOp"] = array(
  "mode_anesth"               => "0",
  "max_add_minutes"           => "10",
  "max_sub_minutes"           => "30",
  "COperation"                => array(
    "mode"                     => "0",
    "modif_salle"              => "0",
    "modif_actes"              => "oneday",
    "use_entree_sortie_salle"  => "1",
    "use_sortie_sans_sspi"     => "0",
    "use_garrot"               => "1",
    "use_debut_fin_op"         => "1",
    "use_entree_bloc"          => "0",
    "use_remise_chir"          => "0",
    "use_suture"               => "0",
    "use_check_timing"         => "0",
    "use_cleaning_timings"     => "0",
    "use_prep_cutanee"         => "0",
  ),
  "CActeCCAM"                 => array(
    "check_incompatibility"        => "allow",
    "commentaire"                  => "1",
    "envoi_actes_salle"            => "0",
    "envoi_motif_depassement"      => "1",
    "del_actes_non_cotes"          => "0"
  ),
  "CReveil"                   => array(
    "multi_tabs_reveil" => "1",
  )
);