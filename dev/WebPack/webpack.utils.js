const VueLoaderPlugin = require('vue-loader/lib/plugin');
const path = require('path');
const scandir = require('scandir');
const webpack = require('webpack');
const colors = require('colors');
const fs = require('fs');
colors.setTheme({
  info: 'blue',
  error: 'red',
  success: 'green'
})

let distFileFilter = /-dist\./;
let awcacheFileFilter = /^([a-z]|[0-9]){128}\.json\.gzip$/;
let componentsFilter = /(\.vue|(Provider|Pagination|Locales|Api|Core|OxVue|Model|Helper)\.ts|(Utils|Style).scss)$/;
let glossaryComponentsFilter = /\.ts$/;
let coreFile = [
  'OxVue.ts'
]
let webpackUtilsTimer = null;
let webpackUtilsGlobalTimer = null;
let importsGlossary = [];

function logProcessIn(label) {
  console.log("[ " + colors.info("WP") + " ] " + label + "...")
  timeIn()
}

function logProcessOut(label) {
  console.log("[ " + colors.success("ok") + " ] " + label + " (" + timeOut() + "s)")
}

function logProcessError(label) {
  console.log("[ " + colors.error("KO") + " ]" + label + " (" + timeOut() + "s)")
}

function timeIn() {
  webpackUtilsTimer = process.hrtime()
}

function timeOut() {
  let time = process.hrtime(webpackUtilsTimer)
  return (time[1] / 1000000000) + time[0]
}

/**
 * Getting a namespace basing on a file name
 *
 * @param file File
 * @returns {string}
 */
function getNameSpaceByFile(file) {
  let fileName = file.name
  let fileDir = file.dir
  const mbCore = fileDir.match(/javascript[\\\/]src[\\\/]vue/)
  if (mbCore) {
    fileDir = fileDir.replace(/javascript[\\\/]src[\\\/]vue/, "modules/system/vue")
  }
  const moduleMatch = fileDir.match(/modules[\\\/].*[\\\/]vue/)
  if (moduleMatch) {
    return '@' + moduleMatch[0].substr(8, moduleMatch[0].length - 12) + "/" + fileName
  }

  let nameSpace = '@components/';
  if (file.base.indexOf('Provider.ts') > -1 || file.base.indexOf('Pagination.ts') > -1) {
    nameSpace = '@providers/';
  } else if (file.base.indexOf('Locales.ts') > -1) {
    nameSpace = '@locales/';
  } else if (file.base.indexOf('Api.ts') > -1) {
    nameSpace = '@api/';
  } else if (file.base.indexOf('Core.ts') > -1 || coreFile.indexOf(file.base) > -1) {
    nameSpace = '@core/';
  } else if (file.base.indexOf('Model.ts') > -1) {
    nameSpace = '@models/';
  } else if (file.base.indexOf('Helper.ts') > -1) {
    nameSpace = '@helpers/';
  } else if (file.base.indexOf('Utils.scss') > -1 || file.base.indexOf('Style.scss') > -1) {
    nameSpace = '@styles/';
  }
  return nameSpace + fileName;
}

/**
 * Scanning a path, triggering event on each file and on end
 * @param componentsDir Scanned root directory
 * @param onFile        On each file script
 * @param onEnd         On end script
 */
function scanVue(componentsDir, onFile, onEnd, componentExcl) {
  scandir.create()
    .on('file', (filePath) => {
      let file = path.parse(filePath);
      if (componentExcl && file.dir.match(componentExcl)) {
        return;
      }
      onFile(filePath, getNameSpaceByFile(file))
    })
    .on('end', onEnd)
    .scan({
      dir:    componentsDir,
      filter: componentsFilter
    });
}

/**
 * Scanning a path, generating Webpack and Typescript aliases
 *
 * @param confWP         WebPack base configuration
 * @param confTS         TypeScript base configuration
 * @param dirTS          Typescript tsconfig creation directory
 * @param componentsDir  Scanned directory
 * @param onend          Callback
 * @param baseNameSpaces (opt) Preformated aliases : used to detect the missing components.
 */
function scanVueAndUpdateConf(confWP, confTS, dirTS, componentsDir, onend, baseNameSpaces, dirAssets, directoryExclusion, onFile) {
  logProcessIn("Generating aliases");

  let nameSpaces = [];
  let emptyComponent = {
    alias:  "",
    wsPath: "",
    tsPath: []
  };

  if (dirAssets) {
    let imgAssets = path.parse(dirAssets)
    let imgAlias = "assets"
    confWP.resolve.alias[imgAlias] = dirAssets;
    confTS.compilerOptions.paths[imgAlias] = [imgAssets.dir + path.sep + imgAssets.name];
  }

  scanVue(
    componentsDir,
    (filePath, nameSpace) => {
      let file = path.parse(filePath);
      nameSpaces[nameSpace] = filePath;
      if (baseNameSpaces && baseNameSpaces.indexOf(nameSpace) !== -1) {
        baseNameSpaces.splice(baseNameSpaces.indexOf(nameSpace), 1);
      }
      if (file.base === "OxEmpty.vue") {
        emptyComponent = {
          alias:  nameSpace,
          wsPath: filePath,
          tsPath: [file.dir + path.sep + file.name]
        }
      }
      confWP.resolve.alias[nameSpace] = filePath;
      confTS.compilerOptions.paths[nameSpace] = [file.dir + path.sep + file.name];
      if (onFile) {
        onFile(filePath, nameSpace);
      }
    },
    () => {
      if (baseNameSpaces && baseNameSpaces.length > 0) {
        baseNameSpaces.forEach(
          (nameSpace) => {
            confWP.resolve.alias[nameSpace] = emptyComponent.wsPath;
            confTS.compilerOptions.paths[nameSpace] = emptyComponent.tsPath;
          }
        );
      }
      confWP.plugins.push(
        new webpack.DefinePlugin({
          COMPONENTS: JSON.stringify(nameSpaces)
        })
      );

      logProcessOut("Aliases generated");
      logProcessIn("Writing tsconfig");
      fs.writeFile(
        dirTS + '/tsconfig.json',
        JSON.stringify(confTS),
        (err) => {
          if (err) {
            console.error(err);
            logProcessError("An error occured while trying to write the tsconfig file");
          }
          else {
            logProcessOut("Tsconfig written");
          }
          onend(confWP);
        })
    },
    directoryExclusion
  )
}

module.exports = {
  /**
   * Getting a basic WebPack configuration
   *
   * @param entry
   * @param outputPath
   * @param publicPath
   * @param configTSPath
   * @param sassUtilsFile
   * @returns object
   */
  getBaseWPConfig:       (entry, outputPath, publicPath, configTSPath, sassOpt = false) => {
    return {
      devtool: 'source-map',
      mode:    'production',
      resolve: {
        extensions: ['.js', '.vue', '.json', '.ts'],
        alias:      {
          'vue$': 'vue/dist/vue.esm.js'
        }
      },
      entry:   {
        app: entry
      },
      output:  {
        filename:      '[name]-dist.js',
        chunkFilename: '[name]-dist.[contenthash].js',
        path:          outputPath,
        publicPath:    publicPath
      },
      module:  {
        rules: [
          {
            test:   /\.vue$/,
            loader: 'vue-loader'
          },
          {
            test: /\.css$/,
            use:  [
              'style-loader', 'vue-style-loader', 'css-loader'
            ]
          },
          {
            test: /\.sass$/,
            use: [
              'vue-style-loader',
              'css-loader',
              {
                loader: 'sass-loader',
                options: sassOpt ? sassOpt.sass : {}
              },
            ],
          },
          {
            test: /\.scss$/,
            use: [
              'vue-style-loader',
              'css-loader',
              {
                loader: 'sass-loader',
                options: sassOpt ? sassOpt.scss : {}
              },
            ],
          },
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
                reportFiles:          ['src/**/*.{ts,tsx}'],
                babelCore:            '@babel/core'
              },
              loader:  'awesome-typescript-loader'
            }
          },
          {
            test: /app.js$/,
            use:  {
              loader:  'babel-loader',
              options: {
                presets: [
                  ["@babel/preset-env", {"targets": "last 2 versions, ie 11", "modules": false}]
                ]
              }
            }
          },
          {
            test: /\.(png|jpe?g|gif)$/i,
            loader: 'file-loader',
            options: {
              name: '[name]-dist.[contenthash].[ext]',
            }
          },
        ]
      },
      plugins: [
        new VueLoaderPlugin()
      ]
    }
  },
  emptyWPConfig: {
    resolve: {
      alias:      {}
    },
    plugins: []
  },
  /**
   * Getting a basic Typescript configuration
   *
   * @param outDir
   * @param includePath
   * @param excludes
   * @returns object
   */
  getBaseTSConfig:       (outDir, includePath, excludes) => {
    excludes = excludes ? excludes : [];
    return {
      "compilerOptions": {
        "outDir":                       outDir,
        "sourceMap":                    true,
        "strict":                       true,
        "noImplicitReturns":            true,
        "experimentalDecorators":       true,
        "allowSyntheticDefaultImports": true,
        "noImplicitAny":                false,
        "module":                       "es2015",
        "moduleResolution":             "node",
        "target":                       "es5",
        "lib":                          [
          "dom",
          "es5",
          "es2015.promise"
        ],
        "baseUrl":                      "./",
        "paths":                        {}
      },
      "include":         [
        includePath + '/*.ts',
        includePath + '/*.tsx',
        includePath + '/*.vue'
      ],
      "exclude":         excludes
    }
  },
  /**
   * See scanVueAndUpdateConf function
   */
  scanVueAndUpdateConf:               scanVueAndUpdateConf,
  /**
   * See ScanVueFunction
   */
  scanVue: scanVue,
  /**
   * See logProcessIn function
   */
  logProcessIn: logProcessIn,
  /**
   * See logProcessOut function
   */
  logProcessOut: logProcessOut,
  /**
   * Scanning a path, generating Webpack and Typescript aliases using gloassary file to detect missing components
   *
   * @param confWP
   * @param confTS
   * @param dirTS
   * @param directory
   * @param onend
   * @param dirAssets
   */
  scanVueUsingGlossary:  (confWP, confTS, dirTS, directory, onend, dirAssets, directoryExclusion, onFile, writeTS = true) => {
    logProcessIn("Generating imports glossary");
    scandir.create()
      .on('file', (filePath) => {
        const file = path.parse(filePath);
        if (directoryExclusion && file.dir.match(directoryExclusion)) {
          return;
        }
        fs.readFile(
          filePath,
          (error, data) => {
            if (error) {
              console.warn("Error while reading " + path.parse(filePath).name);
              return;
            }
            if (path.parse(filePath).ext !== ".ts") {
              return;
            }
            const importLines = Buffer.from(data, 'base64').toString()
              .match(/import .* from "@(components|providers|locales|api|core|models|helpers|styles)\/.*"/g)
            if (importLines) {
              importLines.forEach(
                (importLine) => {
                  const importFile = importLine.match(/("|').*("|')/g);
                  if (!importFile || importFile.length === 0) {
                    return;
                  }
                  let fileNameSpace = importFile[0].substr(1, importFile[0].length - 2);
                  // fileNameSpace += " from " + path.parse(filePath).name
                  if (importsGlossary.indexOf(fileNameSpace) >= 0) {
                    return;
                  }
                  importsGlossary.push(fileNameSpace)
                }
              );
            }
          }
        )

      })
      .on('end', () => {
        logProcessOut("Imports glossary generated")
        scanVueAndUpdateConf(
          confWP,
          confTS,
          dirTS,
          directory,
          onend,
          importsGlossary,
          dirAssets,
          directoryExclusion,
          onFile,
          writeTS
        )
      })
      .scan({
        dir:    directory,
        filter: componentsFilter
      });
  },

  /**
   * Delete all the distribuable files in the given directory
   *
   * @param directory
   * @param onend
   */
  purgeDist: (directory, onend) => {
    logProcessIn("Cleaning up obsolete dist files ");
    scandir.create()
      .on('file', (filePath) => {
        fs.unlinkSync(filePath);
      })
      .on('end', () => {
        logProcessOut("Files deleted")
        onend();
      })
      .scan({
        dir:    directory,
        filter: distFileFilter
      });
  },
  purgeAWCache: (awDir, onend) => {
    logProcessIn("Checking .awcache directory");
    if (!fs.existsSync(awDir)) {
      fs.mkdir(
        awDir,
        () => {
          logProcessOut("No directory : directory created, cleanup skipped");
          onend();
        }
      )
      return
    }
    logProcessOut("Directory found");
    logProcessIn("Cleaning up awesome-typescript-loader cache (.awcache)");
    scandir.create()
      .on('file', (filePath) => {
        fs.unlinkSync(filePath);
      })
      .on('end', () => {
        logProcessOut("Files deleted")
        onend();
      })
      .scan(
        {
          dir: awDir,
          filter: awcacheFileFilter
        }
      )
  },
  resolveRootDir: (rootDir, subDir) => {
    return new RegExp(
      rootDir.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + "(\\/|\\\\)(" + subDir.join("|") + ")"
    )
  }
};
