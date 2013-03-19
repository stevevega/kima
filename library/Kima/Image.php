<?php
/**
 * Kima Image
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Error,
    \Imagick;

/**
 * Kima Image library
 */
class Image
{

    /**
     * Error messages
     */
    const ERROR_NO_IMAGICK = 'Imagick extension is not present on this server';
    const ERROR_FORMAT_NO_AVAILABLE = 'Image format "%s" is not available';
    const ERROR_INVALID_FILE = 'Cannot access file "%s"';

    /**
     * Imagick Extension
     */
    const IMAGICK_EXTENSION = 'imagick';

    /**
     * Constructor
     */
    public function __construct()
    {
        if (!extension_loaded(self::IMAGICK_EXTENSION))
        {
            Error::set(self::ERROR_NO_IMAGICK);
        }
    }

    /**
     * Creates a thumbnail of an image file
     * @param string $file
     * @param string $destination
     * @param int $width
     * @param int $height
     * @param string $format
     * @return boolean
     */
    public function thumbnail($file, $destination, $width, $height, $format = 'jpg')
    {
        if (!is_readable($file))
        {
            Error::set(sprintf(self::ERROR_INVALID_FILE, $file));
        }

        // get the image and new image resource
        $image = new Imagick($file);

        // remove potential insecure exif data
        $image->stripImage();

        // set the image format
        $format = strtoupper($format);
        if (!in_array($format, $image->queryFormats()))
        {
            Error::set(sprintf(self::ERROR_FORMAT_NO_AVAILABLE, $format));
        }
        $image->setImageFormat($format);

        // create the thumbnail
        $image->cropThumbnailImage((int)$width, (int)$height);

        // saves the image to disk
        $result = $image->writeImage($destination);
        $image->destroy();
        return $result;
    }

    /**
     * Transforms an image into another format
     * @param string $file
     * @param string $destination the path to write
     * @param string $format
     * @return boolean
     */
    protected function convert($file, $destination, $format)
    {
        if (!is_readable($file))
        {
            Error::set(sprintf(self::ERROR_INVALID_FILE, $file));
        }

        // set the image format
        $image = new Imagick($file);
        $this->set_format($image, $format);

        // save image
        $result = $image->writeImage($destination);
        $image->destroy();
        return $result;
    }

    /**
     * MOv a new image based on a source image
     * @param string $source
     * @param string $destination
     * @param string $format
     */
    public function move_uploaded_image($source, $destination, $format = '')
    {
        $image = new Imagick($source);

        if (!empty($format))
        {
            $this->set_format($image, $format);
        }

        // remove potential insecure exif data
        $image->stripImage();

        // save the image into the new location
        $result = $image->writeImage($destination);
        $image->destroy();

        // remove the source
        @unlink($source);

        return $result;
    }

    /**
     * Sets the image format if available
     * @param Imagick $image
     * @param string $format
     */
    protected function set_format(Imagick &$image, $format)
    {
        // set the image format
        $format = strtoupper($format);
        if (!in_array($format, $image->queryFormats()))
        {
            Error::set(sprintf(self::ERROR_FORMAT_NO_AVAILABLE, $format));
        }
        $image->setImageFormat($format);
    }

}