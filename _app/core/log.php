<?php
/**
 * Statamic Log
 * API for logging messages to the site's error log
 *
 * @author  Fred LeBlanc
 */
class Log
{
	const DEBUG = \Slim\Log::DEBUG;
	const INFO = \Slim\Log::INFO;
	const WARN = \Slim\Log::WARN;
	const ERROR = \Slim\Log::ERROR;
	const FATAL = \Slim\Log::FATAL;


	protected static $types = array(
		\Slim\Log::DEBUG => 'DEBUG',
		\Slim\Log::INFO =>  'INFO',
		\Slim\Log::WARN =>  'WARN',
		\Slim\Log::ERROR => 'ERROR',
		\Slim\Log::FATAL => 'FATAL'
		);


	/**
	 * debug
	 * Log a debug message
	 *
	 * @param string  $message  Message to log
	 * @param string  $aspect  Aspect for context
	 * @param string  $instance  Instance for context
	 */
	public static function debug($message, $aspect, $instance=NULL) {
		self::write($message, \Slim\Log::DEBUG, $aspect, $instance);
	}


	/**
	 * info
	 * Log an info message
	 *
	 * @param string  $message  Message to log
	 * @param string  $aspect  Aspect for context
	 * @param string  $instance  Instance for context
	 */
	public static function info($message, $aspect, $instance=NULL) {
		self::write($message, \Slim\Log::INFO, $aspect, $instance);
	}


	/**
	 * warn
	 * Log a warn message
	 *
	 * @param string  $message  Message to log
	 * @param string  $aspect  Aspect for context
	 * @param string  $instance  Instance for context
	 */
	public static function warn($message, $aspect, $instance=NULL) {
		self::write($message, \Slim\Log::WARN, $aspect, $instance);
	}


	/**
	 * error
	 * Log an error message
	 *
	 * @param string  $message  Message to log
	 * @param string  $aspect  Aspect for context
	 * @param string  $instance  Instance for context
	 */
	public static function error($message, $aspect, $instance=NULL) {
		self::write($message, \Slim\Log::ERROR, $aspect, $instance);
	}


	/**
	 * fatal
	 * Log a fatal message
	 *
	 * @param string  $message  Message to log
	 * @param string  $aspect  Aspect for context
	 * @param string  $instance  Instance for context
	 */
	public static function fatal($message, $aspect, $instance=NULL) {
		self::write($message, \Slim\Log::FATAL, $aspect, $instance);
	}


	/**
	  * convert_log_level
	  * Converts a given $log_level string to its corresponding integer value
	  *
	  * @param $log_level  string  Log level to convert
	  * @return int
	  */
	public static function convert_log_level($log_level) {
		switch(strtolower($log_level)) {
			case 'debug':
				return \Slim\Log::DEBUG;
				break;

			case 'info':
				return \Slim\Log::INFO;
				break;

			case 'warn':
				return \Slim\Log::WARN;
				break;

			case 'error':
				return \Slim\Log::ERROR;
				break;

			default:
				return \Slim\Log::FATAL;
				break;
		}
	}


	/**
	 * log_slim_exception
	 * Parses and logs slim exceptions for Statamic's logger
	 *
	 * @param object  $exception  Slim Exception
	 * @return void
	 */
	public static function log_slim_exception($exception) {
		$filename = $exception->getFile();
		$message = ($exception->getLine() && !preg_match("/line [\d]+/i", $exception->getMessage())) ? $exception->getMessage() . " on line " . $exception->getLine() . "." : $exception->getMessage();
		$aspect = 'unknown';
        $instance = 'unknown';

		if (strstr($filename, "/") !== FALSE) {
		    preg_match("#/vendor/([\w\d\-_]+)/#i", $filename, $matches);

		    $path_info  = pathinfo($filename);
		    $instance   = $path_info['filename'];
		    $aspect     = (isset($matches[1])) ? $matches[1] : "core";
		}

		// log this as a fatal message
		self::fatal($message, $aspect, $instance);
	}


	/**
	 * is_logging_enabled
	 * Is logging currently enabled for this site?
	 *
	 * @return boolean
	 */
	protected static function is_logging_enabled() {
		$app = \Slim\Slim::getInstance();
		return (isset($app->config['_log_enabled']) && $app->config['_log_enabled']);
	}


	/**
	 * log
	 * If logging is enabled for this site, logs message to log
	 *
	 * @param string  $message  Message to log
	 * @param int  $level  Level of error to log
	 * @param string  $context  Context of message
	 * @param string  $instance  Specific instance
	 * @return void
	 */
	protected static function write($message, $level, $context, $instance=NULL) {
		// check that logging is enabled
		if (!self::is_logging_enabled()) {
			return;
		}

		$app = \Slim\Slim::getInstance();
		$log = $app->getLog();

		$entry  = self::$types[$level] . "|";
		$entry .= Date::format("r") . "|";
		$entry .= $context . "|";
		$entry .= $instance . "|";
		$entry .= $_SERVER["REQUEST_URI"] . "|";
		$entry .= $_SERVER["REMOTE_ADDR"] . "|";
		$entry .= (isset($app->config['environment']) && $app->config['environment']) ? $app->config['environment'] . "|" : "|";
		$entry .= $message;

		switch($level) {
			case \Slim\Log::DEBUG:
				return $log->debug($entry);
				break;

			case \Slim\Log::INFO:
				return $log->info($entry);
				break;

			case \Slim\Log::WARN:
				return $log->warn($entry);
				break;

			case \Slim\Log::ERROR:
				return $log->error($entry);
				break;

			default:
				return $log->fatal($entry);
				break;
		}
	}
}
?>