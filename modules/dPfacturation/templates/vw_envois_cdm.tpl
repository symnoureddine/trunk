{{*
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  refreshListCDM = function(){
    var oForm = getForm("filter_envois_cdm");
    var url = new Url("facturation" , "vw_envois_cdm");
    url.addFormData(oForm);
    url.addParam("view_list", 1);
    url.requestUpdate("list_envois_cdm");
  };

  changePageCDM = function(page) {
    var url = new Url("facturation" , "vw_envois_cdm");
    url.addParam('page', page);
    url.requestUpdate("list_envois_cdm");
  };

  Main.add(function () {
    refreshListCDM();
  });
</script>

<form name="filter_envois_cdm" action="" method="get">
  <input type="hidden" name="page" value="{{$page}}" onchange="refreshList()"/>
  <table class="form" name="table_envoi_cdm">
    <tr>
      <th>{{tr}}Since-long{{/tr}}</th>
      <td>{{mb_field object=$filter field="_date_min" form="filter_envois_cdm" canNull="false" register=true}}</td>
      <th>{{mb_title object=$envoi_cdm field=result}}</th>
      <td>{{mb_field object=$envoi_cdm field=result emptyLabel="All"}}</td>
      <th>{{mb_title object=$envoi_cdm field=filename}}</th>
      <td>{{mb_field object=$envoi_cdm field=filename}}</td>
    </tr>
    <tr>
      <th>{{tr}}date.To_long{{/tr}}</th>
      <td>{{mb_field object=$filter field="_date_max" form="filter_envois_cdm" canNull="false" register=true}}</td>
      <th>{{mb_title class=CEnvoiCDMMessage field=code}}</th>
      <td>
        <select name="code">
          <option value="">-- {{tr}}All{{/tr}}</option>
          {{foreach from='Ox\Mediboard\Tarmed\CEnvoiCDMMessage'|static:"list_error" item=_code}}
            <option value="{{$_code}}" {{if $code == $_code}}selected="selected"{{/if}}>{{tr}}CEnvoiCDMMessage.code.{{$_code}}{{/tr}}</option>
          {{/foreach}}
        </select>
      </td>
      <th>{{mb_title object=$envoi_cdm field=statut}}</th>
      <td>{{mb_field object=$envoi_cdm field=statut emptyLabel="All"}}</td>
    </tr>
    <tr>
      <td class="button" colspan="6">
        <button type="button" onclick="$V(this.form.page, 0);refreshListCDM();" class="search" >{{tr}}Filter{{/tr}}</button>
      </td>
    </tr>
  </table>
</form>

<div id="list_envois_cdm" class="me-padding-0"></div>