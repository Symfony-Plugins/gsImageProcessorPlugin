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

        // determines if we are to save the processed image before returning and if so, where
        if(isset($params['save']) && !empty($output_file))
        {
            $full_path = ($path != null) ? $path.$output_file : sfConfig::get('sf_upload_dir').'/images/'.$output_file;
            $save = true;

            // determines the output file type
            if(isset($params['save']['type']))
                $output_type = $params['save']['type'];
            else
            {
                $output_type = gsFileHelper::getExtension($output_file);
                if(empty($output_type))
                    $output_type = gsFileHelper::getExtension($input_file);
            }

            // defaults to png
            if(empty($output_type))
                $output_type = 'png';

            $quality = (isset($params['save']['quality']))?$params['save']['quality']:90;
        }
        else if(isset($params['save']) && isset($params['save']['type']))
        {
            if(in_array($params['save']['type'],array('png','jpg','gif')))
                $output_type = $params['save']['type'];
            else
                $save = false;
        }
        else
            $save = false;

        if(isset($params['create']) && !empty($params['create']))
        {
            $im          = null;
            $cparams     = &$params['create'];

            /*START HERE*/
            /* First, we find the bounding box of the text: */
            $dims = imagettfbbox($params['font-size'], 0, $params['font'], $params['text']);

            $imageWidth  = $cparams['width'];
            $imageHeight = $cparams['height'];

            $depth = isset($cparams['depth']) ? $cparams['depth']:null;
            switch($depth)
            {
                case '8':
                    $im = imagecreate($imageWidth, $imageHeight);
                    break;
                case '32':
                default:
                    $im = imagecreatetruecolor($imageWidth, $imageHeight);
                    break;
            }

            if(!$im)
                throw new sfException('Unable to create image from scratch');

            // set values for transparency and opacity
            $transparency = isset($cparams['transparent']);
            $opacity      = ($transparency) ? (isset($cparams['opacity']) ? $cparams['opacity']: 127): 0;

            if(isset($cparams['bgcolor']) || isset($cparams['bgcolour']))
            {
                $bg         = isset($cparams['bgcolor']) ? $cparams['bgcolor'] : $cparams['bgcolour'];
                $bgcolor    = ($transparency) ? @imagecolorallocatealpha($im, $bg[0], $bg[1], $bg[2], $opacity): @imagecolorallocate($im, $bg[0], $bg[1], $bg[2]);

                @imagefill($im, 0, 0, $bgcolor);
            }

            $img = new gdImage($im);
        }
        else if($input_file instanceof gdImage)
            $img = $input_file;
        else// passing an existing image.
        {
        	$img = new gdImage();
        	$img->loadImage($input_file);
        }


        if(isset($params['resize']))
        {
            $rparams = &$params['resize'];

            if(!isset($rparams['width']))
                throw new sfException('Resize specified but no width provided!');

        	if(!isset($rparams['strict']) || $rparams['strict'] == false)
            {
                $img = gsResizeProcessor::resizeToSize($img,$rparams['width']);

                if(isset($rparams['height']) && $img->getHeight() > $rparams['height']) // still too long
                {
                    if( isset($rparams['crop']) && $rparams['crop'] ) // crop to size
                        $img = gsCroppingProcessor::cropToSize($img,$rparams['width'],$rparams['height'], gsCroppingProcessor::ccCENTER);
                    else // resize height to max
                        $img = gsResizeProcessor::resizeToSize($img,0,$rparams['height']);
                }
            }
            else if(isset($rparams['strict']) && $rparams['strict'] == true)
            {
                $dims1 = gsResizeProcessor::getProportionalSize($img,$rparams['width'],0);
                if(isset($rparams['height']) && $dims1[1] < $rparams['height']) // resizing would make the height smaller than the max.
                {
                    $dims2 = gsResizeProcessor::getProportionalSize($img,0,$rparams['height']);
                    if($dims2[0] < $rparams['width']) // resizing would make the width smaller than the max
                        throw new sfException('Unable to resize image strictly');
                    else
                    {
                        $img = gsResizeProcessor::resizeToSize($img,0,$rparams['height']);

                        if($img->getWidth() > $rparams['width']) // still too wide
                            $img = gsCroppingProcessor::cropToSize($img,$rparams['width'],$rparams['height'], gsCroppingProcessor::ccCENTER);
                    }
                }
                else
                {
                    $img = gsResizeProcessor::resizeToSize($img,$rparams['width']);

                    if($img->getHeight() > $rparams['height']) // still too long
                        $img = gsCroppingProcessor::cropToSize($img,$rparams['width'],$rparams['height'], gsCroppingProcessor::ccCENTER);
                }
            }
        }

        if( isset($params['crop']) ) //just crop
        {
            $cparams = &$params['crop'];

            if(isset($cparams['height']) && $cparams['width'])
                $img = gsCroppingProcessor::cropToSize($img,$cparams['width'],$cparams['height'], gsCroppingProcessor::ccCENTER);
        }

        /*
         * colorize:
         *   color: [ R,G,B ] or FFAAFF
         *   clone: false
         */
        if( isset($params['colorize']) && is_array($params['colorize']) )
            $img = gsColorizeProcessor::colorize($img,$params['colorize']);

        /*
         * border:
         *   width:  10   (default if not specified)
         *   color:  [ R,G,B ] or FFAAFF
         *   clone:  false (apply to passed in
         */
        if( isset($params['border']) && $params['border'] )
            $img = gsBorderProcessor::addBorder($img, $params['border']);

        /*
         * shadow:
         *   color:   [ R,G,B ] or 'FFAAFF' (default 000000)
         *   path:   /full/path/to/dropshadow/images.png
         *
         */
        if( isset($params['shadow']) && $params['shadow'] )
            $img = gsShadowProcessor::applyShadow($img, $params['shadow']);

        /*
         * rotate:
         *   degrees: int
         *   background: [R,G,B] or FFAAFF
         *   clone: false
         */
        if( isset($params['rotate']) )
            gsRotationProcessor::rotate($img, $params);

        /*
         * watermark:
         */
        if( isset($params['watermark']) && is_array($params['watermark']))
            $img = gsWatermarkProcessor::createWatermark($img, $params['watermark']);

        /*
         * textoverlay:
         *
         */
        if( isset($params['textoverlay']) && is_array($params['textoverlay']))
            $img = gsTextOverlayProcessor::overlayText($img, $params['textoverlay']);

        if($save)
            $img->save($full_path,$output_type,$quality,(isset($params['save']['transparent'])) ? $params['save']['transparent']:null);

        return $img;
    }
}
