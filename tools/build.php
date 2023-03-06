<?php

function run( $command ) {
	echo $command, "\n";

	passthru( $command );
}

$package = json_decode( file_get_contents( 'package.json' ) );

$slug = $package->config->slug;

run( 'rm -rf ./build/' );

run( 'mkdir ./build/' );

run( 'mkdir ./build/plugin/' );

run( 'rsync --recursive --delete --exclude-from=.pronamic-build-ignore ./ ./build/plugin/' );

run( 'composer install --no-dev --prefer-dist --optimize-autoloader --working-dir=./build/plugin/ --ansi' );

run( "vendor/bin/wp dist-archive ./build/plugin/ --plugin-dirname=$slug" );
