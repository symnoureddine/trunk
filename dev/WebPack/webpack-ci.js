const confPromise = require("./webpack-dev")

module.exports = new Promise(
  (resolve) => {
    confPromise.then(
      (conf) => {
        conf.watch = false
        resolve(conf)
      }
    )
  }
)
