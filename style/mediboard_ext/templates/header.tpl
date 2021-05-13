{{*
 * @package Mediboard\Style\Mediboard
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_include template=common nodebug=true}}

{{assign var=homepage value="-"|explode:$app->user_prefs.DEFMODULE}}
{{if $app->user_id}}
  {{assign var=href value="?m=`$homepage.0`"}}
  {{if $homepage|@count == 2}}
    {{assign var=href value="`$href`&tab=`$homepage.1`"}}
  {{/if}}
{{else}}
  {{assign var=href value=$conf.system.website_url}}
{{/if}}

<script>
  Main.add(
    function () {
      {{if (($app->user_prefs.UISTYLE !== "tamm" && $app->user_prefs.UISTYLE !== "pluus") || $app->_ref_user->isAdmin())}}
      {{foreach from=$modules key=_module_name item=_module}}
      {{if $_module->_can->view && $_module->mod_ui_active}}
      {{assign var=module_label value='Ox\Core\CAppUI::tr'|static_call:"module-$_module_name-court"}}
      {{assign var=module_label value=$module_label|smarty:nodefaults|JSAttribute}}
      MediboardExt.addModule(
        '{{$_module_name}}',
        '{{$module_label}}',
        {{if $_module_name === $m}}1{{else}}0{{/if}}
      );
      {{/if}}
      {{/foreach}}
      {{/if}}
      MediboardExt.initHeader({{if $app->user_prefs.UISTYLE === "tamm" || $app->user_prefs.UISTYLE === "pluus"}}1{{/if}})
        .onRendering();
    });
</script>
<div id="main" class="{{if $dialog}}dialog{{/if}} {{$m}}">
  {{if !$offline && !$dialog}}
    <div
      class="nav-modules compact {{if $app->user_prefs.UISTYLE === "tamm" || $app->user_prefs.UISTYLE === "pluus"}}nav-modules-tamm{{/if}}">
      {{if $app->user_prefs.UISTYLE === "tamm"}}
        <div class="nav-modules-content-tamm">
          {{mb_include style=mediboard_ext template=tamm_menu}}
        </div>
      {{elseif $app->user_prefs.UISTYLE === "pluus"}}
        <div class="nav-modules-content-tamm">
          {{mb_include style=mediboard_ext template=pluus_menu}}
        </div>
      {{/if}}
      <div class="nav-modules-searcher">
        {{me_form_field label="Search"}}
          <input type="text" class="nav-module-searcher"/>
        {{/me_form_field}}
      </div>
      <div class="nav-modules-content">
      </div>
      <div class="nav-modules-void"></div>
      <div class="nav-modules-plus">Plus</div>
    </div>
    <div id="nav-header" class="nav-header">
      <div class="nav-title-container">
        <i class="fas fa-th nav-menu-icon" id="nav-menu-icon"></i>
        <div id="nav-title" class="nav-title" data-link="{{$href}}">
          {{mb_include style="mediboard_ext" template="logo_small" alt="Logo"}}
        </div>
        <div class="nav-module-tabs">
          <a class="nav-title-module" href="?m={{$m}}">
            <div class="nav-title-module-icon"></div>
            {{tr}}module-{{$m}}-court{{/tr}}
          </a>
          <div class="nav-tabs">
            <span id="nav-tabs-badge" class="me-tab-container-badge"></span>
            <div class="nav-tabs-selecter" id="nav-tabs-selecter"></div>
            <div class="nav-tabs-container" id="nav-tabs-container"></div>
          </div>
        </div>
      </div>
      {{if $conf.instance_role === "qualif"}}
        <div class="me-ribbon-trigger"></div>
        <div class="me-qualif-ribbon me-{{$app->user_prefs.UISTYLE}}">
          {{mb_include style="mediboard_ext" template="logo_white" alt="Logo"}}
          <span class="me-ribbon-qualif-text">Qualif</span>
        </div>
      {{/if}}
      <div class="nav-plus">
        {{if $placeholders}}
          <div class="nav-plus-placeholders">
            {{foreach name=placeholders from=$placeholders item=placeholder}}
              <div class="minitoolbar me-minitoolbar me-nav-plus-icon"
                   style="display: inline-block; vertical-align: middle;">
                {{mb_include module=$placeholder->module template=$placeholder->minitoolbar}}
              </div>
            {{/foreach}}
          </div>
        {{/if}}

        {{if $porteDocuments && !$app->isPatient()}}
          <div class="nav-plus-porte-documents me-nav-plus-icon">
            <a id="porte-documents-container" title="{{tr}}mod-porteDocuments-msg-Access to the document holder{{/tr}}">
              {{mb_include module=porteDocuments template=inc_porte_documents_menu}}
            </a>
          </div>
        {{/if}}
        {{if $messagerie && !$app->isPatient()}}
          <div class="nav-plus-mail me-nav-plus-icon">
            {{mb_include module=messagerie template=inc_messagerie_menu}}
          </div>
        {{/if}}
        {{if $appFine && !$app->isPatient()}}
          <div class="nav-plus-appFine me-nav-plus-icon">
            <a id="appFine-container" title="{{tr}}mod-appFineClient-msg-Access to sas{{/tr}}">
              {{mb_include module=appFineClient template=inc_menu}}
            </a>
          </div>
        {{/if}}
        {{if $oxChatClient && !$app->isPatient()}}
          {{mb_include module=oxChatClient template=inc_ox_chat_classes}}
          <div class="nav-plus-oxChat me-nav-plus-icon">
            <a id="oxChat-container" title="{{tr}}mod-oxChatClient-msg-access-live-chat{{/tr}}">
              {{mb_include module=oxChatClient template=inc_messagerie_menu}}
            </a>
          </div>
        {{/if}}
        {{if $assistance && ($app->user_prefs.UISTYLE === "tamm") && !$app->isPatient()}}
          <div class="nav-plus-assistance me-nav-plus-icon">
            {{mb_include module=supportClient template=inc_assistance_menu}}
          </div>
        {{/if}}

        <div class="nav-plus-custom-container">
          <div class="nav-plus-groups">
            {{assign var=current_etab value=""}}
            {{assign var=current_func value=""}}

            {{if $app->_ref_user}}
              {{assign var=current_func value=$app->_ref_user->_ref_function}}
            {{/if}}

            {{foreach from=$Etablissements item=_group key=_group_id}}
              {{if $_group->_id === $g}}
                {{assign var=current_etab value=$_group}}
              {{/if}}

              {{if isset($SecondaryFunctions.$_group_id|smarty:nodefaults)}}
                {{foreach from=$SecondaryFunctions.$_group_id item=_sec_func}}
                  {{if $_sec_func->_id === $f}}
                    {{assign var=current_func value=$_sec_func}}
                  {{/if}}
                {{/foreach}}
              {{/if}}
            {{/foreach}}

            <div class="nav-groups-container" id="nav-groups-container">
              {{if $app->user_prefs.UISTYLE === "tamm"}}
                <div class="nav-groups-filter" id="nav-groups-filter">
                  {{me_form_field field_class="me-form-icon search me-form-group me-form-group_fullw"}}
                    <input class="me-placeholder" name="code" type="text" value=""
                           placeholder="{{tr}}SearchAGroup{{/tr}}"/>
                  {{/me_form_field}}
                </div>
              {{/if}}

              {{foreach from=$Etablissements item=_group key=_group_id}}
                {{assign var=group_class value=""}}
                {{if $_group->_id === $g && $f === $app->_ref_user->function_id}}
                  {{assign var=group_class value="selected"}}
                {{/if}}
                {{if $_group->_id == $app->_ref_user->_ref_function->group_id}}
                  <div data-group="{{$_group->_id}}"
                       data-function="{{$app->_ref_user->function_id}}"
                       class="group-item {{$group_class}} displayed"
                       title="{{tr}}CMediuser-Functions primary{{/tr}}">
                    <div class="nav-group">
                      {{$_group}}
                    </div>
                    <div class="function-status function-main"></div>
                  </div>
                {{/if}}
                {{if isset($SecondaryFunctions.$_group_id|smarty:nodefaults)}}
                  {{foreach from=$SecondaryFunctions.$_group_id item=_sec_func}}
                    {{assign var=group_class value=""}}
                    {{if $_group->_id === $g && $f === $_sec_func->_id}}
                      {{assign var=group_class value="selected"}}
                    {{/if}}
                    <div data-group="{{$_group->_id}}"
                         data-function="{{$_sec_func->_id}}"
                         class="group-item {{$group_class}} displayed"
                         title="{{tr}}CMediuser-Functions secondary{{/tr}}">
                      <div class="nav-group">
                        {{$_group}}
                      </div>
                      <div class="nav-function">
                        {{$_sec_func}}
                      </div>
                      <div class="function-status function-secondary"></div>
                    </div>
                  {{/foreach}}
                {{elseif $_group->_id != $app->_ref_user->_ref_function->group_id}}
                  {{assign var=group_class value=""}}
                  {{if $_group->_id === $g}}
                    {{assign var=group_class value="selected"}}
                  {{/if}}
                  <div data-group="{{$_group->_id}}"
                       class="group-item {{$group_class}} displayed"
                       title="{{tr}}CFunctions.none{{/tr}}">
                    <div class="nav-group">
                      {{$_group}}
                    </div>
                    <div class="function-status function-missing"></div>
                  </div>
                {{/if}}
              {{/foreach}}
            </div>
            <div class="nav-groups-selecter nav-groups-unique" id="nav-groups-selecter">
              <div class="nav-groups-selecter-content">
                <div class="nav-group">
                  {{$current_etab}}
                </div>
                <div class="nav-function">
                  {{$current_func}}
                </div>
              </div>
            </div>
          </div>

          <div id="nav-plus-icon" class="nav-plus-account">
            <div id="nav-plus-content" class="nav-plus-content">
              {{if $app->user_prefs.UISTYLE !== "tamm" && $app->user_prefs.UISTYLE !== "pluus"}}
                <a title="{{tr}}menu-myInfo{{/tr}}" href="?m=mediusers&amp;a=edit_infos" class="userMenu-preference">
                  {{tr}}menu-myInfo{{/tr}}
                </a>
              {{/if}}
              {{if !$app->isPatient()}}
                <a title="{{tr}}menu-switchUser{{/tr}}" href="#1" onclick="UserSwitch.popup()" class="userMenu-loginAs">
                  {{tr}}menu-switchUser{{/tr}}
                </a>
              {{/if}}
              <a title="{{tr}}menu-logout{{/tr}}" href="?logout=-1" class="userMenu-logOut">
                {{tr}}menu-logout{{/tr}}
              </a>

              <div class="nav-separator"></div>
              <div class="me-switch {{if $IS_MEDIBOARD_EXT_DARK}}me-switch_me-enabled{{/if}}">
                <input type="checkbox" id="userMenu-dark-theme"
                       name="userMenu-changeTheme" class="me-switch_input"
                  {{if $IS_MEDIBOARD_EXT_DARK}}
                    onchange="App.savePref('mediboard_ext_dark', '0', function() { document.location.reload(true) });" checked="checked"
                  {{else}}
                    onchange="App.savePref('mediboard_ext_dark', '1', function() { document.location.reload(true) });"
                  {{/if}}
                >
                <label for="userMenu-dark-theme" title="{{tr}}pref-mediboard_ext_dark-desc{{/tr}}"
                       class="me-switch_label">{{tr}}pref-mediboard_ext_dark{{/tr}}</label>
              </div>
              <div class="nav-separator"></div>

              {{mb_include template=inc_help}}

              {{if $app->isPatient()}}
                <a title="{{tr}}common-action-Change your password{{/tr}}" href="#1" onclick="patientChangePassword()"
                   class="userMenu-changePasswd me-color-black-medium-emphasis">
                  {{tr}}config-db-dbpass{{/tr}}
                </a>
              {{else}}
                {{* Vérification nécessaire pour les nouvelles installations *}}
                {{if $app->_ref_user && $app->_ref_user->_ref_user && $app->_ref_user->_ref_user->canChangePassword()}}
                  <a title="{{tr}}menu-changePassword{{/tr}}" href="#1" onclick="popChgPwd()"
                     class="userMenu-changePasswd me-color-black-medium-emphasis">
                    {{tr}}config-db-dbpass{{/tr}}
                  </a>
                {{/if}}
                <a title="{{tr}}menu-lockSession{{/tr}}" href="#1" onclick="Session.lock()"
                   class="userMenu-lock me-color-black-medium-emphasis">
                  {{tr}}menu-lockSession{{/tr}}
                </a>
              {{/if}}

              {{if !$offline}}
                {{mb_include style=mediboard_ext template=svnstatus}}
              {{/if}}
            </div>
            <div class="nav-plus-account-user">
              {{if $app->_ref_user}}
                {{$app->_ref_user->_shortview}}
              {{/if}}
            </div>
            <div class="nav-plus-account-user-full">
              {{if $app->_ref_user}}
                {{$app->_ref_user->_view}}
              {{/if}}
            </div>

            <div class="nav-plus-icon">
              <i class="fas fa-ellipsis-v"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="nav-compenser"></div>
    {{mb_include style=mediboard_ext template=message nodebug=true}}
    {{mb_include style=mediboard_ext template=offline_mode}}
    <div class="nav-smoke" id="nav-smoke"></div>
  {{/if}}


  {{mb_include template=obsolete_module}}
  <div id="systemMsg">
    {{$errorMessage|nl2br|smarty:nodefaults}}
  </div>
