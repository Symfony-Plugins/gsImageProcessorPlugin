<?php

/**
 * gdImage
 *
 * @package gsImageProcessorPlugin
 * @version $id$
 * @copyright 2007 Gnat Solutions, Inc
 * @author Nathanael D. Noblet <nathanael@gnat.ca>
 */

class gdImage
{
    private $_img;
    private $_is_saved;
    private $_filename;
    private $_dirname;

    public function __construct($newImage = null)
    {
        $this->_img = $newImage;
        $this->_filename = null;
        $this->_dirname = null;
        $this->_is_saved = false;
    }

    public function __destruct()
    {
        if($this->_img != null)
            @($this->_img);
    }

    /**
     * loadImage
     *
     * @param mixed $filename
     * @param string $forceext
     * @access public
     * @return void
     */
    public function loadImage($filename, $forceext = '')
    {
        if (!@file_exists($filename))
            throw new sfException("The supplied file name '$filename' does not point to an existing file.");

        $ext = ($forceext == '') ? gsFileHelper::getExtension($filename) : $forceext;

        if(empty($ext))
        {
            $size = getimagesize($filename);
            $ext = str_replace("image/","",$size[2]);
        }

        if ($ext == 'jpg') $ext = 'jpeg';

        $func = "imagecreatefrom$ext";

        if(sfConfig::get('sf_logging_enabled'))
            sfLogger::getInstance()->debug('gd load image function type: '.$func);

        if (!function_exists($func))
        {
            if(sfConfig::get('sf_logging_enabled'))
                sfLogger::getInstance()->debug('gd function does not exist?? '.$func);

            $this->loadImageFromString(file_get_contents($filename));
        }
        else
        {
            if(sfConfig::get('sf_logging_enabled'))
            {
                sfLogger::getInstance()->debug('gd load image filename: '.$filename);
                sfLogger::getInstance()->debug('gd load image->calling: '.$func.'('.$filename.')');
            }

            $this->_img = $func($filename);
        }

        if ($this->_img == NULL || $this->_img === false)
            throw new sfException('The image could not be loaded.');

        return true;
    }

    /**
     * loadImageFromString
     *
     * @param mixed $string
     * @access public
     * @return void
     */
    public function loadImageFromString($string )
    {
        if(!$string)
            throw new sfException('The image could not be loaded from an (empty) string.');

        $this->_img = imagecreatefromstring($string);
        if ( $this->_img === false || $this->_img == NULL)
            throw new sfException('The image could not be loaded from a string.');
    }

    /**
     * getX
     *
     * @access public
     * @return void
     */
    public function getWidth()
    {
        return @ImageSX($this->_img);
    }

    /**
     * getY
     *
     * @access public
     * @return void
     */
    public function getHeight()
    {
        return @ImageSY($this->_img);
    }

    /**
     * getFilename
     *
     * @access public
     * @return void
     */
    public function getFilename()
    {
        return $this->_filename;
    }

    /**
     * getFullpath
     *
     * @access public
     * @return void
     */
    public function getFullpath()
    {
        return $this->_dirname.$this->_filename;
    }

    /**
     * getPath
     *
     * @access public
     * @return void
     */
    public function getPath()
    {
        return $this->_dirname;
    }

    /**
     * isSaved
     *
     * @access public
     * @return void
     */
    public function isSaved()
    {
        return $this->_is_saved;
    }

    /**
     * getData
     *
     * @access public
     * @return void
     */
    public function getData()
    {
        return $this->_img;
    }

    /**
     * setData
     *
     * @param mixed $newImage
     * @access public
     * @return void
     */
    public function setData($newImage = null)
    {
//        if($newImage != null)
            $this->_img = $newImage;
    }

    /**
     * save
     *
     * saves the image resource to the file system.  If no extension is given for $type
     * then it will attempt to save based on the file name.
     * $quality is a value from 1 to 100, inclusive, and only used when outputting as a jpg
     * if $trans is set to an HTML colour, the resulting image will be a png.
     * $img is the image resource
     *
     * @param mixed $filename
     * @param mixed $img
     * @param mixed $type
     * @param mixed $quality
     * @param string $trans transparent color for image
     * @access private
     * @return void
     */
    public function save($filename, $type = null, $quality= null, $trans = null)
    {
        $ext = ($type == '' ? gsFileHelper::getExtension($filename) : $type);
        if ($ext == 'jpg') $ext = 'jpeg';
        $func = "image$ext";

        if (!@function_exists($func))
            throw new sfException('No method found to save image of type '.$type);

        if($trans != null)
        {
            $colour = gsImageHelper::HTMLHexToBinArray($trans);
            $trans = imagecolorallocate($this->_img,$colour[0],$colour[1],$colour[2]);
            imagecolortransparent($this->_img,$trans);

            $filename = str_replace($ext,'png',$filename);
            $ext = 'png';
        }

        if ($ext == 'jpeg')
            $this->_is_saved= $func($this->_img, $filename, $quality);
        else
            $this->_is_saved = $func($this->_img, $filename);
        
        if (!$this->_is_saved) 
        {
            $err = error_get_last();
        	throw new sfException('Unable to save image to filesystem. '.$err['message']);
        }
        else
        {
            $this->_is_saved = true;
            $this->_filename = basename($filename);
            $this->_dirname = dirname($filename);
        }
    }

    public function delete()
    {
        if($this->_is_saved)
            return (unlink($this->_dirname.$this->_filename));
        else
            return false;
    }
}
