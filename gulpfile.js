const gulp = require('gulp');
const postcss = require('gulp-postcss');
const terser = require('gulp-terser');
const rename = require('gulp-rename');
const sourcemaps = require('gulp-sourcemaps');
const concat = require('gulp-concat');
const browserSync = require('browser-sync').create();
const tailwindcss = require('tailwindcss');
const autoprefixer = require('autoprefixer');
const cssnano = require('cssnano');

const paths = {
  css: {
    src: 'src/css/*.css',
    dest: 'assets/css/',
  },
  js: {
    src: 'src/js/*.js',
    dest: 'assets/js/',
  },
  php: {
    src: ['./*.php', './template-parts/**/*.php'],
  },
};

const browserSyncConfig = {
  proxy: {
    target: 'http://jxmc.local',
    proxyReq: [
      function (proxyReq) {
        proxyReq.setHeader('Accept-Encoding', 'identity');
      },
    ],
  },
  files: [
    'assets/css/**/*.css',
    'assets/js/**/*.js',
    './*.php',
    './template-parts/**/*.php',
  ],
  open: false,
  notify: false,
  ghostMode: false,
};

function compileCSS() {
  return gulp
    .src(paths.css.src)
    .pipe(sourcemaps.init())
    .pipe(
      postcss([
        tailwindcss(),
        autoprefixer(),
        cssnano(),
      ])
    )
    .pipe(rename('main.min.css'))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(paths.css.dest))
    .pipe(browserSync.stream());
}

function compileJS() {
  return gulp
    .src(paths.js.src)
    .pipe(sourcemaps.init())
    .pipe(concat('main.min.js'))
    .pipe(terser())
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(paths.js.dest))
    .pipe(browserSync.stream());
}

function watchFiles() {
  browserSync.init(browserSyncConfig);

  gulp.watch('src/css/**/*.css', compileCSS);
  gulp.watch('src/js/**/*.js', gulp.parallel(compileJS, compileCSS));
  gulp.watch(paths.php.src, gulp.series(compileCSS, function reload(done) {
    browserSync.reload();
    done();
  }));
}

const build = gulp.parallel(compileCSS, compileJS);
const dev = gulp.series(build, watchFiles);

exports.css = compileCSS;
exports.js = compileJS;
exports.watch = watchFiles;
exports.build = build;
exports.default = dev;
