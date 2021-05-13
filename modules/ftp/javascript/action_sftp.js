/**
 * @package Mediboard\Ftp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

SFTP = {
  connexion: function (exchange_source_name) {
    new Url("ftp", "ajax_connexion_sftp")
      .addParam("exchange_source_name", exchange_source_name)
      .requestModal(500, 400);
  },

  getFiles: function (exchange_source_name) {
    new Url("ftp", "ajax_getFiles_sftp")
      .addParam("exchange_source_name", exchange_source_name)
      .requestModal(500, 400);
  }
};