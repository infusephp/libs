<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.1
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse;

use Pimple\Container;

class View
{
    private static $container;
    private static $defaultEngine;

    private $template;
    private $data;
    private $engine;

    /**
	 * Sets the default ViewEngine used by views
	 *
	 * @param ViewEngine $engine
	 */
    public static function inject(Container $container)
    {
        self::$container = $container;
        self::$defaultEngine = false;
    }

    /**
	 * Gets the default ViewEngine used by views
	 *
	 * @return ViewEngine
	 */
    public static function defaultEngine()
    {
        if (!self::$defaultEngine) {
            if (self::$container && isset(self::$container['view_engine']))
                self::$defaultEngine = self::$container['view_engine'];
            else
                // default to php view engine
                self::$defaultEngine = new ViewEngine\PHP();
        }

        return self::$defaultEngine;
    }

    /**
	 * Creates a new View
	 *
	 * @param string $template template name
	 * @param array $templateParameters optional parameters to render template with
	 * @param string $engine rendering engine to use
	 */
    public function __construct($template, $templateParameters = [], $engine = null)
    {
        // deal with relative template paths by checking for an optional
        // $viewsDir property on the calling class
        if (substr($template, 0, 1) != '/') {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

            if (isset($backtrace[1])) {
                $class = Utility::array_value($backtrace[1], 'class');
                if (class_exists($class) && property_exists($class, 'viewsDir') && $class::$viewsDir) {
                    $template = $class::$viewsDir . '/' . $template;
                    $templateParameters['viewsDir'] = $class::$viewsDir;
                }
            }
        }

        $this->template = $template;
        $this->data = $templateParameters;

        if ($engine)
            $this->engine = $engine;
    }

    /**
	 * Returns the template this view represents
	 *
	 * @return string
	 */
    public function template()
    {
        return $this->template;
    }

    /**
	 * Updates the template parameters associated with this view
	 *
	 * @param array $parameters template parameters
	 *
	 * @return View
	 */
    public function setParameters(array $parameters)
    {
        $this->data = array_replace($this->data, $parameters);

        return $this;
    }

    /**
	 * Gets the template parameters associated with this view
	 *
	 * @return array
	 */
    public function getParameters()
    {
        return $this->data;
    }

    /**
	 * Sets the ViewEngine associated with this view
	 *
	 * @param ViewEngine $engine
	 *
	 * @return View
	 */
    public function setEngine(ViewEngine $engine)
    {
        $this->engine = $engine;

        return $this;
    }

    /**
	 * Gets the ViewEngine associated with this view
	 *
	 * @return ViewEngine
	 */
    public function getEngine()
    {
        if (!$this->engine) {
            $this->engine = static::defaultEngine();
        }

        return $this->engine;
    }

    /**
	 * Renders this view using the associated ViewEngine
	 *
	 * @return string compiled view
	 */
    public function render()
    {
        return $this->getEngine()->renderView($this);
    }
}
