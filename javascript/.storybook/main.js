const path = require("path");
const rimraf = require("rimraf");
const VuetifyLoaderPlugin = require("vuetify-loader/lib/plugin");
const {
  logProcessIn,
  logProcessOut,
  scanVueUsingGlossary,
  resolveRootDir,
  getBaseWPConfig
} = require("../../dev/WebPack/webpack.utils");
const Vue = require("vue/dist/vue.js")
const scandir = require("scandir")
const fs = require("fs")

const rootDir = path.resolve(__dirname, "..");
const rootProject = path.resolve(rootDir, "..");
const componentsDir = path.resolve(rootDir);
const localesFile = path.resolve(rootProject, "tmp", "storybook-fr.json");
const buildRepository = path.resolve(rootProject, "tmp", "storybook");
const dirAssets = path.resolve(rootDir, "images", "assets_vue");

const confTS = {
  "module": "es2015",
  "moduleResolution": "node",
  "target": "es5",
  "allowSyntheticDefaultImports": true,
  "experimentalDecorators": true,
  "compilerOptions": {
    "baseUrl": "",
    "paths":   {}
  }
};

const outputArg = process.argv.indexOf("--output-dir");
logProcessIn("Building storybook...");
if (outputArg !== -1 && process.argv.length > (outputArg + 1)) {
  const outputDir = process.argv[outputArg + 1];
  if (fs.existsSync(buildRepository)) {
    rimraf(
      path.resolve(rootProject, outputDir),
      {},
      () => {
            logProcessOut("Skipping, build already exists.");
            process.exit(0);
      }
    );
  }
}

let traductions = {};
const baseExport = {
  typescript: {
    check:        true,
    checkOptions: {},
  },
  "stories": [
    "./stories/*.story.mdx",
    "./stories/UtilityComponents/*.story.@(ts|mdx)",
    "./stories/VisualComponents/**/*.story.@(ts|mdx)"
  ],
  "addons": [
    "@storybook/addon-links",
    {
      name: "@storybook/addon-essentials",
      options: {
        actions: false,
      }
    }
  ],
  plugins: [
    [require.resolve("@babel/plugin-proposal-decorators"), { legacy: true }],
    require.resolve("@babel/plugin-transform-runtime"),
    require.resolve("babel-plugin-add-module-exports"),
    require.resolve("@babel/plugin-proposal-class-properties"),
    require.resolve("@babel/plugin-syntax-dynamic-import")
  ],
  "webpackFinal": async (configWP) => {
    return await (new Promise((resolve) => {
      configWP.module.rules.push(
        {
          test: /\.scss$/,
          use: [
            "vue-style-loader",
            "css-loader",
            {
              loader: "sass-loader",
              options: {
                implementation: require("sass"),
                prependData: "@import '@styles/StoryStyle';"
              }
            }
          ]
        }
      );
      configWP.module.rules.push(
        {
          test: /\.sass$/,
          use: [
            "vue-style-loader",
            "css-loader",
            {
              loader: "sass-loader",
              options: {
                implementation: require("sass"),
                prependData: "@import '@styles/StoryStyle'"
              }
            },
          ],
        }
      );
      configWP.module.rules.push(
        {
          test:    /\.tsx?$/,
          exclude: /node_modules/,
          use:     {
            options: {
              useTranspileModule:   true,
              forceIsolatedModules: true,
              useCache:             true, /* The default cache folder is "/.awcache", the cacheDirectory can be used to change
                                               the directory. */
              useBabel:             true,
              babelOptions:         {
                babelrc: false /* Important line */,
                presets: [
                  ["@babel/preset-env", {"targets": "last 2 versions, ie 11", "modules": false}]
                ]
              },
              reportFiles:          ["src/**/*.{ts,tsx}"],
              babelCore:            "@babel/core"
            },
            loader:  "awesome-typescript-loader"
          }
        }
      );
      configWP.plugins.push(new VuetifyLoaderPlugin());
      logProcessIn("Searching traductions...");
      scandir.create()
        .on("file", (filePath) => {
          const file = path.parse(filePath);
          // not fr-common detection
          if (file.name === "common" && !file.dir.match(/.*fr$/)) {
            return;
          }
          // not system module detection
          if (file.name === "fr" && !file.dir.match(/.*modules[\\|\/]system[\\|\/]/)) {
            return;
          }
          fs.readFile(
            filePath,
            "latin1",
            (err, data) => {
              if (err) {
                console.error(err);
                return;
              }
              const lines = data.split("\n");
              for (const line of lines) {
                const keyMatches = line.match(/\$locales\['.*'\]/g);
                if (!keyMatches) {
                  continue;
                }
                const valueMatches = line.match(/ '.*';/g);
                if (!valueMatches) {
                  continue;
                }
                const key = keyMatches[0].replace("$locales['", "").replace("']", "");
                const value = valueMatches[0].replace(" '", "").replace("';", "");
                traductions[key] = value;
              }
            }
          );
        })
        .on("end", () => {
          fs.writeFile(
            localesFile,
            // __dirname + "/lang.json",
            JSON.stringify(traductions),
            () => {
              logProcessOut("");
              scanVueUsingGlossary(
                configWP,
                confTS,
                __dirname,
                componentsDir,
                (configWP) => {
                  configWP.resolve.alias["vue"] = "vue/dist/vue.js";
                  configWP.resolve.alias["@system/OxVue"] = configWP.resolve.alias["@system/OxVueForStoriesCore"];
                  logProcessOut("Aliases loaded");
                  logProcessIn("Launching storybook");
                  resolve(configWP);
                },
                dirAssets,
                resolveRootDir(rootDir, ["installation", "node_modules"])
              );
            }
          )
        })
        .scan({
          dir:    rootProject,
          filter: /^(fr|common)\.php$/
        });
    }))
  }
};

module.exports = baseExport;
