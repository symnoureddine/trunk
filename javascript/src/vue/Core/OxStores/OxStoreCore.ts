/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import Vue from "vue"
import Vuex from "vuex"
import { Notification } from "@system/OxNotifyModel"
import { Alert } from "@system/OxAlertModel"
// import VuexPersistence from 'vuex-persist'

Vue.use(Vuex)

export default new Vuex.Store({
    state: {
        /**
         * Main parameters
         */
        rootUrl: "",
        baseUrl: "",
        loadings: ([] as string[]),

        /**
         * Global configurations
         */
        configurations: {},
        configuration_promises: [],
        group_configurations: {},
        group_configuration_promises: [],
        preferences: {},
        locales: ([] as {key: string; traduction: string}[]),

        /**
         * Field specifications
         */
        specs: [],
        saved_specs: ([] as string[]),

        /**
         * Events
         */
        document_click: ([] as Function[]),
        document_click_id: ([] as string[]),
        document_scroll: ([] as Function[]),
        document_scroll_id: ([] as string[]),

        /**
         * SIH Parameters
         */
        sih_url: "",
        sih_id: "",
        sih_type: "",
        sih_group_id: "",
        tamm_patient_id: "",
        sih_cabinet_id: "",

        /**
         * Api cache
         */
        /* eslint-disable  @typescript-eslint/no-explicit-any */
        api_cache: ([] as any[]),

        /**
         * Notifications
         */
        notifications: [] as Notification[],
        hidden_notifications: [] as number[],

        /**
         * Notifications
         */
        alert: null as Alert|null
    },
    getters: {
        /**
         * Main parameters
         */
        url: (state) => {
            return state.baseUrl
        },
        rooturl: (state) => {
            return state.rootUrl
        },
        loading: (state) => {
            return state.loadings.length > 0
        },

        /**
         * Global configurations
         */
        conf: (state) => (conf) => {
            if (typeof (state.configurations[conf]) !== "undefined") {
                return state.configurations[conf]
            }
            return "undefined"
        },
        gconf: (state) => (gconf) => {
            if (typeof (state.group_configurations[gconf]) !== "undefined") {
                return state.group_configurations[gconf]
            }
            return "undefined"
        },
        pref: (state) => (label) => {
            return typeof (state.preferences[label]) !== "undefined" ? state.preferences[label] : false
        },
        tr: (state) => (key) => {
            return typeof (state.locales[key]) !== "undefined" ? state.locales[key] : false
        },
        trs: (state) => {
            return state.locales
        },

        hasConfigurationPromise: (state) => (conf) => {
            return typeof (state.configuration_promises[conf]) !== "undefined"
        },
        configurationPromise: (state) => (conf) => {
            return typeof (state.configuration_promises[conf]) === "undefined" ? false : state.configuration_promises[conf]
        },
        hasGroupConfigurationPromise: (state) => (conf) => {
            return typeof (state.group_configuration_promises[conf]) !== "undefined"
        },
        groupConfigurationPromise: (state) => (conf) => {
            return typeof (state.group_configuration_promises[conf]) === "undefined" ? false : state.group_configuration_promises[conf]
        },

        /**
         * Field specifications
         */
        spec: (state) => (objectType, objectField) => {
            return state.specs[objectType] && state.specs[objectType][objectField] ? state.specs[objectType][objectField] : false
        },
        // hasSpecByType: (state) => (objectType) => { return objectType },
        hasSpecByFieldset: (state) => (objectType, objectFieldset) => {
            if (state.saved_specs.indexOf(objectType + "-" + objectFieldset) === -1) {
                return false
            }
            return true
        },
        hasSpecByFieldsets: (state) => (objectType, objectFieldsets) => {
            for (let i = 0; i < objectFieldsets.length; i++) {
                if (state.saved_specs.indexOf(objectType + "-" + objectFieldsets[i]) === -1) {
                    return false
                }
            }
            return true
        },

        /**
         * Events
         */
        getDocumentClickEvents: (state) => {
            return state.document_click_id.map(
                (_id) => {
                    return state.document_click[_id]
                }
            )
        },
        getDocumentScrollEvents: (state) => {
            return state.document_scroll_id.map(
                (_id) => {
                    return state.document_scroll[_id]
                }
            )
        },

        /**
         * SIH parameters
         */
        getSIHType: (state) => {
            return state.sih_type
        },
        getSIHParameters: (state) => {
            return {
                sihType: state.sih_type,
                sihId: state.sih_id,
                sihUrl: state.sih_url,
                sihGroupId: state.sih_group_id,
                tammPatientId: state.tamm_patient_id,
                sihCabinetId: state.sih_cabinet_id
            }
        },

        /**
         * API Cache
         */
        getApiCache: (state) => (key) => {
            if (typeof (state.api_cache[key]) === "undefined") {
                return false
            }
            return state.api_cache[key]
        },

        /**
         * Notifications
         */
        getNotifications: (state) => {
            return state.notifications
        },
        getHiddenNotifications: (state) => {
            return state.hidden_notifications
        },

        /**
         * Alert
         */
        getAlert: (state) => {
            return state.alert
        }
    },
    mutations: {
        /**
         * Main parameters
         */
        setBaseUrl: (state, baseUrl: string) => {
            state.baseUrl = baseUrl
        },
        setRootUrl: (state, rootUrl: string) => {
            state.rootUrl = rootUrl
        },
        addLoading: (state) => {
            state.loadings.push("_")
        },
        removeLoading: (state) => {
            state.loadings.shift()
        },
        resetLoading: (state) => {
            state.loadings = []
        },
        /**
         * Global configurations
         */
        /* eslint-disable  @typescript-eslint/no-explicit-any */
        setConfiguration: (state, conf: { name: string; value: any }) => {
            state.configurations[conf.name] = conf.value
        },
        /* eslint-disable  @typescript-eslint/no-explicit-any */
        setGroupConfiguration: (state, conf: { name: string; value: any }) => {
            state.group_configurations[conf.name] = conf.value
        },
        setPreference: (state, pref: { name: string; value: string }) => {
            state.preferences[pref.name] = pref.value
        },
        setPreferences: (state, prefs: {name: string; value: string}[]) => {
            state.preferences = prefs
        },
        setLocale: (state, locale: { key: string; traduction: string }) => {
            state.locales[locale.key] = locale.traduction
        },
        setLocales: (state, locales: {key: string; traduction: string}[]) => {
            state.locales = locales
        },
        setConfigurationPromise: (state, params: { conf: string; promise: Promise<string> }) => {
            if (typeof (state.configuration_promises[params.conf]) !== "undefined") {
                return
            }
            state.configuration_promises[params.conf] = params.promise
        },
        removeConfigurationPromise: (state, conf: string) => {
            if (typeof (state.configuration_promises[conf]) === "undefined") {
                return
            }
            delete (state.configuration_promises[conf])
        },
        setGroupConfigurationPromise: (state, params: { conf: string; promise: Promise<string> }) => {
            if (typeof (state.group_configuration_promises[params.conf]) !== "undefined") {
                return
            }
            state.group_configuration_promises[params.conf] = params.promise
        },
        removeGroupConfigurationPromise: (state, conf: string) => {
            if (typeof (state.group_configuration_promises[conf]) === "undefined") {
                return
            }
            delete (state.group_configuration_promises[conf])
        },

        /**
         * Field specifications
         */
        setSpec: (state, object: { type: string; specs: any; fieldsets: string[] }) => {
            state.specs[object.type] = object.specs
            object.fieldsets.forEach(
                (_fieldset) => {
                    state.saved_specs.push(object.type + "-" + _fieldset)
                }
            )
        },

        /**
         * Events
         */
        setDocumentClick: (state, func: { id: string; func: Function }) => {
            state.document_click[func.id] = func.func
            state.document_click_id.push(func.id)
        },
        removeDocumentClick: (state, id: string) => {
            if (typeof (state.document_click[id]) === "undefined") {
                return
            }
            state.document_click_id.splice(state.document_click_id.indexOf(id), 1)
            delete (state.document_click[id])
        },
        setDocumentScroll: (state, func: { id: string; func: Function }) => {
            state.document_scroll[func.id] = func.func
            state.document_scroll_id.push(func.id)
        },
        removeDocumentScroll: (state, id: string) => {
            if (typeof (state.document_scroll[id]) === "undefined") {
                return
            }
            state.document_scroll_id.splice(state.document_scroll_id.indexOf(id), 1)
            delete (state.document_scroll[id])
        },

        /**
         * SIH Parameters
         */
        setSIHParameters: (
            state, sihParameters: {
                sihType: string
                sihId: string
                sihUrl: string
                sihGroupId: string
                tammPatientId: string
                sihCabinetId: string
            }
        ) => {
            state.sih_id = sihParameters.sihId
            state.sih_url = sihParameters.sihUrl
            state.sih_type = sihParameters.sihType
            state.sih_group_id = sihParameters.sihGroupId
            state.tamm_patient_id = sihParameters.tammPatientId
            state.sih_cabinet_id = sihParameters.sihCabinetId
        },

        /**
         * Api Cache
         */
        setApiCache: (state, cache: { key: string; value: any }) => {
            state.api_cache[cache.key] = cache.value
        },

        /**
         * Notifications
         */
        addNotification: (state, notification: Notification) => {
            state.notifications.push(notification)
            if (notification.delay) {
                setTimeout(
                    () => {
                        state.notifications.splice(
                            state.notifications.findIndex(a => a.key === notification.key),
                            1
                        )
                        if (notification.callback && !notification.callbackDone) {
                            notification.callbackDone = true
                            notification.callback()
                        }
                    },
                    notification.delay
                )
            }
        },
        removeNotification: (state, key: number) => {
            const notificationIndex = state.notifications.findIndex(notification => notification.key === key)
            if (notificationIndex !== -1) {
                state.notifications.splice(notificationIndex, 1)
            }
            const hiddenNotificationIndex = state.hidden_notifications.findIndex(notificationKey => notificationKey === key)
            if (hiddenNotificationIndex !== -1) {
                state.hidden_notifications.splice(hiddenNotificationIndex, 1)
            }
        },
        addHiddenNotification: (state, key: number) => {
            state.hidden_notifications.push(key)
        },
        callbackDoneNotification: (state, key: number) => {
            const notificationIndex = state.notifications.findIndex(notification => notification.key === key)
            if (notificationIndex !== -1) {
                state.notifications[notificationIndex].callbackDone = true
            }
        },
        removeAllNotifications: (state) => {
            state.notifications = []
            state.hidden_notifications = []
        },

        /**
         * Alert
         */
        setAlert: (state, alert: Alert) => {
            state.alert = alert
        },
        unsetAlert: (state) => {
            state.alert = null
        }
    }
    // ,
    // plugins: [ vuexSession.plugin ]
})
