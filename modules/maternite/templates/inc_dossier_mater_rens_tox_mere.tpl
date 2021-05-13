{{*
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<form name="Tox-mere-{{$dossier->_guid}}" method="post"
      onsubmit="return onSubmitFormAjax(this);">
  {{mb_class object=$dossier}}
  {{mb_key   object=$dossier}}
  <input type="hidden" name="_count_changes" value="0" />
  <input type="hidden" name="grossesse_id" value="{{$grossesse->_id}}" />
  <table class="form me-no-align me-no-box-shadow me-small-form">
    <tr>
      <th class="title" colspan="2">Mère</th>
    </tr>
    <tr>
      <th class="halfPane">{{mb_label object=$dossier field=tabac_avant_grossesse}}</th>
      <td>{{mb_field object=$dossier field=tabac_avant_grossesse default=""}}</td>
    </tr>
    <tr>
      <th><span class="compact">{{mb_label object=$dossier field=qte_tabac_avant_grossesse}}</span></th>
      <td>{{mb_field object=$dossier field=qte_tabac_avant_grossesse}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$dossier field=tabac_debut_grossesse}}</th>
      <td>{{mb_field object=$dossier field=tabac_debut_grossesse default=""}}</td>
    </tr>
    <tr>
      <th><span class="compact">{{mb_label object=$dossier field=qte_tabac_debut_grossesse}}</span></th>
      <td>{{mb_field object=$dossier field=qte_tabac_debut_grossesse}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$dossier field=alcool_debut_grossesse}}</th>
      <td>{{mb_field object=$dossier field=alcool_debut_grossesse default=""}}</td>
    </tr>
    <tr>
      <th><span class="compact">{{mb_label object=$dossier field=qte_alcool_debut_grossesse}}</span></th>
      <td>{{mb_field object=$dossier field=qte_alcool_debut_grossesse}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$dossier field=canabis_debut_grossesse}}</th>
      <td>{{mb_field object=$dossier field=canabis_debut_grossesse default=""}}</td>
    </tr>
    <tr>
      <th><span class="compact">{{mb_label object=$dossier field=qte_canabis_debut_grossesse}}</span></th>
      <td>{{mb_field object=$dossier field=qte_canabis_debut_grossesse}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$dossier field=subst_avant_grossesse}}</th>
      <td>{{mb_field object=$dossier field=subst_avant_grossesse default=""}}</td>
    </tr>
    <tr>
      <th><span class="compact">{{mb_label object=$dossier field=mode_subst_avant_grossesse}}</span></th>
      <td>
        {{mb_field object=$dossier field=mode_subst_avant_grossesse
        style="width: 12em;" emptyLabel="CDossierPerinat.mode_subst_avant_grossesse."}}
      </td>
    </tr>
    <tr>
      <th><span class="compact">{{mb_label object=$dossier field=nom_subst_avant_grossesse}}</span></th>
      <td>{{mb_field object=$dossier field=nom_subst_avant_grossesse}}</td>
    </tr>
    <tr>
      <th><span class="compact">{{mb_label object=$dossier field=subst_subst_avant_grossesse}}</span></th>
      <td>{{mb_field object=$dossier field=subst_subst_avant_grossesse}}</td>
    </tr>
    <tr>
      <th>{{mb_label object=$dossier field=subst_debut_grossesse}}</th>
      <td>{{mb_field object=$dossier field=subst_debut_grossesse default=""}}</td>
    </tr>
  </table>
</form>