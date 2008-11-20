<?php

class gsFileHelper
{
    static public function cleanFilename($filename)
    {
        $ext = '.'.self::getExtension($filename);

        return self::stripText(substr($filename,0, (-1 * strlen($ext)) ) ).$ext;
    }

    static public function getExtension($filename)
    {
        if(strpos($filename,'.') === false)
            return null;

        $ext = strtolower(substr($filename, (strrpos($filename, '.') ? strrpos($filename, '.') + 1 : strlen($filename)), strlen($filename)));

        return $ext;
    }

    public static function stripText($text)
    {
        $text = strtolower(strip_tags($text));

        // strip all non word chars
        $text = preg_replace('/\W/', ' ', $text);

        // replace all white space sections with a dash
        $text = preg_replace('/\ +/', '-', $text);

        // trim dashes
        $text = preg_replace('/\-$/', '', $text);
        $text = preg_replace('/^\-/', '', $text);

        return $text;
    }

}
