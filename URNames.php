<?php

/**
 * Extension adds users' real names, if set (only for logged in users) to following pages:
 * History pages
 * Recentchanges
 * Special:Listusers
 * Special:Activeusers
 * Special:BlockList
 */



if ( !defined( 'MEDIAWIKI' ) ) {
echo <<<EOT
	To install my extension, put the following line in LocalSettings.php:
	require_once( '\$IP/extensions/URNames/URNames.php' );
EOT;
exit( 1 );
}


$wgExtensionCredits['specialpage' ][] = array(
	'path' => __FILE__,
	'name' => 'URNames',
	'author' => 'Josef Martiňák',
	'url' => 'https://www.mediawiki.org/wiki/Extension:URNames',
	'descriptionmsg' => 'urnames-desc',
	'version' => '0.1.1'
);

$dir = dirname( __FILE__ );
$wgExtensionMessagesFiles['URnames'] = $dir . '/URnames.i18n.php';

$wgHooks['BeforePageDisplay'][] = 'replaceUserNames';

/**
 * Class for replacing output code parts
 */
class URNames{

	/**
	 * @var string $pagename: page name, we want to add a real name to
	 * @example: 'Listusers', 'Activeusers', 'Blocklist', 'RecentChanges', 'history'
	 */
	private $pagename;

	/**
	 * @var object $user: instance of User
	 */
	private $user;

	/**
	 * @var object $out: instance of OutputPage
	 */
	private $out;


	/**
	 * Constructor - init variables
	 */
	public function __construct( $pagename, $user, $out ) {
		$this->pagename = $pagename;
		$this->user = $user;
		$this->out = $out;
	}


	/**
	 * Add real name to output code
	 */
	public function replace() {
		if( $this->pagename == 'Listusers' ) {
			$pattern = '/title=\"([^\"]*)\">([^<]*)<\/a>/';
			$callback = preg_replace_callback( $pattern, array( $this, 'listUsersReplace' ),
				$this->out->mBodytext );
			return $callback;
		}
		else {
			// default
			$pattern = '/class=\"mw-userlink\">([^<]*)<\/a>/';
			$callback = preg_replace_callback( $pattern, array( $this, 'defaultReplace' ),
				$this->out->mBodytext );
			return $callback;
		}
	}


	/**
	 * Replace code parts - default method
	 * @param array $matches: found occurances of searched string
	 * @return code part with real name
	 */
	private function defaultReplace( $matches ) {
		$output = "class=\"mw-userlink\">$matches[1]</a> ";
		$output .= '(' . $this->user->whoIsReal( $this->user->idFromName( $matches[1] ) ) . ')';
		$output = preg_replace( '/ \(\)/', '', $output );
		return $output;
	}


	/**
	 * Replace code parts in Special:ListUsers
	 * @param array $matches: found occurances of searched string
	 * @return code part with real name
	 */
	private function listUsersReplace( $matches ) {
		$output = "title=\"$matches[1]\">$matches[2]</a> ";
		$output .= '(' . $this->user->whoIsReal( $this->user->idFromName( $matches[2] ) ) . ')';
		$output = preg_replace( '/ \(\)/', '', $output );
		return $output;
	}
}



/**
 * Adds real user names to specific special page or history page
 * @param OutputPage $out
 * @param Skin $skin Unused
 * @return bool
 */
function replaceUserNames( &$out, &$skin ) {
	$user = $out->getUser();
	if( !$user->isLoggedIn() ) {
		// user is not logged - no action
		return true;
	}

	$title = $out->getTitle();
	$request = $out->getRequest();
	$query = $request->getQueryValues();
	$pagename = '';

	if( $title->isSpecialPage() ) {
		list( $pagename, /*...*/ ) = SpecialPageFactory::resolveAlias( $title->getBaseText() );
	}
	elseif( isset( $query['action'] ) && $query['action'] == 'history' ) {
		$pagename = 'history';
	}
	if( in_array( $pagename, array( 'Recentchanges', 'Activeusers', 'BlockList', 'Listusers', 'history' ) ) ) {
		$urnames = new URNames( $pagename, $user, $out );
		$out->mBodytext = $urnames->replace();
	}

	return true;
}

