<?php
/**
 * Implementation of a result set where a file is returned to the client. The contents may come
 * from an actual file on the file system or data passed to this class when it is instantiated.
 */	
class FileResult extends AbstractResult {
	private $outputName;
	private $data;
	private $dataIsFile;
	
	/**
	 * Constructor.
	 * 
	 * @param object $action     Current action.
	 * @param string $mime       MIME-type of the file.
	 * @param string $charset    Charset of the file.
	 * @param string $data       Data contents to return directly or file name of the file.
	 * @param bool $isFile       <code>TRUE</code> if $data is a file name, <code>FALSE</code> if not.
	 * @param string $outputName Optional name to give the returned file, if different from $file when
	 *                           referring to an actual file.
	 */
	public function __construct ($action, $mime, $charset, $data, $isFile = false, $outputName = false) {
		parent::__construct($action, $mime, $charset);

		if (empty($data)) {
			throw new Exception('No data supplied');
		}

		$this->data = $data;
		$this->dataIsFile = $isFile;

		if (empty($outputName)) {
			// If actual file and no custom file name, set correct file name
			if ($isFile) {
				$this->outputName = basename($data);
			}
			else {
				throw new Exception('No output file name given');
		}
		else {
			$this->outputName = $outputName;
		}
	}

	/**
	 * Sends the file to the client.
	 */
	public function render ($request) {
		header("Content-Type: {$this->contentType}; charset={$this->charset}");
		header("Content-Disposition: attachment; filename=\"{$this->outputName}\"");

		if ($this->dataIsFile && is_file($this->data)) {
			header('Content-Length: ' . filesize($this->data));
			readfile($this->data);
		}
		else {
			header('Content-Length: ' . mb_strlen($this->data));
			echo $this->data;
		}
	}
}
?>
