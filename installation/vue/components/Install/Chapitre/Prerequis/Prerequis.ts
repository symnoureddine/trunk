/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component } from "vue-property-decorator"
import PrerequisProvider from "@providers/PrerequisProvider"
import INTable from "@components/INTable"
import INField from "@components/INField"
import Chapitre from "@components/Chapitre"
import INTabs from "@components/INTabs"
import INLoading from "@components/INLoading"

/**
 * Gestion de la page des prérequis de l'Install
 */
@Component({components: {
  INTable,
  INField,
  INTabs,
  INLoading
}})
export default class Prerequis extends Chapitre {
  private loaded: boolean = false
  private phpExtensions: object[] = []
  private urlRestrictions: object[] = []
  private phpVersion: object = {}
  private sqlVersion: object = {}
  private pathAccess: object[] = []

  private phpExtensionsColumns: string[] = ["name", "description", "reasons", "mandatory", "check"]
  private urlRestrictionsColumns: string[] = ["url", "description", "check"]
  private pathAccessColumns: string[] = ["path", "description", "check"]

  private currentTab: string = "PHPSQLVersions"
  private tabs: object[] = [
    {
      label: this.tr("PHPSQLVersions"),
      id: "PHPSQLVersions"
    },
    {
      label: this.tr("PHPExtensions"),
      id: "PHPExtensions"
    },
    {
      label: this.tr("URLRestrictions"),
      id: "URLRestrictions"
    },
    {
      label: this.tr("PathAccess"),
      id: "PathAccess"
    }
  ]

  public async load(): Promise<void> {
    this.loaded = false
    let prerequisData = await new PrerequisProvider().getData()
    this.phpExtensions = this.extractData(prerequisData.phpExtensions)
    this.urlRestrictions = this.extractData(prerequisData.urlRestrictions)
    this.pathAccess = this.extractData(prerequisData.pathAccess)
    this.phpVersion = prerequisData.phpVersion
    this.sqlVersion = prerequisData.sqlVersion
    this.loaded = true
  }

  private filterPHPExtensions(search: string): void {
    this.applyFilter(search, this.phpExtensions, ["name", "description", "reasons"])
  }

  private filterURLRestrictions(search: string): void{
    this.applyFilter(search, this.urlRestrictions, ["url", "description"])
  }
  private filterPathAccess(search: string): void{
    this.applyFilter(search, this.pathAccess, ["path", "description"])
  }

  private selectTab(tab: string): void {
    this.currentTab = tab
  }

  private get displayedPHPExtensions(): object[] {
    return this.phpExtensions.filter((extension) => extension["displayed"])
  }

  private get displayedURLRestrictions(): object[] {
    return this.urlRestrictions.filter((extension) => extension["displayed"])
  }

  private get displayedPathAccess(): object[] {
    return this.pathAccess.filter((extension) => extension["displayed"])
  }
}
