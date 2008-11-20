<?php

/**
 * gsBorderProcessor
 *
 * @package gsImageProcessorPlugin
 * @version $id$
 * @copyright 2007 Gnat Solutions, Inc
 * @author Nathanael D. Noblet <nathanael@gnat.ca>
 * @license GPL Version 2
 */

class gsBorderProcessor
{
    static public function addBorder(&$input_img, $params = array()) //$width = 10, $color= null)
    {
        $img = (isset($params['clone'])) ? new gdImage($input_img->getData()): $input_img;

        // defaults
        $width = (isset($params['width']) ? $params['width']:10);
        $color = (isset($params['color']) ? $params['color']:array(0,0,0));

        if(!is_array($color))
            $color = gsImageHelper::HTMLHexToBinArray($color);

        $mask = @imagecolorallocate($img->getData(),$color[0],$color[1],$color[2]);

        @imagefilledrectangle($img->getData(),0,0,$width,$img->getHeight(),$mask);

        @imagefilledrectangle($img->getData(),0,0,$img->getWidth(),$width,$mask);

        @imagefilledrectangle($img->getData(),($img->getWidth()-$width),0,$img->getWidth(),$img->getHeight(),$mask);

        @imagefilledrectangle($img->getData(),0,($img->getHeight()-$width),$img->getWidth(),$img->getHeight(),$mask);

        return $img;
    }
}