{{*
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
Main.add(function() {
  var form = getForm('form-vw-conflict-options');
  form.onsubmit();
});
</script>

<form name="form-vw-conflict-options" method="get" onsubmit="return onSubmitFormAjax(this, null, 'vw-conflict-options')">
  <input type="hidden" name="m" value="openData"/>
  <input type="hidden" name="a" value="ajax_show_options"/>
</form>

<div id="vw-conflict-options"></div>