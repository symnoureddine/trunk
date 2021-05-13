{{*
* @package Mediboard\Developpement
* @author  SAS OpenXtrem <dev@openxtrem.com>
* @license https://www.gnu.org/licenses/gpl.html GNU General Public License
* @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function () {
    Control.Tabs.create('main_tab_group', true, {
      afterChange: function (container) {
        if (container === routes) {
          var url = new Url('dPdeveloppement', 'ajax_list_routes');
          url.requestUpdate(container.id);
          return;
        }
        if (container === legacy) {
          var url = new Url('dPdeveloppement', 'ajax_list_legacy');
          url.requestUpdate(container.id);
          return;
        }
      }
    });
  });
</script>

<style>
  #stat table tr:not(:first-of-type) th {
    text-align: left;
    width: 150px;
  }


  #stat table td {
    vertical-align: top;
  }

  #routes table {
    margin-top: 15px;
  }

  #routes table tr:hover {
    cursor: pointer;
  }

</style>

<ul id="main_tab_group" class="control_tabs">
  <li><a href="#stat">Statistiques</a></li>
  <li><a href="#routes">Routes</a></li>
  <li><a href="#tools">Outils</a></li>
  <li><a href="#legacy">Legacy</a></li>
</ul>
<br>
<div id="stat">
  <table class="tbl main">
    <tr>
      <th class="title" colspan="2">
        {{$file}}
      </th>
    </tr>
    <tr>
      <th>Date de création</th>
      <td>{{$file_date|date_format:$conf.datetime}}</td>
    </tr>
    <tr>
      <th>Size</th>
      <td>{{$file_size}}</td>
    </tr>
    <tr>
      <th>Routes</th>
      <td>{{$routes_count|number_format:0:'.':' '}}</td>
    </tr>
  </table>
  {{foreach from=$files_sf item=_file_sf}}
    <br>
    <table class="tbl main">
      <tr>
        <th class="title" colspan="2">{{$_file_sf.path}}</th>
      </tr>
      <tr>
        <th>Date de création</th>
        <td>{{$_file_sf.date|date_format:$conf.datetime}}</td>
        <td></td>
      </tr>
      <tr>
        <th>Size</th>
        <td>{{$_file_sf.size}}</td>
      </tr>
    </table>
  {{/foreach}}
  <br>
  <table class="tbl main">
    <tr>
      <th class="title" colspan="2">
        {{$root_dir}}/modules/*/routes/*.yml
      </th>
    </tr>
    {{foreach from=$ressources item=_ressource}}
      <tr>
        <td>{{$_ressource}}</td>
      </tr>
    {{/foreach}}
  </table>
</div>

<div id="routes">
  <!-- AJAX -->
</div>

<div id="tools">
  <div style="margin-bottom: 10px;">
    <button type="button" class="fa-external-link-alt" onclick="window.open('./openapi')">
      APIs documentation (OAS3)
    </button>
  </div>

  <div>
    <form name="match" action="?" method="get" onsubmit="return false;">
      <input type="text" placeholder="/path/to/your/route?with=requirements" size="50" name="url" id="url" value="">
      <button type="button" onclick="$V(this.form.url, '');" class="erase notext" title="Vider le champ"></button>
      <select id="method" name="method">
        <option value="" disabled selected>Method</option>
        <option value="get">Get</option>
        <option value="post">Post</option>
        <option value="put">Put</option>
        <option value="delete">Delete</option>
        <option value="patch">Patch</option>
      </select>

      <button type="button" class="search" onclick="new Url('developpement', 'ajax_match_routes')
      .addFormData(getForm('match'))
      .requestModal(600,400, {title :  'Url Matcher'});">Url Matcher
      </button>
    </form>
    <div id="result"></div>
  </div>

</div>


<div id="legacy">
  <!-- AJAX -->
</div>
