<?php

/**
 * ImageProcessorHelper
 *
 * @package gsImageProcessorPlugin
 * @version $id$
 * @copyright 2007 Gnat Solutions, Inc
 * @author Nathanael D. Noblet <nathanael@gnat.ca>
 * @license GPL Version 2
 */

class ImageProcessorHelper
{
    /**
     * ProcessImage
     *
     * @param mixed $input_file
     * @param array $params
     * @param mixed $output_file
     * @param mixed $path
     * @static
     * @access public
     * @return void
     */
    static public function ProcessImage($input_file = null, $params = array(), $output_file = null, $path = null)
    {
        if( ($input_file == null && !isset($params['create']) || ($input_file != null && !is_file($input_file) && !($input_file instanceof gdImage) )) ) //empty($input_file) || !is_file($input_file) && !$input_file InstanceOf gdImage)
            throw new sfException('Missing required params input file: '.$input_file);

        if(empty($params))
            throw new sfException('Missing required params dimensions array!');

        // determines if we are to save the processed image before returning
        if(isset($params['save']) && !empty($output_file))
        {
            $full_path = ($path != null) ? $path.$output_file : sfConfig::get('sf_upload_dir').'/images/'.$output_file;
            $save = true;

            // determines the output file type
            if(isset($params['output_type']))
                $output_type = $params['output_type'];
            else
            {
                $output_type = gsFileHelper::getExtension($output_file);
                if(empty($output_type))
                    $output_type = gsFileHelper::getExtension($input_file);
            }

            // defaults to png
            if(empty($output_type))
                $output_type = 'png';

            $quality = (isset($params['quality']))?$params['quality']:90;
        }
        else if(isset($params['save']) && isset($params['output_type']))
        {
            if(in_array($params['output_type'],array('png','jpg','gif')))
                $output_type = $params['output_type'];
            else
                $save = false;
        }
        else
            $save = false;

        if(isset($params['create']) && !empty($params['create']))
        {
            $im          = null;
            $cparams     = &$params['create'];

            $imageWidth  = null;
            $imageHeight = null;

            if(isset($cparams['auto-size']))
            {
                $dims        = self::autoSize($params[$cparams['auto-size']['field']]);
                $imageWidth  = $dims['width'];
                $imageHeight = $dims['height'];
            }
            else if(isset($cparams['width']) && isset($cparams['height']))
            {
            	$imageWidth  = $cparams['width'];
                $imageHeight = $cparams['height'];
            }

            if($imageWidth == null)
                throw new sfException('Image auto-create requested but no params provided');

            $depth = isset($cparams['depth']) ? $cparams['depth']:32;
            switch($depth)
            {
                case '8':
                    $im = imagecreate($imageWidth, $imageHeight);
                case '32':
                default:
                    $im = imagecreatetruecolor($imageWidth, $imageHeight);
                    break;
            }

            // set values for transparency and opacity
            $transparency = isset($params['transparency']);
            $opacity = ($transparency) ? 127 : 0;

            if(isset($cparams['bgcolor']) || isset($cparams['bgcolour']))
            {
                $bg         = isset($cparams['bgcolor']) ? $cparams['bgcolor'] : $cparams['bgcolour'];
                $bgcolor    = ($transparency) ? @imagecolorallocatealpha($im, $bg[0], $bg[1], $bg[2], $opacity): @imagecolorallocate($im, $bg[0], $bg[1], $bg[2]);

                @imagefill($im, 0, 0, $bgcolor);
            }

            if(!$im)
                throw new sfException('Unable to create image from scratch');

            $img = new gdImage($im);
        }
        else if($input_file instanceof gdImage)
            $img = $input_file;
        else// passing an existing image.
        {
        	$img = new gdImage();
        	$img->loadImage($input_file);
        }

        if(isset($params['resize']) && !isset($params['max_width']))
            throw new sfException('Resize specified but no max_width provided!');

        if(isset($params['resize']) && $params['resize'] && (!isset($params['strict']) || $params['strict'] == false))
        {
            $img = gsResizeProcessor::resizeToSize($img,$params['max_width']);

            if(isset($params['max_height']) && $img->getHeight() > $params['max_height']) // still too long
            {
                if( isset($params['crop']) && $params['crop'] ) // crop to size
                    $img = gsCroppingProcessor::cropToSize($img,$params['max_width'],$params['max_height'], gsCroppingProcessor::ccCENTER);
                else // resize height to max
                    $img = gsResizeProcessor::resizeToSize($img,0,$params['max_height']);
            }
        }
        else if(isset($params['resize']) && $params['resize'] && isset($params['strict']) && $params['strict'] == true)
        {
            $dims1 = gsResizeProcessor::getProportionalSize($img,$params['max_width'],0);

            if(isset($params['max_height']) && $dims1[1] < $params['max_height']) // resizing would make the height smaller than the max.
            {
                $dims2 = gsResizeProcessor::getProportionalSize($img,0,$params['max_height']);
                if($dims2[0] <$params['max_width']) // resizing would make the width smaller than the max
                    throw new sfException('Unable to resize image strictly');
                else
                {
                    $img = gsResizeProcessor::resizeToSize($img,0,$params['max_height']);

                    if($img->getWidth() > $params['max_width']) // still too wide
                        $img = gsCroppingProcessor::cropToSize($img,$params['max_width'],$params['max_height'], gsCroppingProcessor::ccCENTER);
                }
            }
            else
            {
                $img = gsResizeProcessor::resizeToSize($img,$params['max_width']);

                if($img->getHeight() > $params['max_height']) // still too long
                    $img = gsCroppingProcessor::cropToSize($img,$params['max_width'],$params['max_height'], gsCroppingProcessor::ccCENTER);
            }
        }
        else if( isset($params['crop']) && $params['crop'] && isset($params['max_height'])) //just crop
            $img = gsCroppingProcessor::cropToSize($img,$params['max_width'],$params['max_height'], gsCroppingProcessor::ccCENTER);

        if( isset($params['colorize']) && is_array($params['colorize']) )
            $img = gsColorizeProcessor::colorize($img,$params['colorize']);

        if( isset($params['border']) && $params['border'] )
            $img = gsBorderProcessor::addBorder($img, $params['border_width'], ((isset($params['border_colour']))?$params['border_colour']:$params['border_color']) );

        if( isset($params['shadow']) && $params['shadow'] )
            $img = gsShadowProcessor::applyShadow($img, (isset($params['shadow_color'])?$params['shadow_color']:$params['shadow_colour']));

        if( isset($params['rotate']) && $params['rotate'] && isset($params['degrees']) )
            gsRotationProcessor::rotate($img, $params['degrees'],$params['background']);

        if( isset($params['watermark']) && is_array($params['watermark']))
            $img = gsWatermarkProcessor::createWatermark($img, $params['watermark']);

        if( isset($params['textoverlay']) && is_array($params['textoverlay']))
            $img = gsTextOverlayProcessor::overlayText($img, $params['textoverlay']);

        if($save)
            $img->save($full_path,$output_type,$quality,(isset($params['transparent']))?$params['transparent']:null);

        return $img;
    }

    static public function autoSize($params = array())
    {
        /*
         * First, we find the bounding box of the text:
         */

        $dims = imagettfbbox($params['font']['size'], 0, $params['font']['file'], $params['text']['content']);

        /* Then we calculate the total width of the image (the x-coord of
         * the left corners of the image may be negative, due to anti-aliasing
         * and various other font metrics issues, so we need to use absolute
         * values)
         */

        $width = abs($dims[2] - $dims[0]);

        /*
         * Playing around with dimensions depending on just HOW far the text
         * moves off the left side
         */

        if($dims[0] < -1)
            $boxWidth = abs($dims[2]) + abs($dims[0]) - 1;

        /*
         * Then we calculate the total height of the image using the same
         * logic
         */

        $height = abs($dims[7]) - abs($dims[1]);

        /* Account for AA again */
        if($dims[3] > 0)
            $boxHeight = abs($dims[7] - $dims[1]) - 1;

        /*
         * Now, we find where we need to place the x-coord of the font's baseline.
         * This is usually the same as the x-coord of the bottom-left corner,
         * give or take a pixel or two
         */

        $x = ($dims[0] >= -1) ? abs($dims[0] + 1) * -1: abs($dims[0] + 2);

        /* This is the important one.  For reasons I admittedly don't fully understand,
         * the y-coord of the top right (or left) corner of the bounding box is
         * a negative value equal to the inverse of the baseline height of the
         * font: I.E. if the b-line height = 24px, then the y-coord of the top-
         * right = -24. So we set the baseline-y position of the text to the
         * absolute value of the y-coord of the top-right corner, plus one pixel
         * to account for anti-aliasing.
         */

        $y = abs($dims[5] + 1);

        return array('width'=>$boxWidth,'height'=>$boxHeight);
    }
}
