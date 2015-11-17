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
use Twig_Environment;
use Twig_Loader_Filesystem;

class Twig extends ViewEngine
{
    const EXTENSION = '.html';

    /**
     * @var string
     */
    private $viewsDir = 'views';

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @var array
     */
    private $twigConfig;

    /**
     * Creates a new Mustache ViewEngine.
     *
     * @param string $viewsDir optional dir containing templates
     */
    public function __construct($viewsDir = '', array $twigConfig = [])
    {
        if ($viewsDir) {
            $this->viewsDir = $viewsDir;
        }

        $this->twigConfig = $twigConfig;
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
        // determine the full template name
        $template = $view->template();

        // add extension if left off
        $len = strlen(self::EXTENSION);
        if (substr($template, -$len, $len) != self::EXTENSION) {
            $template .= self::EXTENSION;
        }

        // assign global and view parameters
        $parameters = array_replace($this->getGlobalParameters(), $view->getParameters());

        // render it
        return $this->twig()->render($template, $parameters);
    }

    /**
     * Gets (and creates) a new Twig instance.
     *
     * @return Twig_Environment
     */
    public function twig()
    {
        if (!$this->twig) {
            $loader = new Twig_Loader_Filesystem($this->viewsDir);
            $this->twig = new Twig_Environment($loader, $this->twigConfig);
        }

        return $this->twig;
    }
}
