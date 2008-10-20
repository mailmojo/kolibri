<?php
class FileValidator {
	private $model;

	public function __construct ($model) {
		$this->model = $model;
	}

	public function validate ($property, $rules) {
		if (empty($this->model->$property)) {
			return true;
		}

		if (!$this->model->$property instanceof UploadFile) {
			return array('file' => $rules['name']);
		}

		// Check PHP upload error codes
		$code = $this->model->$property->getErrorCode();
		switch ($code) {
			case UPLOAD_ERR_INI_SIZE:
				return array('file_ini_size' => $rules['name']);
			case UPLOAD_ERR_FORM_SIZE:
				$maxMsg = (isset($rules['max']) ? $rules['max'] : '???');
				return array('maxsize' => array($rules['name'], $maxMsg));
			case UPLOAD_ERR_PARTIAL:
				return array('file_partial' => $rules['name']);
			default:
		}

		// Check file type
		$type = (isset($rules['type']) ? $rules['type'] : null);
		if ($type !== null) {
			$filetype = $this->model->$property->getType();

			if (is_array($type)) {
				if (!in_array($filetype, $type)) {
					/*
					 * File type not in list of allowed types. Strip the general part of the MIME type to
					 * make the type more readable (i.e. image/gif renamed to gif).
					 */
					foreach ($type as $i => $t) {
						$type[$i] = substr($t, strpos($t, '/') + 1);
					}

					return array('type' => array($rules['name'], implode(', ', $type)));
				}
			}
			else {
				if ($filetype != $type) {
					$type = substr($type, strpos($type, '/') + 1); // Strip general MIME part as above
					return array('type' => array($rules['name'], $type));
				}
			}
		}

		// Normalize size restriction values for comparison to bytes
        $bytes = null;
		if (isset($rules['minsize'])) {
			$bytes['minsize'] = $this->normalizeSize($rules['minsize']);
		}
		if (isset($rules['maxsize'])) {
			$bytes['maxsize'] = $this->normalizeSize($rules['maxsize']);
		}

		return ValidationHelper::validateSize($this->model->$property->getSize(), $rules, $bytes);
	}

	private function normalizeSize ($size) {
		$value = substr($size, 0, -2);
		return $value * 1024 * 1024;
	}
}
?>
