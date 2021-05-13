import Vue from "vue"
import Vuetify from "vuetify/lib"
import fr from "vuetify/src/locale/fr"
import colors from "vuetify/lib/util/colors"
import OxThemeCore from "@system/OxThemeCore"
import OxIconCore from "@system/OxIconCore"
import OxDate from "@system/OxDate"

Vue.use(Vuetify)
Vue.mixin({
    data: function () {
        return {
            get OxThemeCore () {
                return OxThemeCore
            },
            get OxIconCore () {
                return OxIconCore
            },
            get OxDate () {
                return OxDate
            }
        }
    }
})

export default new Vuetify({
    lang: {
        locales: { fr },
        current: "fr"
    },
    icons: {
        iconfont: "mdiSvg"
    },
    theme: {
        themes: {
            light: {
                // @todo: À changer pour vraies couleurs
                primary: colors.indigo.base,
                secondary: colors.lightBlue.darken2,
                anchor: colors.lightBlue.base
            }
        }
    }
})
