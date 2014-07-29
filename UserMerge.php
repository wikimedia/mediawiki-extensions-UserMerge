<?php
/** \file
 * \brief Contains setup code for the User Merge and Delete Extension.
 */

/**
 * UserMerge Extension for MediaWiki
 *
 * Copyright (C) Tim Laqua
 * Copyright (C) Thomas Gries
 * Copyright (C) Matthew April
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

# Not a valid entry point, skip unless MEDIAWIKI is defined
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

$wgExtensionCredits['specialpage'][] = array(
	'path'           => __FILE__,
	'name'           => 'User Merge and Delete',
	'url'            => 'https://www.mediawiki.org/wiki/Extension:User_Merge_and_Delete',
	'author'         => array( 'Tim Laqua', 'Thomas Gries', 'Matthew April' ),
	'descriptionmsg' => 'usermerge-desc',
	'version'        => '1.9.0'
);

// Configuration options:

/**
 * Whether to allow users to delete other users.
 * If false, users with the "usermerge" right
 * will only be able to merge other users.
 *
 * @var bool
 */
$wgUserMergeEnableDelete = true;


$wgAvailableRights[] = 'usermerge';
# $wgGroupPermissions['bureaucrat']['usermerge'] = true;

$dir = __DIR__ . '/';
$wgAutoloadClasses['SpecialUserMerge'] = $dir . 'SpecialUserMerge.php';
$wgAutoloadClasses['UserMerge'] = $dir . 'SpecialUserMerge.php'; // back-compat
$wgAutoloadClasses['MergeUser'] = $dir . 'MergeUser.php';
$wgAutoloadClasses['IUserMergeLogger'] = $dir . 'IUserMergeLogger.php';
$wgAutoloadClasses['UserMergeLogger'] = $dir . 'UserMergeLogger.php';


$wgMessagesDirs['UserMerge'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['UserMerge'] = $dir . 'UserMerge.i18n.php';
$wgExtensionMessagesFiles['UserMergeAlias'] = $dir . 'UserMerge.alias.php';
$wgSpecialPages['UserMerge'] = 'UserMerge';
$wgSpecialPageGroups['UserMerge'] = 'users';

$wgUserMergeProtectedGroups = array( "sysop" );

# Add a new log type
$wgLogTypes[]                         = 'usermerge';
$wgLogNames['usermerge']              = 'usermerge-logpage';
$wgLogHeaders['usermerge']            = 'usermerge-logpagetext';
$wgLogActions['usermerge/mergeuser']  = 'usermerge-success-log';
$wgLogActions['usermerge/deleteuser'] = 'usermerge-userdeleted-log';
