var gulp            = require('gulp'),
    sass            = require('gulp-sass'),
    autoprefixer    = require('gulp-autoprefixer'),
    rename          = require("gulp-rename"),
    cssnano         = require('gulp-cssnano');

gulp.task('default', function() {
    // place code for your default task here
});

gulp.task('sass', function () {
    return gulp.src("src/**/*.sass")
        .pipe(sass())
        .pipe(autoprefixer({
            browsers: ['last 2 versions'],
            cascade: false
        }))
        .pipe(rename({prefix: '../css/'}))
        .pipe(gulp.dest("src"))
        .pipe(cssnano())
        .pipe(rename({suffix: '.min'}))
        .pipe(gulp.dest("src"));
});

gulp.task('watch', function () {
    gulp.watch('src/**/*.sass', ['sass']);
});