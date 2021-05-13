/**
 * @package Mediboard\novxtelHospitality
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

NovxtelHospitality = {
  /**
   * Open the hospitality software
   *
   * @param sejour_id
   */
  openNovxtelHospitality: function(sejour_id) {
    new Url("novxtelHospitality", "ajax_vw_novxtel_hospitality")
      .addParam("sejour_id", sejour_id)
      .pop(950,700, 'appel contextuel Novxtel - Hospitality');
  }
};
