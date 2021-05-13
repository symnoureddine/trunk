/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { NotificationDelay, NotificationType } from "@system/OxNotifyModel"
import OxNotify from "@system/OxNotify"
import { OxiTest } from "oxify"
import { Wrapper } from "@vue/test-utils"
import OxStoreCore from "@system/OxStoreCore"
import OxNotifyManagerApi from "@system/OxNotifyManagerApi"

/**
 * Test pour la classe OxNotify
 */
export default class OxNotifyTest extends OxiTest {
    protected component = OxNotify

    private notifyManager = new OxNotifyManagerApi(OxStoreCore)

    /**
     * @inheritDoc
     */
    protected mountComponent (props: object): Wrapper<OxNotify> {
        return super.mountComponent(props) as Wrapper<OxNotify>
    }

    /**
     * @inheritDoc
     */
    protected vueComponent (props: object): OxNotify {
        return super.vueComponent(props) as OxNotify
    }

    /**
     * @inheritDoc
     */
    protected afterTest () {
        this.notifyManager.removeAllNotifications()
    }

    /**
     * Attente d'un nombre défini de secondes
     * @param delay {number} - Nombre de secondes
     *
     * @return {Promise<void>}
     */
    private async wait (delay: number): Promise<void> {
        await new Promise((resolve) => { setTimeout(resolve, delay) })
    }

    /**
     * Test de l'affichage d'un label donné
     */
    public testLabel (): void {
        const testLabel = "Test"
        const notify = this.vueComponent({ notificationManager: this.notifyManager })
        this.notifyManager.addInfo(testLabel)
        const notifications = this.privateCall(notify, "notifications")
        this.assertHaveLength(notifications, 1)
        const notification = notifications[0]
        this.assertEqual(
            notification.libelle,
            testLabel
        )
    }

    /**
     * Test d'affichage d'une notification de type information
     */
    public testInfo (): void {
        const notify = this.vueComponent({ notificationManager: this.notifyManager })
        this.notifyManager.addInfo("test")
        const notifications = this.privateCall(notify, "notifications")
        const notification = notifications[0]
        this.assertEqual(
            notification.type,
            NotificationType.info
        )
        this.assertEqual(
            this.privateCall(
                notify,
                "notifyColor",
                notification
            ),
            OxNotify.COLOR_INFO
        )
    }

    /**
     * Test d'affichage d'une notification de type avertissement
     */
    public testWarning (): void {
        const notify = this.vueComponent({ notificationManager: this.notifyManager })
        this.notifyManager.addWarning("test")
        const notifications = this.privateCall(notify, "notifications")
        const notification = notifications[0]
        this.assertEqual(
            notification.type,
            NotificationType.warning
        )
        this.assertEqual(
            this.privateCall(
                notify,
                "notifyColor",
                notification
            ),
            OxNotify.COLOR_WARNING
        )
    }

    /**
     * Test d'affichage d'une notification de type erreur
     */
    public testError (): void {
        const notify = this.vueComponent({ notificationManager: this.notifyManager })
        this.notifyManager.addError("test")
        const notifications = this.privateCall(notify, "notifications")
        const notification = notifications[0]
        this.assertEqual(
            notification.type,
            NotificationType.error
        )
        this.assertEqual(
            this.privateCall(
                notify,
                "notifyColor",
                notification
            ),
            OxNotify.COLOR_ERROR
        )
    }

    /**
     * Test d'affichage de plusieurs notifications
     */
    public testMultipleNotification (): void {
        const notify = this.vueComponent({ notificationManager: this.notifyManager })
        this.notifyManager.addInfo("test")
        this.notifyManager.addError("test")
        this.notifyManager.addWarning("test")
        const notifications = this.privateCall(notify, "notifications")
        this.assertHaveLength(notifications, 3)
    }

    /**
     * Test du retrait d'une notification
     */
    public async testUnsetNotification (): Promise<void> {
        const notify = this.vueComponent({ notificationManager: this.notifyManager })
        let callbackFlag = 0
        const expectedCallbackFlag = 1
        this.notifyManager.addInfo(
            "test",
            {
                callback: () => {
                    callbackFlag = expectedCallbackFlag
                }
            }
        )
        const notifications = this.privateCall(notify, "notifications")
        this.assertHaveLength(notifications, 1)
        const notification = notifications[0]
        this.notifyManager.removeNotification(notification.key)
        await this.wait(2000)
        this.assertHaveLength(this.privateCall(notify, "notifications"), 0)
        this.assertEqual(callbackFlag, expectedCallbackFlag)
    }

    /**
     * Test du délai de disparition d'une notification
     */
    public async testDelay (): Promise<void> {
        const notify = this.vueComponent({ notificationManager: this.notifyManager })
        let callbackFlag = 0
        const expectedCallbackFlag = 1
        this.notifyManager.addInfo(
            "test",
            {
                delay: NotificationDelay.short,
                callback: () => {
                    callbackFlag = expectedCallbackFlag
                }
            }
        )
        let notifications = this.privateCall(notify, "notifications")
        this.assertHaveLength(notifications, 1)

        await this.wait(NotificationDelay.short)

        notifications = this.privateCall(notify, "notifications")
        this.assertHaveLength(notifications, 0)
        this.assertEqual(callbackFlag, expectedCallbackFlag)
    }
}

(new OxNotifyTest()).launchTests()
