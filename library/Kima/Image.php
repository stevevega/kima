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
    const ERROR_CANNOT_WRITE_FILE = 'Cannot write on path "%s"';

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
        // make sure the file exists
        $this->validate_file($file);

        // get the image and new image resource
        $image = new Imagick($file);

        // set the image format
        $format = strtoupper($format);
        if (!in_array($format, $image->queryFormats()))
        {
            Error::set(sprintf(self::ERROR_FORMAT_NO_AVAILABLE, $format));
        }
        $image->setImageFormat($format);

        // create the thumbnail
        $image->cropThumbnailImage((int)$width, (int)$height);

        // remove potential insecure exif data
        $image->stripImage();

        // save the image
        $result = $this->save_image($image, $destination);
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
        // make sure the file exists
        $this->validate_file($file);

        // set the image format
        $image = new Imagick($file);
        $this->set_format($image, $format);

        // save image
        $result = $this->save_image($image, $destination);
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
        // make sure the file exists
        $this->validate_file($source);

        $image = new Imagick($source);

        if (!empty($format))
        {
            $this->set_format($image, $format);
        }

        // remove potential insecure exif data
        $image->stripImage();

        // save the image into the new location
        $result = $this->save_image($image, $destination);
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

    /**
     * Makes sure the image file exists and is readable
     * Throw error on failure
     * @param  string $file
     */
    private function validate_file($file)
    {
        if (!is_readable($file))
        {
            Error::set(sprintf(self::ERROR_INVALID_FILE, $file));
        }
    }

    /**
     * Writes and image in a desired destination
     * @param  Imagick $image The Imagick resource
     * @param  string $destination The destination
     * @return boolean
     */
    private function save_image(Imagick $image, $destination)
    {
        // saves the image to disk
        $image_path = dirname($destination);
        if (!is_writable($image_path))
        {
            Error::set(sprintf(self::ERROR_CANNOT_WRITE_FILE, $image_path));
        }

        return $image->writeImage($destination);
    }

}