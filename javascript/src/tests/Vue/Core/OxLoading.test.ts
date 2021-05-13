/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import OxLoading from "@system/OxLoading"
import { OxiTest } from "oxify"
import { Wrapper } from "@vue/test-utils"

/**
 * Test pour la classe OxLoading
 */
export default class OxLoadingTest extends OxiTest {
    protected component = OxLoading

    /**
     * @inheritDoc
     */
    protected mountComponent (props: object): Wrapper<OxLoading> {
        return super.mountComponent(props) as Wrapper<OxLoading>
    }

    /**
     * Test chargement forcé
     */
    public testForceLoading (): void {
        this.assertTrue(
            this.mountComponent({ forceLoad: true }).find(".OxLoading.displayed").exists()
        )
    }
}

(new OxLoadingTest()).launchTests()
