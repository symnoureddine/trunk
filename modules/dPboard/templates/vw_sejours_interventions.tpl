{{*
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module="dPplanningOp" script="ccam_selector"}}

{{if $prat->_id}}

<script>
  var graphs = {{$graphs|@json}};
  Main.add(function(){
    graphs.each(function(g, i){
      g.options.legend.container = $('graph-'+i+'-legend');
      Flotr.draw($('graph-'+i), g.series, g.options);
    });
  });
</script>

<form name="filters" action="?" method="get" onsubmit="return checkForm(this)">

<input type="hidden" name="m" value="dPboard" />
<input type="hidden" name="_chir" value="{{$prat->_id}}" />
<input type="hidden" name="_class" value="" />

<table class="main form">

  <tr>
    <th colspan="4" class="category">Statistiques cliniques</th>
  </tr>

  <tr>
    <th>{{mb_label object=$filterSejour field="_date_min_stat"}}</th>
    <td>{{mb_field object=$filterSejour field="_date_min_stat" form="filters" register=true canNull="false"}} </td>
    <th>{{mb_label object=$filterSejour field="_date_max_stat"}}</th>
    <td>{{mb_field object=$filterSejour field="_date_max_stat" form="filters" register=true canNull="false"}} </td>
  </tr>

  <tr>
    <th>{{mb_label object=$filterSejour field="type"}}</th>
    <td>
      <select name="type">
        <option value="">&mdash; Tous les types d'hospi</option>
        <option value="1" {{if $filterSejour->type == "1"}}selected="selected"{{/if}}>Hospi complètes + ambu</option>
        {{foreach from=$filterSejour->_specs.type->_locales key=key_hospi item=curr_hospi}}
        <option value="{{$key_hospi}}" {{if $key_hospi == $filterSejour->type}}selected="selected"{{/if}}>
          {{$curr_hospi}}
        </option>
        {{/foreach}}
      </select>
    </td>
    <th>{{mb_label object=$filterOperation field="_codes_ccam"}}</th>
    <td>
      {{mb_field object=$filterOperation field="_codes_ccam" canNull="true" size="20"}}
      <button class="search notext" type="button" onclick="CCAMSelector.init()">{{tr}}Search{{/tr}}</button>   
      <script>
        CCAMSelector.init = function() {
          this.sForm = 'filters';
          this.sView = '_codes_ccam';
          this.sChir = '_chir';
          this.sClass = '_class';
          this.pop();
        };
        Main.add(function() {
          var oForm = getForm('filters');
          var url = new Url('ccam', 'httpreq_do_ccam_autocomplete');
          url.autoComplete(oForm._codes_ccam, '', {
            minChars: 1,
            dropdown: true,
            width: '250px',
            updateElement: function(selected) {
              $V(oForm._codes_ccam, selected.down('strong').innerHTML);
            }
          });
        });
      </script>
    </td>
  </tr>

  <tr>
    <td colspan="4" class="button">
      <button type="submit" class="search">Afficher</button>
    </td>
  </tr>
  
</table>

</form>

<table class="main layout">
  {{foreach from=$graphs item=graph key=key}}
    <tr>
      <td class="narrow">
        <div style="width: 480px; height: 350px; float: left; margin: 1em;" id="graph-{{$key}}"></div>
      </td>
      <td id="graph-{{$key}}-legend"></td>
    </tr>
  {{/foreach}}
</table>
{{/if}}