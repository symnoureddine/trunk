/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import {Component, Prop} from "vue-property-decorator"
import INVue from "@components/INVue"
import INValue from "@components/INValue"
import INField from "@components/INField"
import INButton from "@components/INButton"
import INIcon from "@components/INIcon"

/**
 * Wrapper des champs de saisie de texte de l'Install
 */
@Component({components: {
  INValue,
  INField,
  INButton,
  INIcon
}})
export default class INTable extends INVue {
  @Prop()
  private data!: []
  @Prop({default: ""})
  private headerTrPrefix!: string
  @Prop()
  private columns!: object[]
  @Prop({default: true})
  private canAutoSort!: boolean
  @Prop({default: false})
  private canExternalSort!: boolean
  @Prop({default: true})
  private canFilter!: boolean
  @Prop({default: false})
  private hasAction!: boolean
  @Prop({default: ""})
  private iconAction!: string
  @Prop({default: ""})
  private textAction!: string
  @Prop({default: ""})
  private fieldAction!: string
  @Prop({default: false})
  private usePagination!: boolean
  @Prop({default: 1})
  private currentPage!: number
  @Prop({default: true})
  private canPreviousPage!: boolean
  @Prop({default: true})
  private canLastPage!: boolean
  @Prop({default: true})
  private canNextPage!: boolean
  @Prop({default: true})
  private canFirstPage!: boolean

  private sort: string = ""

  private headTr(key: string): string {
    return this.tr((this.headerTrPrefix ? this.headerTrPrefix + "-" : "") + this.extractKeyField(key))
  }

  private get sortedData(): object[] {
    if (!this.canAutoSort) {
      return this.data
    }
    let orderMode = this.currentSort.mode === "asc" ? -1 : 1
    let key = this.currentSort.field
    return this.data.sort(
      (cell1, cell2) => {
        return (cell1[key] > cell2[key] ? orderMode : (cell1[key] < cell2[key] ? (-1 * orderMode) : 0))
      }
    )
  }

  private extractKeyField(key: (string|{field: string})): string {
    return key = typeof(key) === "object" ? key.field : key
  }

  private sortBy(key: string): void {
    if (!this.canAutoSort && !this.canExternalSort) {
      return
    }
    key = this.extractKeyField(key)
    this.sort = ((this.currentSort.field === key && this.currentSort.mode === "asc") ? "-" : "") + key
    if (this.canExternalSort) {
      this.$emit("sortby", this.sort)
    }
  }

  private get currentSort(): {field: string, mode: string} {
    return {
      field: this.sort[0] === "-" ? this.sort.substr(1) : this.sort,
      mode : this.sort[0] === "-" ? "desc" : "asc"
    }
  }

  private columnIcon (key: string): string {
    if (this.currentSort.field !== this.extractKeyField(key)) {
      return "sort"
    }
    return "sort-" + (this.currentSort.mode === "asc" ? "up" : "down")
  }

  private columnIconClassName(key: string): object {
    return {
      "active" : this.currentSort.field === this.extractKeyField(key)
    }
  }

  private filter(input: string): void {
    this.$emit("filter", input)
  }

  private clickAction(field: string): void {
    this.$emit("lineaction", field)
  }

  private firstPage(): void {
    this.$emit("firstpage")
  }
  private previousPage(): void {
    this.$emit("previouspage")
  }
  private nextPage(): void {
    this.$emit("nextpage")
  }
  private lastPage(): void {
    this.$emit("lastpage")
  }
}
