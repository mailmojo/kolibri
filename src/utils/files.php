<?php
	/**
	 * Helper functions related to handling of files and executables.
	 *
	 * @version		$Id: files.php 1499 2008-06-02 10:51:46Z anders $
	 */

	/**
	 * Returns a list of the files in directory <code>$dir</code>. The pseudo-files . and .. are excluded
	 * from the list.
	 *
	 * @param string $dir	Path to the directory to list.
	 * @return array		Containing the files in <code>$dir</code>.
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
	 * If not <code>$to_dir</code> exists, the directory is created. Hidden directories and files are
	 * not included by default.
	 * 
	 * @param string $from_dir	Source directory to copy.
	 * @param string $to_dir	Target directory.
	 * @param bool $hidden		TRUE if hidden files and directories should be copied. Defaults to FALSE.
	 * @param int $mode			Attributes of the target files and directories. Defaults to 0644.
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
	 * Sletter en mappe og dens innhold rekursivt, iom. at man ikke kan slette en mappe som ikke er tom.
	 * Støtter også å slette en enkelt fil.
	 *
	 * @param string $dirname	Mappen som skal slettes.
	 * @return boo				<code>TRUE</code> om sletting var vellykket, <code>FALSE</code> hvis ikke.
	 */
	function remove_dir ($dirname) {
		if (is_file($dirname)) return unlink($dirname);

		// Løp gjennom mappens innhold og slett rekursivt.
		$dir = dir($dirname);
		while (false !== ($entry = $dir->read())) {
			// Hopp over *nix sine . og ..-filer.
			if ($entry == '.' || $entry == '..') continue;
	
			// Traverser mappen rekursivt om nødvendig
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
	 * Eksekverer et PHP-skript i PHP CLI, og returnerer resultatet av skriptet.
	 *
	 * @param string $script	Sti til PHP-skriptet som skal eksekveres.
	 * @param array $arguments	Array med argumenter som skal sendes til skriptet.
	 * @param mixed $output		Støtter å ta i mot en array for å lagre utdata fra skriptet.
	 * @param bool $background	TRUE om skriptet skal eksekveres som en bakgrunnsprosess og denne
	 *							funksjonen skal returnere umiddelbart, FALSE om vi skal vente på
	 *							skriptets fullføring.
	 * @return					<code>TRUE</code> om skriptet eksekverte uten problemer,
	 * 							<code>FALSE</code> ved feil.
	 */
	function execute_php_cli ($script, $arguments, &$output, $background = true) {
		// Gå gjennom argumentene, og gjør de trygge for shellet
		if (is_array($arguments) && count($arguments) > 0) {
			$arguments = array_map("escapeshellarg", $arguments);
			$arguments = implode(' ', $arguments);
		}
		else $arguments = '';
		
		if ($background !== false) {
			// Start en bakgrunnsprosess
			if (strpos(php_uname('s'), 'Windows') !== false) {
				$result = popen('start "php_cli" /B php.exe -f ' . $script . ' -- ' . $arguments . ' > NUL', 'r');
				if ($result !== false) pclose($result);
			}
			else {
				$result = exec(sprintf('%s -f %s %s >> %s &', _PHP_CLI_, $script, $arguments, _CLI_LOG_FILE_));
			}
			return ($result !== false ? true : false);
		}
		else {
			// Bare kjør skriptet og lagre utdata fra skriptet i angitt array.
			exec(sprintf('%s -f %s %s >> %s', _PHP_CLI_, $script, $arguments, _CLI_LOG_FILE_), $output, $result);
		}
		
		return ($result == 0 ? true : false);
	}
?>
