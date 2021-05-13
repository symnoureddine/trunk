{{*
 * @package Mediboard\Lpp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  syncActField = function(form, field, value) {
    $V(getForm(form)[field], value);
  };

  changePrice = function(form, field, value) {
    if (field == 'quantite' && parseFloat(value) <= 0) {
      value = 1;
      $V(getForm(form + '-quantite').quantite, value);
    }

    syncActField(form, field, value);
    var quantite = parseInt($V(getForm(form + '-quantite').quantite));
    var base = parseFloat($V(getForm(form + '-montant_base').montant_base));
    $V(getForm(form + '-montant_final').montant_final, quantite * base);
  };

  updateActesLPP = function() {
    var url = new Url('lpp', 'ajax_codage_lpp');
    url.addParam('object_id', '{{$codable->_id}}');
    url.addParam('object_class', '{{$codable->_class}}');
    url.requestUpdate('lpp');
  };

  editDEP = function(form) {
    Modal.open(form + '-dep_modal', {showClose: true});
  };

  syncDEPFields = function(form) {
    var form_dep = getForm(form + '-dep');
    syncActField(form, 'accord_prealable', $V(form_dep.accord_prealable));
    syncActField(form, 'date_demande_accord', $V(form_dep.date_demande_accord));
    syncActField(form, 'reponse_accord', $V(form_dep.reponse_accord));

    Control.Modal.close();
  };

  Main.add(function() {
    $('count_lpp_{{$codable->_guid}}').innerHTML = '({{$codable->_ref_actes_lpp|@count}})';
  });
</script>

<table class="form">
  <tr>
    <th class="category">
      {{mb_title class=CActeLPP field=code}}
    </th>
    <th class="category">
      {{mb_title class=CActeLPP field=code_prestation}}
    </th>
    <th class="category">
      {{mb_title class=CActeLPP field=type_prestation}}
    </th>
    <th class="category">
      {{mb_title class=CActeLPP field=executant_id}}
    </th>
    <th class="category">
      {{mb_title class=CActeLPP field=date}}
    </th>
    <th class="category">
      {{mb_title class=CActeLPP field=date_fin}}
    </th>
    <th class="category">
      {{mb_title class=CActeLPP field=accord_prealable}}
    </th>
    <th class="category">
      {{mb_title class=CActeLPP field=qualif_depense}}
    </th>
    <th class="category">
      {{mb_title class=CActeLPP field=quantite}}
    </th>
    <th class="category">
      {{mb_title class=CActeLPP field=montant_base}}
    </th>
    <th class="category">
      {{mb_title class=CActeLPP field=montant_final}}
    </th>
    <th class="category compact"></th>
  </tr>

  {{mb_include module=lpp template=inc_acte acte_lpp=$acte_lpp}}

  {{foreach from=$codable->_ref_actes_lpp item=_acte_lpp}}
    {{mb_include module=lpp template=inc_acte acte_lpp=$_acte_lpp}}
  {{/foreach}}
</table>