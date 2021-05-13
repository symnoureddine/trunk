{{*
 * @package Mediboard\Pmsi
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<thead id="title_lines">
  <tr>
    <th class="not-printable narrow"></th>
    <th class="narrow">
      {{tr}}CPatient-NDA{{/tr}}
    </th>
    <th class="narrow" onclick="Relance.changeSort(this, 3)">
      <a class="sortable">
        {{tr}}CPatient{{/tr}}
      </a>
    </th>
    <th class="narrow" onclick="Relance.changeSort(this, 4)">
      <a class="sortable">
        {{tr}}common-Operating entree-court{{/tr}}
      </a>
    </th>
    <th class="narrow" onclick="Relance.changeSort(this, 5)">
      <a class="sortable">
        {{tr}}common-Operating sortie-court{{/tr}}
      </a>
    </th>
    <th class="narrow" onclick="Relance.changeSort(this, 6)">
      <a class="sortable">
        {{tr}}CRelancePMSI-Statistics-court{{/tr}}
      </a>
    </th>
    <th class="narrow" onclick="Relance.changeSort(this, 7)">
      <a class="sortable">
        {{tr}}CRelancePMSI-Responsible Physician-court{{/tr}}
      </a>
    </th>
    <th class="narrow" onclick="Relance.changeSort(this, 8)">
      <a class="sortable">
        {{tr}}CRelancePMSI-Restate Status{{/tr}}
      </a>
    </th>
    {{foreach from='Ox\Mediboard\Pmsi\CRelancePMSI'|static:"docs" item=doc}}
      {{if "dPpmsi relances $doc"|gconf}}
        <th style="width: 46px;" title="{{tr}}CRelancePMSI-{{$doc}}-desc{{/tr}}">{{tr}}CRelancePMSI-{{$doc}}-court{{/tr}}</th>
      {{/if}}
    {{/foreach}}
    <th>{{tr}}CRelancePMSI-commentaire_dim{{/tr}}</th>
    <th>{{tr}}CRelancePMSI-Medical Comment-court{{/tr}}</th>
    <th class="narrow" onclick="Relance.changeSort(this, -1)">
      <a class="sortable">
        {{tr}}CRelancePMSI-Level{{/tr}}
      </a>
    </th>
  </tr>
</thead>
