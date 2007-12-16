<?php
/** \file
* \brief Contains setup code for the User Merge and Delete Extension.
*/

# Not a valid entry point, skip unless MEDIAWIKI is defined
if (!defined('MEDIAWIKI')) {
        echo "User Merge and Delete extension";
        exit(1);
}

$wgExtensionFunctions[] = 'efUserMerge';
$wgExtensionCredits['specialpage'][] = array(
    'name'=>'User Merge and Delete',
    'url'=>'http://www.mediawiki.org/wiki/Extension:User_Merge_and_Delete',
    'author'=>'Tim Laqua',
    'description'=>"Merges references from one user to another user in the Wiki database - will also delete old users following merge.  Requires 'usermerge' privileges.",
    'version'=>'1.3'
);

$wgAutoloadClasses['UserMerge'] = dirname(__FILE__) . '/UserMerge_body.php';
$wgSpecialPages['UserMerge'] = 'UserMerge';

require( dirname( __FILE__ ) . '/UserMerge.i18n.php' );

function efUserMerge() {
	#Add Messages
	global $wgMessageCache, $usermergeMessages;
	foreach( $usermergeMessages as $key => $value ) {
		$wgMessageCache->addMessages( $usermergeMessages[$key], $key );
	}
	
	# Add a new log type
	global $wgLogTypes, $wgLogNames, $wgLogHeaders, $wgLogActions;
	$wgLogTypes[]                 		= 'usermerge';
	$wgLogNames['usermerge']            = 'usermerge-logpage';
	$wgLogHeaders['usermerge']          = 'usermerge-logpagetext';
	$wgLogActions['usermerge/mergeuser'] 	= 'usermerge-success-log';
	$wgLogActions['usermerge/deleteuser']	= 'usermerge-userdeleted-log';
}

