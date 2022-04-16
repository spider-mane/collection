const mix = require('laravel-mix');

/**
 * Basic settings
 */
mix
  .setResourceRoot('src')
  .setPublicPath('dist')
  .sourceMaps(true, 'eval-source-map', 'source-map')
  .version();

/**
 * Browsersync
 */
mix.browserSync({proxy: ':package_name.test'});

/**
 * Javascript
 */
mix
  .js('src/js/index.js', 'dist/js/:package_name.js')
  // .autoload({jquery: ['$', 'window.jQuery']})
  .extract();

/**
 * Sass
 */
mix
  .sass('src/scss/main.scss', 'dist/css/:package_name.css', {
    sassOptions: {
      outputStyle: 'expanded',
    },
  })
  .options({
    processCssUrls: false,
  });
