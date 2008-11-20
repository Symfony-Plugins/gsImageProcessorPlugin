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

	static public function overlayText(&$input_img, $params = array())
    {
        if( !($input_img instanceof gdImage))
            throw new sfException('Cannot create text overlay on a non-existant image.');

    	if(!isset($params['font']) || empty($params['font']))
            throw new sfException('Cannot create a text overlay without a font!');

    	if(!isset($params['text']) || empty($params['text']))
            throw new sfException('Cannot create a text overlay without text parameters!');

        if(!isset($params['font']['file']) || !is_file(sfConfig::get('sf_root_dir').$params['font']['file']))
            throw new sfException('Cannot use a non-existent font. '.sfConfig::get('sf_root_dir').$params['font']['file']);
        else
            $fontFile = sfConfig::get('sf_root_dir').$params['font']['file'];

        $img = (isset($params['modify_original'])) ? $input_img: new gdImage($input_img->getData());


	    $leading          = (isset($params['text']['leading']) && !empty($params['text']['leading'])) ? $params['leading'] : false;

        $dims = (!isset($params['font']['dims'])) ? imagettfbbox($params['font']['size'], 0, sfConfig::get('sf_root_dir').$params['font']['file'], $params['text']['content']):$params['font']['dims'];
	    $position         = (isset($params['text']['position'])) ? $params['text']['position'] : array(3, 3);

        /*
         * Now, we find where we need to place the x-coord of the font's baseline.
         * This is usually the same as the x-coord of the bottom-left corner,
         * give or take a pixel or two
         */
        $x = (($dims[0] >= -1) ? abs($dims[0] + 1) * -1: abs($dims[0] + 2))+$position[0];

        /*
         * This is the important one.  For reasons I admittedly don't fully understand,
         * the y-coord of the top right (or left) corner of the bounding box is
         * a negative value equal to the inverse of the baseline height of the
         * font: I.E. if the b-line height = 24px, then the y-coord of the top-
         * right = -24. So we set the baseline-y position of the text to the
         * absolute value of the y-coord of the top-right corner, plus one pixel
         * to account for anti-aliasing.
         */
        $y = abs($dims[5] + 1)+$position[1];

        // FIX ME This won't work with autosized images because the autosizer doesn't pay attention to angles
        $textAngle        = (isset($params['text']['angle'])) ? $params['text']['angle']: 0;

	    // add shadow
	    if (isset($params['dropshadow']))
	    {
            $shadowRGB    = (isset($params['dropshadow']['color']) && is_array($params['dropshadow']['color']) && count($params['dropshadow']['color']) == 3) ? $params['dropshadow']['color'] : ((isset($params['dropshadow']['color']) && !is_array($params['dropshadow']['color'])) ? gsImageHelper::HTMLHexToBinArray($params['dropshadow']['color']): array(0,0,0));
            $shadowColor  = @imagecolorallocatealpha($img->getData(), $shadowRGB[0], $shadowRGB[1], $shadowRGB[2], 0);
            $shadowOffset = (isset($params['dropshadow']['offset']) && is_array($params['dropshadow']['offset']) && count($params['dropshadow']['offset']) == 2) ? $params['dropshadow']['offset'] : array(-2,2);

            if ($leading) //letter by letter
                self::createTextWithLeading($img->getData(), array($x+$shadowOffset[0], $y+$shadowOffset[1]), $params['text']['content'], $textAngle, $fontFile, $params['font']['size'], $shadowColor, $leading);
            else
                imagettftext($img->getData(), $params['font']['size'], $textAngle, $x + $shadowOffset[0], ( $y + $shadowOffset[1] ), $shadowColor, $fontFile, $params['text']['content']);
    	}

		// text color
		$textRGB   = (isset($params['text']['color']) && is_array($params['text']['color']) && count($params['text']['color']) == 3) ? $params['text']['color'] : ((isset($params['text']['color']) && !is_array($params['text']['color'])) ? gsImageHelper::HTMLHexToBinArray($params['text']['color']): array(0,0,0));
        $textColor = imagecolorallocate($img->getData(),$textRGB[0],$textRGB[1],$textRGB[2]);

        // add text
	    if ($leading) //letter by letter
	    	gsTextImageProcessor::createTextWithLeading($img->getData(), array($x,$y), $params['text']['content'], $textAngle, $fontFile, $params['font']['size'], $textColor, $leading);
    	else
    		imagettftext($img->getData(), $params['font']['size'], $textAngle, $x, $y, $textColor, $fontFile, $params['text']['content']);

	    return $img;
    }

    // FIX ME This won't work with autosized images because the autosizer doesn't pay attention to that
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


