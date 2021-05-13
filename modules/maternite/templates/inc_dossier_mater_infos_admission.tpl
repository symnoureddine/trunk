{{*
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=admissions script=admissions ajax=true}}
{{mb_script module=planningOp script=sejour     ajax=true}}

<table class="form me-no-box-shadow">
  <tr>
    <th class="halfPane">{{mb_label object=$sejour field=praticien_id}}</th>
    <td>{{mb_include module=mediusers template=inc_vw_mediuser mediuser=`$sejour->_ref_praticien`}}</td>
    <td class="button" rowspan="4" style="vertical-align: middle;">

      <button class="edit not-printable me-tertiary"
              onclick="Admissions.validerEntree('{{$sejour->_id}}', null, DossierMater.refreshEntreeSortie.curry('{{$sejour->_id}}', 'infos_admission'));">
        Admission
      </button>

      <br />

      <button type="button" class="edit not-printable me-tertiary"
              onclick="Sejour.editModal('{{$sejour->_id}}', 0, 0, DossierMater.refreshEntreeSortie.curry('{{$sejour->_id}}', 'infos_admission'))">
        DHE
      </button>
    </td>
  </tr>
  <tr>
    <th>{{mb_label object=$sejour field=entree_prevue}}</th>
    <td>{{mb_value object=$sejour field=entree_prevue}}</td>
  </tr>
  <tr>
    <th>{{mb_label object=$sejour field=entree_reelle}}</th>
    <td>{{mb_value object=$sejour field=entree_reelle}}</td>
  </tr>
  <tr>
    <th>{{mb_label object=$sejour field=mode_entree}}</th>
    <td>{{mb_value object=$sejour field=mode_entree}}</td>
  </tr>
</table>