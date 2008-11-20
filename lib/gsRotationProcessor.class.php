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
    static public function rotate(&$img,$degree ,$background = '000000',$ret_new=false)
    {
        if($degree == 0)
        {
            throw new sfException('DEGREE == 0!!!');
            if($ret_new)
                return clone $img;
            else
                return $img;
        }

        if(!is_array($background))
        {
            $background = gsImageHelper::HTMLHexToBinArray($background);
            $background = @imagecolorallocate($img->getData(),$background[0],$background[1],$background[2]); 
        }

        if($ret_new)
        {
            $tmp = clone $img;
            $tmp->setData(imagerotate($tmp->getData(), $degree,$background));

            return $tmp;
        }
        else
        {
            $img->setData(imagerotate($img->getData(), $degree,$background));

            return $img;
        }
    }
}