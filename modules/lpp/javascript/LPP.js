/**
 * @package Mediboard\LogicMax
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

LPP = {
  viewCode: function(code) {
    new Url('lpp', 'ajax_view_code')
      .addParam('code', code)
      .requestModal();
  }
};