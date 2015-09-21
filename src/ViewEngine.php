<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace infuse;

abstract class ViewEngine
{
    /**
     * @var string
     */
    private $assetMapFile = 'assets/static.assets.json';

    /**
     * @var array
     */
    private $assetMap;

    /**
     * @var string
     */
    private $assetsBaseUrl;

    /**
     * @var array
     */
    private $templateParameters = [];

    ///////////////////////
    // ASSET URLS
    ///////////////////////

    /**
     * Sets the file where static assets can be located.
     *
     * @param string $filename
     *
     * @return ViewEngine
     */
    public function setAssetMapFile($filename)
    {
        $this->assetMapFile = $filename;
        $this->assetMap = false;

        return $this;
    }

    /**
     * Sets the base URL to be prepended to static assets.
     *
     * @param string $url
     *
     * @return ViewEngine
     */
    public function setAssetBaseUrl($url)
    {
        $this->assetsBaseUrl = $url;

        return $this;
    }

    /**
     * Attempts to look up the versioned URL for a given asset in the asset map if available.
     * If not found in the asset map, the original URL will be returned.
     * i.e. asset_url( '/img/logo.png' ) -> http://cdn.example.com/img/logo.2d82lf9sd8f.png.
     *
     * @param string $path path portion of URL (everything after host name beginning with /)
     *
     * @return string
     */
    public function asset_url($path)
    {
        // load asset version numbers (if they exist)
        if (!$this->assetMap) {
            if (file_exists($this->assetMapFile)) {
                $this->assetMap = json_decode(file_get_contents($this->assetMapFile), true);
            } else {
                $this->assetMap = [];
            }
        }

        $path = (isset($this->assetMap[$path])) ? $this->assetMap[$path] : $path;

        return $this->assetsBaseUrl.$path;
    }

    ///////////////////////
    // TEMPLATING
    ///////////////////////

    /**
     * Updates the global template parameters for views rendered with this engine.
     *
     * @param array $parameters
     *
     * @return ViewEngine
     */
    public function setGlobalParameters(array $parameters)
    {
        $this->templateParameters = array_replace($this->templateParameters, $parameters);

        return $this;
    }

    /**
     * Gets the global template parameters.
     *
     * @return array
     */
    public function getGlobalParameters()
    {
        return $this->templateParameters;
    }

    /**
     * Renders a view into a string.
     *
     * @return string
     */
    abstract public function renderView(View $view);
}
