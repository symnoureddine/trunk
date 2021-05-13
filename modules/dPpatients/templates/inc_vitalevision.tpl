{{*
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{assign var=debug value="false"}}

<!-- Ne pas mettre "display: none" sinon l'applet ne se lancera pas dans Firefox -->
<applet name="resultVitaleVision"
        code="org.yoplet.Yoplet.class"
        archive="includes/applets/yoplet.jar?build={{app_version_key}}"
  {{if $debug=="true"}}
  width="400" height="200"
  {{else}}
  width="1" height="1" style="position: absolute;"
  {{/if}}>
  <param name="action" value="sleep" />
  <param name="initial_focus" value="false" />
  <param name="lineSeparator" value="" />
  <param name="debug" value="{{$debug}}" />
  <param name="codebase_lookup" value="false" />
  <param name="permissions" value="all-permissions" />
  <param name="filePath" value="{{$app->user_prefs.VitaleVisionDir}}/VitaleHex.xml" />
  {{if !@$keepFiles}}
    <param name="flagPath" value="{{$app->user_prefs.VitaleVisionDir}}/Vitale.csv" />
  {{/if}}

  <param name="cookies" value="{{$app->session_name}}" />
</applet>

{{mb_script script=vitalevision ajax=1}}