<?php
/**
 * Kima File
 * @author Steve Vega
 */
namespace Kima\Upload;

use Kima\Error;
use Kima\Http\Request;
use finfo;

/**
 * Kima File Upload library
 */
class File
{

    /**
     * Error messages
     */
    const ERROR_NO_FILES = 'No file inputs were found';
    const ERROR_NO_FILE_INPUT = 'File input "%s" was not found';
    const ERROR_INVALID_FOLDER = 'Cannot write on folder "%s"';

    /**
     * Upload errors
     */
    const ERROR_INI_SIZE = 'File exceeds the upload_max_filesize directive in php.ini (%s)';
    const ERROR_FORM_SIZE = 'File exceeds the MAX_FILE_SIZE directive specified in the HTML form';
    const ERROR_PARTIAL_UPLOAD = 'File was only partially uploaded';
    const ERROR_NO_FILE = 'No file was uploaded';
    const ERROR_NO_TMP_DIR = 'Missing a temporary folder';
    const ERROR_CANT_WRITE = 'Failed to write file to disk';
    const ERROR_PHP_EXTENSION = 'File upload stopped by a PHP extension';
    const ERROR_FILE_SIZE = 'File size exceeds the limit (%s)';
    const ERROR_FILE_TYPE = 'File type not allowed';
    const ERROR_FILE_EXTENSION = 'File extension not allowed';
    const ERROR_UNABLE_TO_MOVE = 'Unable to move file to new destination';
    const ERROR_UNKOWN = 'Unknown upload error';

    /**
     * Upload Error values
     */
    const ERROR_CODE_INI_SIZE = UPLOAD_ERR_INI_SIZE;        // 1
    const ERROR_CODE_FORM_SIZE = UPLOAD_ERR_FORM_SIZE;      // 2
    const ERROR_CODE_PARTIAL_UPLOAD = UPLOAD_ERR_PARTIAL;   // 3
    const ERROR_CODE_NO_FILE = UPLOAD_ERR_NO_FILE;          // 4
    const ERROR_CODE_NO_TMP_DIR = UPLOAD_ERR_NO_TMP_DIR;    // 6
    const ERROR_CODE_CANT_WRITE = UPLOAD_ERR_CANT_WRITE;    // 7
    const ERROR_CODE_PHP_EXTENSION = UPLOAD_ERR_EXTENSION;  // 8
    const ERROR_CODE_FILE_SIZE = 10;
    const ERROR_CODE_FILE_TYPE = 11;
    const ERROR_CODE_FILE_EXTENSION = 12;
    const ERROR_CODE_UNABLE_TO_MOVE = 13;
    const ERROR_CODE_UNKOWN = 66;

    /**
     * Error messages for file uploads
     * @var array
     */
    protected $error_messages;

    /**
     * Set the allowed types for upload
     * Key => extension, Value => Mime type
     * @var array
     */
    protected $allowed_types;

    /**
     * Set the max allowed size (in bytes) for upload
     * @var int
     */
    protected $max_allowed_size;

    /**
     * Name key used for multiple file uploads with same name
     * @var int
     */
    protected $name_key;

    /**
     * Whether it should check for mime/type and extension as key value strict mode
     * @var boolean
     */
    protected $strict_type_validation;

    /**
     * Uploads a file into the desired location
     * @param  string  $input
     * @param  string  $folder
     * @param  string  $name   the desired file name, on multiple files: name_1, name_2, name_n
     * @return boolean
     */
    public function upload($input, $folder, $name = '')
    {
        // make sure the file input exists
        if (empty($_FILES[$input])) {
            if (!$this->exceeds_post_max_size()) {
                Error::set(sprintf(self::ERROR_NO_FILE_INPUT, $input));
            }

            return;
        }

        // make sure the folder is writable
        if (!is_writable($folder)) {
            Error::set(sprintf(self::ERROR_INVALID_FOLDER, $folder));
        }

        // stores whether all files were uploaded successfully or not
        $all_succeed = true;

        // get $_FILES as a rearranged array
        $files = $this->rearrange_files($_FILES[$input]);

        // set the name key if required
        if (!empty($name) && empty($this->name_key) && count($files) > 1) {
            $this->name_key = 1;
        }

        // upload each file
        foreach ($files as $file) {
            if ($this->is_successful_upload($file['error']) && $this->is_valid_file($file)) {
                // set the file path/name
                if (!empty($name)) {
                    $name = (string) $name;
                    $new_name = empty($this->name_key) ? $name : $name . '_' . $this->name_key;

                    $this->name_key++;
                } else {
                    $new_name = $file['name'];
                }

                $this->apply_custom_modifications($file['tmp_name']);

                // try to move the uploaded file to its destination
                if (!$this->transfer($file['tmp_name'], $folder, $new_name)) {
                    $this->set_error_message(self::ERROR_CODE_UNABLE_TO_MOVE);
                    $all_succeed = false;
                }
            } else {
                $all_succeed = false;
            }
        }

        return $all_succeed;
    }

    /**
     * Uploads all files to the destination folder
     * @param  string   $folder
     * @param  string   $name
     * @return $boolean
     */
    public function upload_all($folder, $name = '')
    {
        // make sure the file input exists
        if (empty($_FILES)) {
            Error::set(self::ERROR_NO_FILES);
        }

        // stores whether all files were uploaded successfully or not
        $all_succeed = true;

        // set name key if required
        if (!empty($name) && count($_FILES) > 1) {
            $this->name_key = 1;
        }

        // upload all files
        foreach ($_FILES as $input => $file) {
            if (!$this->upload($input, $folder, $name)) {
                $all_succeed = false;
            }
        }

        return $all_succeed;
    }

    /**
     * Sets the allowed types for upload
     * @see http://www.iana.org/assignments/media-types
     * @param  array $allowed_types An key/value array of extension/allowed mime types
     * @return File
     */
    public function set_allowed_types(array $allowed_types)
    {
        $this->allowed_types = $allowed_types;

        return $this;
    }

    /**
     * Sets the max allowed size (in bytes) for a file
     * @param  int  $max_allowed_size
     * @return File
     */
    public function set_max_allowed_size($max_allowed_size)
    {
        $this->max_allowed_size = (int) $max_allowed_size;

        return $this;
    }

    /**
     * Gets the errors on file uploads
     * @return @array
     */
    public function get_errors()
    {
        return $this->error_messages;
    }

    /**
     * Sets the strict type validation on
     * @return File
     */
    public function set_strict_type_validation()
    {
        $this->strict_type_validation = true;

        return $this;
    }

    /**
     * Rearrange $_FILES array to make it easier to work with
     * @param array
     * @return array
     */
    protected function rearrange_files(array $files)
    {
        $rearranged_files = [];

        // check if it is a single or multiple upload
        if (is_array($files['error'])) {
            // rearrange multiple files array
            foreach ($files as $input => $values) {
                foreach ($values as $key => $value) {
                    $rearranged_files[$key][$input] = $value;
                }
            }
        } else {
            $rearranged_files[] = $files;
        }

        return $rearranged_files;
    }

    /**
     * Check whether the file upload was successful or not
     * @param  int      $error
     * @return $boolean
     */
    protected function is_successful_upload($error)
    {
        if (UPLOAD_ERR_OK === $error) {
            return true;
        }

        // set error messages
        $this->set_error_message($error);

        return false;
    }

    /**
     * Checks if the file upload attempt is valid
     * @param  array   $file
     * @return boolean
     */
    protected function is_valid_file(array $file)
    {
        // check the file size if required
        if ($this->max_allowed_size > 0 && !$this->is_valid_size($file)) {
            return false;
        }

        // check the file type if required
        if (!empty($this->allowed_types) && !$this->is_valid_type($file)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the file size is valid
     * @param  array   $file
     * @return boolean
     */
    protected function is_valid_size(array $file)
    {
        $file_size = @filesize($file['tmp_name']);
        if (!$file_size || $file_size > $this->max_allowed_size) {
            $this->set_error_message(self::ERROR_CODE_FILE_SIZE);

            return false;
        }

        return true;
    }

    /**
     * Checks if the file mime type is valid
     * @param  array   $file
     * @return boolean
     */
    protected function is_valid_type(array $file)
    {
        // get the mime type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $type = $finfo->file($file['tmp_name']);

        if (!in_array($type, $this->allowed_types)) {
            $this->set_error_message(self::ERROR_CODE_FILE_TYPE);

            return false;
        }

        // the mime type was ok, now check the extension match
        $name_parts = explode('.', $file['name']);
        $extension = strtolower(end($name_parts));

        // check the extension strict or normal mode
        if (true === $this->strict_type_validation) {
            // strict mode makes sure the key value set in allowed types match
            if ($extension !== array_search($type, $this->allowed_types)) {
                $this->set_error_message(self::ERROR_CODE_FILE_EXTENSION);

                return false;
            }
        } else {
            // normal mode just check the extension was set for any type
            $allowed_extensions = array_keys($this->allowed_types);
            if (!in_array($extension, $allowed_extensions)) {
                $this->set_error_message(self::ERROR_CODE_FILE_EXTENSION);

                return false;
            }
        }

        return true;
    }

    /**
     * Transfer the uploaded file to its new destination
     * @param string $temp_file
     * @param string $folder
     * @param string $new_name
     */
    protected function transfer($temp_file, $folder, $new_name)
    {
        // move the file to the new location
        $path = $folder . DIRECTORY_SEPARATOR . $new_name;

        return move_uploaded_file($temp_file, $path);
    }

    /**
     * Sets a file upload error message
     * @param int $error
     */
    protected function set_error_message($error)
    {
        switch ($error) {
            case self::ERROR_CODE_INI_SIZE:
                $post_max_size = ini_get('post_max_size');
                $message = sprintf(self::ERROR_INI_SIZE, $post_max_size);
                break;
            case self::ERROR_CODE_INI_SIZE:
                $message = self::ERROR_INI_SIZE;
                break;
            case self::ERROR_CODE_PARTIAL_UPLOAD:
                $message = self::ERROR_PARTIAL_UPLOAD;
                break;
            case self::ERROR_CODE_NO_FILE:
                $message = self::ERROR_NO_FILE;
                break;
            case self::ERROR_CODE_NO_TMP_DIR:
                $message = self::ERROR_NO_TMP_DIR;
                break;
            case self::ERROR_CODE_CANT_WRITE:
                $message = self::ERROR_CANT_WRITE;
                break;
            case self::ERROR_CODE_PHP_EXTENSION:
                $message = self::ERROR_PHP_EXTENSION;
                break;
            case self::ERROR_CODE_FILE_SIZE:
                $message = sprintf(self::ERROR_FILE_SIZE, $this->max_allowed_size);
                break;
            case self::ERROR_CODE_FILE_TYPE:
                $message = self::ERROR_FILE_TYPE;
                break;
            case self::ERROR_CODE_FILE_EXTENSION:
                $message = self::ERROR_FILE_EXTENSION;
                break;
            case self::ERROR_CODE_UNABLE_TO_MOVE:
                $message = self::ERROR_UNABLE_TO_MOVE;
            default:
                $message = self::ERROR_UNKOWN;
                break;
        }

        $this->error_messages[] = ['error_code' => $error, 'message' => $message];
    }

    /**
     * Checks whether the post max size was exceeced or not
     * @see http://us3.php.net/manual/en/features.file-upload.php#73762
     * @return boolean
     */
    protected function exceeds_post_max_size()
    {
        // get the max post size set in the config ini and the value unit used
        $post_max_size = ini_get('post_max_size');
        $unit = strtoupper(substr($post_max_size, -1));

        // set the unit multiplier to convert to bytes
        switch ($unit) {
            case 'M':
                $multiplier = 1048576;
                break;
            case 'K':
                $multiplier = 1024;
                break;
            case 'G':
                $multiplier = 1073741824;
                break;
            default:
                $multiplier = 1;
                break;
        }

        // get the post size and the max post size value in bytes
        $post_size = (int) Request::server('CONTENT_LENGTH');
        $post_max_size_bytes = $multiplier * (int) $post_max_size;

        // check if the post size exceeded the max allowed size
        if ($post_max_size && $post_size > $post_max_size_bytes) {
            $this->set_error_message(self::ERROR_CODE_INI_SIZE);

            return true;
        }

        return false;
    }

    /**
     * Applies custom modifications to the file been uploaded.
     */
    protected function apply_custom_modifications($temp_file = '') {}

}
