{{*
 * @package Mediboard\Core\Templates
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(
    function() {
      MediboardExt.PluusMenu.initContactCallback(
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
      <a onclick="MediboardExt.PluusMenu.editInfosPerso()">{{tr}}mod-oxCabinet-tab-edit_info_perso{{/tr}}</a>
      {{if $app->_ref_user->isPraticien() || $can->admin}}
        <a onclick="MediboardExt.PluusMenu.showSecretary()">{{tr}}mod-oxCabinet-tab-Secretary management|pl{{/tr}}</a>
      {{/if}}
      <a onclick="MediboardExt.PluusMenu.contactSupport()">
          {{tr}}common-contact-support{{/tr}}
      </a>
    </div>
    <div class="nav-module-tamm-element">
      <div class="nav-module-tamm-icon">
        <span class="nav-module-tamm-settings"></span>
          {{tr}}common-settings{{/tr}}
      </div>
      <a href="?m=compteRendu">{{tr}}mod-dPcompteRendu-tab-court{{/tr}}</a>
        {{if $app->user_prefs.tamm_allow_prescription}}
          <a onclick="MediboardExt.PluusMenu.editProtocoles()">{{tr}}mod-prescription-tab-court{{/tr}}</a>
          <a onclick="MediboardExt.PluusMenu.editCataloguePrescription()">{{tr}}mod-oxCabinet-catalogue_prescription{{/tr}}</a>
        {{/if}}
      <a onclick="MediboardExt.PluusMenu.editCorrespondantsTAMM()">{{tr}}mod-patients-tab-gestion-correspondants{{/tr}}</a>
        {{if 'notifications'|module_active && @$modules.notifications->_can->read}}
          <a onclick="MediboardExt.PluusMenu.showNotifications()">{{tr}}module-notifications-court{{/tr}}</a>
        {{/if}}
      <a onclick="MediboardExt.PluusMenu.editProtocoleStructureTAMM()">{{tr}}mod-patients-tab-vw_programmes{{/tr}}</a>
    </div>
    <div class="nav-module-tamm-element">
      <div class="nav-module-tamm-icon">
        <span class="nav-module-tamm-tools"></span>
          {{tr}}common-Tools{{/tr}}
      </div>
      <a href="?m=cim10">{{tr}}mod-dPcim10-tab-court{{/tr}}</a>
      <a href="?m=tarmed">{{tr}}mod-tarmed-tab-court{{/tr}}</a>
        {{if $app->user_prefs.tamm_allow_prescription}}
          <a href="?m=medicament">{{tr}}mod-dPmedicament-tab-court{{/tr}}</a>
        {{/if}}
        {{if "dPlabo"|module_active}}
          <a href="?m=dPlabo">{{tr}}mod-dPlabo-tab-court{{/tr}}</a>
        {{/if}}
        {{if "dPfacturation"|module_active}}
          <a href="?m=dPfacturation">{{tr}}mod-dPfacturation-tab-court{{/tr}}</a>
        {{/if}}
      <a onclick="MediboardExt.PluusMenu.showListPatients()">{{tr}}mod-patients-tab-Export of patient|pl{{/tr}}</a>
      <a onclick="MediboardExt.PluusMenu.showListVerrouDossier()">{{tr}}mod-oxCabinet-tab-List of archive|pl{{/tr}}</a>
      <a href="?m=patients&tab=vw_recherche_doc">{{tr}}mod-dPpatients-recherche-doc-tab-court{{/tr}}</a>
    </div>
  </div>
</div>