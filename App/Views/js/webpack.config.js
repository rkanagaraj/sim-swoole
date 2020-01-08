const path = require('path')

const rules = [
  {
    test: /\.vue$/,
    use: {
      loader: 'vue-loader',
      options: {}
    }
  },
  {
    test: /\.js$/,
    exclude: /(node_modules|bower_components)/,
    use: {
      loader: 'babel-loader',
      options: {
        presets: [
         
        ],
        
      }
    }
  }
]

module.exports = [{
  target: 'node',
  entry: {
    server: './entry-server.js',
  },
  output: {
    libraryTarget: 'commonjs2',
    path: path.join(__dirname, 'build'),
    filename: 'server.compiled.js',
    // publicPath: 'js/',
  },
  resolve: {
    alias: {
      'create-api': './create-api-server.js'
    }
  },
  externals: {
    'vue': 'vue',
    'vue-router': 'vue-router',
    'axios': 'axios',
  },
  module: { rules },
}, {
  target: 'web',
  entry: {
    client: './entry-client.js',
  },
  output: {
    publicPath: 'js/',
    path: path.join(__dirname,"../../",'Public/js'),
    filename: 'client.compiled.js',
    publicPath: 'build/',
  },
  resolve: {
    alias: {
      'create-api': './create-api-client.js'
    }
  },
  externals: {},
  module: { rules },
}]