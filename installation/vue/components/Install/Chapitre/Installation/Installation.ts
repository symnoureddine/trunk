/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component } from "vue-property-decorator"
import PaquetsProvider from "@providers/PaquetsProvider"
import LibrairiesProvider from "@providers/LibrairiesProvider"
import INLineElement from "@components/INLineElement"
import INField from "@components/INField"
import Chapitre from "@components/Chapitre"
import INTabs from "@components/INTabs"
import INTable from "@components/INTable"
import INLoading from "@components/INLoading"

/**
 * Gestion de la page d'installation de l'Install
 */
@Component({components: {
  INLineElement,
  INField,
  INTabs,
  INLoading,
  INTable
}})
export default class Installation extends Chapitre {
  private paquets: object[] = []
  private composerUrl: string = ""
  private packagistUrl: string = ""

  private librairies: object[] = []

  private paquetsLoaded: boolean = false
  private librairiesLoaded: boolean = false

  private currentTab: string = "Libraries"
  private tabs: object[] = [
    {
      label: this.tr("Libraries"),
      id: "Libraries"
    },
    {
      label: this.tr("Packages"),
      id: "Packages"
    }
  ]

  private packagesColumns: (string|object)[] = [
    "name",
    "description",
    "versionRequired",
    "versionInstalled",
    "license",
    "isInstalled"
  ]
  private librariesColumns: (string|object)[] = [
    "name",
    "description",
    {"field": "licenseName", "link": "licenseLink"},
    "isInstalled",
    "isUptodate"
  ]

  public async load(): Promise<void> {
    await this.loadLibrairies()
    await this.loadPackages()
  }

  private async loadLibrairies(): Promise<void> {
    this.librairiesLoaded = false
    this.paquetsLoaded = false
    let librairiesResponse = await new LibrairiesProvider().getData()
    this.librairies = this.extractData(librairiesResponse.libraries)
    this.librairiesLoaded = true
  }

  private async loadPackages(): Promise<void>{
    let paquetsResponse = await new PaquetsProvider().getData()
    this.paquets = this.extractData(paquetsResponse.packages)
    this.composerUrl = paquetsResponse.composerUrl
    this.packagistUrl = paquetsResponse.packagistUrl
    this.paquetsLoaded = true
  }

  private get packagesDisplayed(): object[] {
    return this.paquets ? this.paquets.filter((paquet) => paquet["displayed"]) : []
  }
  private get librariesDisplayed(): object[] {
    return this.librairies ? this.librairies.filter((librairie) => librairie["displayed"]) : []
  }

  private filterPackages(search: string): void {
    this.applyFilter(
      search,
      this.paquets,
      ["name", "description", "license"]
    )
  }
  private filterLibraries(search: string): void {
    this.applyFilter(
      search,
      this.librairies,
      ["name", "description", "licenseName"]
    )
  }

  private selectTab(tab: string): void{
    this.currentTab = tab
  }

  private lineClick(url: string): void{
    window.open(url, "_blank")
  }
}
