/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component } from "vue-property-decorator"
import INLineElement from "@components/INLineElement"
import ConfigurationsProvider from "@providers/ConfigurationsProvider"
import INField from "@components/INField"
import Chapitre from "@components/Chapitre"
import INLoading from "@components/INLoading"

/**
 * Gestion de la page des configurations de l'Install
 */
@Component({components: { INLineElement, INField, INLoading }})
export default class Configuration extends Chapitre {
  private configs: object[] = []

  private loaded: boolean = false

  public async load(): Promise<void> {
    this.loaded = false
    this.configs = this.extractData(this.parseConfigsToArray(await new ConfigurationsProvider().getData()))
    this.loaded = true
  }

  private filterConfiguration(search): void {
    this.applyFilter(search, this.configs, ["label", "id", "value"])
  }

  private parseConfigsToArray(configs): object[] {
    let array: object[] = []
    Object.keys(configs).map(
      (config) => {
        if (typeof(configs[config]) === "object") {
          Object.keys(configs[config]).map(
            (subConfig) => {
              array.push(
                {
                  label: this.tr("Configs-" + config + "-" + subConfig),
                  id: config + "-" + subConfig,
                  value: configs[config][subConfig]
                }
              )
            }
          )
        }
        else {
          array.push(
            {
              label: this.tr("Configs-" + config),
              id: config,
              value: configs[config]
            }
          )
        }
      }
    )
    return array
  }
}
