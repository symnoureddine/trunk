/**
 * @package Mediboard\system
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

CacheManager = {
  id: 'CacheManagerFeedback',
  getMessage: function (keys, modules, ips_list) {
    var message = 'Confirmez-vous cette suppression ?';
    var keys_array = keys.split(',');
    keys_array.forEach(
      function(key, item){
        message += '<br> - ' + $T('CacheManager-cache_values.' + key);
      }
    );

    if (modules) {
      var modules_array = modules.split(',');
      message += '<br>' + $T('Pour les modules :');
      modules_array.forEach(
        function(item){
          message += '<br> - ' + item;
        }
      );
    }
    if (ips_list) {
      var ips_array = ips_list.split(',');
      message += '<br>' + $T('Sur les serveurs :');
      ips_array.forEach(
        function(item) {
          message += '<br> - ' + item;
        }
      );
    }
    message += '<br>';
    return message;
  },
  empty: function (keys, modules = false) {
    Modal.confirm(
      this.getMessage(keys, modules),
      {
        onOK: function () {
          var url = new Url("system", "httpreq_do_empty_shared_memory");
          if (keys) {
            url.addParam('keys', keys);
          }
          if (modules) {
            url.addParam('modules', modules);
          }
          url.requestUpdate(CacheManager.id);
        }
      }
    )
  },
  allEmpty: function (keys, modules = false, ips_list = false) {
    Modal.confirm(
      this.getMessage(keys, modules, ips_list),
      {
        onOK: function () {
          var url = new Url("system", "httpreq_do_empty_shared_memory_all_servers");
          if (keys) {
            url.addParam('keys', keys);
          }
          if (modules) {
            url.addParam('modules', modules);
          }
          if (ips_list) {
            url.addParam('ips_list', ips_list);
          }
          url.requestUpdate(CacheManager.id);
        }
      }
    )
  }
};