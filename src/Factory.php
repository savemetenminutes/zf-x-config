<?php

namespace Smtm\ZendExtended\Config;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Zend\Config\Config;
use Zend\Config\Factory as ZendConfigFactory;
use Zend\Config\Reader\ReaderInterface;
use Zend\Stdlib\ArrayUtils;

class Factory extends ZendConfigFactory
{
    /**
     * Registered config data formats.
     * key is data format, value is reader instance or plugin name
     *
     * @var array
     */
    protected static $formats = [
        'ini' => 'ini',
        'json' => 'json',
        'xml' => 'xml',
        'yml' => 'yaml',
        'yaml' => 'yaml',
        'properties' => 'javaproperties',
    ];

    /**
     * Read config from a string.
     *
     * @param  string $dataString
     * @param  string $dataFormat
     * @param  bool $returnConfigObject
     * @return array|Config
     */
    public static function fromString(string $dataString, string $dataFormat, $returnConfigObject = false)
    {
        $reader = static::$formats[$dataFormat];
        if (!$reader instanceof ReaderInterface) {
            $reader = static::getReaderPluginManager()->get($reader);
            static::$formats[$dataFormat] = $reader;
        }

        /** @var ReaderInterface $reader */
        $config = $reader->fromString($dataString);

        return ($returnConfigObject) ? new Config($config) : $config;
    }

    /**
     * Read configuration from multiple config data strings and merge them.
     *
     * @param  array $dataStrings
     * @param  string $dataFormat
     * @param  bool $returnConfigObject
     * @return array|Config
     */
    public static function fromStrings(array $dataStrings, string $dataFormat, $returnConfigObject = false)
    {
        $config = [];

        foreach ($dataStrings as $dataString) {
            $config = ArrayUtils::merge($config, static::fromString($dataString, $dataFormat, false));
        }

        return ($returnConfigObject) ? new Config($config) : $config;
    }

    /**
     * Read config from a directory.
     *
     * @param  string $path
     * @param  bool $returnConfigObject
     * @param  bool $useIncludePath
     * @return array|Config
     */
    public static function fromDirectory(string $path, $returnConfigObject = false, $useIncludePath = false)
    {
        if (!is_dir($path)) {
            return [];
        }

        $config = [];
        $directoryIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::SELF_FIRST);
        foreach ($directoryIterator as $item) {
            if (is_dir($item->getPathName())) {
                continue;
            }

            $config = ArrayUtils::merge($config,
                parent::fromFile($item->getPathName(), $returnConfigObject, $useIncludePath));
        }

        return ($returnConfigObject) ? new Config($config) : $config;
    }

    /**
     * Read configuration from multiple directories and merge them.
     *
     * @param  array $paths
     * @param  bool $returnConfigObject
     * @param  bool $useIncludePath
     * @return array|Config
     */
    public static function fromDirectories(array $paths, $returnConfigObject = false, $useIncludePath = false)
    {
        $config = [];

        foreach ($paths as $path) {
            $config = ArrayUtils::merge($config, static::fromDirectory($path, $returnConfigObject, $useIncludePath));
        }

        return ($returnConfigObject) ? new Config($config) : $config;
    }
}
