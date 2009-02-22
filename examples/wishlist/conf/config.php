<?php
/*
 * General configuration. Put whatever application-specific configuration you like here. Each setting can be
 * get by calling Config::get('key'), where key is the setting you want to return, i.e. 'mail'.
 */
$config = array(
		'webRoot'    => '',        // Change if not on root level. Prefix with slash if not empty, but no trailing!
		'staticRoot' => '/static', // URI of static resources (can be another host as http://static.example.com)
		'locale'     => 'en_US.utf8',
		'logging'    => array(
			'enabled'  => false,   // When logging is disabled, errors are outputted directly. When enabled...
			'logFile'  => '',      // ...they can be logged to a file
			'logEmail' => ''       // ...and/or an email address (make sure you config 'mail' for this to work)
		/*
		 * Database configuration. 'type' is mandatory, while implementations define other settings.
		 */
		'db'        => array(
			'type'          => 'Sqlite', // We also support PostgreSql, but not for this demo app
			'database'      => APP_PATH . '/db/wishlist.db' // Filename for SQLite (writable by Apache)
		),
		/*
		 * Configure your e-mail details for the MailService library used to send e-mails.
		 */
		'mail'      => array(
			'from'          => '',
			'from_name'     => '',
 			'smtp_auth'     => false,
	 		'smtp_host'     => '',
 			'smtp_username' => '',
 			'smtp_password' => ''
		)
);

/*
 * Defines which action mappers to use for the specified URIs. The first match is used - the entire
 * array is not necessarily iterated.
 */
$actionMappers = array(
		'*' => 'DefaultActionMapper'
);

/*
 * $interceptorMappings defines which interceptors are to be invoked at a given URI. Wildcard mapping
 * is supported. You specify interceptors by their name configured in
 * <code>interceptors.php</code>, which can either refer to single interceptors or interceptor
 * stacks. The interceptors must be wrapped in an array, and you may reference both single interceptors
 * and interceptor stacks in the same mapping.
 * 
 * You can exclude certain interceptors from being invoked at specific URIs by prefixing the
 * interceptor name with a ! (exclamation mark). For instance, you may have an authentication
 * interceptor mapped to /admin/* (everything within /admin), but want to leave /admin/login open
 * to the public (after all, users must be allowed to log in). This can be done by these mappings:
 * 
 *    '/admin/*'     => array('auth'),
 *    '/admin/login' => array('!auth')
 * 
 * Order is of significance when mapping interceptors. If you were to define the excluding mapping
 * in this example before the regular inclusive mapping, it would not work as advertised above.
 * The wild-card mapping within /admin would then override the specific /admin/login, and the
 * exclude-mapping would not have any effect.
 */
$interceptorMappings = array(
		'*' => array('defaultStack')
);

/*
 * You may override any interceptor specific settings you like. For example, to specify a custom model for the
 * user object, you could do the following (see kolibri/conf/interceptors.php for more).
 */
//$interceptors['auth']['AuthInterceptor']['userModel'] = 'MyUser';
?>
