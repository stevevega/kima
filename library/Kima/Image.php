<?php
/**
 * Kima Image
 * @author Steve Vega
 */
namespace Kima;

use Imagick;

/**
 * Kima Image library
 */
class Image extends Imagick
{

    /**
     * Stores locally the file passed in the constructor
     */
    private $image_file;

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
    public function __construct($file)
    {
        if (!extension_loaded(self::IMAGICK_EXTENSION)) {
            Error::set(self::ERROR_NO_IMAGICK);
        }
        // make sure the file exists
        $this->validate_file($file);

        // Pass images to parent Imageck object
        parent::__construct($file);

        $this->image_file = $file;
    }

    /**
     * Creates a thumbnail of an image file, if the image does not fit the specified dimmesions
     * after resizing, it will be cropped
     * @param  string  $destination
     * @param  int     $width
     * @param  int     $height
     * @param  string  $format
     * @return boolean
     */
    public function cropThumbnail($destination, $width, $height, $format = 'jpg')
    {
        // set the image format
        $this->set_format($format);

        // create the thumbnail
        $this->cropThumbnailImage((int) $width, (int) $height);

        // remove potential insecure exif data
        $this->stripImage();

        // save the image
        $result = $this->save_image($this, $destination);
        $this->destroy();

        return $result;
    }

    /**
     * Creates a thumbnail of an image file
     * @param  string  $destination
     * @param  int     $width
     * @param  int     $height
     * @param  string  $format
     * @param  string  $best_fit
     * @return boolean
     */
    public function thumbnail($destination, $width, $height, $format = 'jpg', $best_fit = false)
    {
        // set the image format
        $this->set_format($format);

        // create the thumbnail
        $this->thumbnailImage((int) $width, (int) $height, $best_fit);

        // remove potential insecure exif data
        $this->stripImage();

        // save the image
        $result = $this->save_image($this, $destination);
        $this->destroy();

        return $result;
    }

    /**
     * Transforms an image into another format
     * @param  string  $destination the path to write
     * @param  string  $format
     * @return boolean
     */
    protected function convert($destination, $format)
    {
        // set the image format
        $this->set_format($format);

        // save image
        $result = $this->save_image($this, $destination);
        $this->destroy();

        return $result;
    }

    /**
     * MOv a new image based on a source image
     * @param string $destination
     * @param string $format
     */
    public function move_uploaded_image($destination, $format = '')
    {
        if (!empty($format)) {
            $this->set_format($format);
        }

        // remove potential insecure exif data
        $this->stripImage();

        // save the image into the new location
        $result = $this->save_image($this, $destination);
        $this->destroy();

        // remove the source
        @unlink($this->image_file);

        return $result;
    }

    /**
     * Sets the image format if available
     * @param Imagick $image
     * @param string  $format
     */
    protected function set_format($format)
    {
        // set the image format
        $format = strtoupper($format);
        if (!in_array($format, $this->queryFormats())) {
            Error::set(sprintf(self::ERROR_FORMAT_NO_AVAILABLE, $format));
        }
        $this->setImageFormat($format);
    }

    /**
     * Makes sure the image file exists and is readable
     * Throw error on failure
     * @param string $file
     */
    private function validate_file($file)
    {
        if (!is_readable($file) || !is_file($file)) {
            Error::set(sprintf(self::ERROR_INVALID_FILE, $file));
        }
    }

    /**
     * Writes and image in a desired destination
     * @param  Imagick $image       The Imagick resource
     * @param  string  $destination The destination
     * @return boolean
     */
    private function save_image(Imagick $image, $destination)
    {
        // saves the image to disk
        $image_path = dirname($destination);
        if (!is_writable($image_path)) {
            Error::set(sprintf(self::ERROR_CANNOT_WRITE_FILE, $image_path));
        }

        return $image->writeImage($destination);
    }

    /**
     * Reads the EXIF information and rotates the images accordingly
     * so that it is normalized.
     * @return boolean
     */
    public function fix_exif_rotation()
    {
        $orientation = $this->getImageOrientation();

        switch ($orientation) {
            case Imagick::ORIENTATION_BOTTOMRIGHT:
                $this->rotateimage("#000", 180); // rotate 180 degrees
            break;

            case Imagick::ORIENTATION_RIGHTTOP:
                $this->rotateimage("#000", 90); // rotate 90 degrees
            break;

            case Imagick::ORIENTATION_LEFTBOTTOM:
                $this->rotateimage("#000", -90); // rotate 90 degrees
            break;
        }

        $this->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);

        $this->save_image($this, $this->image_file);

    }

}
