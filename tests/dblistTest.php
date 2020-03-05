<?php
/**
 * Various tests made to test Wikimedia Foundation .dblist files.
 *
 * @license GPL-2.0-or-later
 * @author Antoine Musso <hashar at free dot fr>
 * @copyright Copyright © 2012, Antoine Musso <hashar at free dot fr>
 * @file
 */

class DbListTest extends PHPUnit\Framework\TestCase {

	public static function provideProjectDbnames() {
		foreach ( DBList::getLists() as $project => $databases ) {
			if ( !DBlist::isWikiProject( $project ) ) {
				// Skip files such as s1, private ...
				continue;
			}
			foreach ( $databases as $database ) {
				yield [ $project, $database ];
			}
		}
	}

	/**
	 * Projects dblist should only contains databasenames which
	 * belongs to them.
	 *
	 * @dataProvider provideProjectDbnames
	 */
	public function testDatabaseSuffixMatchProject( $projectname, $database ) {
		// Override suffix for wikipedia project
		$dbsuffix = ( $projectname === 'wikipedia' ) ? 'wiki' : $projectname;

		// Verifiy the databasename suffix
		$this->assertStringEndsWith( $dbsuffix, $database,
			"Database name $database lacks db suffix $dbsuffix of $projectname"
		);
	}

	public function testDblistAllContainsEverything() {
		$lists = DBList::getLists();

		// Content of all.dblist
		$all = $lists['all'];

		$skip = [
			// No point in checking that all includes itself
			'all',

			// Labs wikis (beta.wmflabs.org) might not (yet) exist in production.
			'all-labs',
			'flow-labs',
			'flow_only_labs',

			'deleted',
		];

		foreach ( $lists as $dbfile => $dbnames ) {
			if ( in_array( $dbfile, $skip ) ) {
				continue;
			}

			$this->assertEquals(
				[],
				array_diff( $dbnames, $all ),
				"'{$dbfile}.dblist' must not contain names not in 'all.dblist'"
			);
		}
	}

	public static function provideWikisAreIncluded() {
		return [
			'section' => [
				'all',
				// If you're adding a new section, make sure it's widely announced
				// so all the people who do things per section know about it!
				[ 's1', 's2', 's3', 's4', 's5', 's6', 's7', 's8', 's10', 's11', ],
			],

			'size' => [
				'all',
				[ 'small', 'medium', 'large', ],
			],

			'multiversion' => [
				'all',
				[ 'group0', 'group1', 'group2', ],
			],

			'family' => [
				'all',
				[
					'special',
					'wikibooks',
					'wikimedia',
					'wikinews',
					'wikipedia',
					'wikiquote',
					'wikisource',
					'wikiversity',
					'wikivoyage',
					'wiktionary',
				]
			],

			'wiki-suffix disambiguation' => [
				// Based on suffixes as set in wgConf.php
				'all - wikibooks - wikimedia - wikinews - wikiquote - wikisource - wikiversity - wikivoyage - wiktionary',
				[
					'wikipedia',
					'special',
				]
			],
		];
	}

	/**
	 * @dataProvider provideWikisAreIncluded
	 * @param string $input Which dblist to read for these assertions
	 * @param string[] $dbLists DBList names that should collectively contain all wikis
	 */
	public function testWikisAreIncluded( string $input, array $dbLists ) {
		$lists = DBList::getLists();

		$all = array_fill_keys( MWWikiversions::evalDbListExpression( $input ), [] );

		foreach ( $dbLists as $list ) {
			foreach ( $lists[$list] as $name ) {
				$all[$name][] = $list;
			}
		}

		$all = array_filter( $all, function ( $v ) {
			return count( $v ) !== 1;
		} );

		$this->assertSame( [], $all,
			"All names in 'all.dblist' are in exactly one of the lists" );
	}

	/**
	 * Production code that is web-facing MUST NOT use dblists
	 * that contain expressions because these have a significant performance cost.
	 */
	public function testNoExpressionListUsedInSettings() {
		$dblists = DBList::getDblistsUsedInSettings();

		$actual = [];
		foreach ( $dblists as $file ) {
			$content = file_get_contents( dirname( __DIR__ ) . "/dblists/$file.dblist" );
			if ( strpos( $content, '%' ) !== false ) {
				$actual[] = $file;
			}
		}

		// FIXME: These should not be used in wmf-config, or be pre-computed.
		$grandgathered = [ 'group1', 'group2' ];
		$actual = array_diff( $actual, $grandgathered );

		$this->assertEquals(
			[],
			$actual,
			'Dblist files used in web requests must not contain lazy expressions'
		);
	}

	/**
	 * @covers MWWikiversions::evalDbListExpression
	 */
	public function testEvalDbListExpression() {
		$allDbs = MWWikiversions::readDbListFile( 'all' );
		$allLabsDbs = MWWikiversions::readDbListFile( 'private' );
		$exprDbs = MWWikiversions::evalDbListExpression( 'all - private' );
		$expectedDbs = array_diff( $allDbs, $allLabsDbs );
		sort( $exprDbs );
		sort( $expectedDbs );
		$this->assertEquals( $exprDbs, $expectedDbs );
	}

	/**
	 * This test ensures that all dblists are alphasorted
	 */
	public function testListsAreSorted() {
		$lists = DBList::getLists();
		foreach ( $lists as $listname => $dbnames ) {
			if ( strpos( $listname, 'computed' ) !== false ) {
				continue;
			}

			$origdbnames = $dbnames;
			sort( $dbnames );

			$this->assertEquals(
				$origdbnames,
				$dbnames,
				"{$listname}.dblist is not alphasorted"
			);
		}
	}

	/**
	 * @note Does not support special wikis in RTL languages, luckily there are none currently
	 */
	public function testRtlDblist() {
		ini_set( 'user_agent', 'mediawiki-config tests' );
		$siteMatrix = file_get_contents( 'https://meta.wikimedia.org/w/api.php?action=sitematrix&format=json&smtype=language&smlangprop=dir%7Ccode%7Csite&smsiteprop=dbname&formatversion=2' );
		if ( !$siteMatrix ) {
			$this->fail( 'Error retrieving site matrix!' );
		}
		$siteMatrix = json_decode( $siteMatrix, true );

		$rtl = array_flip( MWWikiversions::readDbListFile( 'rtl' ) );
		$shouldBeRtl = [];

		foreach ( $siteMatrix['sitematrix'] as $key => $lang ) {
			if ( !is_numeric( $key )
				|| $lang['dir'] !== 'rtl'
			) {
				continue;
			}
			foreach ( $lang['site'] as $site ) {
				$dbname = $site['dbname'];
				if ( !isset( $rtl[$dbname] ) ) {
					$shouldBeRtl[] = $dbname;
				}
				unset( $rtl[$dbname] );
			}
		}
		$this->assertEquals( [], array_keys( $rtl ), 'All entries in rtl.dblist should correspond to RTL wikis' );
		$this->assertEquals( [], $shouldBeRtl, 'All RTL wikis should be registered in rtl.dblist' );
	}
}
