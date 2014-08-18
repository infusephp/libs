<?php

/*
	This class was modified from: http://jarrodoberto.com/articles/2011/09/image-resizing-made-easy-with-php
*/

   # ========================================================================#
   #
   #  Author:    Jarrod Oberto
   #  Version:	 1.0
   #  Date:      17-Jan-10
   #  Purpose:   Resizes and saves image
   #  Requires : Requires PHP5, GD library.
   #  Usage Example:
   #                     $resizeObj = new Resize('images/cars/large/input.jpg');
   #                     $resizeObj -> resizeImage(150, 100, 0);
   #                     $resizeObj -> saveImage('images/cars/large/output.jpg', 100);
   #
   # ========================================================================#

namespace infuse;

class Resize
{
	// *** Class variables
	private $image;
    private $width;
    private $height;
	private $imageResized;
	private $type;

	function __construct($fileName)
	{
		// *** Open up the file
		$this->image = $this->openImage($fileName);

	    // *** Get width and height
	    if( $this->image )
	    {
		    $this->width  = imagesx($this->image);
		    $this->height = imagesy($this->image);
		}
	}
	
	public function imageIsValid()
	{
		return $this->image !== false;
	}

	## --------------------------------------------------------

	private function openImage($file)
	{
		// determine image type
		$this->type = exif_imagetype( $file );

		try
		{
			switch( $this->type )
			{
			case IMAGETYPE_JPEG:
				return @imagecreatefromjpeg( $file );
			case IMAGETYPE_PNG:
				return @imagecreatefrompng( $file );
			case IMAGETYPE_GIF:
				return @imagecreatefromgif( $file );
			default:
				return false;
			}
		}
		catch( \Exception $e )
		{
			return false;
		}

		return $img;
	}

	## --------------------------------------------------------

	public function resizeImage($newWidth, $newHeight, $option="auto")
	{
		// *** Get optimal width and height - based on $option
		$optionArray = $this->getDimensions($newWidth, $newHeight, $option);

		$optimalWidth  = $optionArray['optimalWidth'];
		$optimalHeight = $optionArray['optimalHeight'];


		// *** Resample - create image canvas of x, y size
		$this->imageResized = imagecreatetruecolor($optimalWidth, $optimalHeight);
		
		// transparency
		$this->addTransparency( $optimalWidth, $optimalHeight );
		
		imagecopyresampled($this->imageResized, $this->image, 0, 0, 0, 0, $optimalWidth, $optimalHeight, $this->width, $this->height);


		// *** if option is 'crop', then crop too
		if ($option == 'crop') {
			$this->crop($optimalWidth, $optimalHeight, $newWidth, $newHeight);
		}
	}

	## --------------------------------------------------------
	
	private function getDimensions($newWidth, $newHeight, $option)
	{

	   switch ($option)
		{
			case 'exact':
				$optimalWidth = $newWidth;
				$optimalHeight= $newHeight;
				break;
			case 'portrait':
				$optimalWidth = $this->getSizeByFixedHeight($newHeight);
				$optimalHeight= $newHeight;
				break;
			case 'landscape':
				$optimalWidth = $newWidth;
				$optimalHeight= $this->getSizeByFixedWidth($newWidth);
				break;
			case 'auto':
				$optionArray = $this->getSizeByAuto($newWidth, $newHeight);
				$optimalWidth = $optionArray['optimalWidth'];
				$optimalHeight = $optionArray['optimalHeight'];
				break;
			case 'crop':
				$optionArray = $this->getOptimalCrop($newWidth, $newHeight);
				$optimalWidth = $optionArray['optimalWidth'];
				$optimalHeight = $optionArray['optimalHeight'];
				break;
		}
		return ['optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight];
	}

	## --------------------------------------------------------

	private function getSizeByFixedHeight($newHeight)
	{
		$ratio = $this->width / $this->height;
		$newWidth = $newHeight * $ratio;
		return $newWidth;
	}

	private function getSizeByFixedWidth($newWidth)
	{
		$ratio = $this->height / $this->width;
		$newHeight = $newWidth * $ratio;
		return $newHeight;
	}

	private function getSizeByAuto($newWidth, $newHeight)
	{
		if ($this->height < $this->width)
		// *** Image to be resized is wider (landscape)
		{
			$optimalWidth = $newWidth;
			$optimalHeight= $this->getSizeByFixedWidth($newWidth);
		}
		elseif ($this->height > $this->width)
		// *** Image to be resized is taller (portrait)
		{
			$optimalWidth = $this->getSizeByFixedHeight($newHeight);
			$optimalHeight= $newHeight;
		}
		else
		// *** Image to be resizerd is a square
		{
			if ($newHeight < $newWidth) {
				$optimalWidth = $newWidth;
				$optimalHeight= $this->getSizeByFixedWidth($newWidth);
			} else if ($newHeight > $newWidth) {
				$optimalWidth = $this->getSizeByFixedHeight($newHeight);
				$optimalHeight= $newHeight;
			} else {
				// *** Sqaure being resized to a square
				$optimalWidth = $newWidth;
				$optimalHeight= $newHeight;
			}
		}

		return ['optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight];
	}

	## --------------------------------------------------------

	private function getOptimalCrop($newWidth, $newHeight)
	{

		$heightRatio = $this->height / $newHeight;
		$widthRatio  = $this->width /  $newWidth;

		if ($heightRatio < $widthRatio) {
			$optimalRatio = $heightRatio;
		} else {
			$optimalRatio = $widthRatio;
		}

		$optimalHeight = $this->height / $optimalRatio;
		$optimalWidth  = $this->width  / $optimalRatio;

		return ['optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight];
	}

	## --------------------------------------------------------

	private function crop($optimalWidth, $optimalHeight, $newWidth, $newHeight)
	{
		// *** Find center - this will be used for the crop
		$cropStartX = ( $optimalWidth / 2) - ( $newWidth /2 );
		$cropStartY = ( $optimalHeight/ 2) - ( $newHeight/2 );

		$crop = $this->imageResized;
		//imagedestroy($this->imageResized);
		
		// *** Now crop from center to exact requested size
		$this->imageResized = imagecreatetruecolor($newWidth , $newHeight);

		// transparency
		$this->addTransparency( $newWidth, $newHeight );

		imagecopyresampled($this->imageResized, $crop , 0, 0, $cropStartX, $cropStartY, $newWidth, $newHeight , $newWidth, $newHeight);
	}

	## --------------------------------------------------------

	public function saveImage( $savePath = '', $imageQuality = 100 )
	{
		if( empty( $savePath ) )
			$savePath = $this->image;
	
		if( !$this->imageResized )
			$this->imageResized = $this->image;

		switch( $this->type )
		{
			case IMAGETYPE_GIF:
				if (imagetypes() & IMG_GIF) {
					imagegif($this->imageResized, $savePath);
					break;
				}

			case IMAGETYPE_PNG:
				// *** Scale quality from 0-100 to 0-9
				$scaleQuality = round(($imageQuality/100) * 9);

				// *** Invert quality setting as 0 is best, not 9
				$invertScaleQuality = 9 - $scaleQuality;

				if (imagetypes() & IMG_PNG) {
					 imagepng($this->imageResized, $savePath, $invertScaleQuality);
					 break;
				}

			default:
			case IMAGETYPE_JPEG:
				if (imagetypes() & IMG_JPG) {
					imagejpeg($this->imageResized, $savePath, $imageQuality);
					break;
				}
		}

		imagedestroy($this->imageResized);
	}


	## --------------------------------------------------------
	
	private function addTransparency( $nWidth, $nHeight )
	{
		/* Check if this image is PNG or GIF, then set if Transparent*/  
		if( in_array( $this->type, [ IMAGETYPE_GIF, IMAGETYPE_PNG ] ) )
		{
			imagealphablending( $this->imageResized, false );
			imagesavealpha( $this->imageResized, true );
			$transparent = imagecolorallocatealpha( $this->imageResized, 255, 255, 255, 127 );
			imagefilledrectangle( $this->imageResized, 0, 0, $nWidth, $nHeight, $transparent );
		}	
	}

}