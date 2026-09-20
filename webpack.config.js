const path = require('path');
const fs = require('fs');
const { copyFileSync, mkdirSync, readFileSync, writeFileSync } = require('fs');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

class CopyUnprocessedAssetPlugin {
  constructor(patterns) {
    this.patterns = patterns;
  }

  apply(compiler) {
    compiler.hooks.afterEmit.tap('CopyUnprocessedAssetPlugin', () => {
      this.patterns.forEach(({ from, to }) => {
        const src = path.resolve(__dirname, from);
        const dest = path.resolve(__dirname, to);

        mkdirSync(path.dirname(dest), { recursive: true });

        if (path.basename(dest) === 'bs-country-data.min.css') {
          const css = readFileSync(src, 'utf8').replace(/url\(\s*['"]?\.\.\/images\//g, 'url(../../images/');
          writeFileSync(dest, css);
          return;
        }

        copyFileSync(src, dest);
      });
    });
  }
}

const entries = {
  bootstrap: [
    './src/Assets/js/bootstrap.js',
    './src/Assets/scss/bootstrap.scss',
  ],
  'admin.ui': [
    './src/Assets/js/admin.ui.js',
    './src/Assets/js/admin.dashboard.js',
    './src/Assets/js/admin.page.js',
    './src/Assets/js/admin.plugins.js',
    './src/Assets/js/admin.settings.js',
    './src/Assets/js/admin.tools.js',
    './src/Assets/js/admin.mods.js',
    './src/Assets/scss/admin.ui.scss',
  ],
  'wpoverride': './src/Assets/scss/wpoverride.scss'
};

const fontAwesomeEntries = {
  'icon-picker': [
    './src/Includes/Plugins/FontAwesome/Assets/js/icon-picker.js',
    './src/Includes/Plugins/FontAwesome/Assets/scss/icon-picker.scss',
  ],
};
const tinyMCEEntries = {
  'tinyMCE': [
    './src/Includes/Plugins/TinyMCE/Assets/js/tinymce.js',
    './src/Includes/Plugins/TinyMCE/Assets/scss/tinymce.scss',
  ],
};

const jsDirectory = path.resolve(__dirname, 'src/Assets/js');
fs.readdirSync(jsDirectory)
  .filter((file) => /^admin\.[^.]+\.js$/.test(file) && file !== 'admin.ui.js')
  .forEach((file) => {
    const page = file.match(/^admin\.([^.]+)\.js$/)[1];
    const entry = [`./src/Assets/js/${file}`];
    entries[`admin.${page}`] = entry;
  });

const shared = {
  mode: process.env.NODE_ENV === 'development' ? 'development' : 'production',
  devtool: process.env.NODE_ENV === 'development' ? 'source-map' : false,
  module: {
    rules: [
      {
        test: /\.scss$/,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader',
          {
            loader: 'sass-loader',
            options: {
              api: 'modern',
              sassOptions: {
                quietDeps: true,
                includePaths: [path.resolve(__dirname, 'src/Assets/scss')],
              },
            },
          },
        ],
      },
      {
        test: /\.css$/,
        use: [MiniCssExtractPlugin.loader, 'css-loader'],
      },
      {
        test: /\.js$/,
        exclude: /node_modules/,
        type: 'javascript/auto',
      },
    ],
  },
  optimization: { splitChunks: false },
};

module.exports = [
  {
    ...shared,
    entry: entries,
    output: {
      path: path.resolve(__dirname, 'src/Assets/dist'),
      filename: 'js/[name].js',
      clean: true,
    },
    plugins: [
      new MiniCssExtractPlugin({ filename: 'css/[name].css' }),
      new CopyUnprocessedAssetPlugin([
        {
          from: 'node_modules/@crestapps/bootstrap-select/dist/css/bootstrap-select.min.css',
          to: 'src/Assets/dist/css/bootstrap-select.min.css',
        },
        {
          from: 'node_modules/@crestapps/bootstrap-select/dist/js/bootstrap-select.min.js',
          to: 'src/Assets/dist/js/bootstrap-select.min.js',
        },
        {
          from: 'node_modules/@trilbdev/bootstrap-select-country-data/dist/js/bs-country-data.min.js',
          to: 'src/Assets/dist/js/bs-country-data.min.js',
        },
        {
          from: 'node_modules/@trilbdev/bootstrap-select-country-data/dist/css/bs-country-data.min.css',
          to: 'src/Assets/dist/css/bs-country-data.min.css',
        }
      ]),
    ],
  },
  {
    ...shared,
    entry: entries,
    output: {
      path: path.resolve(__dirname, 'src/Assets/dist'),
      filename: 'js/[name].js',
      clean: true,
    },
    plugins: [
      new MiniCssExtractPlugin({ filename: 'css/[name].css' }),
    ],
  },
  {
    ...shared,
    entry: fontAwesomeEntries,
    output: {
      path: path.resolve(__dirname, 'src/Includes/Plugins/FontAwesome/Assets/dist'),
      filename: 'js/[name].js',
      clean: true,
    },
    plugins: [
      new MiniCssExtractPlugin({ filename: 'css/[name].css' }),
    ],
  },
  {
    ...shared,
    entry: tinyMCEEntries,
    output: {
      path: path.resolve(__dirname, 'src/Includes/Plugins/TinyMCE/Assets/dist'),
      filename: 'js/[name].js',
      clean: true,
    },
    plugins: [
      new MiniCssExtractPlugin({ filename: 'css/[name].css' }),
    ],
  }
];
