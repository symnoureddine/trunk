{{*
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{if !$app->user_prefs.directory_to_watch}}
  {{mb_return}}
{{/if}}

{{mb_script module=cabinet script=yoplet ajax=true}}

{{assign var=yoplet_upload_url value="dPfiles General yoplet_upload_url"|gconf}}
{{assign var=cookies value=""}}

{{foreach from="; "|explode:$smarty.server.HTTP_COOKIE item=cookie}}
  {{assign var=temp_cookie value='='|explode:$cookie}}
  {{assign var=cookies value="`$cookies` `$temp_cookie.0`"}}
{{/foreach}}

<script>
  {{assign var=extensions_yoplet value="dPfiles General extensions_yoplet"|gconf}}
  File.applet.extensions = "{{$extensions_yoplet|lower}} {{$extensions_yoplet|upper}}";
  File.appletDirectory = "{{$app->user_prefs.directory_to_watch|addslashes}}";
</script>

<!-- Modale pour l'applet -->
{{mb_include module=files template=yoplet_modal object=$object}}

<applet
  name="yopletuploader"
  code="org.yoplet.Yoplet.class"
  archive="includes/applets/yoplet.jar?build={{app_version_key}}"
  {{if $app->user_prefs.debug_yoplet == 1}}
    width="400" height="400"
  {{else}}
    width="0" height="0" style="position: absolute;"
  {{/if}}>
  <param name="action" value="" />
  <param name="debug" value="{{if $app->user_prefs.debug_yoplet}}true{{else}}false{{/if}}" />
  <param name="codebase_lookup" value="false" />
  <param name="permissions" value="all-permissions" />

  {{if $yoplet_upload_url}}
    <param name="url" value="{{$yoplet_upload_url}}/?m=files&raw=ajax_yoplet_upload" />
  {{else}}
    <param name="url" value="{{$base_url}}/?m=files&raw=ajax_yoplet_upload" />
  {{/if}}

  <param name="content" value="a" />
  <param name="cookies" value="{{$app->session_name}} {{$cookies}}" />
  <param name="user_agent" value="{{$smarty.server.HTTP_USER_AGENT}}" />
  <param name="do_callback" value="1" />
</applet>

{{if $app->user_prefs.debug_yoplet}}
  <div id="yoplet-debug-console" style="border: 1px solid grey;">
    Directory watched: "{{$app->user_prefs.directory_to_watch}}"<br />
  </div>
{{/if}}