<?php
// TODO: Gå over til konstanter fremfor format-string for opprettelse av en DF
//define(DF_SHORT, 0);
//define(DF_MEDIUM, 1);
//define(DF_LONG, 2);
//define(DF_FULL, 3);
//define(DF_TRANSIENT, 4);*/

/**
 * The <code>DateFormat</code> class is a class for formatting (date to string) and parsing (string to date)
 * dates and/or times. The date is represented as a <code>Date</code> object.
 *
 * To obtain a <code>DateFormat</code> instance any of the provided static factory methods should be used.
 * Some require format strings corresponding to PHPs <code>strftime()</code> format strings.
 *
 * $Id: DateFormat.php 1545 2008-08-19 21:32:44Z anders $
 */
class DateFormat {
	private $formatString;

	const ISO_8601		= '%Y-%m-%d %H:%M:%S';
	const ISO_8601_DATE	= '%Y-%m-%d';

	/**
	 * Creates a new instance of this class with the specified date and time styles.
	 *
	 * @access protected
	 * @param string $date_style	Style of the date part of a date.
	 * @param string $time_style	Style of the time part of a date.
	 */
	protected function __construct ($formatString) {
		$this->formatString = $formatString;
	}

	/**
	 * Returns a new date/time formatter with default date and time formats according to the current
	 * locale.
	 */
	public static function getInstance ($style) {
		return new DateFormat($style);
	}

	/**
	 * Returns a new date formatter with the specified date style of a date.
	 */
	public static function getDateInstance ($style = '%x') {
		return new DateFormat($style);
	}

	/**
	 * Returns a new date/time formatter with the specified date and time styles of a date.
	 */
	public static function getDateTimeInstance ($dateStyle = '%x', $timeStyle = '%X') {
		return new DateFormat($dateStyle . ' ' . $timeStyle);
	}

	/**
	 * Returns a new time formatter with the specified time style of a date.
	 */
	public static function getTimeInstance ($style = '%X') {
		return new DateFormat($style);
	}

	/**
	 * Returns a new date/time formatter for representing close dates in a transient format.
	 */
	public static function getTransientInstance ($dateStyle = '', $timeStyle = '') {
		return new TransientDateFormat($dateStyle . ' ' . $timeStyle);
	}

	/**
	 * Parses the given string to produce a <code>Date</code> object.
	 *
	 * @param string $string	The string to parse.
	 * @return Date				A <code>Date</code> parsed from the string.
	 */
	public static function parse ($string) {
		// Commented out as PHP5 is supposed to ignore fractural seconds
		//if (($msPos = strpos($string, '.')) !== false) {
		//	$exMs = substr($string, 0, $msPos);
		//	$time = strtotime($exMs);
		//}
		//else {
			$time = strtotime($string);
		//}
		
		return new Date($time);
	}

	/**
	 * Formats a <code>Date</code> into a date/time string.
	 *
	 * @param Date &$date	The <code>Date</code> to format into a string.
	 * @return string		Formatted date/time string.
	 */
	public function format ($date) {
		return strftime($this->formatString, $date->getTime());
	}
}

/**
 * The <code>TransientDateFormat</code> class extends <code>DateFormat</code> to format a date within the
 * last week as a transient date. A transient date is a date which will not represent the same instance in
 * time when read at different times. For instance, "last Monday" is transient, as it is not conclusive
 * except for at the exact time when it was produced.
 *
 * $Id: DateFormat.php 1545 2008-08-19 21:32:44Z anders $
 */
class TransientDateFormat extends DateFormat {
	private $days = array(
		0 => 'søndag',
		1 => 'mandag',
		2 => 'tirsdag',
		3 => 'onsdag',
		4 => 'torsdag',
		5 => 'fredag',
		6 => 'lørdag');

	/**
	 * Creates a new instance of this class with the specified date and time styles to be used when outside
	 * the transient time period.
	 *
	 * @access protected
	 * @param string $date_style	Style of the date part of a date.
	 * @param string $time_style	Style of the time part of a date.
	 */
	public function __construct ($formatString) {
		parent::__construct($formatString);
	}

	/**
	 * Formats a <code>Date</code> into a transient date/time string.
	 *
	 * @param Date &$date	The <code>Date</code> to format.
	 * @return string		Formattet transient date/time string.
	 */
	public function format ($date) {
		$now = new Date();
		$yearadjust = ($now->getTimeField(Date::YEAR) - $date->getTimeField(Date::YEAR)) * 365;

		if ($date->getTimeField(Date::DAY_OF_YEAR) == $now->getTimeField(Date::DAY_OF_YEAR)
				&& $date->getTimeField(Date::YEAR) == $now->getTimeField(Date::YEAR)) {
			return 'I dag kl ' . $this->formatTime($date);
		}
		else if (($date->getTimeField(Date::DAY_OF_YEAR) - $yearadjust)
				== ($now->getTimeField(Date::DAY_OF_YEAR) - 1)) {
			return 'I går kl ' . $this->formatTime($date);
		}
		else if (($date->getTimeField(Date::DAY_OF_YEAR) - $yearadjust)
				> ($now->getTimeField(Date::DAY_OF_YEAR) - 7)) {
			return 'Sist ' . $this->days[$date->getTimeField(Date::DAY_OF_WEEK)]
				. ' kl ' . $this->formatTime($date);
		}

		return parent::format($date);
	}

	/**
	 * Helper function to format the time part of a date.
	 */
	private function formatTime ($date) {
		$hour = $date->getTimeField(Date::HOUR);
		$min = $date->getTimefield(Date::MINUTE);

		return (strlen($hour) > 1 ? $hour : "0$hour") . ':' . (strlen($min) > 1 ? $min : "0$min");
	}
}
?>
