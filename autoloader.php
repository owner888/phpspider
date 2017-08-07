<?php
/**
 * This file is part of phpspider.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    seatle<seatle@foxmail.com>
 * @copyright seatle<seatle@foxmail.com>
 * @link      http://www.phpspider.org/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace phpspider;

/**
 * autoloader.
 */
class autoloader
{
    /**
     * Autoload root path.
     *
     * @var string
     */
    protected static $_autoload_root_path = '';

    /**
     * Set autoload root path.
     *
     * @param string $root_path
     * @return void
     */
    public static function set_root_path($root_path)
    {
        self::$_autoload_root_path = $root_path;
    }

    /**
     * Load files by namespace.
     *
     * @param string $name
     * @return boolean
     */
    public static function load_by_namespace($name)
    {
        $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $name);

        if (strpos($name, 'phpspider\\') === 0) 
        {
            $class_file = __DIR__ . substr($class_path, strlen('phpspider')) . '.php';
        }
        else 
        {
            if (self::$_autoload_root_path) 
            {
                $class_file = self::$_autoload_root_path . DIRECTORY_SEPARATOR . $class_path . '.php';
            }
            if (empty($class_file) || !is_file($class_file)) 
            {
                $class_file = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . "$class_path.php";
            }
        }

        if (is_file($class_file)) 
        {
            require_once($class_file);
            if (class_exists($name, false)) 
            {
                return true;
            }
        }
        return false;
    }
}

spl_autoload_register('\phpspider\autoloader::load_by_namespace');
