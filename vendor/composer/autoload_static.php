<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit6c2b451d2f3248bd2d1e74ff045f8e29
{
    public static $prefixLengthsPsr4 = array (
        'H' => 
        array (
            'HelloWP\\HWLoyalty\\App\\Modules\\' => 30,
            'HelloWP\\HWLoyalty\\App\\Helper\\' => 29,
            'HelloWP\\HWLoyalty\\App\\Functions\\' => 32,
            'HelloWP\\HWLoyalty\\App\\Admin\\' => 28,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'HelloWP\\HWLoyalty\\App\\Modules\\' => 
        array (
            0 => __DIR__ . '/../..' . '/app/modules',
        ),
        'HelloWP\\HWLoyalty\\App\\Helper\\' => 
        array (
            0 => __DIR__ . '/../..' . '/app/helper',
        ),
        'HelloWP\\HWLoyalty\\App\\Functions\\' => 
        array (
            0 => __DIR__ . '/../..' . '/app/functions',
        ),
        'HelloWP\\HWLoyalty\\App\\Admin\\' => 
        array (
            0 => __DIR__ . '/../..' . '/app/admin',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit6c2b451d2f3248bd2d1e74ff045f8e29::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit6c2b451d2f3248bd2d1e74ff045f8e29::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit6c2b451d2f3248bd2d1e74ff045f8e29::$classMap;

        }, null, ClassLoader::class);
    }
}