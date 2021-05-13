{{*
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<div class="small-info">Si la source "<strong>Boîte de dépôt d'envoi des relances</strong>" n'est pas définie, les relances seront déposées de la même manière que les factures.</div>
{{foreach from=$fs_sources_tarmed item=_sources_tarmed key=name_source_tarmed}}
  <fieldset>
    <legend>{{tr}}{{$name_source_tarmed}}_tarmed{{/tr}}</legend>

    {{assign var=_source_tarmed value=$_sources_tarmed.0}}

    {{if !$_source_tarmed->_id}}
      <button type="button" class="add"
              onclick="ExchangeSource.editSource('{{$_source_tarmed->_guid}}', true, '{{$_source_tarmed->name}}',
                '{{$_source_tarmed->_wanted_type}}', null, MediusersCh.loadArchivesFacturation('{{$mediuser->_id}}'))">
        {{tr}}CSourceFileSystem-title-create{{/tr}}
      </button>
    {{/if}}

    {{mb_include module=system template=inc_vw_list_sources sources=$_sources_tarmed callback_source="MediusersCh.loadArchivesFacturation(`$mediuser->_id`)"}}
  </fieldset>
{{/foreach}}