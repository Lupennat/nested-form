let mix = require('laravel-mix')

require('./nova.mix')

mix
  .setPublicPath('dist')
  .js('resources/js/nested-form.js', 'js')
  .vue({ version: 3 })
  .css('resources/css/nested-form.css', 'css')
  .nova('lupennat/nested-form')
  .version();
