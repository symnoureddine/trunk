{{*
 * @package Mediboard\SourceCode\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function () {
    var tabs = Control.Tabs.create('install-test-tabs', true, {defaultTab: 'install-test-infos-tab'});
  });
</script>

<ul id="install-test-tabs" class="control_tabs">
  <li><a href="#install-test-infos-tab">{{tr}}Information{{/tr}}</a></li>
  <li><a href="#install-test-params-tab">{{tr}}common-Configuration{{/tr}}</a></li>
</ul>


<div id="install-test-infos-tab" style="display: none;" class="me-padding-0">
  {{mb_include module=sourceCode template=inc_install_test_infos}}
</div>

<div id="install-test-params-tab" style="display: none;" class="me-padding-0">
  {{mb_include module=sourceCode template=inc_install_test_params}}
</div>

