{{*
 * @package Mediboard\Context
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=context script=ContextualIntegration ajax=true}}

{{mb_default var=return_line value=false}}
{{mb_default var=style_used value=""}}
{{mb_default var=show_menu value=false}}

{{foreach from=$locations item=_location}}
    {{assign var=_integration value=$_location->_ref_integration}}

    {{assign var=_fa value=false}}
    {{if $_integration->icon_url|strpos:"fa-" === 0}}
        {{assign var=_fa value=true}}
    {{/if}}

    {{if !$_fa}}
        <style>
            #integration-{{$uid}} button.integration-{{$_integration->_id}}::before {
                content: "";
                background-image: url({{$_integration->icon_url}});
                height: 18px;
                width: 18px;
                background-size: cover;
                background-position: center center;
                background-repeat: no-repeat;
            }
            #integration-{{$uid}} button.dd-integration::before {
                vertical-align: middle;
            }
        </style>
    {{/if}}

    {{if !$show_menu}}
        <span title="{{$_integration->description}}">
            {{if $_location->button_type == "button" || $_location->button_type == "button_text"}}
                <button type="button"
                        class="me-tertiary me-small contextual-trigger {{if $_fa}}fa {{$_integration->icon_url}}{{/if}} integration-{{$_integration->_id}}
                            {{if $_location->button_type == "button"}} notext {{/if}}"
                        title="{{$_integration->description}}"
                        data-url="{{$_integration->_url}}"
                        data-title="{{$_integration->title}}"
                        data-display_mode="{{$_integration->display_mode}}"
                        onclick="ContextualIntegration.do_integration(this);">
                    {{$_integration->title}}
                </button>
            {{elseif $_location->button_type == "icon"}}
                <a href="{{$_integration->_url}}"
                     class="contextual-trigger"
                     {{if $_integration->display_mode == "new_tab"}} target="_blank" {{/if}}
                     data-url="{{$_integration->_url}}"
                     data-title="{{$_integration->title}}"
                     data-display_mode="{{$_integration->display_mode}}">
                    {{mb_include module=context template=inc_integration_icon integration=$_integration}}
                </a>
            {{/if}}
        </span>
        {{if $return_line}}<br/>{{/if}}
    {{else}}
        {{if $_integration->display_mode === "current_tab"}}
            {{assign var=onclick value="window.location='`$_integration->_url`'"}}
        {{elseif $_integration->display_mode === "new_tab"}}
            {{assign var=onclick value="window.open('`$_integration->_url`', '_blank').focus()"}}
        {{elseif $_integration->display_mode === "modal"}}
            {{assign var=onclick
            value="new Url().modal({width: '100%', height: '100%', baseUrl: '`$_integration->_url`'})"}}
        {{else}}
            {{assign var=onclick
            value="new Url().popup('100%', '100%', 'Appel contextuel ', null, null, '`$_integration->_url`')"}}
        {{/if}}
        {{if $_fa}}
            {{assign var=icon_value value=$_integration->icon_url}}
        {{else}}
            {{assign var=icon_value value="dd-integration integration-`$_integration->_id`"}}
        {{/if}}
        {{me_button label=$_integration->title icon=$icon_value
            title=$_integration->description onclick=$onclick}}
    {{/if}}
{{/foreach}}

{{if $show_menu}}
    {{me_dropdown_button button_icon="opt" button_class="notext" button_label="CContextualIntegration.show_menu_title"
    container_class="me-dropdown-button-left"}}
{{/if}}
