{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=sourceCode script=gitlab ajax=1}}
{{mb_script module=oxERP      script=ox.erp ajax=1}}

<script>
  Main.add(function() {
    Control.Tabs.create('control-tabs-sourcecode-gitlab-ci', false);
  });
</script>

<ul class="control_tabs" id="control-tabs-sourcecode-gitlab-ci">
  <li><a href="#inc_vw_gitlab_pipelines">{{tr}}CGitlabPipeline|pl{{/tr}}</a></li>
  <li><a href="#inc_vw_gitlab_tools">{{tr}}Tools{{/tr}}</a></li>
</ul>

{{* Gitlab CI Pipelines *}}
<div id="inc_vw_gitlab_pipelines" style="display: none">
  {{mb_include module=sourceCode template=inc_vw_gitlab_pipelines}}
</div>

{{* Gitlab CI Tools (Legacy) *}}
<div id="inc_vw_gitlab_tools" style="display: none">
  <table class="form">
    <tbody>
    <tr>
      <th class="title" colspan="2">Actions</th>
    </tr>
    <tr>
      <th style="width: 50%">
        <label>Clear old pipelines</label>
      </th>
      <td>
        <a href="{{$url_clear_pipelines}}" target="new">{{$url_clear_pipelines}}</a>
      </td>
    </tr>
    </tbody>
  </table>
</div>
