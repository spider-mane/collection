const path = require("path");
const yargs = require("yargs").argv;

const mode = yargs.mode;

const jsSrc = "./assets/src/js/index.js";
const cssSrc = "./assets/src/scss/styles.scss";

const exportDir = path.join(__dirname, "assets", "dist");

module.exports = {
  entry: [jsSrc],
  output: {
    path: exportDir,
    filename: "index.js",
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: [/node_modules/, /vendor/],
        loader: "babel-loader",
      },
    ],
  },
  resolve: {
    extensions: [".json", ".js", ".jsx"],
  },
  devtool: mode === "development" ? "source-map" : false,
  devServer: {
    contentBase: path.join(__dirname, "/dist/"),
    inline: true,
    host: "localhost",
    port: 8080,
  },
};
