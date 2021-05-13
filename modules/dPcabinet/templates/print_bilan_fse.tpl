{{*
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_include module=patients template=inc_intermax}}

<table class="main">
  <tr>
    <td class="halfPane">
      <table class="form">
        <tr>
          <th colspan="2" class="category">
            <a href="#" onclick="window.print()">
              Rapport du {{$filter->_date_min|date_format:$conf.date}}
              {{if $filter->_date_min != $filter->_date_max}}
              au {{$filter->_date_max|date_format:$conf.date}}
              {{/if}}
            </a>
          </th>
        </tr>
        {{if $prat->_id}}
        <tr>
          <th>{{mb_label object=$prat field=_user_last_name}}</th>
          <td>{{mb_value object=$prat field=_view}}</td>
        </tr>
        <tr>
          <th>{{mb_label object=$prat field=adeli}}</th>
          <td>{{mb_value object=$prat field=adeli}}</td>
        </tr>
        {{/if}}
        <tr>
          <th>{{mb_label object=$filter field=S_FSE_ETAT}}</th>
          <td>{{mb_value object=$filter field=S_FSE_ETAT}}</td>
        </tr>
      </table>
    </td>

    <td class="halfPane">
      <table class="form">
        <tr><th class="category" colspan="2">Récapitulatif</th></tr>
        <tr><th>Nombre FSE :</th><td>{{$total.count}}</td></tr>
        <tr><th>Total facturé :</th><td>{{$total.S_FSE_TOTAL_FACTURE|currency}}</td></tr>
      </table>
    </td>
  </tr>

  {{foreach from=$days key=_day item=_fses}}
  <tr>
    <td colspan="2"><strong>{{$_day|date_format:$conf.longdate}}</strong></td>
  </tr>
	
  <tr>
    <td colspan="2">
      <table class="tbl">
        <tr>
          <th width="15%">{{mb_label object=$filter field=S_FSE_NUMERO_FSE}}</th>
          <th width="05%">{{mb_title object=$filter field=S_FSE_LOT}}</th>
          <th width="05%">{{mb_title class=CLmLot   field=S_LOT_FIC}}</th>
          <th width="15%">{{mb_label object=$filter field=_consult_id}}</th>
          <th width="15%">{{mb_label object=$filter field=S_FSE_ETAT}}</th>
          <th width="15%">{{mb_label object=$filter field=S_FSE_TOTAL_FACTURE}}</th>
        </tr>
        {{foreach from=$_fses item=_fse}}
        <tr>
          <td>
				  	<span onmouseover="ObjectTooltip.createEx(this, '{{$_fse->_guid}}')">
				  	  {{$_fse->_id}}
				  	</span>
			      <button class="search singleclick" type="button" onclick="Intermax.Triggers['Consulter FSE']('{{$_fse->_id}}');">
			        {{tr}}View{{/tr}} 
			      </button>
			      <button class="print singleclick" type="button" onclick="Intermax.Triggers['Editer FSE']('{{$_fse->_id}}');">
			        {{tr}}Print{{/tr}}
			      </button>
			    </td>
			      
			    <td>
			      {{assign var=lot value=$_fse->_ref_lot}}
			      {{if $lot->_id}}
				  	<span onmouseover="ObjectTooltip.createEx(this, '{{$lot->_guid}}')">
			      {{$lot->_id|pad:6:'0':'left'}}
				  	</span>
						{{/if}}
			      
			    </td>
			    
			    <td>
			      {{if $lot->S_LOT_FIC}}
				  	<span onmouseover="ObjectTooltip.createEx(this, 'CLmFichier-{{$lot->S_LOT_FIC}}')">
			      {{$lot->S_LOT_FIC|pad:6:'0':'left'}}
				  	</span>
						{{/if}}
			    </td>
			    
          <td>
            {{if $_fse->_consult_id}}
				  	<span onmouseover="ObjectTooltip.createEx(this, 'CConsultation-{{$_fse->_consult_id}}')">
				  	  <a href="#CConsultation-{{$_fse->_consult_id}}" onclick="Consultation.show('{{$_fse->_consult_id}}')">
				  	  	{{tr}}CConsultation{{/tr}} #{{$_fse->_consult_id}}
				  	  </a>
				  	</span>
            {{/if}}
          </td>
          {{if $_fse->_annulee}}
          <td colspan="2" class="cancelled">{{mb_value object=$_fse field=S_FSE_ETAT}}</td>
          {{else}}
          <td>{{mb_value object=$_fse field=S_FSE_ETAT}}</td>
          <td>{{mb_value object=$_fse field=S_FSE_TOTAL_FACTURE}}</td>
          {{/if}}
        </tr>
        {{/foreach}}
				
        <tr>
          <td colspan="5" style="font-weight:bold; text-align:right">Total pour {{$cumuls.$_day.count}} FSE</td>
          <td style="font-weight:bold;">{{$cumuls.$_day.S_FSE_TOTAL_FACTURE|currency}}</td>
        </tr>
      </table>
    </td>
  </tr>
  {{/foreach}}
</table>
      