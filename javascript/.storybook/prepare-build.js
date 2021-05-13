const path = require("path");
const fs = require("fs");
const chmodr = require('chmodr');
const {
  logProcessIn,
  logProcessOut,
} = require("../../dev/WebPack/webpack.utils");

const rootProject = path.resolve(__dirname, "..", "..");
const tmpFolder = path.resolve(rootProject, "tmp");
const tmpBuildFolder = path.resolve(tmpFolder, ".storybook");
const buildFolder = path.resolve(tmpFolder, "storybook");
const buildIndex = path.resolve(buildFolder, "index.html");
const tsconfigPath = path.resolve(rootProject, "javascript", ".storybook", "tsconfig.json");
const localesPath = path.resolve(tmpFolder, "storybook-fr.json");

if (!fs.existsSync(tmpBuildFolder)) {
  process.exit(0);
}

logProcessIn("Moving temp storybook");

fs.rename(
  tmpBuildFolder,
  buildFolder,
  (err) => {
    if (err) {
      console.warn(err);
      return;
    }
    logProcessOut("Storybook moved");
    logProcessIn("Preparing storybook");

    try {
      const data = fs.readFileSync(buildIndex, "utf-8")
        .replace("<head>", "<head><base href=\"./tmp/storybook/\">");
      fs.writeFileSync(buildIndex, data);
      logProcessOut("Storybook finished");
      if (fs.existsSync(tsconfigPath)) {
        logProcessIn("Removing temporary tsconfig");
        fs.unlinkSync(tsconfigPath);
        logProcessOut("Tsconfig removed");
      }
      if (fs.existsSync(localesPath)) {
        logProcessIn("Removing temporary locales file");
        fs.unlinkSync(localesPath);
        logProcessOut("Locales file removed");
      }
      logProcessIn("Fixing folder rights");
      chmodr(
        buildFolder,
        0o777,
        () => {
          logProcessOut("Rights fixed");
        }
      )
    } catch (err) {
      console.error(err)
    }

  }
);
