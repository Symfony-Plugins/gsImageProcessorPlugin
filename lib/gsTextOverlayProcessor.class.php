<?php

/**
 * gsTextImageProcessor
 *
 * @package gsImageProcessorPlugin
 * @version $id$
 * @copyright 2007 Gnat Solutions, Inc
 * @author Blair D. Patterson <nathanael@gnat.ca>
 * @license GPL Version 2
 */

class gsTextOverlayProcessor
{

	/**
     * overlayText
     *
     *
     * @param params
     *      array font
     *         string file         - /path/to/font.ttf
     *         string size         - size in points
     *         array color         - R,G,B ( defaults 0,0,0 )
     *      array text
     *         array position      - x,y
     *         int   angle         - int (default 0)
     *         int   leading       - int default false
     *      array dropshadow
     *         array color         - R,G,B
     *         array offset        - x,y ( default 2px )
     *      bool modify_original   - perform operation on input image
     *

		$testImage = gsTextImageProcessor::createTextImage($img, $params, $text);

		SYMFONY APPLICATION:
		<conf>.yml sample:
		all:
  		  .values:
    	    image_dimensionts:
              textoverlay:
                font:               (required)
                  file:             /path/to/fontfile
                  size:             int (point size)
                  color             [R,G,B]
                text:               (required)
                  position:         [x,y]
                  angle:            int
                  leading:          int
		        dropshadow:                     (optional)
                  color:            [R,G,B]     (default black)
                  offset:           [x,y]       (default 2px)
                modify_original:    true        (optional default: false)
     */

	static public function overlayText(&$input_img, $params = array(), $text = null)
    {
        if( !($input_img instanceof gdImage))
            throw new sfException('Cannot create text overlay on a non-existant image.');

    	if(!isset($params['font']) || empty($params['font']))
            throw new sfException('Cannot create a text overlay without a font!');

    	if(!isset($params['text']) || empty($params['text']))
            throw new sfException('Cannot create a text overlay without text parameters!');

        if(!isset($params['font']['file']) || !is_file($params['font']['file']))
            throw new sfException('Cannot use a non-existent font. '.$params['font']['file']);

        $img = (isset($params['modify_original'])) ? $input_img: new gdImage($input_img->getData());

        // what is this for???
	    $safeText         = str_replace(array(' ', '-'), array('_', '_'), $text);

	    $leading          = (isset($params['text']['leading']) && !empty($params['text']['leading'])) ? $params['leading'] : false;
	    $position         = isset($params['text']['position']) ? $params['text']['position'] : array(3, 3);
        $textAngle        = isset($params['text']['angle']) ? $params['text']['angle']: 0;

	    // add shadow
	    if (isset($params['dropshadow']))
	    {
            $shadowRGB    = (isset($params['dropshadow']['color']) && is_array($params['dropshadow']['color']) && count($params['dropshadow']['color']) == 3) ? $params['dropshadow']['color'] : array(0,0,0);
            $shadowColor  = @imagecolorallocatealpha($img->getData(), $shadowRGB[0], $shadowRGB[1], $shadowRGB[2], 0);
            $shadowOffset = (isset($params['dropshadow']['offset']) && is_array($params['dropshadow']['offset']) && count($params['dropshadow']['offset']) == 2) ? $params['dropshadow']['offset'] : array(-2,2);

            if ($leading) //letter by letter
                self::createTextWithLeading($img->getData(), array($position[0]+$shadowOffset[0], $position[1]+$shadowOffset[1]), $text, $textAngle, $params['font']['file'], $params['font']['size'], $shadowColor, $leading);
            else
                imageTTFText($img->getData(), $params['font']['size'], $textAngle, $position[0] + $shadowOffset[0], ( $position[1]+$params['font']['size'] + $shadowOffset[1] ), $shadowColor, $params['font']['file'], $text);
    	}

		// text color
		$textRGB = (isset($params['text']['color']) && is_array($params['text']['color']) && count($params['text']['color']) == 3) ? $params['text']['color'] : array(0,0,0);

        // add text
	    if ($leading) //letter by letter
	    	gsTextImageProcessor::createTextWithLeading($img->getData(), $position, $text, $textAngle, $params['font']['file'], $params['font']['size'], $textRGB, $leading);
    	else
    		ImageTTFText($img->getData(), $params['font']['size'], $textAngle, $position[0], $position[1]+$params['font']['size'], $textRGB, $params['font']['file'], $text);

	    return $img;
    }

    private function createTextWithLeading(&$im, $textPosition, &$text, &$textAngle, &$font, &$fontSize, &$fontColor, &$leading)
    {
    	$xpos = $textPosition[0];
		$ypos = $textPosition[1];
		for ($i=0; $i<strlen($text); $i++)
		{
			$char = substr($text, $i, 1);
			$bb = ImageTTFBBox ($fontSize, 0, $font, $char);
			ImageTTFText ($im, $fontSize, 0, $xpos, $ypos+$fontSize, $fontColor, $font, $char);
			$xpos += $bb[4]+$leading;
		}
    }
}


