<?php

/**
 * gsColorizeProcessor
 *
 * @package gsImageProcessorPlugin
 * @version $id$
 * @copyright 2007 Gnat Solutions, Inc
 * @author Nathanael D. Noblet <nathanael@gnat.ca>
 * @license GPL Version 2
 * @description : colourizes photos.
 * @param $colour - array of RBG values, from -255 to +255 where 0 == no change
                   to get these numbers, use photoshop, grayscale your image
                   duplicate onto a new layer & colourize as you wish. Use
                   window -> historgram and grab the differences in medians of
                   each of the RGB channels.
 */

class gsColorizeProcessor 
{
    static public function colourize(&$img,$colour = null,$ret_new=false)
    {
        if($colour == null)
            throw new sfException('No Color provided to colourize with!');

        if(!is_array($colour))
            $colour = gsImageHelper::HTMLHexToBinArray($colour);

        if(count($colour) != 3)
            throw new sfException('Wrong parameter count for colourization!');

        if($ret_new)
        {
            $tmp = new gdImage();
            $tmp->setData($img->getData());
            imagefilter($tmp->getData(), IMG_FILTER_GRAYSCALE);
            imagefilter($tmp->getData(), IMG_FILTER_COLORIZE, $colour[0], $colour[1], $colour[2]);
            return $tmp;
        }
        else
        {
            imagefilter($img->getData(), IMG_FILTER_GRAYSCALE);
            imagefilter($img->getData(), IMG_FILTER_COLORIZE, $colour[0], $colour[1], $colour[2]);

            return $img;
        }
    }
}