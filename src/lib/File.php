<?php
/**
 * This class encapsulates a file. Convenience methods are made availible to simplify file
 * handling, such as directly reading the contents of the file or moving it to another
 * location.
 */
class File {
	/**
	 * Absolute path to the file.
	 * @var string
	 */
	protected $filename;
	
	/**
	 * Creates an instance representing the file specified.
	 * 
	 * @param string $filename	Path to the file to represent.
	 */
	public function __construct ($filename) {
		$this->filename = $filename;
	}

    /**
     * Deletes the file.
     *
     * @return bool TRUE if successful, FALSE upon failure.
     */
    public function delete () {
        if (is_writable($this->getName())) {
            return unlink($this->getName());
        }
        return false;
    }

	/**
	 * Reads and returns the contents of this file as a string.
	 *
	 * @return string	Contents of this file.
	 */
	public function read () {
		return file_get_contents($this->filename);
	}
	
	/**
	 * Writes content to this file. Default is to overwrite the existing file content, but
	 * appending is also supported.
	 *
	 * @param string $content The new content.
	 * @param boolean $append Specifies if new content should be appended instead of
	 *                        overwriting the old. Default to <code>FALSE</code>, meaning all
	 *                        content is replaced.
	 * @return int            Bytes written, or <code>FALSE</code> on errors.
	 */
	public function write ($content, $append = false) {
		$mode = ($append ? 'a' : 'w');
		
		if (($f = fopen($this->filename, $mode)) !== false) {
			$written = fwrite($f, $content);
			fclose($f);
			
			return $written;
		}
		
		return false;
	}
	
	/**
	 * Returns the full name of this file.
	 *
	 * @return string	Name of file.
	 */
	public function getName () {
		return $this->filename;
	}
	
	/**
	 * Returns the MIME type of this file. The type is determined by the Fileinfo module if
	 * enabled, or else through the OS <code>file</code> utility on non-Windows systems.
	 *
	 * @return string	MIME type of file.
	 */
	public function getType () {
		if (class_exists('finfo', false)) {
			$finfo = new finfo(FILEINFO_MIME);
			$mime = $finfo->file($this->filename);
		}
		else if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
			$mime = trim(exec('file --brief --mime ' . escapeshellarg($this->filename)));
		}

		if ($mime !== false) {
			$parts = explode(';', $mime);
			return $parts[0];
		}
		return false;
	}
	
	/**
	 * Returns the size of this file in bytes.
	 *
	 * @return string	Size of file (bytes).
	 */
	public function getSize () {
		return filesize($this->filename);
	}
	
	/**
	 * Attempts to move this file from its current location to another. <code>$to</code> can
	 * either be a directory path in which case the file will be named as the original file,
	 * or a full path including file name.
	 *
	 * @param string $to Path to new location.
	 * @return bool      <code>TRUE</code> on success, <code>FALSE</code> on failure.
	 */
	public function move ($to, $keepOriginal = false) {
		if (is_dir($to)) {
			$to = $this->appendFilenameToPath($to, basename($this->filename));
		}
		$method = ($keepOriginal === true ? 'copy' : 'rename');
		
		// Update current filename (path) if the move was successful
		if ($status = $method($this->filename, $to)) {
			$this->filename = $to;
		}

		return $status;
	}

	/**
	 * Attempts to copy this file from its current location to another. Works like
	 * File::move() except it retains the original file at its current location as well.
	 *
	 * @param string $to Path to new location.
	 * @return bool      <code>TRUE</code> on success, <code>FALSE</code> on failure.
	 */
	public function copy ($to) {
		return $this->move($to, true);
	}

	/**
	 * Appends <code>$file</code> onto the full <code>$path</code>, taking care to make sure
	 * the path ends with a slash before appending the file name.
	 *
	 * @param string $path Base path.
	 * @param string $file File to append onto $path.
	 * @return string      The path with file name appended.
	 */
	protected function appendFilenameToPath ($path, $file) {
		if (substr($path, strlen($path) - 1) == '/') {
			$path = $path . $file;
		}
		else {
			$path = $path . '/' . $file;
		}
		return $path;
	}
}
?>
