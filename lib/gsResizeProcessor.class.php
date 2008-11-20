<?php

/**
 * gsResizeProcessor
 * 
 * @package gsImageProcessorPlugin
 * @version $id$
 * @copyright 2007 Gnat Solutions, Inc 
 * @author Nathanael D. Noblet <nathanael@gnat.ca> 
 * @license GPL Version 2
 */

class gsResizeProcessor
{
    /**
     * resizeBySize 
     * 
     * resize the original image by a certain pixel size
     * if 0 is supplied for $x or $y then the resize will be proportional
     * 
     * @param mixed $img 
     * @param int $x 
     * @param int $y 
     * @param mixed $keepAlpha 
     * @access public
     * @return void
     */
    static public function resizeBySize(&$img, $x = 0, $y = 0,$keepAlpha=true)
    {
        $nx = $img->getWidth() - $x;
        $ny = $img->getHeight() - $y;

        if ($x == 0)
            list($nx, $ny) = self::getProportionalSize($img,0, $ny);

        if ($y == 0)
            list($nx, $ny) = self::getProportionalSize($img,$nx, 0);

        return self::_resizeImage($img, $nx, $ny);
    }

    /**
     * resizeToSize 
     * 
     * resize the original image to a certain pixel size
     * if 0 is supplied for $x or $y then the resize will be proportional
     * 
     * @param mixed $img 
     * @param int $x 
     * @param int $y 
     * @param mixed $keepAlpha 
     * @access public
     * @return void
     */
    static public function resizeToSize(&$img, $x = 0, $y = 0,$keepAlpha=true)
    {
        $nx = $x;
        $ny = $y;

        if ($x == 0) 
            list($nx, $ny) = self::getProportionalSize($img,0, $y);

        if ($y == 0) 
            list($nx, $ny) = self::getProportionalSize($img,$x, 0);

        return self::_resizeImage($img, $nx, $ny);
    }

    /**
     * resizeByPercent 
     *
     * resize the original image by a certain percent of the original
     * if 0 is supplied for $percentx or $percenty then the resize will be proportional
     *  
     * @param mixed $img 
     * @param int $percentx 
     * @param int $percenty 
     * @param mixed $keepAlpha 
     * @access public
     * @return void
     */
    static public function resizeByPercent(&$img, $percentx = 0, $percenty = 0,$keepAlpha=true)
    {
        $nx = $img->getWidth() - (($percentx / 100) * $img->getWidth());
        $ny = $img->getHeight() - (($percenty / 100) * $img->getHeight());

        if ($percentx == 0) 
            list($nx, $ny) = self::getProportionalSize($img,0, $ny);

        if ($percenty == 0) 
            list($nx, $ny) = self::getProportionalSize($img,$nx, 0);

        return self::_resizeImage($img, $nx, $ny);
    }

    /**
     * resizeToPercent 
     * 
     * resize the original image to a certain percent of the original
     * if 0 is supplied for $percentx or $percenty then the resize will be proportional
     * 
     * @param mixed $img 
     * @param int $percentx 
     * @param int $percenty 
     * @param mixed $keepAlpha 
     * @access public
     * @return void
     */
    static public function resizeToPercent(&$img, $percentx = 0, $percenty = 0,$keepAlpha=true)
    {
        $nx = ($percentx / 100) * $img->getWidth();
        $ny = ($percenty / 100) * $img->getHeight();

        if ($percentx == 0) 
            list($nx, $ny) = self::getProportionalSize($img, 0, $ny);

        if ($percenty == 0) 
            list($nx, $ny) = self::getProportionalSize($img,$nx, 0);

        return self::_resizeImage($img, $nx, $ny);
    }

    /**
     * resizeToMaximumSize 
     * 
     * @param mixed $img 
     * @param int $x 
     * @param int $y 
     * @static
     * @access public
     * @return void
     */
    static public function resizeToMaximumSize(&$img,$x = 0, $y = 0)
    {
        $ox = $img->getWidth();
        $oy = $img->getHeight();

        $nx = $x;
        $ny = $y;

        if($x == 0 && $y == 0)
            list($nx,$ny) = ($x==0) ? self::getProportionalSize($img, 0,$y):self::getProportionalSize($img, $x,0);
        else
        {
            if($ox > $oy) //width greater landscape
                list($nx,$ny) = self::getProportionalSize($img, $x,0);
            else // height greater portrait
                list($nx,$ny) = self::getProportionalSize($img, 0,$y);
        }

        return self::_resizeImage($img,$nx, $ny);
    }

    /**
     * getProportionalSize 
     *
     * if only the width is supplied, get the height based on the original image size
     * if only the height is supplied, get the width based on the original image size
     *  
     * @param mixed $img 
     * @param mixed $x 
     * @param mixed $y 
     * @access private
     * @return array
     */
    static public function getProportionalSize(&$img, $x, $y)
    {
        if(!$x) 
            $x = ($y / $img->getHeight()) * $img->getWidth();
        else 
            $y = $img->getHeight() / ($img->getWidth() / $x);

        return array($x, $y);
    }

    /**
     * _resizeImage 
     * 
     * core functionality for the resizing of an image
     * 
     * @param mixed $img 
     * @param mixed $nx 
     * @param mixed $ny 
     * @param mixed $function 
     * @access private
     * @return gdImage
     */
    static private function _resizeImage(&$img = null,$nx, $ny)
    {
        if($img == null)
            throw new sfException('No image to resize!');

        if (($nx < 0) || ($ny < 0))
            throw new sfException('Unable to resize image because the size requested is not valid.');

        $tmp = @ImageCreateTrueColor($nx, $ny);
        @ImageCopyResampled($tmp, $img->getData(), 0, 0, 0, 0, $nx, $ny, $img->getWidth(), $img->getHeight());

        return new gdImage($tmp);
    }
}