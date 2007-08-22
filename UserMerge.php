<?php
# Not a valid entry point, skip unless MEDIAWIKI is defined
if (!defined('MEDIAWIKI')) {
        echo "User Merge and Delete extension";
        exit(1);
}
 
$wgExtensionCredits['specialpage'][] = array(
    'name'=>'User Merge and Delete',
    'url'=>'http://www.mediawiki.org/wiki/Extension:User_Merge_and_Delete',
    'author'=>'Tim Laqua, t.laqua at gmail dot com',
    'description'=>"Merges references from one user to another user in the Wiki database - will also delete old users following merge.  Requires 'userrights' privileges.",
    'version'=>'1.0'
);
 
$wgAutoloadClasses['UserMerge'] = dirname(__FILE__) . '/UserMerge_body.php';
$wgSpecialPages['UserMerge'] = 'UserMerge';
 
if ( version_compare( $wgVersion, '1.10.0', '<' ) ) {
    //Extension designed for 1.10.0+, but will work on some older versions
    //LoadAllMessages hook throws errors before 1.10.0
} else {
    $wgHooks['LoadAllMessages'][] = 'UserMerge::loadMessages';
}
