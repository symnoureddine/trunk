{{*
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=urgences script=motif register=true}}

<script>
  Main.add(function() {
    Control.Tabs.create("tabs_motifs", true);
  });
</script>
<ul id="tabs_motifs" class="control_tabs">
  <li>
    <a href="#chapitres">Chapitres</a>
  </li>
  <li>
    <a href="#motifs">Motifs</a>
  </li>
</ul>

<div id="chapitres" style="display:none;">
  {{mb_include module=urgences template=vw_list_chapitres}}
</div>

<div id="motifs" style="display:none;">
  {{mb_include module=urgences template=vw_list_motifs}}
</div>
