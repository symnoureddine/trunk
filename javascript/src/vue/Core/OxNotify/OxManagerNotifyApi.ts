/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import OxStoreCore from "@system/OxStoreCore"
import { Notification, NotificationDelay, NotificationType, NotificationOpt } from "@system/OxNotifyModel"

/**
 * OxManagerNotifyApi
 */
export default class OxManagerNotifyApi {
    private store: typeof OxStoreCore

    constructor (store: typeof OxStoreCore) {
        this.store = store
    }

    /**
     * Ajout d'une notification à afficher
     * @param {string} libelle - Texte de la notification
     * @param {NotificationType} type - Type de la notification
     * @param {NotificationOpt} options - Options de la notification
     */
    public addNotification (libelle: string, type: NotificationType, options: NotificationOpt = {}): void {
        options.delay = options.delay || NotificationDelay.none
        const key = Math.ceil(Math.random() * Math.pow(10, 16))
        this.store.commit(
            "addNotification",
            { libelle: libelle, type: type, delay: options.delay, key: key, callback: options.callback }
        )
    }

    /**
     * Ajout d'une notification de type Information
     * @param {string} libelle - Texte de la notification
     * @param {NotificationOpt} options - Options de la notification
     */
    public addInfo (libelle: string, options: NotificationOpt = {}): void {
        this.addNotification(libelle, NotificationType.info, options)
    }

    /**
     * Ajout d'une notification de type Warning
     * @param {string} libelle - Texte de la notification
     * @param {NotificationOpt} options - Options de la notification
     */
    public addWarning (libelle: string, options: NotificationOpt = {}): void {
        this.addNotification(libelle, NotificationType.warning, options)
    }

    /**
     * Ajout d'une notification de type Error
     * @param {string} libelle - Texte de la notification
     * @param {NotificationOpt} options - Options de la notification
     */
    public addError (libelle: string, options: NotificationOpt = {}): void {
        this.addNotification(libelle, NotificationType.error, options)
    }

    /**
     * Ajout d'une notification de type Success
     * @param {string} libelle - Texte de la notification
     * @param {NotificationOpt} options - Options de la notification
     */
    public addSuccess (libelle: string, options: NotificationOpt = {}): void {
        this.addNotification(libelle, NotificationType.success, options)
    }

    /**
     * Retrait d'une notification
     * @param {number} key - Identifiant de la notification dans la collection des notifications
     */
    public removeNotification (key: number): void {
        this.store.commit("addHiddenNotification", key)
        setTimeout(
            () => {
                this.store.commit("removeNotification", key)
            },
            2000
        )
        const notification = this.notifications.find(notification => notification.key === key)
        if (notification && notification.callback && !notification.callbackDone) {
            this.store.commit("callbackDoneNotification", key)
            notification.callback()
        }
    }

    /**
     * Récupération de la liste des notifications
     *
     * @return {Array<Notification>}
     */
    public get notifications (): Notification[] {
        return this.store.getters.getNotifications.map(
            (notification: Notification) => {
                return Object.assign(
                    {
                        show: !this.hiddenNotifications.includes(notification.key)
                    },
                    notification
                )
            }
        )
    }

    public get hiddenNotifications (): number[] {
        return this.store.getters.getHiddenNotifications
    }
}
