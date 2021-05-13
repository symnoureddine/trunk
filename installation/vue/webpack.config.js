const path = require('path');
const {getBaseWPConfig, getBaseTSConfig, scanVueAndUpdateConf} = require('../../dev/WebPack/webpack.utils');

module.exports = new Promise(
  (resolve) => {
    let confWP = getBaseWPConfig(
      path.resolve(__dirname, 'components/app.js'),
      path.resolve(__dirname, 'dist'),
      './installation/vue/dist',
      __dirname
    );
    let confTS = getBaseTSConfig('./dist', './components/**');
    scanVueAndUpdateConf(confWP, confTS, __dirname, path.resolve(__dirname, 'components'), (conf) => {
      resolve(conf)
    });
  }
);
