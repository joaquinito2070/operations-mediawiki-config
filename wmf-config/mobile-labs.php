<?php

# WARNING: This file is publically viewable on the web.
# # Do not put private data here.


if ( $wmgMobileFrontend ) {
	if ( $wmgZeroBanner ) {
		$wgZeroBannerClusterDomain = 'beta.wmflabs.org'; // need a better way to calc this
		if ( !$wmgZeroPortal ) {
			$wgJsonConfigs['JsonZeroConfig']['remote']['url'] = 'https://zero.wikimedia.beta.wmflabs.org/w/api.php';
		}
	}
}

// T114552
$wgMobileFrontendLogo = $wgLogo;

$wgMFForceSecureLogin = false;
$wgMFUseCentralAuthToken = $wmgMFUseCentralAuthToken;
$wgMFSpecialCaseMainPage = $wmgMFSpecialCaseMainPage;

$wgMFMobileFormatterHeadings = $wmgMFMobileFormatterHeadings;
