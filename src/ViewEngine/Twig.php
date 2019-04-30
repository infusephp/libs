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
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Twig extends ViewEngine
{
    const EXTENSION = '.twig';

    /**
     * @var string
     */
    private $viewsDir = 'views';

    /**
     * @var Environment
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
     * @return Environment
     */
    public function twig()
    {
        if (!$this->twig) {
            $loader = new FilesystemLoader($this->viewsDir);
            $this->twig = new Environment($loader, $this->twigConfig);
        }

        return $this->twig;
    }
}
