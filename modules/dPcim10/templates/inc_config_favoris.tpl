{{*
 * @package Mediboard\Cim10
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script type="text/javascript">
  onChangeOwner = function(field) {
    if ($V(field) != '') {
      countFavoris(field);
    }
  };

  countFavoris = function(field) {
    var url = new Url('cim10', 'ajax_count_favoris');
    url.addParam(field.name, $V(field));
    url.requestJSON(function(data) {
      if (data.count != 0) {
        $('favoris_count').innerHTML = data.count + ' favoris � exporter';
        $('favoris_count').removeClassName('empty');
        $('button_export_favoris').enable();
      }
      else {
        $('favoris_count').innerHTML = 'Aucun favoris � exporter';
        $('favoris_count').addClassName('empty');
        $('button_export_favoris').disable();
      }
    });
  };

  emptySelector = function(form, object) {
    $V(form.elements['_' + object + '_view'], '', false);
    $V(form.elements[object + '_id'], '', false);
    $('button_export_favoris').disable();
  };

  Main.add(function() {
    var form = getForm('exportFavoris');

    var url = new Url('mediusers', 'ajax_users_autocomplete');
    url.addParam('praticiens', 1);
    url.addParam('input_field', '_user_view');
    url.autoComplete(form.elements['_user_view'], null, {
      minChars: 0,
      method: 'get',
      select: 'view',
      dropdown: true,
      afterUpdateElement: function(field, selected) {
        $V(field, selected.down('.view').innerHTML);
        $V(field.form.elements['user_id'], selected.getAttribute('id').split('-')[2]);
      }
    });

    form = getForm('importFavoris');

    url = new Url('mediusers', 'ajax_users_autocomplete');
    url.addParam('praticiens', 1);
    url.addParam('input_field', '_user_view');
    url.autoComplete(form.elements['_user_view'], null, {
      minChars: 0,
      method: 'get',
      select: 'view',
      dropdown: true,
      afterUpdateElement: function(field, selected) {
        $V(field, selected.down('.view').innerHTML);
        $V(field.form.elements['user_id'], selected.getAttribute('id').split('-')[2]);
      }
    });
  });
</script>

<fieldset style="display: inline-block; width: 49%;">
  <legend>Export de favoris</legend>
  <form name="exportFavoris" method="post" action="?" target="_blank">
    <input type="hidden" name="m" value="cim10"/>
    <input type="hidden" name="dosql" value="do_export_favoris"/>

    <table class="form">
      <tr>
        <td colspan="2">
          <div class="small-info">
            S�lectionner l'utilisateur ou la fonction dont vous voulez exporter les favoris
          </div>
        </td>
      </tr>
      <tr>
        <th>
          <label for="user_id">{{tr}}CUser{{/tr}}</label>
        </th>
        <td>
          <input type="text" name="_user_view" value="" style="width: 12em;">
          <input type="hidden" name="user_id" value="" onchange="onChangeOwner(this);">
          <button type="button" class="cancel notext" onclick="emptySelector(this.form, 'user');">{{tr}}Empty{{/tr}}</button>
        </td>
      </tr>
      <tr>
        <th>Nombre de favoris</th>
        <td id="favoris_count" class="empty">Aucun favoris � importer</td>
      </tr>
      <tr>
        <td class="button" colspan="2">
          <button id="button_export_favoris" type="submit" class="fa fa-download" disabled>
            Exporter
          </button>
        </td>
      </tr>
    </table>
  </form>
</fieldset>

<fieldset style="display: inline-block; width: 49%;">
  <legend>Import de favoris</legend>
  <form name="importFavoris" method="post" action="?" enctype="multipart/form-data" onsubmit="return onSubmitFormAjax(this);">
    <input type="hidden" name="m" value="cim10">
    <input type="hidden" name="dosql" value="do_import_favoris">
    <input type="hidden" name="ajax" value="1" />

    <table class="form">
      <tr>
        <td colspan="2">
          <div class="small-info">
            Le fichier doit �tre un fichier CSV (au format ISO), dont les champs sont s�par�s par des <strong>;</strong><br/>
            et les textes par <strong>"</strong>, la premi�re ligne �tant saut�e :
            <ul>
              <li>Identifiant Mediboard du propri�taire</li>
              <li>Liste des tags (s�par�s par des |)</li>
              <li>Code *</li>
            </ul>
            <em>* : champs obligatoires</em>
            <br/>
            <br/>
            Dans le cas o� un utilisateur ou une fonction est s�lectionn�e via les champs ci-dessous,<br/>
            les propri�taires renseign�s dans le fichier seront ignor�s,<br>
            et les favoris seront attribu�s au propri�taire s�lectionn� dans les champs.
          </div>
        </td>
      </tr>
      <tr>
        <th>
          <label for="user_id">{{tr}}CUser{{/tr}}</label>
        </th>
        <td>
          <input type="text" name="_user_view" value="" style="width: 12em;">
          <input type="hidden" name="user_id" value="">
          <button type="button" class="cancel notext" onclick="emptySelector(this.form, 'user');">{{tr}}Empty{{/tr}}</button>
        </td>
      </tr>
      <tr>
        <th>
          <label for="import">Fichier d'import</label>
        </th>
        <td>
          {{mb_include module=system template=inc_inline_upload lite=true paste=false extensions='csv'}}
        </td>
      </tr>
      <tr>
        <td class="button" colspan="2">
          <button id="button_import_favoris" type="submit" class="fa fa-upload">
            Importer
          </button>
        </td>
      </tr>
    </table>
  </form>
</fieldset>