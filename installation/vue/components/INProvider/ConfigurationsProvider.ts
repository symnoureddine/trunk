/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import INProvider from "@providers/INProvider"

/**
 * Provider principal de l"Install
 */
export default class ConfigurationsProvider extends INProvider {
  constructor () {
    super()
    this.url = "configs"
  }
  protected translateData(data: any): object {
    let attributes = data.attributes
    return {
      rootDir: attributes.root_dir,
      baseUrl: attributes.base_url,
      instanceRole: attributes.instance_role,
      httpRedirections: attributes.http_redirections,
      bdd: {
        type: attributes.bdd.bdd_type,
        host: attributes.bdd.bdd_host,
        name: attributes.bdd.bdd_name, user: attributes.bdd.bdd_user,
        password: attributes.bdd.bdd_password
      },
      memory: {
        sharedMemory: attributes.memory.shared_memory,
        sharedMemoryDistributed: attributes.memory.shared_memory_distributed,
        sharedMemoryParams: attributes.memory.shared_memory_params
      },
      session: attributes.session,
      mutex: {
        mutexSession: attributes.mutex.session_mutex,
        mutexRedis: attributes.mutex.mutex_redis,
        mutexApc: attributes.mutex.mutex_apc,
        mutexFiles: attributes.mutex.mutex_files
      },
      isMaintenance: attributes.is_maintenance,
      isMaintenanceAllowAdmin: attributes.is_maintenance_allow_admin,
      isMigration: attributes.is_migration
    }
  }
}