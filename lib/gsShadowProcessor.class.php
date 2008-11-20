<?php

/**
 * gsShadowProcessor 
 * 
 * @package gsImageProcessorPlugin
 * @version $id$
 * @copyright 2007 Gnat Solutions, Inc 
 * @author Nathanael D. Noblet <nathanael@gnat.ca> 
 * @license GPL Version 2
 */

class gsShadowProcessor
{

    /**
     * applyShadow
     *
     * Creates a copy of the image and applies a drop shadow to it and then returns the new image. 
     * the background colour can be changed by passing an HTML hex value (with or without the #)  
     * 
     * @return gdImage a new image with drop shadow applied
     * @param gdImage $img 
     * @param string $bgcolour 
     * @param string $shadowPath the path to the shadow images defaults to the plugin data/img directory
     * @access public
     * 
     */
    static public function applyShadow($img=null,$bgcolour = '000000', $shadowPath = null)
    {
        // make sure we have the image resource
        if($img == null || !($img instanceof gdImage))
            throw new sfException('Cannot apply a shadow to a non-existant image.');

        $shadow_path = ($shadowPath != null && is_file($shadowPath.'ds_left.png')) ? $shadowPath: realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        $shadows = array();
        // attempt to load the drop shadow array
        if (!isset($shadows['l']) && empty($shadows['l']))
             $shadows['l']  = @ImageCreateFromPNG($shadow_path . "ds_left.png");
        if (!isset($shadows['r']) && empty($shadows['r'])) 
            $shadows['r']  = @ImageCreateFromPNG($shadow_path . "ds_right.png");
        if (!isset($shadows['t']) && empty($shadows['t'])) 
            $shadows['t']  = @ImageCreateFromPNG($shadow_path . "ds_top.png");
        if (!isset($shadows['b']) && empty($shadows['b'])) 
            $shadows['b']  = @ImageCreateFromPNG($shadow_path . "ds_bottom.png");
        if (!isset($shadows['tl']) && empty($shadows['tl'])) 
            $shadows['tl'] = @ImageCreateFromPNG($shadow_path . "ds_tlcorner.png");
        if (!isset($shadows['tr']) && empty($shadows['tr'])) 
            $shadows['tr'] = @ImageCreateFromPNG($shadow_path . "ds_trcorner.png");
        if (!isset($shadows['bl']) && empty($shadows['bl'])) 
            $shadows['bl'] = @ImageCreateFromPNG($shadow_path . "ds_blcorner.png");
        if (!isset($shadows['br']) && empty($shadows['br'])) 
            $shadows['br'] = @ImageCreateFromPNG($shadow_path . "ds_brcorner.png");

        // verify all is well
        foreach($shadows as $key => $val)
        {
            if ($val == NULL)
                throw new sfException('The shadow files could not be loaded '.$shadow_path);
        }

        // create go-between image
        $ox = $img->getWidth();
        $oy = $img->getHeight();
        $nx = @ImageSX($shadows['l']) + @ImageSX($shadows['r']) + $img->getWidth();
        $ny = @ImageSY($shadows['t']) + @ImageSY($shadows['b']) + $img->getHeight();

        //drop shadow image resource
        $tmp = @ImageCreateTrueColor($nx, $ny);

        if ($tmp == NULL)
            throw new sfException('The drop shadowed image resource could not be created.');

        // pre-process the image
        $background = gsImageHelper::HTMLHexToBinArray($bgcolour);
        $back_color = @ImageColorAllocate($tmp, $background[0], $background[1], $background[2]);
        @imageColorTransparent($back_color);
        @ImageAlphaBlending($tmp, true);
        @ImageFill($tmp, 0, 0,$back_color );

        // apply the shadow

        // top left corner
        @ImageCopyResampled($tmp, $shadows['tl'],
                        0, 0, 0, 0,
                        @ImageSX($shadows['tl']), @ImageSY($shadows['tl']), @ImageSX($shadows['tl']), @ImageSY($shadows['tl']));
        // top shadow
        @ImageCopyResampled($tmp, $shadows['t'],
                        @ImageSX($shadows['l']), 0, 0, 0,
                        $ox, @ImageSY($shadows['t']), @ImageSX($shadows['t']), @ImageSY($shadows['t']));
        // top right corner
        @ImageCopyResampled($tmp, $shadows['tr'],
                        ($nx - @ImageSX($shadows['r'])), 0, 0, 0,
                        @ImageSX($shadows['tr']), @ImageSY($shadows['tr']), @ImageSX($shadows['tr']), @ImageSY($shadows['tr']));
        // left shadow
        @ImageCopyResampled($tmp, $shadows['l'],
                        0, @ImageSY($shadows['t']),  0, 0,
                        @ImageSX($shadows['l']), $oy, @ImageSX($shadows['l']), @ImageSY($shadows['l']));
        // right shadow
        @ImageCopyResampled($tmp, $shadows['r'],
                        ($nx - @ImageSX($shadows['r'])), @ImageSY($shadows['tl']), 0, 0,
                        @ImageSX($shadows['r']), $oy, @ImageSX($shadows['r']), @ImageSY($shadows['r']));
        // bottom left corner
        @ImageCopyResampled($tmp, $shadows['bl'],
                        0, ($ny - @ImageSY($shadows['b'])), 0, 0,
                        @ImageSX($shadows['bl']), @ImageSY($shadows['bl']), @ImageSX($shadows['bl']), @ImageSY($shadows['bl']));
        // bottom shadow
        @ImageCopyResampled($tmp, $shadows['b'],
                        @ImageSX($shadows['l']), ($ny - @ImageSY($shadows['b'])), 0, 0,
                        $ox, @ImageSY($shadows['b']), @ImageSX($shadows['b']), @ImageSY($shadows['b']));
        // bottom right corner
        @ImageCopyResampled($tmp, $shadows['br'],
                        ($nx - @ImageSX($shadows['r'])), ($ny - @ImageSY($shadows['b'])), 0, 0,
                        @ImageSX($shadows['br']), @ImageSY($shadows['br']), @ImageSX($shadows['br']), @ImageSY($shadows['br']));

        // apply the picture
        @ImageCopyResampled($tmp, $img->getData(),
                        @ImageSX($shadows['l']), @ImageSY($shadows['t']), 0, 0,
                        $ox, $oy,$img->getWidth(), $img->getHeight());


        return new gdImage($tmp);
    }

   
}
