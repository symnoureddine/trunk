{{*
 * @package Mediboard\system
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<div id="cache">

    {{mb_script module="system" script="cache"}}

  <table id="CacheClearTab" class="tbl main" style="table-layout: fixed;">
    <thead>
    <tr>
      <th id="CacheClearTabKeyStone" class="text no-server">Cibles</th>
        {{if $servers_ip|@count}}
          <th class="text all-servers">{{tr}}CMonitorServer.all{{/tr}}</th>
        {{/if}}
      <th class="text current-server">{{tr}}CMonitorServer.this{{/tr}}</th>
        {{if $servers_ip|@count > 0}}
            {{foreach from=$servers_ip item=server_ip}}
              <th class="text target-server">{{$server_ip}}</th>
            {{/foreach}}
        {{/if}}
    </tr>
    </thead>
    <tbody>
    {{foreach from=$cache_keys key=cache_key item=cache_value}}
      <tr class="cache" id="cache-{{$cache_key}}">
        <th class="text no-server" style="text-align:left; padding-left: 10px;">
            {{if $cache_key === 'css'}}
              <i class="fab fa-css3"></i>
            {{elseif $cache_key === 'js' || $cache_key === 'storybook'}}
              <i class="fab fa-js"></i>
            {{elseif $cache_key === 'config'}}
              <i class="fas fa-wrench"></i>
            {{elseif $cache_key === 'locales'}}
              <i class="fas fa-globe"></i>
            {{elseif $cache_key === 'logs'}}
              <i class="fas fa-clipboard-list"></i>
            {{elseif $cache_key === 'templates'}}
              <i class="fas fa-file"></i>
            {{elseif $cache_key === 'devtools'}}
              <i class="fas fa-toolbox"></i>
            {{elseif $cache_key === 'children'}}
              <i class="fas fa-sitemap"></i>
            {{elseif $cache_key === 'core'}}
              <i class="fas fa-cogs"></i>
            {{elseif $cache_key === 'modules'}}
              <i class="fa fa-cubes"></i>
            {{/if}}
            {{tr}}CacheManager-cache_values.{{$cache_key}}{{/tr}}
        </th>
          {{if $servers_ip|@count}}
            <td class="all-servers">
              <button class="singleclick fill {{if $cache_key === 'all'}}cancel{{else}}trash{{/if}} notext"
                      onclick="CacheManager.allEmpty('{{$cache_key}}');"
                      title="{{tr}}Clear{{/tr}}&nbsp;:&nbsp;{{tr}}CacheManager-cache_values.{{$cache_key}}{{/tr}}">
              <span class="sr-only">
                {{tr}}Clear{{/tr}}&nbsp;:&nbsp;{{tr}}CacheManager-cache_values.{{$cache_key}}{{/tr}}
              </span>
              </button>
            </td>
          {{/if}}
        <td class="current-server">
          <button class="singleclick fill {{if $cache_key === 'all'}}cancel{{else}}trash{{/if}} notext"
                  onclick="CacheManager.empty('{{$cache_key}}');"
                  title="{{tr}}Clear{{/tr}}&nbsp;:&nbsp;{{tr}}CacheManager-cache_values.{{$cache_key}}{{/tr}}">
            <span class="sr-only">
              {{tr}}Clear{{/tr}}&nbsp;:&nbsp;{{tr}}CacheManager-cache_values.{{$cache_key}}{{/tr}}
            </span>
          </button>
        </td>
          {{foreach from=$servers_ip item=server_ip}}
            <td class="target-server">
              <button class="singleclick fill {{if $cache_key === 'all'}}cancel{{else}}trash{{/if}} notext"
                      onclick="CacheManager.allEmpty('{{$cache_key}}', false, '{{$server_ip}}');"
                      title="{{tr}}Clear{{/tr}}&nbsp;:&nbsp;{{tr}}CacheManager-cache_values.{{$cache_key}}{{/tr}}">
              <span class="sr-only">
                {{tr}}Clear{{/tr}}&nbsp;:&nbsp;{{tr}}CacheManager-cache_values.{{$cache_key}}{{/tr}}
              </span>
              </button>
            </td>
          {{/foreach}}
      </tr>

        {{* Below clear modules cache, display modules list *}}
        {{if $cache_key === 'modules'}}
            {{foreach from=$modules_cache key=module_name item=module_cache}}
              <tr class="cache-module" id="cache-module-{{$module_name}}">
                <th class="text no-server section" style="text-align:left; padding-left: 10px;">
                  <i class="fa fa-cube"></i>
                  <span style="text-transform: initial;">{{tr}}module-{{$module_name}}-court{{/tr}}</span>
                </th>
                  {{if $servers_ip|@count}}
                    <td class="all-servers">
                      <button class="singleclick fill trash notext"
                              onclick="CacheManager.allEmpty('{{$cache_key}}', '{{$module_cache.class|JSAttribute}}');"
                              title="{{tr}}Clear{{/tr}}&nbsp;:&nbsp;{{tr}}CacheManager-cache_module{{/tr}} {{$module_name}}">
                  <span class="sr-only">
                    {{tr}}Clear{{/tr}}&nbsp;:&nbsp;{{tr}}CacheManager-cache_module{{/tr}} {{$module_name}}
                  </span>
                      </button>
                    </td>
                  {{/if}}
                <td class="current-server">
                  <button class="singleclick fill trash notext"
                          onclick="CacheManager.empty('{{$cache_key}}', '{{$module_cache.class|escape:javascript}}');"
                          title="{{tr}}Clear{{/tr}}&nbsp;:&nbsp;{{tr}}CacheManager-cache_module{{/tr}} {{$module_name}}">
                <span class="sr-only">
                  {{tr}}Clear{{/tr}}&nbsp;:&nbsp;{{tr}}CacheManager-cache_module{{/tr}} {{$module_name}}
                </span>
                  </button>
                </td>
                  {{foreach from=$servers_ip item=server_ip}}
                    <td class="target-server">
                      <button class="singleclick fill {{if $cache_key === 'all'}}cancel{{else}}trash{{/if}} notext"
                              onclick="CacheManager.allEmpty('{{$cache_key}}', '{{$module_cache.class|escape:javascript}}', '{{$server_ip}}');"
                              title="{{tr}}Clear{{/tr}}&nbsp;:&nbsp;{{tr}}CacheManager-cache_module{{/tr}} {{$module_name}}">
                  <span class="sr-only">
                    {{tr}}Clear{{/tr}}&nbsp;:&nbsp;{{tr}}CacheManager-cache_module{{/tr}} {{$module_name}}
                  </span>
                      </button>
                    </td>
                  {{/foreach}}
              </tr>
                {{foreachelse}}
                {{math equation='(x+y)' x=$servers_ip|@count y=3 assign=columns}}
              <tr>
                <td class="warning" colspan="{{$columns}}">
                  <div class="small-warning">{{tr}}CacheManager-msg-cache_modules_unavailable_cache_must_be_cleared{{/tr}}</div>
                </td>
              </tr>
            {{/foreach}}
        {{/if}}
    {{/foreach}}
    </tbody>
  </table>

  <table id="CacheManagerFeedbackTab" class="main tbl">
    <tbody>
    <tr>
      <th class="section" colspan="2">
          {{tr}}common-Message|pl{{/tr}}
      </th>
    </tr>
    <tr>
      <td id="CacheManagerFeedback">
        <div class="info">{{tr}}CacheManager-msg-cache_logs_will_be_displayed_here{{/tr}}</div>
      </td>
    </tr>
    </tbody>
  </table>
</div>
