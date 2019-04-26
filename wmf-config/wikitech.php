<?php
# WARNING: This file is publicly viewable on the web. Do not put private data here.

use MediaWiki\Logger\LoggerFactory;

require_once "$IP/extensions/LdapAuthentication/LdapAuthentication.php";
$wgAuthManagerAutoConfig['primaryauth'] += [
	LdapPrimaryAuthenticationProvider::class => [
		'class' => LdapPrimaryAuthenticationProvider::class,
		'args' => [ [
			'authoritative' => true, // don't allow local non-LDAP accounts
		] ],
		'sort' => 50, // must be smaller than local pw provider
	],
];
$wgLDAPDomainNames = [ 'labs' ];
switch ( $wgDBname ) {
case 'labswiki' :
	$wgLDAPServerNames = [ 'labs' => 'ldap-labs.eqiad.wikimedia.org' ];
		break;
case 'labtestwiki' :
	$wgLDAPServerNames = [ 'labs' => 'labtestservices2001.wikimedia.org' ];
		break;
}
// T165795: require exact case matching of username via :caseExactMatch:
$wgLDAPSearchAttributes = [ 'labs' => 'cn:caseExactMatch:' ];
$wgLDAPBaseDNs = [ 'labs' => 'dc=wikimedia,dc=org' ];
$wgLDAPUserBaseDNs = [ 'labs' => 'ou=people,dc=wikimedia,dc=org' ];
$wgLDAPEncryptionType = [ 'labs' => 'tls' ];
$wgLDAPWriteLocation = [ 'labs' => 'ou=people,dc=wikimedia,dc=org' ];
$wgLDAPAddLDAPUsers = [ 'labs' => true ];
$wgLDAPUpdateLDAP = [ 'labs' => true ];
$wgLDAPPasswordHash = [ 'labs' => 'clear' ];
// 'invaliddomain' is set to true so that mail password options
// will be available on user creation and password mailing
// Force strict mode. T218589
//$wgLDAPMailPassword = [ 'labs' => true, 'invaliddomain' => true ];
$wgLDAPPreferences = [ 'labs' => [ "email" => "mail" ] ];
$wgLDAPUseFetchedUsername = [ 'labs' => true ];
$wgLDAPLowerCaseUsernameScheme = [ 'labs' => false, 'invaliddomain' => false ];
$wgLDAPLowerCaseUsername = [ 'labs' => false, 'invaliddomain' => false ];
// Only enable UseLocal if you need to promote an LDAP user
// $wgLDAPUseLocal = true;
// T168692: Attempt to lock LDAP accounts when blocked
$wgLDAPLockOnBlock = true;
$wgLDAPLockPasswordPolicy = 'cn=disabled,ou=ppolicies,dc=wikimedia,dc=org';

$wgLDAPDebug = 5; // Maximally verbose logs for Andrew Bogott, 8-Dec-2015

// Local debug logging for troubleshooting LDAP issues
// @codingStandardsIgnoreStart
if ( false ) {
	// @codingStandardsIgnoreEnd
	$wgLDAPDebug = 5;
	$monolog = LoggerFactory::getProvider();
	$monolog->mergeConfig( [
		'loggers' => [
			'ldap' => [
				'handlers' => [ 'wikitech-ldap' ],
				'processors' => array_keys( $wmgMonologProcessors ),
			],
		],
		'handlers' => [
			'wikitech-ldap' => [
				'class' => '\\Monolog\\Handler\\StreamHandler',
				'args' => [ '/tmp/ldap-s-1-debug.log' ],
				'formatter' => 'line',
			],
		],
	] );
}

require_once "$IP/extensions/OpenStackManager/OpenStackManager.php";
switch ( $wgDBname ) {
case 'labswiki' :
	$wgOpenStackManagerNovaIdentityURI = 'http://cloudcontrol1003.wikimedia.org:35357/v2.0';
	$wgOpenStackManagerNovaIdentityV3URI = 'http://cloudcontrol1003.wikimedia.org:35357/v3';
	$wgOpenStackManagerDNSOptions = [
		'enabled' => true,
		'servers' => [ 'primary' => 'cloudcontrol1003.wikimedia.org' ],
		'soa'     => [ 'hostmaster' => 'hostmaster.wikimedia.org', 'refresh' => '1800', 'retry' => '3600', 'expiry' => '86400', 'minimum' => '7200' ],
	];
		break;
case 'labtestwiki' :
	$wgOpenStackManagerNovaIdentityURI = 'http://labtestcontrol2001.wikimedia.org:35357/v2.0';
	$wgOpenStackManagerNovaIdentityV3URI = 'http://labtestcontrol2001.wikimedia.org:35357/v3';
	$wgOpenStackManagerDNSOptions = [
		'enabled' => true,
		'servers' => [ 'primary' => 'labtestcontrol2001.wikimedia.org' ],
		'soa'     => [ 'hostmaster' => 'hostmaster.wikimedia.org', 'refresh' => '1800', 'retry' => '3600', 'expiry' => '86400', 'minimum' => '7200' ],
	];
		break;
}
$wgOpenStackManagerNovaKeypairStorage = 'ldap';
$wgOpenStackManagerLDAPDomain = 'labs';
$wgOpenStackManagerLDAPProjectBaseDN = 'ou=projects,dc=wikimedia,dc=org';
$wgOpenStackManagerLDAPProjectGroupBaseDN = "ou=groups,dc=wikimedia,dc=org";
$wgOpenStackManagerLDAPInstanceBaseDN = 'ou=hosts,dc=wikimedia,dc=org';
$wgOpenStackManagerLDAPServiceGroupBaseDN = 'ou=servicegroups,dc=wikimedia,dc=org';
$wgOpenStackManagerLDAPDefaultGid = '500';
$wgOpenStackManagerLDAPDefaultShell = '/bin/bash';
$wgOpenStackManagerLDAPUseUidAsNamingAttribute = true;
$wgOpenStackManagerPuppetOptions = [
	'enabled' => true,
	'defaultclasses' => [],
	'defaultvariables' => [],
];
$wgOpenStackManagerInstanceUserData = [
	'cloud-config' => [
		# 'puppet' => array( 'conf' => array( 'puppetd' => array( 'server' => 'wikitech.wikimedia.org', 'certname' => '%i' ) ) ),
		# 'apt_upgrade' => 'true',
		'apt_update' => 'false', // Puppet will cause this
		# 'apt_mirror' => 'http://ubuntu.wikimedia.org/ubuntu/',
	],
	'scripts' => [
		# Used for new images
		'runpuppet.sh' => '/srv/org/wikimedia/controller/scripts/runpuppet.sh',
		# Used for pre-configured images
		'runpuppet-new.sh' => '/srv/org/wikimedia/controller/scripts/runpuppet-new.sh',
	],
	'upstarts' => [
		'ttyS0.conf' => '/srv/org/wikimedia/controller/upstarts/ttyS0.conf',
		'ttyS1.conf' => '/srv/org/wikimedia/controller/upstarts/ttyS1.conf',
	],
];
$wgOpenStackManagerDefaultSecurityGroupRules = [
	# Allow all traffic within the project
	[ 'group' => 'default' ],
	# Allow ping from everywhere
	[ 'fromport' => '-1',
		'toport' => '-1',
		'protocol' => 'icmp',
		'range' => '0.0.0.0/0' ],
	# Allow ssh from all projects
	[ 'fromport' => '22',
		'toport' => '22',
		'protocol' => 'tcp',
		'range' => '10.0.0.0/8' ],
	# Allow nrpe access from all projects (access is limited in config)
	[ 'fromport' => '5666',
		'toport' => '5666',
		'protocol' => 'tcp',
		'range' => '10.0.0.0/8' ],
];
$wgOpenStackManagerInstanceBannedInstanceTypes = [
	"m1.tiny",
	"s1.tiny",
	"s1.small",
	"s1.medium",
	"s1.large",
	"s1.xlarge",
	"pmtpa-1",
	"pmtpa-2",
	"pmtpa-3",
	"pmtpa-4",
	"pmtpa-5",
	"pmtpa-6",
	"pmtpa-7",
	"pmtpa-8",
	"pmtpa-9",
	"pmtpa-10",
	"pmtpa-11"
];

# Enable doc links on the 'configure instance' page
$wgOpenStackManagerPuppetDocBase = 'http://doc.wikimedia.org/puppet/classes/__site__/';

$wgOpenStackManagerProxyGateways = [ 'eqiad' => '208.80.155.156' ];

// Dummy setting for conduit api token to be used by the BlockIpComplete hook
// that tries to disable Phabricator accounts. Real value should be provided
// by /etc/mediawiki/WikitechPrivateSettings.php
$wmfPhabricatorApiToken = false;

// Dummy settings for Gerrit api access to be used by the BlockIpComplete hook
// that tries to disable Gerrit accounts. Real values should be provided by
// /etc/mediawiki/WikitechPrivateSettings.php
$wmfGerritApiUser = false;
$wmfGerritApiPassword = false;

# This must be loaded AFTER OSM, to overwrite it's defaults
# Except when we're not an OSM host and we're running like a maintenance script.
if ( file_exists( '/etc/mediawiki/WikitechPrivateSettings.php' ) ) {
	require_once '/etc/mediawiki/WikitechPrivateSettings.php';
}

# wgCdnReboundPurgeDelay is set to 11 in reverse-proxy.php but
# since we aren't using the shared jobqueue, we don't support delays
$wgCdnReboundPurgeDelay = 0;

# Wikitech on labweb is behind the misc-web varnishes so we need a different
# multicast IP for cache invalidation.  This file is loaded
# after the standard MW equivalent (in reverse-proxy.php)
# so we can just override it here.
$wgHTCPRouting = [
	'' => [
		'host' => '239.128.0.115',
		'port' => 4827
	]
];

// Temporarily disable password resets. Revisit in 2 weeks
$wgPasswordResetRoutes = false;
// T218654
$wgHooks['BlockIpComplete'][] = function ( $block, $performer, $priorBlock ) {
	global $wgBlockDisablesLogin;
	if ( $wgBlockDisablesLogin && $block->getTarget() instanceof User && $block->getExpiry() === 'infinity' && $block->isSitewide() ) {
		MediaWiki\Auth\AuthManager::singleton()->revokeAccessForUser( $block->getTarget()->getName() );
	}
};

// Attempt to disable related accounts when a developer account is
// permablocked.
$wgHooks['BlockIpComplete'][] = function ( $block, $user, $prior ) use ( $wmfPhabricatorApiToken ) {
	if ( !$wmfPhabricatorApiToken
		|| $block->getType() !== /* Block::TYPE_USER */ 1
		|| $block->getExpiry() !== 'infinity'
		|| !$block->isSitewide()
	) {
		// Nothing to do if we don't have config or if the block is not
		// a site-wide indefinite block of a named user.
		return;
	}
	try {
		// Lookup and block phab user tied to developer account
		$phabClient = function ( $path, $query ) use ( $wmfPhabricatorApiToken ) {
			$query['__conduit__'] = [ 'token' => $wmfPhabricatorApiToken ];
			$post = [
				'params' => json_encode( $query ),
				'output' => 'json',
			];
			$phabUrl = 'https://phabricator.wikimedia.org';
			$ch = curl_init( "{$phabUrl}/api/{$path}" );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $post );
			$ret = curl_exec( $ch );
			curl_close( $ch );
			if ( $ret ) {
				$resp = json_decode( $ret, true );
				if ( !$resp['error_code'] ) {
					return $resp['result'];
				}
			}
			wfDebugLog(
				'WikitechPhabBan',
				"Phab {$path} error " . var_export( $ret, true )
			);
			return false;
		};

		$username = $block->getTarget()->getName();
		$resp = $phabClient( 'user.ldapquery', [
			'ldapnames' => [ $username ],
			'offset' => 0,
			'limit' => 1,
		] );
		if ( $resp ) {
			$phid = $resp[0]['phid'];
			$phabClient( 'user.disable', [
				'phids' => [ $phid ],
			] );
		}
	} catch ( Throwable $t ) {
		wfDebugLog(
			'WikitechPhabBan',
			"Unhandled error blocking Phabricator user: {$t}"
		);
	}
};
$wgHooks['BlockIpComplete'][] = function ( $block, $user, $prior ) use ( $wmfGerritApiUser, $wmfGerritApiPassword ) {
	if ( !$wmfGerritApiUser
		|| !$wmfGerritApiPassword
		|| $block->getType() !== /* Block::TYPE_USER */ 1
		|| $block->getExpiry() !== 'infinity'
		|| !$block->isSitewide()
	) {
		// Nothing to do if we don't have config or if the block is not
		// a site-wide indefinite block of a named user.
		return;
	}
	try {
		// Disable gerrit user tied to developer account
		$gerritUrl = 'https://gerrit.wikimedia.org';
		$username = strtolower( $block->getTarget()->getName() );
		$ch = curl_init(
			"{$gerritUrl}/r/a/accounts/" . urlencode( $username ) . '/active'
		);
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'DELETE' );
		curl_setopt(
			$ch, CURLOPT_USERPWD,
			"{$wmfGerritApiUser}:{$wmfGerritApiPassword}"
		);
		if ( !curl_exec( $ch ) ) {
			wfDebugLog(
				'WikitechGerritBan',
				"Gerrit block of {$username} failed: " . curl_error( $ch )
			);
		} else {
			$status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			if ( $status !== 204 ) {
				wfDebugLog(
					'WikitechGerritBan',
					"Gerrit block of {$username} failed with status {$status}"
				);
			}
		}
		curl_close( $ch );
	} catch ( Throwable $t ) {
		wfDebugLog(
			'WikitechGerritBan',
			"Unhandled error blocking Gerrit user: {$t}"
		);
	}
};
