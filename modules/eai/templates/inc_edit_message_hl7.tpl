{{*
 * @package Mediboard\eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  toggleInput = function (id_input) {
    var input = document.getElementById(id_input+"2");
    if (input.disabled) {
      input.disabled = false;
    }
    else {
      input.disabled = true;
    }
  };
  
  setValueInput = function (input, name_input_hidden) {
    var input_hidden = document.getElementById(name_input_hidden);
    input_hidden.setAttribute('value', input.value);
  };
  
  valueInput = function (id_input, value_input, search_admit, date_observation, actor_guid) {
    var input_hidden = document.getElementById(id_input);
    var input = document.getElementById(id_input+"2");
    input.setAttribute('value', value_input);
    input_hidden.setAttribute('value', value_input);
    
    if (search_admit)
      searchAdmitIPP(value_input, date_observation, actor_guid);
  };

  searchAdmitIPP = function (IPP, date_observation, actor_guid) {
    new Url("eai", "ajax_search_admit_IPP")
      .addParam("ipp"             , IPP)
      .addParam("date_observation", date_observation)
      .addParam("actor_guid"      , actor_guid)
      .requestUpdate("admits_found");
    return false;
  };
  
</script>

<div style="height : 170px;display: inline; float : left; width : 33%;">
  <fieldset>
    <legend>{{tr}}Message{{/tr}}</legend>
    <table class="main tbl">
      <tr>
        <th style="width: 50%;">{{tr}}CExchangeDataFormat-msg-Old IPP{{/tr}}</th>
        <td> {{$ipp_message}}</td>
      </tr>
      <tr>
        <th>{{tr}}CExchangeDataFormat-msg-Old NDA{{/tr}}</th>
        <td>{{$nda_message}}</td>
      </tr>
      {{foreach from=$info_patient_message item=_info_patient_message key=name_field}}
        {{assign var=traduction value="CPatient-$name_field"}}
        <tr>
          <th>{{tr}}{{$traduction}}{{/tr}}</th>
          {{if $name_field == "naissance"}}
            <td>{{$_info_patient_message|date_format:"%Y-%m-%d"}}</td>
          {{else}}
            <td>{{$_info_patient_message}}</td>
          {{/if}}
        </tr>
      {{/foreach}}
      <tr>
        <th>{{tr}}CExchangeDataFormat-msg-Entree reelle{{/tr}}</th>
        <td>{{$date_entree_sejour|date_format:"%Y-%m-%d %H:%M:%S"}}</td>
      </tr>
      <tr>
        <th>{{tr}}CExchangeDataFormat-msg-Sortie reelle{{/tr}}</th>
        <td>{{$date_sortie_sejour|date_format:"%Y-%m-%d %H:%M:%S"}}</td>
      </tr>
      <tr>
        <th>{{tr}}CExchangeDataFormat-msg-Observation dateTime{{/tr}}</th>
        <td>{{$date_observation|date_format:"%Y-%m-%d %H:%M:%S"}}</td>
      </tr>
    </table>
  </fieldset>
</div>

<div style="display: inline; float : left; width : 33%;">
  <fieldset>
    <legend>{{tr}}CExchangeDataFormat-msg-Patient found|pl{{/tr}}</legend>
    {{if !$ipp_message}}
    <table class="main tbl">
        {{if $admit_found->_id}}
          <tr>
            <td>
              <div class="small-warning">{{tr}}CExchangeDataFormat-msg-Patient found by NDA{{/tr}}</div>
              <strong>{{tr}}CPatient{{/tr}}</strong> :
              <span onmouseover="ObjectTooltip.createEx(this, '{{$admit_found->_ref_patient->_guid}}');">
                {{$admit_found->_ref_patient->_view}} <br/> {{$admit_found->_ref_patient->naissance}} <br/>
              </span>
              <strong>{{tr}}CPatient-_IPP{{/tr}}</strong> : {{$admit_found->_ref_patient->_IPP}}
            </td>
          </tr>
        {{elseif $patients_found|@count > 0}}
          {{foreach from=$patients_found item=_patient_found}}
            <tr>
              <td>
                <input type="radio" name="input_ipp_admit" onchange="valueInput('new_ipp', '{{$_patient_found->_IPP}}', true, '{{$date_observation}}', '{{$actor->_guid}}')"/>
                <strong>{{tr}}CPatient{{/tr}}</strong> :
                <span onmouseover="ObjectTooltip.createEx(this, '{{$_patient_found->_guid}}');">
                  {{$_patient_found->_view}} <br/> {{$_patient_found->naissance}} <br/>
                </span>
                <strong style="margin-left : 15px;">{{tr}}CPatient-_IPP{{/tr}}</strong> : {{$_patient_found->_IPP}}
              </td>
            </tr>
          {{/foreach}}
        {{else}}
          <tr>
            <td>
              <div class="small-warning">Le patient {{$patient_found->nom}} {{$patient_found->prenom}} <br/> n'a pas été retrouvé</div>
            </td>
          </tr>
        {{/if}}
    </table>
    {{else}}
      {{if $patient_found->_id}}
        <div class="small-info">{{tr}}CExchangeDataFormat-msg-Patient found by IPP message{{/tr}}</div>
        <strong>{{tr}}CPatient{{/tr}}</strong> :
        <span onmouseover="ObjectTooltip.createEx(this, '{{$patient_found->_guid}}');">
          {{$patient_found->_view}} <br/> {{$patient_found->naissance}} <br/>
        </span>
        <strong>{{tr}}CPatient-_IPP{{/tr}}</strong> : {{$patient_found->_IPP}}
      {{else}}
        {{if $admit_found->_id}}
          <div class="small-warning">{{tr}}CExchangeDataFormat-msg-Patient found by NDA{{/tr}}</div>
          <span onmouseover="ObjectTooltip.createEx(this, '{{$admit_found->_ref_patient->_guid}}');">
          {{$admit_found->_ref_patient->_view}} <br/> {{$admit_found->_ref_patient->naissance}} <br/>
        </span>
          <strong>{{tr}}CPatient-_IPP{{/tr}}</strong> : {{$admit_found->_ref_patient->_IPP}}
        {{else}}
          <div class="small-warning">{{tr}}CExchangeDataFormat-msg-Patient not found by IPP message{{/tr}}</div>
        {{/if}}
      {{/if}}
    {{/if}}
  </fieldset>
</div>

<div style="display: inline;" id="admits_found">
  {{mb_include module=eai template=inc_admits_found}}
</div>

{{if $admit_found->_id && $patient_found->_id && $admit_found->_ref_patient->_id != $patient_found->_id}}
  <div class="small-error" style="clear: both;">
    Le patient <span onmouseover="ObjectTooltip.createEx(this, '{{$patient_found->_guid}}');">{{$patient_found->_view}}</span>
    retrouvé par l'IPP du message et différent du patient
    <span onmouseover="ObjectTooltip.createEx(this, '{{$admit_found->_ref_patient->_guid}}');">
      {{$admit_found->_ref_patient->_view}}</span> retrouvé par le NDA du message.
  <br/>
  {{tr}}CExchangeDataFormat-msg-Please change IPP or NDA{{/tr}}</div>
{{/if}}

<div style="clear: both;
  {{if $admit_found->_id && $patient_found->_id && $admit_found->_ref_patient->_id != $patient_found->_id}}
    margin-top : 10px;
  {{/if}}">
  <fieldset>
    <legend>{{tr}}Result{{/tr}}</legend>
    <form name="form-edit-message" action="?m=hl7&a=ajax_edit_message"
          onsubmit="return ExchangeDataFormat.storeMessage(this);" method="get" class="prepared">
      <input type="hidden" name="old_ipp" value="{{$ipp_message}}" />
      <input type="hidden" id="new_ipp" name="new_ipp" {{if $ipp_message}}
        value="{{$ipp_message}}"
        {{elseif $admit_found->_id}}
        value="{{$admit_found->_ref_patient->_IPP}}"
        {{/if}}/>
      <input type="hidden" id="new_nda" name="new_nda" value="{{$nda_message}}" />
      <input type="hidden" name="old_nda" value="{{$nda_message}}" />
      <input type="hidden" name="exchange_guid" value="{{$exchange->_guid}}" />
      
      <table class="tbl" style="text-align: center">
        <tr>
          <td>
            <label>
              {{tr}}CExchangeDataFormat-msg-IPP result{{/tr}}
              <input type="number" name="new_ipp2" id="new_ipp2"
                {{if $ipp_message}}
                  value="{{$ipp_message}}"
                {{elseif $admit_found->_id}}
                  value="{{$admit_found->_ref_patient->_IPP}}"
                {{/if}} disabled onchange="setValueInput(this, 'new_ipp');"/>
            </label>
            <button type="button" class="edit notext compact" onclick="toggleInput('new_ipp')"></button>
          </td>
        </tr>
        <tr>
          <td>
            <label>
              {{tr}}CExchangeDataFormat-msg-NDA result{{/tr}}
              <input type="number" name="new_nda2" id="new_nda2" value="{{$nda_message}}"
                     disabled onchange="setValueInput(this, 'new_nda');"/>
            </label>
            <button type="button" class="edit notext compact" onclick="toggleInput('new_nda')"></button>
          </td>
        </tr>
        <tr>
          <td style="text-align: center;">
            <button type="submit" class="tick" style="text-align: center;">{{tr}}Validate{{/tr}}</button>
          </td>
        </tr>
      </table>
    </form>
    <button class="fas fa-sync" style="text-align: center; color:blue; margin-left : 40%;"
            onclick="ExchangeDataFormat.reprocessing('{{$exchange->_guid}}')">
      {{tr}}Reprocess{{/tr}}
    </button>
    <button class="search" onclick="ExchangeDataFormat.showLogModification('{{$exchange->_guid}}')">
      {{tr}}CExchangeDataFormat-msg-Log modification|pl{{/tr}}</button>
  </fieldset>
</div>

<div id="edit-message-result"></div>