/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component } from "vue-property-decorator"
import INButton from "@components/INButton"
import INVue from "@components/INVue"
import INCard from "@components/INCard"
import INField from "@components/INField"
import ConnexionProvider from "@providers/ConnexionProvider"
import Api from "@api/Api"
import INLoading from "@components/INLoading"

/**
 * Gestion de la page de connexion de l'Install
 */
@Component({components: { INButton, INCard, INField, INLoading }})
export default class Connexion extends INVue {
  private login: string = ""
  private password: string = ""
  private connecting: boolean = false
  private connectMessage: string = ""
  private connectStatus!: number
  private connectError: boolean = false

  private loginChange(login: string): void {
    this.login = login
  }

  private passwordChange(password: string): void {
    this.password = password
  }

  private genCredentials(): string {
    return btoa(this.login + ":" + this.password)
  }

  private async connect(): Promise<void> {
    this.connecting = true
    this.connectError = false
    Api.commit("setCredential", this.genCredentials())
    let response = await new ConnexionProvider().getRaw()
    this.connecting = false
    if (!response || !response.data) {
      this.connectMessage = response ? response.message : ""
      this.connectStatus = response ? response.status : null
      if (this.connectStatus === 401) {
        this.connectMessage = this.tr("Connexion-errorCredentialMessage")
      }
      this.connectError = true
      return
    }
    this.$emit("connect", {credentials: this.genCredentials()})
  }
}
