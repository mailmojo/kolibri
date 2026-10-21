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
		$time = (strlen($string) > 6 ? strtotime($string) : false);
		if ($time !== false) {
			return new Date($time);
		}
		return null;
	}

	/**
	 * Formats a <code>Date</code> into a date/time string.
	 *
	 * @param Date &$date	The <code>Date</code> to format into a string.
	 * @return string		Formatted date/time string.
	 */
	public function format ($date) {
		$time = $date->getTime();
		$locale = self::getTimeLocale();

		/*
		 * strftime() is deprecated since PHP 8.1, so its conversion specifiers are translated
		 * here. Names and the preferred date/time representations follow the LC_TIME locale
		 * through IntlDateFormatter, and match strftime() in the C locale otherwise. The '-'
		 * flag removes padding, E and O modifiers are ignored, and unknown specifiers are
		 * left as-is.
		 */
		return preg_replace_callback('/%([-_0^#]?)[EO]?([a-zA-Z%])/',
				function ($match) use ($time, $locale) {
			$value = self::formatSpecifier($match[2], $time, $locale);
			if ($value === null) {
				return $match[0];
			}
			if ($match[1] == '-' && is_numeric($value)) {
				$value = ltrim($value, '0 ') ?: '0';
			}
			return $value;
		}, $this->formatString);
	}

	/**
	 * Formats a single strftime() conversion specifier.
	 *
	 * @param string $specifier	Specifier character, without the leading %.
	 * @param int $time			Timestamp to format.
	 * @param string $locale	ICU locale for names, or NULL for the C locale.
	 * @return string			The formatted value, or NULL if the specifier is unknown.
	 */
	private static function formatSpecifier ($specifier, $time, $locale) {
		if ($locale !== null) {
			$patterns = array(
				'a' => 'EEE', 'A' => 'EEEE', 'b' => 'MMM', 'h' => 'MMM', 'B' => 'MMMM',
				'p' => 'a', 'r' => 'hh:mm:ss a',
			);
			$styles = array(
				'c' => array(IntlDateFormatter::MEDIUM, IntlDateFormatter::MEDIUM),
				'x' => array(IntlDateFormatter::SHORT, IntlDateFormatter::NONE),
				'X' => array(IntlDateFormatter::NONE, IntlDateFormatter::MEDIUM),
			);
			if (isset($patterns[$specifier])) {
				return self::intlFormat($locale, $time, IntlDateFormatter::NONE,
						IntlDateFormatter::NONE, $patterns[$specifier]);
			}
			if (isset($styles[$specifier])) {
				return self::intlFormat($locale, $time, $styles[$specifier][0],
						$styles[$specifier][1]);
			}
			if ($specifier == 'P') {
				return mb_strtolower(self::intlFormat($locale, $time, IntlDateFormatter::NONE,
						IntlDateFormatter::NONE, 'a'));
			}
		}

		switch ($specifier) {
			case '%': return '%';
			case 'n': return "\n";
			case 't': return "\t";
			case 'e': return sprintf('%2d', date('j', $time));
			case 'k': return sprintf('%2d', date('G', $time));
			case 'l': return sprintf('%2d', date('g', $time));
			case 'j': return sprintf('%03d', date('z', $time) + 1);
			case 'C': return sprintf('%02d', intdiv((int) date('Y', $time), 100));
			case 'g': return substr(date('o', $time), -2);
			case 'U':
				$weekday = (int) date('w', $time);
				return sprintf('%02d', intdiv((int) date('z', $time) + 7 - $weekday, 7));
			case 'W':
				$weekday = ((int) date('w', $time) + 6) % 7;
				return sprintf('%02d', intdiv((int) date('z', $time) + 7 - $weekday, 7));
		}

		$formats = array(
			'a' => 'D', 'A' => 'l', 'b' => 'M', 'h' => 'M', 'B' => 'F', 'd' => 'd', 'm' => 'm',
			'y' => 'y', 'Y' => 'Y', 'G' => 'o', 'H' => 'H', 'I' => 'h', 'M' => 'i', 'S' => 's',
			'p' => 'A', 'P' => 'a', 'z' => 'O', 'Z' => 'T', 's' => 'U', 'u' => 'N', 'w' => 'w',
			'V' => 'W', 'D' => 'm/d/y', 'F' => 'Y-m-d', 'R' => 'H:i', 'T' => 'H:i:s',
			'r' => 'h:i:s A', 'x' => 'm/d/y', 'X' => 'H:i:s', 'c' => 'D M j H:i:s Y',
		);
		if (!isset($formats[$specifier])) {
			return null;
		}
		if ($specifier == 'c') {
			// strftime() pads the day of the month with a space in this representation
			return str_replace(' ' . date('j', $time) . ' ',
					sprintf(' %2d ', date('j', $time)), date($formats[$specifier], $time));
		}
		return date($formats[$specifier], $time);
	}

	/**
	 * Formats a timestamp with IntlDateFormatter, using the default timezone.
	 */
	private static function intlFormat ($locale, $time, $dateType, $timeType, $pattern = null) {
		$formatter = IntlDateFormatter::create($locale, $dateType, $timeType,
				date_default_timezone_get(), null, $pattern);
		return $formatter->format($time);
	}

	/**
	 * Returns the ICU locale for the current LC_TIME locale, or NULL when that is the C or
	 * POSIX locale or the intl extension is unavailable.
	 *
	 * @return string
	 */
	private static function getTimeLocale () {
		if (!class_exists('IntlDateFormatter', false)) {
			return null;
		}
		$locale = preg_replace('/[.@].*$/', '', (string) setlocale(LC_TIME, 0));
		return ($locale === '' || $locale == 'C' || $locale == 'POSIX') ? null : $locale;
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
