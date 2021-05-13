{{*
 * @package Mediboard\Core\Templates
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(
    function() {
      MediboardExt.TammMenu.initContactCallback(
        '{{$m}}',
        '{{$tab}}',
        '{{$app->_ref_user}}',
        '{{$app->_ref_user->_ref_function}}',
        '{{"oxCabinet General email_support"|gconf}}'
      );
    }
  );
</script>

<div class="nav-module-tamm-container">
  <div class="nav-module-tamm-header">
    <a href="{{$href}}">
      <div class="nav-module-tamm-home">
        Accueil
      </div>
    </a>
  </div>
  <div class="nav-module-tamm-content">
    <div class="nav-module-tamm-element">
      <div class="nav-module-tamm-icon">
        <span class="nav-module-tamm-account"></span>
          {{tr}}common-my-account{{/tr}}
      </div>
      <a onclick="MediboardExt.TammMenu.editInfosPerso()">{{tr}}mod-oxCabinet-tab-edit_info_perso{{/tr}}</a>
      <a onclick="MediboardExt.TammMenu.showAbonnement()">{{tr}}mod-oxCabinet-tab-my_subscription-court{{/tr}}</a>
      {{if $app->_ref_user->isAdmin()}}
        <a onclick="MediboardExt.TammMenu.showHistory()">{{tr}}mod-oxCabinet-tab-History{{/tr}}</a>
      {{/if}}
      {{if $app->_ref_user->isPraticien() || $can->admin}}
        <a onclick="MediboardExt.TammMenu.showSecretary()">{{tr}}mod-oxCabinet-tab-Secretary management|pl{{/tr}}</a>
      {{/if}}
      <a onclick="MediboardExt.TammMenu.contactSupport()">
          {{tr}}common-contact-support{{/tr}}
      </a>
      <a href="https://declarations.cnil.fr/declarations/declaration/declarant.display.action" target="_blank">
          {{tr}}oxCabinet-CNIL declaration{{/tr}}
      </a>
    </div>
    <div class="nav-module-tamm-element">
      <div class="nav-module-tamm-icon">
        <span class="nav-module-tamm-settings"></span>
          {{tr}}common-settings{{/tr}}
      </div>
      <a href="?m=compteRendu">{{tr}}mod-dPcompteRendu-tab-court{{/tr}}</a>
      {{if $app->user_prefs.tamm_allow_prescription}}
        <a onclick="MediboardExt.TammMenu.editProtocoles()">{{tr}}mod-prescription-tab-court{{/tr}}</a>
        <a onclick="MediboardExt.TammMenu.editCataloguePrescription()">{{tr}}mod-oxCabinet-catalogue_prescription{{/tr}}</a>
      {{/if}}
      {{if isset($modules.dPstock|smarty:nodefaults) && $modules.dPstock->_can->read}}
        <a href="#1" onclick="MediboardExt.TammMenu.editStocks()">{{tr}}CProductStock{{/tr}}</a>
      {{/if}}
      <a onclick="MediboardExt.TammMenu.editCorrespondantsTAMM()">{{tr}}mod-patients-tab-gestion-correspondants{{/tr}}</a>
      <a onclick="MediboardExt.TammMenu.editRessources()">{{tr}}mod-dPcabinet-tab-vw_ressources{{/tr}}</a>
      {{if 'notifications'|module_active && @$modules.notifications->_can->read}}
        <a onclick="MediboardExt.TammMenu.showNotifications()">{{tr}}module-notifications-court{{/tr}}</a>
      {{/if}}
      <a onclick="MediboardExt.TammMenu.editProtocoleStructureTAMM()">{{tr}}mod-patients-tab-vw_programmes{{/tr}}</a>
    </div>
    <div class="nav-module-tamm-element">
      <div class="nav-module-tamm-icon">
        <span class="nav-module-tamm-tools"></span>
          {{tr}}common-Tools{{/tr}}
      </div>
      <a href="?m=cim10">{{tr}}mod-dPcim10-tab-court{{/tr}}</a>
      <a href="?m=ccam">{{tr}}mod-dPccam-tab-court{{/tr}}</a>
      {{if $app->user_prefs.tamm_allow_prescription}}
        <a href="?m=medicament">{{tr}}mod-dPmedicament-tab-court{{/tr}}</a>
      {{/if}}
      {{if isset($modules.fse|smarty:nodefaults) && $modules.fse->_can->view}}
        <a href="?m=fse">{{tr}}mod-fse-tab-court{{/tr}}</a>
      {{/if}}
      {{if isset($modules.bioserveur|smarty:nodefaults) && $modules.bioserveur->_can->view}}
        <a href="?m=bioserveur">{{tr}}mod-bioserveur-tab-court{{/tr}}</a>
      {{/if}}
      <a onclick="MediboardExt.TammMenu.showListPatients()">{{tr}}mod-patients-tab-Export of patient|pl{{/tr}}</a>
      <a onclick="MediboardExt.TammMenu.showListVerrouDossier()">{{tr}}mod-oxCabinet-tab-List of archive|pl{{/tr}}</a>
      <a href="?m=patients&tab=vw_recherche_doc">{{tr}}mod-dPpatients-recherche-doc-tab-court{{/tr}}</a>
    </div>
  </div>
</div>