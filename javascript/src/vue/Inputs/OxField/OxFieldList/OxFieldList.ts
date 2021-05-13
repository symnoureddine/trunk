/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxFieldStrCore from "@system/OxFieldStrCore"

/**
 * OxFieldList
 *
 * Composant de champ de liste
 */
@Component
export default class OxFieldList extends OxFieldStrCore {
    @Prop({ default: "_id" })
    private optionId!: string

    @Prop({ default: "view" })
    private optionView!: string

    /**
     * Liste des �l�ments possibles
     *
     * @return {Array<Object>}
     */
    public get items (): { id: string; text: string }[] {
        return this.viewList.map(
            (_el) => {
                return {
                    id: _el[this.optionId],
                    text: _el[this.optionView]
                }
            }
        )
    }

    /**
     * R�cup�ration de la valeur affichable
     *
     * @return {string}
     */
    private get mutatedValueStr (): string {
        return this.mutatedValue ? this.mutatedValue.toString() : ""
    }

    /**
     * Composant mont�
     */
    public mounted (): void {
        this.updateMutatedValue()
    }

    /**
     * S�lection d'un �l�ment
     * @param {string} value - Valeur de l'�l�ment s�lectionn�
     */
    private select (value: string): void {
        this.mutatedValue = value
        this.$emit("change", value)
    }
}
