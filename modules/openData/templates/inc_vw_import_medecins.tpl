{{*
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=openData script=import_medecins}}

<script>
Main.add(function() {
  var form = getForm('refresh-import-medecins');
  form.onsubmit();
});
</script>

<form name="refresh-import-medecins" method="get" onsubmit="return onSubmitFormAjax(this, null, 'vw-import-medecins')">
  <input type="hidden" name="m" value="openData"/>
  <input type="hidden" name="a" value="ajax_vw_import_medecins"/>
</form>

<div id="vw-import-medecins"></div>