const path = require('path');
const SpeedMeasurePlugin = require("speed-measure-webpack-plugin");
const smp = new SpeedMeasurePlugin();
const {
  getBaseWPConfig,
  getBaseTSConfig,
  scanVueUsingGlossary,
  purgeDist,
  purgeAWCache,
  resolveRootDir
} = require('./webpack.utils');
const VuetifyLoaderPlugin = require('vuetify-loader/lib/plugin');
const ESLintPlugin = require("eslint-webpack-plugin");
const eslintConfiguration = require("./eslint.config");
const eslintOptions = {
  "extensions":   "ts",
  "baseConfig": eslintConfiguration
};

const rootDir = path.resolve(__dirname, '..', '..');
const awcache = path.resolve(rootDir, ".awcache");
const javascriptDir = path.resolve(rootDir, 'javascript');
const srcFile = path.resolve(javascriptDir, 'src', 'app.js');
const distDir = path.resolve(javascriptDir, 'dist');
const componentsDir = path.resolve(rootDir);
const dirAssets = path.resolve(rootDir, 'images', 'assets_vue');

module.exports = new Promise(
  (resolve) => {
    purgeAWCache(
      awcache,
      () => {
        purgeDist(
          distDir,
          () => {
            let confWP = getBaseWPConfig(
              srcFile,
              distDir,
              './javascript/dist/',
              __dirname,
              {
                sass : {
                  implementation: require('sass'),
                  prependData: "@import '@system/variablesStyle'"
                },
                scss : {
                  implementation: require('sass'),
                  prependData: "@import '@system/variablesStyle';"
                }
              }
            );
            confWP.plugins.push(new VuetifyLoaderPlugin());
            confWP.plugins.push(new ESLintPlugin(eslintOptions));
            confWP.mode  = 'development';
            confWP.watch = true;
            let confTS = getBaseTSConfig('./dist', './modules/**', ["./modules/offlineMode", "./node_modules"]);
            scanVueUsingGlossary(
              confWP,
              confTS,
              rootDir,
              componentsDir,
              (conf) => {
                resolve(conf)
              },
              dirAssets,
              resolveRootDir(rootDir, ["installation", "node_modules"])
            );
          }
        );
      }
    );
  }
);
