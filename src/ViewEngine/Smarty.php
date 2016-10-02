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
use Smarty as SmartyClass;

class Smarty extends ViewEngine
{
    const EXTENSION = '.tpl';

    /**
     * @var string
     */
    private $viewsDir = 'views';

    /**
     * @var string
     */
    private $compileDir = 'temp/smarty';

    /**
     * @var string
     */
    private $cacheDir = 'temp/smarty/cache';

    /**
     * @var \Smarty
     */
    private $smarty;

    /**
     * Creates a new Smarty ViewEngine.
     *
     * @param string $viewsDir   optional dir containing templates
     * @param string $compileDir optional dir to save compiled templates
     * @param string $cacheDir   optional dir to save cached templates
     */
    public function __construct($viewsDir = '', $compileDir = '', $cacheDir = '')
    {
        if ($viewsDir) {
            $this->viewsDir = $viewsDir;
        }

        if ($compileDir) {
            $this->compileDir = $compileDir;
        }

        if ($cacheDir) {
            $this->cacheDir = $cacheDir;
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

    /**
     * Gets the compile directory.
     *
     * @return string
     */
    public function getCompileDir()
    {
        return $this->compileDir;
    }

    /**
     * Gets the cache directory.
     *
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * Gets the Smarty instance.
     *
     * @return \Smarty
     */
    public function getSmarty()
    {
        if (!$this->smarty) {
            $this->smarty = new SmartyClass();

            $this->smarty->muteExpectedErrors();
            $this->smarty->setTemplateDir($this->viewsDir)
                         ->setCompileDir($this->compileDir)
                         ->setCacheDir($this->cacheDir)
        // this escapes all template variables by wrapping them with:
        // htmlspecialchars({$output}, ENT_QUOTES, SMARTY_RESOURCE_CHAR_SET)
        // see: http://www.smarty.net/docs/en/variable.escape.html.tpl
                         ->setEscapeHtml(true);
        }

        return $this->smarty;
    }

    public function renderView(View $view)
    {
        $smarty = $this->getSmarty();

        // determine the full template name
        $template = $view->template();

        // add extension if left off
        $len = strlen(self::EXTENSION);
        if (substr($template, -$len, $len) != self::EXTENSION) {
            $template .= self::EXTENSION;
        }

        // assign global and view parameters
        $parameters = array_replace($this->getGlobalParameters(), $view->getParameters());
        foreach ($parameters as $key => $value) {
            $smarty->assign($key, $value);
        }

        // now let smarty do its thing
        return $smarty->fetch($template);
    }
}
