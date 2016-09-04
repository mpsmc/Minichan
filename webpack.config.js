const isProduction = process.argv.indexOf('-p') !== -1;
const webpack = require('webpack');
const path = require('path');
const ExtractTextPlugin = require("extract-text-webpack-plugin");

module.exports = {
   entry: {
     main: './javascript/main.js',
     mobile: './javascript/mobile.js',
     polls: './javascript/polls.js',
   },
   devtool: 'source-map',
   output: {
     path: path.join(__dirname, "bin"),
     publicPath: "bin/",
     filename: "[name].js",
     chunkFilename: "[id].js"
   },
   module: {
     loaders: [
       {
         test: /\.js$/,
         exclude: /node_modules/,
         loader: 'babel-loader'
       },
       {
         test: /\.css$/,
         loader: ExtractTextPlugin.extract("style-loader", "css-loader")
       },
       {
         test: /\.less$/,
         loader: ExtractTextPlugin.extract("style-loader", "css-loader!less-loader")
       }
     ]
   },
   plugins: [
     function() {
       this.plugin("done", function(stats) {
         var json = stats.toJson();
         var css = [];
         var js = [];

         json.assets.forEach(function(asset) {
           if(asset.name.endsWith('.css')) {
             css.push({
               name: asset.name,
               mobile: asset.name.indexOf('mobile') !== -1
             });
           }else if(asset.name.endsWith('.js')) {
             js.push({
               name: asset.name,
               mobile: asset.name.indexOf('mobile') !== -1
             });
           }
         });
         require("fs").writeFileSync(
           path.join(__dirname, "includes", "stats.json"),
           JSON.stringify({
             css: css,
             js: js,
             hash: json.hash
           })
         );
       });
     },
     new webpack.optimize.UglifyJsPlugin({
       compress: {
         warnings: false,
       },
       output: {
         comments: false,
       },
     }),
     new ExtractTextPlugin("[name].css")
   ]
};
