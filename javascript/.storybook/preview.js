import lang from "../../tmp/storybook-fr.json"
import OxVueApi from "@system/OxVueApi"
import { addDecorator } from '@storybook/vue'
import { VApp } from 'vuetify/lib'
import OxVuetifyCore from "@system/OxVuetifyCore"

addDecorator(() => ({
    vuetify: OxVuetifyCore,
    components: { VApp },
    template: `
      <v-app>
        <div>
            <story/>
        </div>
      </v-app>
      `
    }
));

OxVueApi.init(lang, lang, "", "")

export const parameters = {
  actions: { argTypesRegex: "^on[A-Z].*" },
  options: {
    storySort: {
      method: 'alphabetical',
      order: ['Intro', 'Colors', 'Typography', 'Visual Components', ['Basics', 'Froms', 'Layout'], 'Utility Components']
    },
  },
}
