{{*
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function () {
    Control.Tabs.create('tabs-interop-norm-domains', true);
  });

  checkAll = function(family_name, category_name) {
    $$(".form-message-supported-"+family_name+"-"+category_name).each(function(form) {
      form.onsubmit();
    });
  }
</script>

<table class="form">
  <tr>
    <td style="vertical-align: top; width: 250px" >
      <ul id="tabs-interop-norm-domains" class="control_tabs_vertical small">
        {{foreach from=$all_messages key=_domain item=_families}}
          <li>
            <a href="#{{$_domain}}" class="me-flex-wrap">
              {{tr}}{{$_domain}}{{/tr}}
              <br />
              <span class="compact">{{tr}}{{$_domain}}-desc{{/tr}}</span>
            </a>
          </li>
        {{/foreach}}
      </ul>
    </td>
    <td style="vertical-align: top;">
      {{foreach from=$all_messages key=_domain_name item=_domains}}
        <div id="{{$_domain_name}}" style="display: none;">
          <script type="text/javascript">
            Control.Tabs.create('tabs-'+'{{$_domain_name}}'+'-families', true);
          </script>

          <ul id="tabs-{{$_domain_name}}-families" class="control_tabs small">
            {{foreach from=$_domains item=_families}}
              {{assign var=_family_name value=$_families|getShortName}}

              <li class="me-tabs-flex">
                <a href="#{{$_family_name}}" class="me-flex-column">
                  {{tr}}{{$_family_name}}{{/tr}}
                  <br />
                  <span class="compact">{{tr}}{{$_family_name}}-desc{{/tr}}</span>
                </a>
              </li>
            {{/foreach}}
          </ul>

          <hr />

          {{foreach from=$_domains item=_families}}
            {{assign var=_family_name value=$_families|getShortName}}

            <div id="{{$_family_name}}" style="display: none;">
              <table class="tbl form">
                {{foreach from=$_families->_categories key=_category_name item=_messages_supported}}
                    <tr>
                      <th style="text-align: left;" class="section" colspan="4">
                        <button class="fa fa-check notext" onclick="checkAll('{{$_family_name}}', '{{$_category_name}}')"></button>
                        {{if $_category_name != "none"}}
                          {{tr}}{{$_category_name}}{{/tr}} (<em>{{$_category_name}})</em></th>
                        {{else}}
                          {{tr}}All{{/tr}}
                        {{/if}}
                    </tr>

                  {{foreach from=$_messages_supported item=_message_supported}}
                    <tr>
                      {{unique_id var=uid numeric=true}}
                      <td class="narrow" id="actor_message_supported_{{$uid}}">
                        {{mb_include template=inc_active_message_supported_form}}
                      </td>
                      <td style="vertical-align: middle;" class="narrow"><strong>{{tr}}{{$_message_supported->message}}{{/tr}}</strong></td>
                      <td style="vertical-align: middle;" class="narrow"> <i class="fa fa-arrow-right"></i></td>
                      <td style="vertical-align: middle;" class="text compact">{{tr}}{{$_message_supported->message}}-desc{{/tr}}</td>
                    </tr>
                  {{/foreach}}
                {{/foreach}}
              </table>
            </div>
          {{/foreach}}
        </div>
      {{/foreach}}
    </td>
  </tr>
</table>