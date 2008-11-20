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
    static public function addBorder(&$img, $size = 10, $colour= null)
    {
        if($colour == null)
            $colour = array(0,0,0);

        if(!is_array($colour))
            $colour =gsImageHelper::HTMLHexToBinArray($colour); 

        $mask = @imagecolorallocate($img->getData(),$colour[0],$colour[1],$colour[2]);

        @imagefilledrectangle($img->getData(),0,0,$size,$img->getHeight(),$mask);

        @imagefilledrectangle($img->getData(),0,0,$img->getWidth(),$size,$mask);

        @imagefilledrectangle($img->getData(),($img->getWidth()-$size),0,$img->getWidth(),$img->getHeight(),$mask);

        @imagefilledrectangle($img->getData(),0,($img->getHeight()-$size),$img->getWidth(),$img->getHeight(),$mask);

        return $img;
    }
}