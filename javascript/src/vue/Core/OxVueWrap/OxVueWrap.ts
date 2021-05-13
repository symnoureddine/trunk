/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component } from "vue-property-decorator"
import OxVue from "@system/OxVue"
import OxLoading from "@system/OxLoading"
import OxNotify from "@system/OxNotify"
import OxAlert from "@system/OxAlert"
import OxAlertManagerApi from "@system/OxAlertManagerApi"
import OxNotifyManagerApi from "@system/OxNotifyManagerApi"
import OxStoreCore from "@system/OxStoreCore"

/**
 * OxVueWrap
 *
 * Wrapper général de l'application Vue.
 */
@Component({ components: { OxLoading, OxNotify, OxAlert } })
export default class OxVueWrap extends OxVue {
    private get alertManager (): OxAlertManagerApi {
        return new OxAlertManagerApi(OxStoreCore)
    }

    private get notifyManager (): OxNotifyManagerApi {
        return new OxNotifyManagerApi(OxStoreCore)
    }
}
