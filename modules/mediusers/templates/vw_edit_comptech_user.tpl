{{*
 * @package Mediboard\Rhm
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<form name="Edit-{{$compte_ch->_guid}}" action="" method="post" onsubmit="return MediusersCh.onSaveCompteCh(this);">
  <input type="hidden" name="del" value="0" />
  {{mb_key   object=$compte_ch}}
  {{mb_class object=$compte_ch}}
  {{mb_field object=$compte_ch field=user_id hidden=true}}
  <table class="form">
    {{mb_include module=system template=inc_form_table_header object=$compte_ch show_notes=false}}
    <tr>
      <th>{{mb_label object=$compte_ch field=name}}</th>
      <td>{{mb_field object=$compte_ch field=name}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$compte_ch field=rcc}}</th>
      <td>{{mb_field object=$compte_ch field=rcc}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$compte_ch field=adherent}}</th>
      <td>{{mb_field object=$compte_ch field=adherent}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$compte_ch field=debut_bvr}}</th>
      <td>{{mb_field object=$compte_ch field=debut_bvr}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$compte_ch field=banque_id}}</th>
      <td>{{mb_field object=$compte_ch field=banque_id options='Ox\Mediboard\Cabinet\CBanque::loadAllBanques'|static_call:null emptyLabel="CBanque.select"}}
      </td>
    </tr>
    <tr>
      <td class="button" colspan="2">
        {{if $compte_ch->_id}}
          <button class="modify" type="submit">{{tr}}Save{{/tr}}</button>
          <button class="trash" type="button" onclick="confirmDeletion(this.form);">
            {{tr}}Delete{{/tr}}
          </button>
        {{else}}
          <button class="submit" type="submit">{{tr}}Create{{/tr}}</button>
        {{/if}}
      </td>
    </tr>
  </table>
</form>