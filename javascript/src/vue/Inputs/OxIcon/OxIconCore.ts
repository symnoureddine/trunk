/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import {
    mdiRefresh,
    mdiHelp,
    mdiMagnify,
    mdiCheck,
    mdiWindowClose,
    mdiCalendar,
    mdiClockOutline,
    mdiDomain,
    mdiDoctor,
    mdiPlus,
    mdiMinus,
    mdiContentCut,
    mdiGenderMale,
    mdiGenderFemale,
    mdiAccount,
    mdiArrowLeft,
    mdiChevronRight,
    mdiCurrencyEur,
    mdiChevronLeft,
    mdiTimerSand,
    mdiAccountMultipleRemove,
    mdiLock,
    mdiDotsVertical,
    mdiDotsHorizontal,
    mdiRadioboxBlank,
    mdiRadioboxMarked
} from "@mdi/js"

const icons = {
    add: mdiPlus,
    account: mdiAccount,
    accountMultipleRemove: mdiAccountMultipleRemove,
    calendar: mdiCalendar,
    cancel: mdiWindowClose,
    check: mdiCheck,
    chevronLeft: mdiChevronLeft,
    chevronRight: mdiChevronRight,
    currency: mdiCurrencyEur,
    female: mdiGenderFemale,
    hDots: mdiDotsHorizontal,
    help: mdiHelp,
    intervention: mdiContentCut,
    lock: mdiLock,
    male: mdiGenderMale,
    praticien: mdiDoctor,
    previous: mdiArrowLeft,
    radioBlank: mdiRadioboxBlank,
    radioMarked: mdiRadioboxMarked,
    refresh: mdiRefresh,
    remove: mdiMinus,
    search: mdiMagnify,
    sejour: mdiDomain,
    time: mdiClockOutline,
    timerSand: mdiTimerSand,
    vDots: mdiDotsVertical
}

export default class OxIconCore {
    static get (icon: string) {
        return typeof (icons[icon]) !== "undefined" ? icons[icon] : icons.help
    }
}
