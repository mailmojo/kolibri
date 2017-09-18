<?php
/*
 * Defines validator constants, used to define rules on model properties. They are mapped to concrete
 * validator classes below.
 */
define('EXISTS', 1);
define('UNIQUE', 2);
define('EQUALS', 4);
define('MATCHES', 8);
define('IS_NUM', 16);
define('IS_TEXT', 32);
define('IS_ALPHANUM', 64);
define('IS_EMAIL', 128);
define('IS_URL', 256);
define('IS_DATE', 512);
define('IS_DATETIME', 1024);
define('IS_HEX', 2048);
define('IS_FILE', 4096);
define('IN_DB', 8192);
define('IS_PHONE', 16384);

/*
 * Maps validation rules to validator classes used to perform the validation.
 */
$validators = array(
	EXISTS      => 'ExistsValidator',
	UNIQUE      => 'UniqueValidator',
	EQUALS      => 'EqualsValidator',
	MATCHES     => 'MatchesValidator',
	IS_NUM      => 'NumberValidator',
	IS_TEXT     => 'TextValidator',
	IS_ALPHANUM => 'AlphaNumValidator',
	IS_EMAIL    => 'EmailValidator',
	IS_URL      => 'UrlValidator',
	IS_DATE     => 'DateValidator',
	IS_DATETIME	=> 'DateTimeValidator',
	IS_HEX      => 'HexValidator',
	IS_FILE     => 'FileValidator',
	IN_DB       => 'InDbValidator',
	IS_PHONE    => 'PhoneValidator',
);

/*
 * Messages the different validators throw upon errors. Uses standard sprintf() placeholders.
 */
$validationMessages = array(
	'exists'        => '%s er påkrevd.',
	'equals'        => '%s må være lik %s.',
	'matches'       => '%s har ikke forventet innhold.',
	'unique'        => '%s «%s» eksisterer allerede.',
	'num'           => '%s må være et tall.',
	'size'          => '%s må være mellom %s og %s.',
	'minsize'       => '%s kan ikke være under %s.',
	'maxsize'       => '%s kan ikke være over %s.',
	'alphanum'      => '%s kan bare innholde bokstavene A-Z, tall, understrek og bindestrek.',
	'text'          => '%s kan bare inneholde vanlig tekst.',
	'email'         => '%s må være en gyldig e-postadresse.',
	'length'        => '%s må inneholde nøyaktig %d tegn.',
	'minlength'     => '%s kan ikke ha færre enn %d tegn.',
	'maxlength'     => '%s kan ikke ha flere enn %d tegn.',
	'url'           => '%s må være en gyldig nettadresse (URL).',
	'date'          => '%s må være en gyldig dato.',
	'hex'           => '%s må være et gyldig hexadesimalt tall.',
	'file'          => '%s må være en fil.',
	'file_ini_size' => '%s overstiger maksimal tillatt filstørrelse.',
	'file_partial'  => '%s ble ikke fullestendig opplastet. Vennligst prøv igjen.',
	'type'          => '%s må være en av følgende filtyper: %s',
	'in_db'         => '%s «%s» eksisterer ikke.',
	'phone'         => '%s må være et gyldig telefonnummer.',
);
?>
