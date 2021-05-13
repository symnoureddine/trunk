/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxVue from "@system/OxVue"
import { Notification } from "@system/OxNotifyModel"
import OxButton from "@system/OxButton"
import OxNotifyManagerApi from "@system/OxNotifyManagerApi"

/**
 * OxNotify
 */
@Component({ components: { OxButton } })
export default class OxNotify extends OxVue {
    @Prop()
    private notificationManager!: OxNotifyManagerApi

    static COLOR_SUCCESS = "green"
    static COLOR_INFO = "blue"
    static COLOR_WARNING = "orange"
    static COLOR_ERROR = "red"

    /**
     * Liste des notifications à afficher
     *
     * @return {Array<Notify>}
     */
    private get notifications (): Notification[] {
        if (!this.notificationManager) {
            return []
        }
        return this.notificationManager.notifications
    }

    /**
     * Récupération de la couleur d'une notification
     * @param {Notification} notification
     *
     * @return {string}
     */
    private notifyColor (notification: Notification): string {
        return {
            success: OxNotify.COLOR_SUCCESS,
            info: OxNotify.COLOR_INFO,
            warning: OxNotify.COLOR_WARNING,
            error: OxNotify.COLOR_ERROR
        }[notification.type]
    }

    private removeNotification (notificationId: number): void {
        this.notificationManager.removeNotification(notificationId)
    }
}
