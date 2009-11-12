<?php
	/**
	 * Helper functions related to handling of files and executables.
	 */

	/**
	 * Returns a list of the files in directory $dir. The pseudo-files . and .. are excluded
	 * from the list.
	 *
	 * @param string $dir	Path to the directory to list.
	 * @return array		Containing the files in $dir.
	 */
	function ls ($dir) {
		$files = array();

		if (($handle = opendir($dir))) {
			while (($file = readdir($handle)) !== false) {
				if ($file != '.' && $file != '..') {
					$files[] = $file;
				}
			}
			closedir($handle);
		}

		return $files;
	}
	
	/**
	 * Recursively copies one directory to another.
	 *
	 * If not $to_dir exists, the directory is created. Hidden directories and files are
	 * not included by default.
	 * 
	 * @param string $from_dir	Source directory to copy.
	 * @param string $to_dir	Target directory.
	 * @param bool $hidden		TRUE if hidden files and directories should be copied. Defaults
	 *							to FALSE.
	 * @param int $mode			Attributes of the target files and directories. Defaults to
	 *							0644.
	 */
	function copy_dir ($from_dir, $to_dir, $hidden = false, $mode = 0644) {
		if (!file_exists($from_dir)) return;

		// Add '/' at the end of paths if missing
		if (substr($from_dir, -1) != '/') $from_dir .= '/';
		if (substr($to_dir, -1) != '/') $to_dir .= '/';
		
		if (!file_exists($to_dir)) {
			// Create target directory
			mkdir($to_dir);
			chmod($to_dir, $mode);
		}
		
		if ($dir = opendir($from_dir)) {
			while (($fname = readdir($dir)) !== false) {
				// Skip *nix . and .. files
				if ($fname == '.' || $fname == '..') continue;
				
				if (!$hidden && substr($fname, 0, 1) != '.') {
					$from = $from_dir . $fname;
					$to = $to_dir . $fname;
					
					if (is_dir($from)) {
						// Recursively copy the inner directory
						copy_dir($from . '/', $to . '/');
					}
					else {
						// Current file is a file, copy
						copy($from, $to);
						chmod($to, $mode);
					}
				}
			}
			closedir($dir);
		}
	}
	
	/**
	 * Deletes a directory and its contents recursively. Also supports deleting a single file.
	 *
	 * @param string $dirname	Directory to delete.
	 * @return bool				TRUE if the delete was successful, FALSE if not.
	 */
	function remove_dir ($dirname) {
		if (is_file($dirname)) return unlink($dirname);

		// Traverse directories content and delete recursively
		$dir = dir($dirname);
		while (false !== ($entry = $dir->read())) {
			// Skip *nix pseudo-files . and ..
			if ($entry == '.' || $entry == '..') continue;
	
			// Traverse directory recursively if needed
			if (is_dir("$dirname/$entry")) {
				remove_dir("$dirname/$entry");
			}
			else {
				unlink("$dirname/$entry");
			}
		}

		$dir->close();
		return rmdir($dirname);
	}

	/**
	 * Executes a PHP script through the shell. If it's not started as a background process,
	 * the output is returned.
	 *
	 * @param string $script	Path to PHP script to execute.
	 * @param array $arguments	Array with arguments to supply to the script.
	 * @param bool $background	Whether or not the script should be executed as a background
	 *							process, whereby this method will return without waiting for the
	 *							script to complete.
	 * @param string $output	If non-null, designates the path to a file to redirect script
	 *							output to. Only used if $background is TRUE.
	 * @return mixed			TRUE or FALSE indicating success and failure if the script is
	 *							run as a background process, otherwise the script output.
	 */
	function exec_php_cli ($script, $arguments, $background = true, $output = null) {
		// Escape arguments for safe use in shell
		if (is_array($arguments) && count($arguments) > 0) {
			$arguments = array_map("escapeshellarg", $arguments);
			$arguments = implode(' ', $arguments);
		}
		else {
			$arguments = '';
		}
		
		if ($background) {
			// Start background process
			if (strpos(php_uname('s'), 'Windows') !== false) {
				$result = popen('start "php_cli" /B php.exe -f ' . $script . ' -- ' . $arguments . ' > NUL', 'r');
				if ($result !== false) {
					pclose($result);
				}
			}
			else {
				if ($output) {
					$result = exec(sprintf('php -f %s %s >> %s &', $script, $arguments, $output));
				}
				else {
					$result = exec(sprintf('php -f %s %s &', $script, $arguments));
				}
			}

			return ($result !== false ? true : false);
		}

		// Run script waiting for completion, and save output for return value
		$output = '';
		exec(sprintf('php -f %s %s', $script, $arguments), $output, $result);

		return ($result == 0 ? $output : false);
	}
?>
