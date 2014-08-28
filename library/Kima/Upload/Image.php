<?php
/**
 * Kima Image Upload
 * @author Steve Vega
 */
namespace Kima\Upload;

use \Kima\Image as KimaImage;

/**
 * Kima Image Upload library
 */
class Image extends File
{

    /**
     * The image type to save the image should be one of $available_types
     * @var int
     */
    protected $save_as;

    /**
     * Sets the type as the file uploaded should be saved
     * @param string $format
     */
    public function save_as($format)
    {
        $this->save_as = $format;

        return $this;
    }

    /**
     * Transfer the uploaded file to its new destination
     * @param string $temp_file
     * @param string $folder
     * @param string $new_name
     */
    protected function transfer($temp_file, $folder, $new_name)
    {
        // set the destination path
        $path = $folder . DIRECTORY_SEPARATOR . $new_name;

        if (!is_uploaded_file($temp_file)) {
            return false;
        }

        if (!empty($this->save_as)) {
            // change the destination file extension
            $path_parts = pathinfo($path);
            $path = $path_parts['dirname'] . DIRECTORY_SEPARATOR . $path_parts['filename'];
            $path .= '.' . $this->save_as;
        }

        // move the file to the new location
        $image = new KimaImage($temp_file);

        return $image->move_uploaded_image($path, $this->save_as);
    }

}
