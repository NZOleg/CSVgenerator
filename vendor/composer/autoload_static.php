<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitcd59be0145148eeb113ac2b47889b94e
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'MathParser\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'MathParser\\' => 
        array (
            0 => __DIR__ . '/..' . '/mossadal/math-parser/src/MathParser',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitcd59be0145148eeb113ac2b47889b94e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitcd59be0145148eeb113ac2b47889b94e::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
