<?php
/**
 * Response implementation where a file is returned to the client. The contents may come
 * from an actual file or data passed to this class.
 */
class FileResponse extends Response {
	private $outputName;
	private $dataIsFile;

	/**
	 * Initialize this response.
	 *
	 * @param mixed $data        File data to return or a file name of the file.
	 * @param string $mime       MIME-type of the file.
	 * @param string $charset    Charset of the file.
	 * @param bool $isFile       <code>true</code> if $data is a file name,
	 *                           <code>false</code> if not.
	 * @param string $outputName Name to give the returned file. Optional when $file is a file
	 *                           name.
	 */
	public function __construct ($data, $mime, $charset, $isFile = false, $outputName = null) {
		parent::__construct($data, 200, $mime, $charset);

		if (empty($data)) {
			throw new Exception('No file data or file path supplied.');
		}

		if ($isFile) {
			if (!file_exists($data)) {
				throw new Exception("File $data to return to user does not exist.");
			}
		}

		if ($outputName === null) {
			// If actual file and no custom file name, set correct file name
			if ($isFile) {
				$this->outputName = basename($data);
			}
			else {
				throw new Exception('No output file name given.');
			}
		}
		else {
			$this->outputName = $outputName;
		}

		$this->dataIsFile = $isFile;
	}

	/**
	 * Sends the file to the client.
	 */
	public function render ($request) {
		$this->setHeader('Content-Disposition', "attachment; filename=\"$this->outputName\"");

		if ($this->dataIsFile) {
			$this->setHeader('Content-Length', filesize($this->data));
			$this->sendHeaders();
			readfile($this->data);
		}
		else {
			$this->setHeader('Content-Length', strlen($this->data));
			$this->sendHeaders();
			echo $this->data;
		}
	}
}
?>
