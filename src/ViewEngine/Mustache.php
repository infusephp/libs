<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace Infuse\ViewEngine;

use Infuse\ViewEngine;
use Infuse\View;
use Mustache_Engine;

class Mustache extends ViewEngine
{
    const EXTENSION = '.mustache';

    /**
     * @var string
     */
    private $viewsDir = 'views';

    /**
     * @var \Mustache_Engine
     */
    private $mustache;

    /**
     * Creates a new Mustache ViewEngine.
     *
     * @param string $viewsDir optional dir containing templates
     */
    public function __construct($viewsDir = '')
    {
        if ($viewsDir) {
            $this->viewsDir = $viewsDir;
        }
    }

    /**
     * Gets the views directory.
     *
     * @return string
     */
    public function getViewsDir()
    {
        return $this->viewsDir;
    }

    public function renderView(View $view)
    {
        $mustache = $this->mustache();

        // determine the full template name
        $template = $view->template();

        // add extension if left off
        $len = strlen(self::EXTENSION);
        if (substr($template, -$len, $len) != self::EXTENSION) {
            $template .= self::EXTENSION;
        }

        // compute full template path
        $fullPath = $this->viewsDir.'/'.$template;

        // assign global and view parameters
        $parameters = array_replace($this->getGlobalParameters(), $view->getParameters());

        // now let mustache do its thing
        return $mustache->render(file_get_contents($fullPath), $parameters);
    }

    /**
     * Gets (and creates) a Mustache instance.
     *
     * @return \Mustache_Engine
     */
    public function mustache()
    {
        if (!$this->mustache) {
            $this->mustache = new Mustache_Engine();
        }

        return $this->mustache;
    }
}
