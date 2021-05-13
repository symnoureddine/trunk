{{*
 * @package Mediboard\CompteRendu
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<h2>{{tr var1=$owner}}CCompteRendu-import_for{{/tr}}</h2>

<form name="editImport" method="post" enctype="multipart/form-data"
      action="?m=compteRendu&a=ajax_import_modele&dialog=1">
  <input type="hidden" name="owner_guid" value="{{$owner->_guid}}" />
  <input type="file" name="datafile" size="40" />
  <button class="tick">Importer</button>
</form>
