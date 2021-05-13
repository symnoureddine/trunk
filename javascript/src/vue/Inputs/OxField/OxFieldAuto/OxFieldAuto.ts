/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop, Watch } from "vue-property-decorator"
import OxProviderCore from "@system/OxProviderCore"
import OxFieldStrCore from "@system/OxFieldStrCore"
import OxIconCore from "@system/OxIconCore"

/**
 * OxFieldAuto
 *
 * Composant de champ Autocomplete
 */
@Component
export default class OxFieldAuto extends OxFieldStrCore {
    @Prop({ default: false })
    private chips!: boolean

    @Prop({ default: "_id" })
    private itemId!: string

    @Prop({ default: "view" })
    private itemText!: string

    @Prop({ default: "view" })
    private itemView!: string

    @Prop({ default: 0 })
    private minChar!: number

    @Prop({ default: false })
    private multiple!: boolean

    @Prop({
        default: () => {
            return []
        }
    })

    /* eslint-disable  @typescript-eslint/no-explicit-any */
    private options!: any[]

    @Prop({ default: false })
    private object!: boolean

    @Prop({
        default: () => {
            return new OxProviderCore()
        }
    })
    private provider!: OxProviderCore

    /* eslint-disable  @typescript-eslint/no-explicit-any */
    private items: any[] = []
    private loading = false
    private recoverTimer!: NodeJS.Timer
    private recoverTiming = 500
    private search = ""
    private syncValue = ""

    /**
     * Aucune réponses récupérée depuis le dernier appel
     *
     * @return {boolean}
     */
    private get noDataResponse (): boolean {
        return !this.loading && this.search !== "" && this.search !== null
    }

    /**
     * Icone à afficher dans le champ
     *
     * @return {string}
     */
    private get iconSearch (): string {
        if (!this.icon) {
            return OxIconCore.get("search")
        }
        return this.iconName
    }

    /**
     * Synchronisation de la valeur sélectionnée
     * @Watch value
     *
     * @return {Promise<void>}
     */
    @Watch("value")
    private async syncSelectedItem (): Promise<void> {
        this.updateMutatedValue()
        if (this.provider && this.mutatedValue && this.syncValue !== this.mutatedValue) {
            this.loading = true
            this.mutatedValue = this.mutatedValue.toString()
            this.items = [(await this.provider.getAutocompleteById(this.mutatedValue.toString()))]
            this.syncValue = this.mutatedValue
            this.loading = false
        }
    }

    /**
     * Mise à jour des items proposés dans la liste
     * @Watch search
     *
     * @return {Promise<void>}
     */
    @Watch("search")
    private async updateItems (): Promise<void> {
        if (this.options.length || !this.search || this.search.length < this.minChar) {
            if (!this.search || this.search.length < this.minChar) {
                this.items = []
            }
            return
        }
        this.loading = true
        if (this.recoverTimer) {
            window.clearTimeout(this.recoverTimer)
        }
        this.recoverTimer = setTimeout(
            () => {
                this.itemCall()
            },
            this.recoverTiming
        )
    }

    /**
     * Composant créé
     *
     * @return {Promise<void>}
     */
    protected async created (): Promise<void> {
        await this.syncSelectedItem()
    }

    /**
     * Mise à jour effective de la liste des items proposés
     *
     * @return {Promise<void>}
     */
    private async itemCall (): Promise<void> {
        this.items = await this.provider.getAutocomplete(this.search)
        this.loading = false
    }

    /**
     * Remontée de l'évenement du changement de valeur
     * @param {string} value - Nouvelle valeur
     */
    private changeAuto (value: string): void {
        this.syncValue = value
        this.change(value)
    }
}
