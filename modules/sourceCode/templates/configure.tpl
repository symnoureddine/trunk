{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=sourceCode script=sourcecode}}

<script>
  Main.add(function () {
    Control.Tabs.create('tabs-configure', true);
  });
</script>

<ul id="tabs-configure" class="control_tabs">
  <li><a href="#config-global">{{tr}}Configuration{{/tr}}</a></li>
  <li><a href="#config-gitlab-maintenance">{{tr}}sourceCode-legend-Gitlab-API-imports{{/tr}}</a></li>
</ul>

<div id="config-global" style="display: none;">
  <form name="editConfig" action="?m=sourceCode&amp;{{$actionType}}=configure" method="post" onsubmit="return checkForm(this)">
    {{mb_configure module=$m}}
    <table class="form">
      <tr>
        <th class="title" colspan="2">Configuration</th>
      </tr>
      <tr>
        <td class="button" colspan="2">
          <button class="modify me-primary">{{tr}}Save{{/tr}}</button>
        </td>
      </tr>
    </table>
  </form>

  {{mb_include module=system template=inc_config_exchange_source source=$source_http}}
</div>

<div id="config-gitlab-maintenance" style="display: none;">
  {{mb_include module=sourceCode template=inc_vw_gitlab_maintenance}}
</div>


