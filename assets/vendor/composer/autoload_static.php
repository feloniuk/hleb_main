<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit6af338bcc0f930f12a5428c6c57c18fb
{
    public static $prefixLengthsPsr4 = array (
        'Y' => 
        array (
            'YourNamespace\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'YourNamespace\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'FPDF' => __DIR__ . '/..' . '/setasign/fpdf/fpdf.php',
        'TTFontFile' => __DIR__ . '/..' . '/setasign/tfpdf/font/unifont/ttfonts.php',
        'tFPDF' => __DIR__ . '/..' . '/setasign/tfpdf/tfpdf.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit6af338bcc0f930f12a5428c6c57c18fb::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit6af338bcc0f930f12a5428c6c57c18fb::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit6af338bcc0f930f12a5428c6c57c18fb::$classMap;

        }, null, ClassLoader::class);
    }
}
