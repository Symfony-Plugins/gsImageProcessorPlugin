<?php

/**
 * gsCroppingProcessor
 * 
 * @package gsImageProcessorPlugin
 * @version $id$
 * @copyright 2007 Gnat Solutions, Inc 
 * @author Nathanael D. Noblet <nathanael@gnat.ca> 
 * @license GPL Version 2
 */

class gsCroppingProcessor
{
    const 
        ccTOPLEFT = 0,
        ccTOP = 1,  
        ccTOPRIGHT = 2,
        ccLEFT = 3,
        ccCENTRE = 4,
        ccCENTER = 4,
        ccRIGHT = 5,
        ccBOTTOMLEFT = 6,
        ccBOTTOM = 7,
        ccBOTTOMRIGHT = 8;

/**
    * @return gdImage
    * @param gdImage img
    * @param int $x
    * @param int $y
    * @param int $position
    * @desc Determines the dimensions to crop to if using the 'crop by size' method
    */
    static public function cropBySize(&$img, $x, $y, $position = self::ccCENTRE,$keepAlpha=true)
    {
        $nx =$img->getWidth() - $x;
        $ny = $img->getHeight() - $y;
        return self::_cropSize($img, -1, -1, $nx, $ny, $position);
    }
    
    /**
    * @return gdImage
    * @param gdImage img
    * @param int $x
    * @param int $y
    * @param int $position
    * @desc Determines the dimensions to crop to if using the 'crop to size' method
    */
    static public function cropToSize(&$img, $x, $y, $position = self::ccCENTRE,$keepAlpha=true)
    {
        if ($x == 0) $x = 1;
        if ($y == 0) $y = 1;
        return self::_cropSize($img, -1, -1, $x, $y, $position);
    }
    
       /**
    * @return gdImage
    * @param gdImage img
    * @param int $sx
    * @param int $sy
    * @param int $ex
    * @param int $ey
    * @desc Determines the dimensions to crop to if using the 'crop to dimensions' method
    */
    static public function cropToDimensions(&$img,$sx, $sy, $ex, $ey,$keepAlpha=true)
    {
        $nx = abs($ex - $sx);
        $ny = abs($ey - $sy);
        return self::_cropSize($sx, $sy, $nx, $ny);
    }

    /**
    * @return gdImage
    * @param gdImage img
    * @param int $percentx
    * @param int $percenty
    * @param int $position
    * @desc Determines the dimensions to crop to if using the 'crop by percentage' method
    */
    static public function cropByPercent(&$img,$percentx, $percenty, $position = self::ccCENTRE,$keepAlpha=true)
    {
        if ($percentx == 0)
            $nx = $img->getWidth();
        else
            $nx = $img->getWidth() - (($percentx / 100) * $img->getWidth());

        if ($percenty == 0)
            $ny = $img->getHeight();
        else
            $ny = $img->getHeight() - (($percenty / 100) * $img->getHeight());

        return self::_cropSize($img,-1, -1, $nx, $ny, $position);
    }


    /**
    * @return gdImage
    * @param gdImage img
    * @param int $percentx
    * @param int $percenty
    * @param int $position
    * @desc Determines the dimensions to crop to if using the 'crop to percentage' method
    */
    static public function cropToPercent(&$img,$percentx, $percenty, $position = self::ccCENTRE,$keepAlpha=true)
    {
        $nx = ($percentx == 0)? $img->getWidth(): ($percentx / 100) * $img->getWidth();
        $ny = ($percenty == 0)? $img->getHeight():($percenty / 100) * $img->getHeight();

        return self::_cropSize($img,-1, -1, $nx, $ny, $position);
    }


    /**
    * @return gdImage
    * @param gdImage img
    * @param int $threshold
    * @desc Determines the dimensions to crop to if using the 'automatic crop by threshold' method
   
    static public function cropByAuto($img,$threshold = 254,$keepAlpha=true)
    {
        if ($threshold < 0) $threshold = 0;
        if ($threshold > 255) $threshold = 255;

        $sizex = $img->getWidth();
        $sizey = $img->getHeight();

        $sx = $sy = $ex = $ey = -1;
        for ($y = 0; $y < $sizey; $y++)
        {
            for ($x = 0; $x < $sizex; $x++)
            {
                if ($threshold >= $this->_getThresholdValue($this->_imgOrig, $x, $y))
                {
                    if ($sy == -1) 
                        $sy = $y;
                    else 
                        $ey = $y;

                    if ($sx == -1) 
                        $sx = $x;
                    else
                    {
                        if ($x < $sx) 
                            $sx = $x;
                        else if ($x > $ex) 
                            $ex = $x;
                    }
                }
            }
        }
        
        $nx = abs($ex - $sx);
        $ny = abs($ey - $sy);
        return self::_cropSize($img, $sx, $sy, $nx, $ny, self::ccTOPLEFT);
    }
*/
/**
    * @return gdImage
    * @param gdImage img
    * @param int $ox Original image width
    * @param int $oy Original image height
    * @param int $nx New width
    * @param int $ny New height
    * @param int $position Where to place the crop
    * @desc Creates the cropped image based on passed parameters
    */
    static private function _cropSize(&$img,$ox, $oy, $nx, $ny, $position)
    {
        if ($img== null)
            throw new sfException('Cannot crop an empty image!');

        if (($nx <= 0) || ($ny <= 0))
            throw new sfException('The image could not be cropped because the size given is not valid.');

        /*
        if (($nx > $img->getWidth()) || ($ny > $img->getHeight()))
        {
            $this->_debug($function, 'The image could not be cropped because the size given is larger than the original image.');
            return false;
        }
        */
        if ($ox == -1 || $oy == -1)
            list($ox, $oy) = self::_getCopyPosition($img,$nx, $ny, $position);

        if (function_exists('imagecreatetruecolor'))
        {
            $tmp = @ImageCreateTrueColor($nx, $ny);
            @ImageCopyResampled($tmp, $img->getData(), 0, 0, $ox, $oy, $nx, $ny, $nx, $ny);
        }
        else
        {
            $tmp = @ImageCreate($nx, $ny);
            @ImageCopyResized($tmp, $img->getData(), 0, 0, $ox, $oy, $nx, $ny, $nx, $ny);
        }

        return new gdImage($tmp);
    }

    /**
    * @return array
    * @param int $nx
    * @param int $ny
    * @param int $position
    * @desc Determines dimensions of the crop
    */
    static private function _getCopyPosition(&$img, $nx, $ny, $position)
    {
        $ox = $img->getWidth();
        $oy = $img->getHeight();

        switch($position)
        {
            case self::ccTOPLEFT:
                return array(0, 0);
            case self::ccTOP:
                return array(ceil(($ox - $nx) / 2), 0);
            case self::ccTOPRIGHT:
                return array(($ox - $nx), 0);
            case self::ccLEFT:
                return array(0, ceil(($oy - $ny) / 2));
            case self::ccCENTER:
            case self::ccCENTRE:
                return array(ceil(($ox - $nx) / 2), ceil(($oy - $ny) / 2));
            case self::ccRIGHT:
                return array(($ox - $nx), ceil(($oy - $ny) / 2));
            case self::ccBOTTOMLEFT:
                return array(0, ($oy - $ny));
            case self::ccBOTTOM:
                return array(ceil(($ox - $nx) / 2), ($oy - $ny));
            case self::ccBOTTOMRIGHT:
                return array(($ox - $nx), ($oy - $ny));
        }
    }
    
    
}
