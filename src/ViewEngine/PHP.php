<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace infuse\ViewEngine;

use infuse\ViewEngine;
use infuse\View;

class PHP extends ViewEngine
{
    private $viewsDir = 'views';

    const EXTENSION = '.php';

    /**
     * Creates a new PHP ViewEngine
     *
     * @param string $viewsDir
     */
    public function __construct($viewsDir = false)
    {
        if ($viewsDir) {
            $this->viewsDir = $viewsDir;
        }
    }

    public function renderView(View $view)
    {
        // determine the full template name
        $template = $view->template();

        // add extension if left off
        $len = strlen(self::EXTENSION);
        if (substr($template, -$len, $len) != self::EXTENSION) {
            $template .= self::EXTENSION;
        }

        // assign global and view parameters
        $parameters = array_replace($this->getGlobalParameters(), $view->getParameters());
        extract($parameters);

        // render the template with output buffering
        ob_start();
        include $this->viewsDir.'/'.$template;
        $renderedString = ob_get_contents();
        ob_end_clean();

        return $renderedString;
    }
}
