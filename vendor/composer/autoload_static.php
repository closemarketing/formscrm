<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit961da3adf9f092850ec93683956927ff
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Smoolabs\\WPU\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Smoolabs\\WPU\\' => 
        array (
            0 => __DIR__ . '/..' . '/smoolabs/wordpress-plugin-updater/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit961da3adf9f092850ec93683956927ff::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit961da3adf9f092850ec93683956927ff::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
