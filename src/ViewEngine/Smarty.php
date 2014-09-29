<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.2
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse\ViewEngine;

use infuse\ViewEngine;
use infuse\View;
use Smarty as SmartyClass;

class Smarty extends ViewEngine
{
    private $viewsDir = 'views';
    private $compileDir = 'temp/smarty';
    private $cacheDir = 'temp/smarty/cache';
    private $smarty;

    const EXTENSION = '.tpl';

    /**
	 * Creates a new Smarty ViewEngine
	 *
	 * @param string $viewsDir optional dir containing templates
	 * @param string $compileDir optional dir to save compiled templates
	 * @param string $cacheDir optional dir to save cached templates
	 */
    public function __construct($viewsDir = false, $compileDir = false, $cacheDir = false)
    {
        if ($viewsDir)
            $this->viewsDir = $viewsDir;

        if ($compileDir)
            $this->compileDir = $compileDir;

        if ($cacheDir)
            $this->cacheDir = $cacheDir;
    }

    public function renderView(View $view)
    {
        $smarty = $this->smarty();

        // determine the full template name
        $template = $view->template();

        // add extension if left off
        $len = strlen(self::EXTENSION);
        if(substr($template, -$len, $len) != self::EXTENSION)
            $template .= self::EXTENSION;

        // assign global and view parameters
        $parameters = array_replace($this->getGlobalParameters(), $view->getParameters());
        foreach ($parameters as $key => $value)
            $smarty->assign($key, $value);

        // now let smarty do its thing
        return $smarty->fetch($template);
    }

    /**
	 * Gets (and creates) a Smarty instance
	 *
	 * @return Smarty
	 */
    public function smarty()
    {
        if (!$this->smarty) {
            $this->smarty = new SmartyClass();

            $this->smarty->muteExpectedErrors();
            $this->smarty->setTemplateDir($this->viewsDir)
                         ->setCompileDir($this->compileDir)
                         ->setCacheDir($this->cacheDir);
        }

        return $this->smarty;
    }
}
