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
 * @param $color - array of RBG values, from -255 to +255 where 0 == no change
                   to get these numbers, use photoshop, grayscale your image
                   duplicate onto a new layer & colourize as you wish. Use
                   window -> historgram and grab the differences in medians of
                   each of the RGB channels.
 */

class gsColorizeProcessor
{
    static public function colorize(&$input_img,$params = array()) //$color = null,$ret_new=false)
    {
        $img = (isset($params['clone'])) ? new gdImage($input_img->getData()): $input_img;

        if (!isset($params['color']))
            throw new sfException('No Color provided to colourize with!');

        $color = (!is_array($params['color'])) ? gsImageHelper::HTMLHexToBinArray($color): $params['color'];

        if(count($color) != 3)
            throw new sfException('Wrong parameter count for colourization!');

        imagefilter($img->getData(), IMG_FILTER_GRAYSCALE);
        imagefilter($img->getData(), IMG_FILTER_COLORIZE, $color[0], $color[1], $color[2]);

        return $img;
    }
}