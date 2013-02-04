<?php
/**
 * Kima Image Upload
 * @author Steve Vega
 */
namespace Kima\Upload;

use \Kima\Error,
    \Kima\Image as KimaImage,
    \Kima\Upload\File;

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
     * Sets the allowed types for upload
     * @param array $allowed_types An array of allowed mime types
     * @return File
     */
    public function set_allowed_types(array $allowed_types)
    {
        foreach ($allowed_types as $type)
        {
            // Check if the image type is available
            if (!in_array($type, KimaImage::$available_types))
            {
                Error::set(sprintf(KimaImage::ERROR_TYPE_NO_AVAILABLE, $type));
            }

            $extension = substr($this->get_extension($type), 1);
            $file_type = image_type_to_mime_type($type);
            $this->allowed_types[$extension] = $file_type;
        }

        return $this;
    }

    /**
     * Sets the type as the file uploaded should be saved
     */
    public function save_as($type)
    {
        // check the image type is valid
        if (!in_array($type, KimaImage::$available_types))
        {
            Error::set(sprintf(KimaImage::ERROR_TYPE_NO_AVAILABLE, $type));
        }

        $this->save_as = $type;
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

        if (!empty($this->save_as))
        {
            if (!is_uploaded_file($temp_file))
            {
                return false;
            }

            // change the destination file extension
            $path_parts = pathinfo($path);
            $path = $path_parts['dirname'] . DIRECTORY_SEPARATOR . $path_parts['filename'];
            $path .= $this->get_extension($this->save_as);

            $image = new KimaImage();
            return $image->convert($temp_file, $this->save_as, $path);
        }
        else
        {
            // move the file to the new location
            return move_uploaded_file($temp_file, $path);
        }
    }

    /**
     * Gets the file extension for a type
     * @param int $type A IMAGETYPE_XXX constant
     * @return string
     */
    protected function get_extension($type)
    {
        $extension = image_type_to_extension($type);
        return '.jpeg' === $extension ? '.jpg' : $extension;
    }

}