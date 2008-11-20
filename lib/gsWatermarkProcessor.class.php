<?php

/**
 * gsWatermarkProcessor
 *
 * @package gsImageProcessorPlugin
 * @version $id$
 * @copyright 2007 Gnat Solutions, Inc
 * @author Nathanael D. Noblet <nathanael@gnat.ca>
 * @license GPL Version 2
 */

class gsWatermarkProcessor
{

      # given two images, return a blended watermarked image
    /**
     * createWatermark
     *
     * @param gdImage $img - the original image
     * @param params
     *      string watermarkImage - the path/to/watermark.png
     *      int alpha - opacity of the watermark
     *      string || array position - positioning of the watermark
     *          @option array (x, y)
     *          @option string
                    'top' || 'top left' || 'top right' ||
                    'center' (default)
                    'bottom' || bottom left' || 'bottom right'
     */
    static public function createWatermark(&$img = null, $params = array())
    {
        // make sure we have the image resource
        if($img == null || !($img instanceof gdImage))
            throw new sfException('Cannot apply a watermark to a non-existant image.');

        //set and check alpha
        $alpha = (!empty($params['alpha'])) ? $params['alpha'] /= 100 : 1;
        if ($params['alpha'] > 1 || $params['alpha'] < 0)
            throw new sfException('Watermark alpha setting out of range, use a number within the range of 0-100.');

        //set and check watermark image file existance
        $watermarkFile = sfConfig::get('sf_root_dir').DIRECTORY_SEPARATOR.$params['image'];
        if ( !is_file($watermarkFile) )
            throw new sfException('Cannot apply a non-existent watermark. Path supplied: '.$watermarkFile);

        // create the watermark image
        $watermarkImage = ImageCreateFromPng($watermarkFile);

        //calculate image dimensions
        $mainImageWidth = $img->getWidth();
        $mainImageHeight = $img->getHeight();
        $watermarkImageWidth = imagesx($watermarkImage );
        $watermarkImageHeight = imagesy($watermarkImage );

        //determine positioning
        if (isset($params['position']))
        {
            $watermarkPosition = $params['position'];
            //x, y coords
            if (is_array($watermarkPosition))
            {
                //set a warning if x or y means it's way off screen...
                //if ($watermarkPosition[1] > ($imageWidth - $watermarkWidth))
                    //throw new sfException('The x coordinate supplied for the watermark places it off of the picture.');
                //if ($watermarkPosition[1] > ($imageHeight - $watermarkHeight))
                    //throw new sfException('The y coordinate supplied for the watermark places it off of the picture.');
                $xPosition = $watermarkPosition[0];
                $yPosition = $watermarkPosition[1];
            }
            else
            {
                switch($watermarkPosition)
                {
                    case 'top': //top center
                        $xPosition = ceil(($mainImageWidth / 2) - ($watermarkImageWidth / 2));
                        $yPosition = 0;
                    break;

                    case 'right': //right center
                        $xPosition = ceil($mainImageWidth - $watermarkImageWidth);
                        $yPosition = ceil(($mainImageHeight / 2) - ($watermarkImageHeight / 2));
                    break;

                    case 'bottom': //bottom center
                        $xPosition = ceil(($mainImageWidth / 2) - ($watermarkImageWidth / 2));
                        $yPosition = ceil($mainImageHeight - $watermarkImageHeight);
                    break;

                    case 'left': //left center
                        $xPosition = 0;
                        $yPosition = ceil(($mainImageHeight / 2) - ($watermarkImageHeight / 2));
                    break;

                    case 'top right':
                        $xPosition = 0;
                        $yPosition = ceil($mainImageWidth - $watermarkImageWidth);
                    break;

                    case 'top left':
                        $xPosition = 0;
                        $yPosition = 0;
                    break;

                    case 'bottom right':
                        $xPosition = ceil($mainImageWidth - $watermarkImageWidth);
                        $yPosition = ceil($mainImageHeight - $watermarkImageHeight);
                    break;

                    case 'bottom left':
                        $xPosition = 0;
                        $yPosition = ceil($mainImageHeight - $watermarkImageHeight);
                    break;

                    //center it
                    default:
                        $xPosition = ceil(($mainImageWidth / 2) - ($watermarkImageWidth / 2));
                        $yPosition = ceil(($mainImageHeight / 2) - ($watermarkImageHeight / 2));
                    break;

                }
            }
        }
        else //no position param set
        {
            $xPosition = ceil(($mainImageWidth / 2) - ($watermarkImageWidth / 2));
            $yPosition = ceil(($mainImageHeight / 2) - ($watermarkImageHeight / 2));
        }

        if(sfConfig::get('sf_logging_enabled'))
            sfLogger::getInstance()->debug('Watermarking: xpos='.$xPosition.' ypos='.$yPosition);

        //create new image to hold merged changes
        $watermarkedImage = imagecreatetruecolor($mainImageWidth, $mainImageHeight);

        //walk through main image, pixel by pixel
        for($y=0; $y<$mainImageHeight; $y++)
        {
            for($x=0; $x<$mainImageWidth; $x++)
            {
                $returnColor = NULL;

                //determine the correct pixel location within our watermark
                $watermarkX = $x - $xPosition;// - $mainImageMinWidth;
                $watermarkY = $y - $yPosition;// - $mainImageMinHeight;

                //fetch color information for both of our images
                $mainRGB = imagecolorsforindex($img->getData(), imagecolorat($img->getData(), $x, $y));

                //if our watermark has a non-transparent value at this pixel intersection
                //and we're still within the bounds of the watermark image
                if ($watermarkX >= 0 && $watermarkX < $watermarkImageWidth && $watermarkY >= 0 && $watermarkY < $watermarkImageHeight )
                {
                    $watermarkRGB = imagecolorsforindex($watermarkImage, imagecolorat($watermarkImage, $watermarkX, $watermarkY));

                    //using image alpha, and user specified alpha, calculate average
                    $watermarkAlpha = round(((127 - $watermarkRGB['alpha']) / 127), 2);
                    $watermarkAlpha = $watermarkAlpha * $alpha;

                    //calculate the color 'average' between the two - taking into account the specified alpha level
                    $avgRed = self::getAverageColor($mainRGB['red'], $watermarkRGB['red'], $watermarkAlpha );
                    $avgGreen = self::getAverageColor($mainRGB['green'], $watermarkRGB['green'], $watermarkAlpha );
                    $avgBlue = self::getAverageColor($mainRGB['blue'], $watermarkRGB['blue'], $watermarkAlpha );

                    //calculate a color index value using the average RGB values we've determined
                    $returnColor = self::getImageColor($watermarkedImage, $avgRed, $avgGreen, $avgBlue);
                }
                else //if we're not dealing with an average color here, then let's just copy over the main color
                {
                  $returnColor = imagecolorat($img->getData(), $x, $y);
                }

                //draw the appropriate color onto the return image
                imagesetpixel( $watermarkedImage, $x, $y, $returnColor);
            }
        }

        //return the resulting, watermarked image for display
        return new gdImage($watermarkedImage);
    }


    /**
     * getImageColor
     * average two colors given an alpha
     *
     * @param string colorA
     * @param string colorB
     * @param string alpha
     *
     */
    static private function getAverageColor( $colorA, $colorB, $alpha)
    {
        return round( ( ( $colorA * ( 1 - $alpha ) ) + ( $colorB  * $alpha ) ) );
    }

    /**
     * getImageColor
     * return closest pallette-color match for RGB values
     *
     * @param $img
     * @param string red
     * @param string green
     * @param string blue
     *
     */
    static private function getImageColor($img, $red, $green, $blue)
    {
        $c = imagecolorexact($img, $red, $green, $blue);
        if ($c!=-1) return $c;
        $c = imagecolorallocate($img, $red, $green, $blue);
        if ($c!=-1) return $c;
        return imagecolorclosest($img, $red, $green, $blue);
    }


    /**
     * applyWatermark
     *
     * Creates a copy of the image, and merges the copy with the defined watermark image
     * The watermark images should to be stored in 'data/watermarks/'
     *
     *
     * @return gdImage a new image with watermark applied
     * @param gdImage $img
     * @param string $watermarkFile
     * @access public
     *
     */

    static public function applyWatermarkImage(&$img = null, $params = array()) //$watermarkFile = null, $watermarkScale=null, $watermarkPosition=null)
    {
        // make sure we have the image resource
        if($img == null || !($img instanceof gdImage))
            throw new sfException('Cannot apply a watermark to a non-existant image.');

        if ( empty($params) || !isseT($params['image']))
            throw new sfException('Cannot apply a non-existent watermark.');

        $water_full_path = sfConfig::get('sf_root_dir').DIRECTORY_SEPARATOR.$params['image'];
        if ( !is_file($water_full_path) )
            throw new sfException('Cannot apply a non-existent watermark.'.$water_full_path);

        // create the watermark image
        $watermark = ImageCreateFromPng($water_full_path);

        // scale the watermark, if asked for
        if (isset($params['scale']))
        {
            //pass to image processor, which returns a copy of the image????
        }

        // grab height and width of the watermark after scaling
        $watermarkWidth = imagesx($watermark);
        $watermarkHeight = imagesy($watermark);

        $imageWidth = $img->getWidth();
        $imageHeight = $img->getHeight();

        // find watermark position, if any, defaults to center
        if (isset($params['position']))
        {
            $watermarkPosition = $params['position'];
            //x, y coords
            if (is_array($watermarkPosition))
            {
                //set a warning if x or y means it's way off screen...
                //if ($watermarkPosition[1] > ($imageWidth - $watermarkWidth))
                    //throw new sfException('The x coordinate supplied for the watermark places it off of the picture.');
                //if ($watermarkPosition[1] > ($imageHeight - $watermarkHeight))
                    //throw new sfException('The y coordinate supplied for the watermark places it off of the picture.');
                $xPosition = $watermarkPosition[0];
                $yPosition = $watermarkPosition[1];
            }
            else
            {
                switch($watermarkPosition)
                {
                    case 'top': //top center
                        $xPosition = ($imageWidth / 2) - ($watermarkWidth / 2);
                        $yPosition = 0;
                    break;

                    case 'right': //right center
                        $xPosition = $imageWidth - $watermarkWidth;
                        $yPosition = ($imageHeight / 2) - ($watermarkHeight / 2);
                    break;

                    case 'bottom': //bottom center
                        $xPosition = ($imageWidth / 2) - ($watermarkWidth / 2);
                        $yPosition = $imageHeight - $watermarkHeight;
                    break;

                    case 'left': //left center
                        $xPosition = 0;
                        $yPosition = ($imageHeight / 2) - ($watermarkHeight / 2);
                    break;

                    case 'top right':
                        $xPosition = 0;
                        $yPosition = $imageWidth - $watermarkWidth;
                    break;

                    case 'top left':
                        $xPosition = 0;
                        $yPosition = 0;
                    break;

                    case 'bottom right':
                        $xPosition = $imageWidth - $watermarkWidth;
                        $yPosition = $imageHeight - $watermarkHeight;
                    break;

                    case 'bottom left':
                        $xPosition = 0;
                        $yPosition = $imageHeight - $watermarkHeight;
                    break;

                    //center it
                    default:
                        $xPosition = ($imageWidth / 2) - ($watermarkWidth / 2);
                        $yPosition = ($imageHeight / 2) - ($watermarkHeight / 2);
                    break;

                }
            }
        }

        if(!is_array($params['transparent']))
            $params['transparent'] = gsImageHelper::HTMLHexToBinArray($params['transparent']);

        @ImageColorTransparent($watermark, @imagecolorallocate($watermark,$params['transparent'][0],$params['transparent'][1],$params['transparent'][2]));

        @ImageCopyMerge($img->getData(), $watermark, $xPosition, $yPosition, 4, 4, $watermarkWidth-8, $watermarkHeight-8, 100);
        return $img;
    }
}
