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
    Control.Tabs.create('control-tabs-sourcecode-gitlab', false);
  });
</script>

<ul class="control_tabs" id="control-tabs-sourcecode-gitlab">
  <li><a href="#inc_vw_gitlab_commits">{{tr}}CGitlabCommit|pl{{/tr}}</a></li>
  {{if !$only_display_commits}}
    <li><a href="#inc_vw_gitlab_projects">{{tr}}CGitlabProject|pl{{/tr}}</a></li>
  {{/if}}
</ul>

<div id="inc_vw_gitlab_commits" style="display: none">
  {{mb_include module=sourceCode template=inc_vw_gitlab_commits}}
</div>

{{if !$only_display_commits}}
  <div id="inc_vw_gitlab_projects" style="display: none">
    {{mb_include module=sourceCode template=inc_vw_gitlab_projects}}
  </div>
{{/if}}
