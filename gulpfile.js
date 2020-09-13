var gulp        = require('gulp');
var browserSync = require('browser-sync');
var sass        = require('gulp-sass');
var sourcemaps 	= require('gulp-sourcemaps');
var plumber 	= require('gulp-plumber');
var concat 		= require('gulp-concat');
var rename 		= require('gulp-rename');
var uglify 		= require('gulp-uglify');
var wpPot 		= require('gulp-wp-pot');
var sort 		= require('gulp-sort');
var del 		= require('del');
var zip			= require('gulp-zip');

// Static Server + watching scss/html files
gulp.task('serve', ['sass', 'js'], function() {

    browserSync.init({
		files: "assets/css/*.css",
        proxy: "rpe.eu.ngrok.io",
		logLevel: "info"
    });

    gulp.watch( "assets/scss/**/*.scss" , ['sass']);
    gulp.watch( "assets/js/plugin.js", ['js'] );


});


//sass
gulp.task('sass', function() {
	return gulp.src(['assets/css/wax-checkout.scss'])
		.pipe(plumber())
		.pipe(sourcemaps.init())
		.pipe(sass())
		.pipe(sourcemaps.write('maps/'))
		.pipe(gulp.dest("assets/css/"));
});


//js frontend
gulp.task('js', function(){
	return gulp.src(['assets/js/wax-checkout.js'])
		.pipe(sourcemaps.init())
		.pipe(concat('wax-checkout.min.js'))
		.pipe(gulp.dest('assets/js/'))
		.pipe(uglify().on('error', console.error))
		.pipe(uglify())
		.pipe(sourcemaps.write('maps/'))
		.pipe(gulp.dest('assets/js/'));
});


gulp.task('clean', function () {
	return del([
		'dist/trunk/**/*',
        'dist/tags/InsertReleaseVersionHere/**/*',
	],({force: true}));
});


gulp.task('zip', ['copy'], function () {
	return gulp.src('dist/**')
		.pipe(zip('woocommerce-gateway-wax.zip'))
		.pipe(gulp.dest(''));
});

gulp.task('copy',['clean','sass', 'js'], function () {
	return gulp.src([
		'./**',
		'!node_modules/**',
		'!node_modules',
		'!.gitignore',
		'!composer.**',
		'!gulpfile.js',
		'!dist',
        '!dist/**',
		'!package.json',
        '!**.zip'
	])
		.pipe(gulp.dest('dist/trunk/'))
		.pipe(gulp.dest('dist/tags/InsertReleaseVersionHere'));

});

gulp.task('default', ['serve']);
gulp.task('deploy', ['zip']);

