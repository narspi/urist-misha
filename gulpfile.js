import gulp from "gulp";
import panini from "panini";
import browserSync from "browser-sync";
import sourcemaps from "gulp-sourcemaps";
import * as dartSass from "sass";
import gulpSass from "gulp-sass";
import gulpif from "gulp-if";
import postcss from "gulp-postcss";
import gulpEsbuild from "gulp-esbuild";
import plumber from "gulp-plumber";
import autoprefixer from "autoprefixer";
import cssnamo from "cssnano";

const isDevelopment = process.env.MODE === "development";
const isTunnel = process.env.TUNNEL === "run";

const scss = gulpSass(dartSass);

const { src, dest, watch, series } = gulp;
const sync = browserSync.create();

const htmlInclude = () => {
  panini.refresh();
  return src("src/html/pages/**/*.html")
    .pipe(plumber())
    .pipe(
      panini({
        root: "src/html/pages",
        layouts: "src/html/layouts",
        partials: "src/html/partials",
        helpers: "src/html/helpers/",
        data: "src/html/data/",
      })
    )
    .pipe(dest("dist/"))
    .pipe(sync.stream());
};

const createCss = () => {
  return src("src/scss/**/*.scss")
    .pipe(gulpif(isDevelopment, sourcemaps.init()))
    .pipe(
      scss({
        outputStyle: `${isDevelopment ? "expanded" : "compressed"}`,
      })
    )
    .pipe(postcss(
      [
        autoprefixer(),
        cssnamo({
            preset: 'default',
        })
      ]
    ))
    .pipe(gulpif(isDevelopment, sourcemaps.write()))
    .pipe(dest("dist/css"))
    .pipe(sync.stream());
};

const createCssTheme = () => {
  return src("src/scss/**/*.scss")
    .pipe(
      scss({
        outputStyle: "compressed",
      })
    )
    .pipe(postcss(
      [
        autoprefixer(),
        cssnamo({
            preset: 'default',
        })
      ]
    ))
    .pipe(dest("src/theme/assets/css"));
};

const createJs = () => {
  return gulp
    .src("./src/js/**/*.js")
    .pipe(plumber())
    .pipe(
      gulpEsbuild({
        bundle: true, // Собираем все импорты в один файл
        minify: !isDevelopment, // Минификация только в production
        sourcemap: isDevelopment, // Генерация sourcemaps в dev-режиме
        target: "es6", // Целевая версия JS
      })
    )
    .pipe(dest("dist/js"))
    .pipe(sync.stream());
};

const createJsTheme = () => {
  return gulp
    .src("./src/js/**/*.js")
    .pipe(
      gulpEsbuild({
        bundle: true,
        minify: true,
        sourcemap: false,
        target: "es6",
      })
    )
    .pipe(dest("src/theme/assets/js"));
};

const transportFonts = () => {
  return src("./src/fonts/**/*.{woff,woff2}", { encoding: false }).pipe(
    dest("dist/fonts")
  );
};

const transportImg = () => {
  return src("./src/img/**/*.*", { encoding: false }).pipe(dest("dist/img"));
};

const transportFiles = () => {
  return src("./src/assets/**/*.*", { encoding: false }).pipe(
    dest("dist/assets")
  );
};

const server = () => {
  sync.init({
    server: "./dist",
    tunnel: isTunnel ? true : null,
  });
  watch("./src/fonts/**/*.*", transportFonts);
  watch("./src/img/**/*.*", transportImg);
  watch("./src/html/**/*.html", htmlInclude);
  watch("./src/scss/**/*.scss", createCss);
  watch("./src/js/**/*.js", createJs);
  watch("./src/assets/**/*.*", transportFiles);
};

const buildServer = () => {
  sync.init({
    server: "./dist",
    tunnel: true,
  });
};

const defaultTask = series(
  htmlInclude,
  createCss,
  createJs,
  transportFonts,
  transportImg,
  transportFiles,
  server
);

const buildTask = series(
  htmlInclude,
  createCss,
  createJs,
  transportFonts,
  transportFiles,
  transportImg
);

const themeTask = series(createCssTheme, createJsTheme);

const startServer = series(buildTask, buildServer);

export {
  defaultTask as default,
  buildTask as build,
  startServer as start,
  themeTask as theme,
};