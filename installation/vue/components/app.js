/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import Vue from "vue"
import 'es6-promise/auto'
// import {polyfillLoader} from 'polyfill-io-feature-detection'

// polyfillLoader({
//   "features":    "Promise",
//   "onCompleted": polyfill
// })
// if (window.NodeList && !NodeList.prototype.forEach) {
//   NodeList.prototype.forEach = Array.prototype.forEach
// }

import Install from "@components/Install"
import { lang } from "@locales/INLocales"

// Initialize the Vue Components
document.addEventListener(
  "readystatechange",
  (event) => {
    if (document.readyState !== "complete") {
      // Escaping while the document is not complete
      return false
    }
    let endPoint = document.head.querySelector("[name='ox-endpoint']")
    endPoint = endPoint ? endPoint.content : "."
    new Vue(
      {
        el:         "#app_install",
        template:   "<Install end-point='" + endPoint + "'/>",
        components: {Install},
        lang
      }
    )
  }
)
