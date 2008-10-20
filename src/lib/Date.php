<?php
/**
 * This class represents a specific instance in time.
 *
 * The <code>Date</code> class allowes for interpretation of dates as year, month, day, hour, minute and
 * second values. This means that a date can be set using any of those time fields, and specific time fields
 * can be extracted separate from the specific instance in time.
 *
 * Parsing and formatting of dates should be done using the <code>DateFormat</code> class.
 *
 * $Id: Date.php 1510 2008-06-17 05:45:50Z anders $ 
 */
class Date {
	/**
	 * Time fields for the currently set time.
	 * @var array
	 */
	private $fields;

	/**
	 * The currently set time as seconds since the Unix epoch.
	 * @var int
	 */
	private $time;

	/**
	 * <code>TRUE</code> if <code>$fields</code> are in sync with the set time.
	 * @var bool
	 */
	private $isFieldsValid;

	/**
	 * <code>TRUE</code> if <code>$time</code> is in sync with the time fields.
	 * @var bool
	 */
	private $isTimeValid;

	// Date field constants
	const SECOND		= 'seconds';
	const MINUTE		= 'minutes';
	const HOUR			= 'hour';
	const DATE			= 'mday';
	const DAY_OF_WEEK	= 'wday';
	const DAY_OF_MONTH	= 'mday';
	const DAY_OF_YEAR	= 'yday';
	const MONTH			= 'mon';
	const YEAR			= 'year';

	/**
	 * Creates a new instance representing the time at the specified seconds since the epoch, or the local
	 * time when it was allocated if no parameter is supplied.
	 *
	 * @param int $time		Seconds since epoch.
	 */
	public function __construct ($time = null) {
		if ($time !== null) {
			$this->time = $time;
		}
		else {
			$this->time = time();
		}

		$this->isFieldsValid = false;
		$this->isTimeValid = true;
	}

	/**
	 * Returns the time in seconds since the Unix epoch. If the internal time is not in sync with the time
	 * fields, the time is recalculated before being returned.
	 *
	 * @return int	Numer of seconds since the Unix epoch represented by this date.
	 */
	public function getTime () {
		if (!$this->isTimeValid && $this->isFieldsValid) {
			$this->time = mktime($this->fields[Date::HOUR], $this->fields[Date::MINUTE],
					$this->fields[Date::SECOND], $this->fields[Date::MONTH],
					$this->fields[Date::DATE], $this->fields[Date::YEAR]);
			$this->isTimeValid = true;
		}
		else if (!$this->isTimeValid && !$this->isFieldsValid) {
			return null;
		}

		return $this->time;
	}

	/**
	 * Returns the value of the specified time field. A time field is one of the constants defined by this
	 * class, and represents a specific part of an instance in time (such as the hour, or day of month). If
	 * the internal time field values is not in sync with the currently set time of this date, they are
	 * recalculated before the requested time field value is returned.
	 *
	 * @param string $field		The time field to return value of.
	 * @return int				Value of the time field represented by this date.
	 */
	public function getTimeField ($field) {
		if (!$this->isFieldsValid && $this->isTimeValid) {
			$this->fields = getdate($this->time);
			$this->isFieldsValid = true;
		}
		else if ((!$this->isFieldsValid && !$this->isTimeValid) || !isset($this->fields[$field])) {
			return null;
		}

		return $this->fields[$field];
	}
}
?>
