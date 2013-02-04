<?php
/**
 * Kima Image
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Error;

/**
 * Kima Image library
 */
class Image
{

    /**
     * Error messages
     */
    const ERROR_NO_GD = 'GD extension is not present on this server';
    const ERROR_TYPE_NO_AVAILABLE = 'Image type "%s" is not available';
    const ERROR_INVALID_FILE = 'Cannot access file "%s"';
    const ERROR_INVALID_IMAGE_SOURCE = 'Cannot create image from source type "%s"';

    /**
     * Sets the available types for file uploads
     * @var array
     */
    public static $available_types = [
        IMAGETYPE_GIF,
        IMAGETYPE_JPEG,
        IMAGETYPE_PNG,
        IMAGETYPE_WBMP,
        IMAGETYPE_XBM];

    /**
     * GD Extension
     */
    const GD_EXTENSION = 'gd';

    /**
     * Constructor
     */
    public function __construct()
    {
        if (!extension_loaded(self::GD_EXTENSION))
        {
            Error::set(self::ERROR_NO_GD);
        }
    }

    /**
     * Transforms an image into another format
     * @param string $file
     * @param int $format one of the IMAGETYPE_XXX constants
     * @param string $destination the path to write
     * @return boolean
     */
    public function convert($file, $format, $destination)
    {
        if (!is_readable($file))
        {
            Error::set(sprintf(self::ERROR_INVALID_FILE, $file));
        }

        $image = $this->create_image_from_file($file);

        // Create image depending on IMAGETYPE_XXX constant
        switch ($format)
        {
            case IMAGETYPE_GIF:
                return imagegif($image, $destination);
                break;
            case IMAGETYPE_JPEG:
                return imagejpeg($image, $destination);
                break;
            case IMAGETYPE_PNG:
                return imagepng($image, $destination);
                break;
            case IMAGETYPE_WBMP:
                return imagewbmp($image, $destination);
                break;
            case IMAGETYPE_XBM:
                return imagexbm($image, $destination);
                break;
            default:
                Error::set(sprintf(self::ERROR_INVALID_IMAGE_SOURCE), image_type_to_mime_type($format));
                break;
        }
    }

    /**
     * Creates an image depending on the format
     * @param string $file
     */
    protected function create_image_from_file($file)
    {
        $size = getimagesize($file);

        // Create image depending on IMAGETYPE_XXX constant
        switch ($size[2])
        {
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($file);
                break;
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($file);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($file);
                break;
            case IMAGETYPE_WBMP:
                $image = imagecreatefromwbmp($file);
                break;
            case IMAGETYPE_XBM:
                $image = imagecreatefromxbm($file);
                break;
            default:
                Error::set(sprintf(self::ERROR_INVALID_IMAGE_SOURCE), image_type_to_mime_type($size[2]));
                break;
        }

        return $image;
    }

}