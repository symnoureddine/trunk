const path = require('path');
const fs = require('fs');
const {
  logProcessIn,
  logProcessOut,
  getBaseTSConfig,
  emptyWPConfig,
  scanVueUsingGlossary,
  resolveRootDir
} = require('./webpack.utils');

const rootDir = path.resolve(__dirname, '..', '..');
const componentsDir = path.resolve(rootDir);

const dirAssets = path.resolve(rootDir, 'images', 'assets_vue');

let confTS = getBaseTSConfig(
  './dist',
  './modules/**',
  ["./modules/offlineMode", "./node_modules"]
);

confTS.compilerOptions.esModuleInterop = true

module.exports = new Promise(
  (resolve) => {
    try {
      fs.writeFileSync(
        path.resolve(
          rootDir,
          '.babelrc'
        ),
        '{"presets": ["@babel/preset-env"]}'
      );
    } catch (e) {
      console.warn("Cannot write .babelrc file", e)
    }
    const outputArg = process.argv.indexOf("--outputFile");
    let conf = {
      "moduleFileExtensions": [
        "js",
        "ts",
        "json",
        "vue"
      ],
      "moduleNameMapper": {},
      "transform": {
      ".*\\.(vue)$": "vue-jest",
        "^.+\\.tsx?$": "ts-jest",
        "^.+\\.(js|jsx)$": "babel-jest"
      },
      "testURL": "http://localhost/",
      "testRegex": "/.*(modules/|javascript/src).*/tests/Vue/.*\\.test\\.ts$",
      "rootDir": rootDir,
      "preset": "ts-jest",
      "setupFiles": [
        __dirname + "/jest-setup.ts"
      ]
    }
    if (outputArg >= 1 && process.argv.length >= (outputArg + 2)) {
      conf = Object.assign(
        conf,
        {
          "coverageDirectory": process.argv[outputArg + 1],
          "collectCoverage": true,
          "coverageReporters": ["json-summary", "html"],
        }
      );
    }

    scanVueUsingGlossary(
      emptyWPConfig,
      confTS,
      rootDir,
      componentsDir,
      () => {
        conf.moduleNameMapper["^@system/OxVue$"] = conf.moduleNameMapper["^@system/OxVueForTestsCore$"];
        // conf.moduleNameMapper["@core/OxStoreCore"] = conf.moduleNameMapper["@core/OxStoreForTestsCore"];
        logProcessOut("Aliases loaded");
        logProcessIn("Launching tests");
        resolve(conf);
      },
      dirAssets,
      resolveRootDir(rootDir, ["installation", "node_modules"]),
      (filePath, nameSpace) => {
        conf.moduleNameMapper['^' + nameSpace + '$'] = "<rootDir>" + filePath.replace(rootDir, "");
      }
    );
  }
);
