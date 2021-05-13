{{*
 * @package Mediboard\Messagerie
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=messagerie script=Messagerie}}

<script type="text/javascript">
  Main.add(function() {
    var container = $('messagerie-external-navigation-container');
    var tooltip = $('messagerie-external-accounts');

    tooltip.remove();
    document.body.insert(tooltip);
    Event.observe(container, 'click', ObjectTooltip.createDOM.bind(ObjectTooltip, container, tooltip, {duration: 0, offsetLeft: false, borderless: true}));
  });
</script>

{{foreach from=$messagerie.accounts key=_category item=_accounts}}
  {{if $_category == 'external'}}
    <a id="messagerie-{{$_category}}-navigation-container" class="me-inline-block" title="{{tr}}messagerie-{{$_category}}-title-access{{/tr}}" href="#">
      <span id="messagerie-{{$_category}}-navigation"{{if !$messagerie.counters.$_category.total}} class="none"{{/if}}>
        <i class="fas fa-envelope fa-lg me-icon" style="font-size: 1.3em;"></i>
        <span id="messagerie-{{$_category}}-total-counter" class="msg-counter"{{if !$messagerie.counters.$_category.total}} style="display: none;"{{/if}}>
          {{$messagerie.counters.$_category.total}}
        </span>
      </span>

      <ul id="messagerie-{{$_category}}-accounts" class="messagerie-menu" style="display: none;">
        {{foreach from=$messagerie.accounts.$_category key=index item=account}}
          <li class="messagerie-menu-element" onclick="Messagerie.openModal('{{$index}}');">
            <span class="msg-counter" id="messagerie-{{$_category}}-{{$index}}-counter"{{if !$messagerie.counters.$_category.$index}} style="display: none;"{{/if}}>
            {{$messagerie.counters.$_category.$index}}
            </span>
            {{if $account->_class == 'CSourcePOP'}}
              {{if $account->name|strpos:'apicrypt' === false}}
                <i class="fa fa-envelope msgicon"></i>
              {{else}}
                <img title="Apicrypt" style="width: 16px; height: 16px;" src="modules/apicrypt/images/icon.png">
              {{/if}}
              {{$account->libelle}}
            {{elseif $account->_class == 'CMSSanteUserAccount'}}
              <img title="MSSante" src="modules/mssante/images/icon_min.png">
              MSSanté
            {{elseif $account->_class == 'CMedimailAccount'}}
              <img src="modules/medimail/images/icon_min.png" title="Medimail">
              {{if $account->libelle}}{{$account->libelle}}{{else}}Medimail{{/if}}
            {{/if}}
          </li>
        {{/foreach}}

        {{if 'messagerie access allow_external_mail'|gconf}}
          <li class="messagerie-menu-element" onclick="Messagerie.manageAccounts();">
            <i class="msgicon fas fa-cog"></i>
            {{tr}}common-action-Account management{{/tr}}
          </li>
        {{/if}}
      </ul>
    </a>
  {{else}}
    {{if $_category == 'internal'}}
      {{assign var=account value='internal'}}
      {{assign var=icon value='fas fa-users me-icon'}}
    {{else}}
      {{foreach from=$_accounts key=_guid item=_account}}
        {{assign var=account value=$_guid}}
        {{assign var=icon value='icon-i-laboratory'}}
      {{/foreach}}
    {{/if}}
    <a id="messagerie-{{$_category}}-navigation-container" class="me-inline-block" title="{{tr}}messagerie-{{$_category}}-title-access{{/tr}}" href="#">
      <span id="messagerie-{{$_category}}-navigation"{{if !$messagerie.counters.$_category.total}} class="none"{{/if}} onclick="Messagerie.openModal('{{$account}}');">
        <i class="{{$icon}} fa-lg" style="font-size: 1.3em;"></i>
        <span id="messagerie-{{$_category}}-total-counter" class="msg-counter"{{if !$messagerie.counters.$_category.total}} style="display: none;"{{/if}}>
          {{$messagerie.counters.$_category.total}}
        </span>
      </span>
    </a>
  {{/if}}
{{/foreach}}