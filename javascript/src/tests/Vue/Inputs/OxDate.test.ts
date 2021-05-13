/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import OxDate from "@system/OxDate"
import { OxiTest } from "oxify"

/**
 * Test pour la classe Date
 */
export default class OxDateTest extends OxiTest {
    protected component = OxDate

    /**
     * Test extraction datetime long
     */
    public testDateYMDHISExtraction (): void {
        this.assertEqual(
            OxDate.getYMDHms(new Date("2020-01-01 10:11:23")),
            "2020-01-01 10:11:23"
        )
    }

    /**
     * Test extraction datetime court
     */
    public testDateYMDHIExtraction (): void {
        this.assertEqual(
            OxDate.getYMDHm(new Date("2020-01-01 10:11:23")),
            "2020-01-01 10:11"
        )
    }

    /**
     * Test extracation date
     */
    public testDateYMDExtraction (): void {
        this.assertEqual(
            OxDate.getYMD(new Date("2020-01-01 10:11:23")),
            "2020-01-01"
        )
    }

    /**
     * Test extraction heure longue
     */
    public testDateHISExtraction (): void {
        this.assertEqual(
            OxDate.getHms(new Date("2020-01-01 10:11:23")),
            "10:11:23"
        )
    }

    /**
     * Test extraction heure courte
     */
    public testDateHIExtraction (): void {
        this.assertEqual(
            OxDate.getHm(new Date("2020-01-01 10:11:23")),
            "10:11"
        )
    }
}

(new OxDateTest()).launchTests()
