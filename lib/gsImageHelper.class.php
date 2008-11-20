<?php

/**
 * gsImageHelper 
 * 
 * @package gsImageProcessorPlugin
 * @version $id$
 * @copyright 1997-2005 The PHP Group
 * @author Tobias Schlitt <toby@php.net> 
 * @author Nathanael D. Noblet <nathanael@gnat.ca>
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */

class gsImageHelper 
{
 /**
     * _htmlHexToBinArray 
     * 
     * converts HTML hex colour value into integer array
     * 
     * @param mixed $hex 
     * @access private
     * @return void
     */
    static public function HTMLHexToBinArray($hex)
    {
        $hex = @preg_replace('/^#/', '', $hex);
        for ($i=0; $i<3; $i++)
        {
            $foo = substr($hex, 2*$i, 2); 
            $rgb[$i] = 16 * hexdec(substr($foo, 0, 1)) + hexdec(substr($foo, 1, 1)); 
        }
        return $rgb;
    }
}