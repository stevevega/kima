<?php
/**
 * Kima Logger
 * @author Steve Vega
 */
namespace Kima;

/**
 * Logger
 * Logger for Kima
 */
class Logger extends Model
{

    /**
     * Database engine
     */
    const DB_ENGINE = 'mongo';

    /**
     * Logger default
     */
    const TABLE = 'log';

    /**
     * Log levels
     */
    const INFO = 'Information';
    const ERROR = 'Error';
    const WARNING = 'Warning';
    const NOTICE = 'Notice';

    /**
     * Log levels list
     */
    protected static $log_levels = [
        self::INFO,
        self::ERROR,
        self::WARNING,
        self::NOTICE];

    /**
     * Logs new content into the logger
     */
    public static function log($content, $type = null, $level = null)
    {
        $logger = new self();

        if (!empty($type)) {
            $logger->table($type);
        }

        // set the fields to store
        $level = in_array($level, self::$log_levels) ? $level : self::INFO;
        $fields = ['log_level' => $level, 'log_timestamp' => time()];

        // add custom content, objects will be store as fields
        $content = is_object($content) ? get_object_vars($content) : ['content' => $content];
        $fields = array_merge($fields, $content);

        $logger->async(true)->put($fields);
    }

}
