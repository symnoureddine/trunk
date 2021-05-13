{{*
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  changePageCorrespondants = function (page, type) {
    var form = getForm('search-medecin');
    var url = new Url('mediusers', 'ajax_search_medecin');
    url.addFormData(form);
    url.addParam('start', page);
    url.addParam('type', type);
    url.requestUpdate('result-search-medecin-' + type);
  };

  fillMediuserFields = function (medecin_id) {
    var url = new Url('mediusers', 'ajax_edit_mediuser');
    url.addParam('medecin_id', medecin_id);
    url.addParam('user_id', null);
    url.requestModal(800, '70%');
  };

  {{if !$type}}
    Main.add(function() {
      Control.Tabs.create('medecin-search-tabs');
    });
  {{/if}}

  {{if $medecins}}
    Main.add(function() {
      Control.Tabs.setTabCount('result-search-medecin-exact', {{$total.exact}});
    });
  {{/if}}

  {{if $medecins_close}}
  Main.add(function() {
    Control.Tabs.setTabCount('result-search-medecin-close', {{$total.close}});
  });
  {{/if}}
</script>

{{if !$type}}
  <ul class="control_tabs" id="medecin-search-tabs">
    <li><a href="#result-search-medecin-exact">{{tr}}mediusers-Search medecin result exact{{/tr}}</a></li>
    <li><a href="#result-search-medecin-close">{{tr}}mediusers-Search medecin result close{{/tr}}</a></li>
  </ul>
{{/if}}


{{if !$type || $medecins}}
  <div id="result-search-medecin-exact" {{if !$type}}style="display: none;"{{/if}}>
    {{mb_include template=inc_list_medecin total=$total.exact page=$page medecins=$medecins type='exact'}}
  </div>
{{/if}}

{{if !$type || $medecins_close}}
  <div id="result-search-medecin-close" {{if !$type}}style="display: none;"{{/if}}>
    {{mb_include template=inc_list_medecin total=$total.close page=$page medecins=$medecins_close type='close'}}
  </div>
{{/if}}