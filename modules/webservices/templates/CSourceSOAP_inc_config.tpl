{{*
 * @package Mediboard\Webservices
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=webservices script=soap ajax=true}}

{{mb_default var=callback value=""}}

<table class="main layout">
  <tr>
    <td>
      <form name="editSourceSOAP-{{$source->name}}" action="?m={{$m}}" method="post"
            onsubmit="return onSubmitFormAjax(this, (function() {
              {{if $callback}}{{$callback}}{{/if}}
              if (this.up('.modal')) {
                Control.Modal.close();
              } else {
                ExchangeSource.refreshExchangeSource('{{$source->name}}', '{{$source->_wanted_type}}');
              }}).bind(this))">

        <input type="hidden" name="m" value="webservices" />
        <input type="hidden" name="dosql" value="do_source_soap_aed" />
        <input type="hidden" name="source_soap_id" value="{{$source->_id}}" />
        <input type="hidden" name="del" value="0" />
        <input type="hidden" name="name" value="{{$source->name}}" />

        <fieldset>
          <legend>
            {{tr}}CSourceSOAP{{/tr}}
            {{mb_include module=system template=inc_object_history object=$source css_style="float: none"}}
          </legend>

          <table class="main form me-no-box-shadow">
            {{mb_include module=system template=CExchangeSource_inc}}

            <tr>
              <th>{{mb_label object=$source field="wsdl_external"}}</th>
              <td>{{mb_field object=$source field="wsdl_external" size="50"}}</td>
            </tr>

            <tr>
              <th>{{mb_label object=$source field="port_name"}}</th>
              <td>{{mb_field object=$source field="port_name" size="50"}}</td>
            </tr>

            <tr>
              <th style="width: 100px">{{mb_label object=$source field="type_soap"}}</th>
              <td>{{mb_field object=$source field="type_soap" typeEnum="radio"}}</td>
            </tr>

            <tr>
              <th>{{mb_label object=$source field="soap_version"}}</th>
              <td>{{mb_field object=$source field="soap_version" typeEnum="radio"}}</td>
            </tr>

            <tr>
              <th>{{mb_label object=$source field="encoding"}}</th>
              <td>{{mb_field object=$source field="encoding" typeEnum="radio"}}</td>
            </tr>

            <tr>
              <th>{{mb_label object=$source field="type_echange"}}</th>
              <td>{{mb_field object=$source field="type_echange"}}</td>
            </tr>

            <tr>
              <th>{{mb_label object=$source field="evenement_name"}}</th>
              <td>{{mb_field object=$source field="evenement_name"}}</td>
            </tr>

            <tr>
              <th>{{mb_label object=$source field="single_parameter"}}</th>
              <td>{{mb_field object=$source field="single_parameter"}}</td>
            </tr>

            <tr>
              <th>{{mb_label object=$source field="safe_mode"}}</th>
              <td>{{mb_field object=$source field="safe_mode"}}</td>
            </tr>

            <tr>
              <th>{{mb_label object=$source field="return_mode"}}</th>
              <td>{{mb_field object=$source field="return_mode"}}</td>
            </tr>
            <tr>
              <th>{{mb_label object=$source field="xop_mode"}}</th>
              <td>{{mb_field object=$source field="xop_mode"}}</td>
            </tr>
            <tr>
              <th>{{mb_label object=$source field="use_tunnel"}}</th>
              <td>{{mb_field object=$source field="use_tunnel"}}</td>
            </tr>
            <tr>
              <th>{{mb_label object=$source field="socket_timeout"}}</th>
              <td>{{mb_field object=$source field="socket_timeout" increment=true form="editSourceSOAP-`$source->name`"}}</td>
            </tr>
            <tr>
              <th>{{mb_label object=$source field="connection_timeout"}}</th>
              <td>{{mb_field object=$source field="connection_timeout" increment=true form="editSourceSOAP-`$source->name`"}}</td>
            </tr>
            <tr>
              <th>{{mb_label object=$source field="feature"}}</th>
              <td>{{mb_field object=$source field="feature" emptyLabel="Choose"}}</td>
            </tr>
          </table>
        </fieldset>

        <fieldset>
          <legend>Authentification HTTP</legend>

          <table class="main form me-no-box-shadow">
            <tr>
              <th style="width: 100px">{{mb_label object=$source field="user"}}</th>
              <td>{{mb_field object=$source field="user" size="50"}}</td>
            </tr>
            <tr>
              <th>{{mb_label object=$source field="password"}}</th>
              <td>{{mb_field object=$source field="password" size="30"}}</td>
            </tr>
          </table>
        </fieldset>

        <fieldset>
          <legend>Authentification à l'aide d'un certificat</legend>

          <table class="main form me-no-box-shadow">
            <tr>
              <th style="width: 100px">{{mb_label object=$source field="local_cert"}}</th>
              <td>{{mb_field object=$source field="local_cert" size="50"}}</td>
            </tr>
            <tr>
              <th>{{mb_label object=$source field="passphrase"}}</th>
              {{assign var=placeholder value="Pas de phrase de passe"}}
              {{if $source->passphrase}}
                {{assign var=placeholder value="Phrase de passe enregistrée"}}
              {{/if}}
              <td>{{mb_field object=$source field="passphrase" placeholder=$placeholder size="30"}}</td>
            </tr>
          </table>
        </fieldset>

        <fieldset>
          <legend>Options de contexte SSL</legend>

          <table class="main form me-no-box-shadow">
            <tr>
              <th style="width: 100px">{{mb_label object=$source field="verify_peer"}}</th>
              <td>{{mb_field object=$source field="verify_peer"}}</td>
            </tr>
            <tr>
              <th>{{mb_label object=$source field="cafile"}}</th>
              <td>{{mb_field object=$source field="cafile" size="45"}}</td>
            </tr>
          </table>
        </fieldset>

        <table class="main form me-no-box-shadow">
          <tr>
            <td class="button">
              {{if $source->_id}}
                <button class="modify" type="submit">{{tr}}Save{{/tr}}</button>
                <button class="trash" type="button" onclick="confirmDeletion(this.form,
                  { ajax: 1, typeName: '', objName: '{{$source->_view}}'},
                  { onComplete: (function() {
                  if (this.up('.modal')) {
                    Control.Modal.close();
                  } else {
                    ExchangeSource.refreshExchangeSource('{{$source->name}}', '{{$source->_wanted_type}}');
                  }}).bind(this.form)})">

                  {{tr}}Delete{{/tr}}
                </button>
              {{else}}
                <button class="submit" type="submit">{{tr}}Create{{/tr}}</button>
              {{/if}}
            </td>
          </tr>
        </table>

        <fieldset>
          <legend>{{tr}}utilities-source-soap{{/tr}}</legend>

          <table class="main form me-no-box-shadow">
            <tr>
              <td class="button">
                <!-- Test connexion SOAP -->
                <button type="button" class="search" onclick="SOAP.connexion('{{$source->name}}');"
                        {{if !$source->_id}}disabled{{/if}}>
                  {{tr}}utilities-source-soap-connexion{{/tr}}
                </button>

                <!-- Liste des functions SOAP -->
                <button type="button" class="search" onclick="SOAP.getFunctions('{{$source->name}}', this.form);"
                        {{if !$source->_id}}disabled{{/if}}>
                  {{tr}}utilities-source-soap-getFunctions{{/tr}}
                </button>
              </td>
            </tr>
          </table>
        </fieldset>
      </form>
    </td>
  </tr>
</table>