import { addons } from "@storybook/addons";
import {create} from "@storybook/theming";
import imageFile from "./assets/ox.png";
import colors from "vuetify/lib/util/colors";

addons.setConfig({
  theme: create({
    base: "light",
    brandTitle: "OX-DS",
    brandUrl: "",
    brandImage: imageFile,

    colorPrimary: colors.indigo.base,
    colorSecondary: colors.lightBlue.darken2,
  }),
});

