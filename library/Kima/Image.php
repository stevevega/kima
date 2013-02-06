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
     * Creates a thumbnail of an image file
     * @param string $file
     * @param string $destination
     * @param int $max_width
     * @param int $max_height
     * @param int $format IMAGETYPE_XXX constant
     * @return boolean
     */
    public function thumbnail($file, $destination, $width_new, $height_new, $format = 0)
    {
        if (!is_readable($file))
        {
            Error::set(sprintf(self::ERROR_INVALID_FILE, $file));
        }

        // get the image and new image resource
        $image = $this->create_image_from_file($file);
        $image_new = imagecreatetruecolor($width_new, $height_new);

        // get the image size and witdh
        $width = imagesx($image);
        $height = imagesy($image);

        // Get palette size for original image
        $palette_size = ImageColorsTotal($image);

        // Assign the color palette to new image
        for ($i = 0; $i < $palette_size; $i++)
        {
            $colors = ImageColorsForIndex($image, $i);
            ImageColorAllocate($image_new, $colors['red'], $colors['green'], $colors['blue']);
        }

        // set white background
        $white_background = imagecolorallocate($image_new, 255, 255, 255);
        imagefill($image_new, 0, 0, $white_background);

        // get the thumbnail values
        if ($width > $height)
        {
            $ratio = $width_new / $width;
            $adjusted_width = $width_new;
            $adjusted_height = $height * $ratio;
            $x = 0;
            $y = ($height_new - $adjusted_height) / 2;
        }
        else
        {
            $ratio = $height_new / $height;
            $adjusted_height = $height_new;
            $adjusted_width = $width * $ratio;
            $x = ($width_new - $adjusted_width) / 2;
            $y = 0;
        }

        // creates the thumbnail
        imagecopyresampled($image_new, $image, $x, $y, 0, 0, $adjusted_width, $adjusted_height, $width, $height);

        // set the image format
        if (!empty($format))
        {
            if (!in_array($format, self::$available_types))
            {
                Error::set(self::ERROR_TYPE_NO_AVAILABLE, $format);
            }
        }
        else
        {
            $format = $this->get_image_format($file);
        }

        // saves the image to disk
        return $this->save($image_new, $destination, $format);
    }

    /**
     * Transforms an image into another format
     * @param string $file
     * @param string $destination the path to write
     * @param int $format one of the IMAGETYPE_XXX constants
     * @return boolean
     */
    public function convert($file, $destination, $format)
    {
        if (!is_readable($file))
        {
            Error::set(sprintf(self::ERROR_INVALID_FILE, $file));
        }

        $image = $this->create_image_from_file($file);
        return $this->save($image, $destination, $format);
    }

    /**
     * Saves an image resource to disk
     * @param image $image
     * @param string $destination
     * @param int $format IMAGETYPE_XXX constant
     * @return boolean
     */
    protected function save($image, $destination, $format)
    {
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
        $format = $this->get_image_format($file);

        // Create image depending on IMAGETYPE_XXX constant
        switch ($format)
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
                Error::set(sprintf(self::ERROR_INVALID_IMAGE_SOURCE), image_type_to_mime_type($format));
                break;
        }

        return $image;
    }

    /**
     * Gets the image format
     * @param string $file
     * @return int IMAGETYPE_XXX constant
     */
    protected function get_image_format($file)
    {
        $size = getimagesize($file);
        return $size[2];
    }

}