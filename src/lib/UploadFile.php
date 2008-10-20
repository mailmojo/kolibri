<?php
require(ROOT . '/lib/File.php');

/**
 * This class extends <code>File</code> to encapsulate a file uploaded specifically over HTTP POST, to
 * include information regarding the upload.
 * 
 * @version		$Id: UploadFile.php 1510 2008-06-17 05:45:50Z anders $
 */
class UploadFile extends File {
	/**
	 * Original name of the file from the client.
	 * @var string
	 */
	private $originalName;

	/**
	 * Specifies whether this file is temporary, which is true if it's in PHPs temporary upload directory.
	 * @var bool
	 */
	private $isTemp;

	/**
	 * Error code associated with this file upload.
	 * @var int
	 */
	private $errorCode;
	
	/**
	 * Creates an instance of this class populated by the values specified.
	 * 
	 * @param string $name		Original name of the file from the client.
	 * @param string $tempName	Name of the file in PHPs temporary upload directory.
	 * @param int $errorCode	Error code associated with this file upload.
	 */
	public function __construct ($name, $tempName, $errorCode) {
		parent::__construct($tempName);
		$this->originalName	= $name;
		$this->errorCode	= $errorCode;
		$this->isTemp		= true;
	}
	
	/**
	 * Returns the original name of this file.
	 *
	 * @return string	Original name of the file.
	 */
	public function getOriginalName () {
		return $this->originalName;
	}
	
	/**
	 * Returns the error code associated with this file upload.
	 *
	 * @return int		Error code corresponding to the PHP file upload error codes.
	 */
	public function getErrorCode () {
		return $this->errorCode;
	}
	
	/**
	 * Attempts to move this file from its current storage to another location. <code>$to</code> can either
	 * be a directory path in which case the file will be named as the original file, or a full path
	 * including file name.
	 *
	 * @param string $to	File name or path to move to.
	 * @return bool			<code>TRUE</code> on success, <code>FALSE</code> on failure.
	 */
	public function move ($to) {
		if ($this->isTemp) {
			if (is_dir($to)) {
				$to = $this->appendFilenameToPath($to, $this->getOriginalName());
			}

			if (($status = move_uploaded_file($this->filename, $to)) !== false) {
				$this->isTemp	= false; // Moved out of temp
				$this->filename	= $to;
			}
		}
		else {
			$status = parent::move($to);
		}

		return $status;
	}
}
?>
