<?php

/**
 * gsRotationProcessor
 *
 * @package gsImageProcessorPlugin
 * @version $id$
 * @copyright 2007 Gnat Solutions, Inc
 * @author Nathanael D. Noblet <nathanael@gnat.ca>
 * @license GPL Version 2
 * @description : colorizes photos.
 * @param $degree.
 */

class gsRotationProcessor
{
    static public function rotate(&$input_img, $params = array())
    {
        $img = isset($params['clone']) ? new gdImage($input_img->getData()): $input_img;

        if(!isset($params['degree']))
            throw new sfException('gsRotationProcessor missing required \'degree\' param.');

        $background = isset($params['background']) ? $params['background']:null;

        if($background)
        {
            $background = !is_array($background) ? gsImageHelper::HTMLHexToBinArray($background): $background;
            $background = @imagecolorallocate($img->getData(),$background[0],$background[1],$background[2]);
        }
        else
            $background = @imagecolorallocate($img->getData(),255,255,255);

        $img->setData(imagerotate($img->getData(), $params['degree'], $background, isset($params['ignore_transparent'])?$params['ignore_transparent']:null));

        return $img;
    }
}