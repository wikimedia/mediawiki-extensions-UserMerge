<?php
class UserMerge extends SpecialPage
{
        function UserMerge() {
                SpecialPage::SpecialPage("UserMerge","userrights");
                self::loadMessages();
        }
 
        function execute( $par ) {
                global $wgRequest, $wgOut, $wgUser, $wgTitle;
 
                $this->setHeaders();
 
                if ( !$wgUser->isAllowed( 'userrights' ) ) {
                        $wgOut->permissionRequired( 'userrights' );
                        return;
                }
 
                if (strlen($wgRequest->getText('olduser').$wgRequest->getText('newuser'))>0 || $wgRequest->getText( 'deleteuser' )) {
                    //POST data found
                    $olduser = Title::newFromText( $wgRequest->getText( 'olduser' ) );
                    $olduser_text = is_object( $olduser ) ? $olduser->getText() : '';
 
                    $newuser = Title::newFromText( $wgRequest->getText( 'newuser' ) );
                    $newuser_text = is_object( $newuser ) ? $newuser->getText() : '';
 
                    if ($wgRequest->getText( 'deleteuser' )) {
                      $deleteUserCheck = "CHECKED ";
                    }
 
                    if (strlen($olduser_text)>0) {
                      $objOldUser = User::newFromName( $olduser_text );
                      $olduserID = $objOldUser->idForName();
 
                      if ( !is_object( $objOldUser ) || $olduserID == 0 ) {
                        $validOldUser = false;
                        $wgOut->addHTML( "<span style=\"color: red;\">Invalid Old Username</span><br>\n" );
                      } else {
                        $validOldUser = true;
 
                        if (strlen($newuser_text)>0) {
 
                          $objNewUser = User::newFromName( $newuser_text );
                          $newuserID = $objNewUser->idForName();
 
                          if ( !is_object( $objNewUser ) || $newuserID == 0 ) {
                            //invalid newuser entered
                            $validNewUser = false;
                            $wgOut->addHTML( "<span style=\"color: red;\">Invalid New User.</span><br>\n" );
                          } else {
                            //newuser looks good
                            $validNewUser = true;
                          }
                        } else {
                          //empty newuser string
                          $validNewUser = false;
                          $newuser_text = User::whoIs(1);
                          $wgOut->addHTML( "<span style=\"color: red;\">Empty New Username - Assuming merge to $newuser_text.\nClick <U>Merge User</u> to accept.</span><br>\n" );
                        }
                      }
                    } else {
                      $validOldUser = false;
                      $wgOut->addHTML( "<span style=\"color: red;\">Empty Old Username</span><br>\n" );
                    }
                } else {
                    //NO POST data found
                }
 
                $action = $wgTitle->escapeLocalUrl();
                $token = $wgUser->editToken();
 
                $wgOut->addHTML( "
<form id='usermergeform' method='post' action=\"$action\">
<table>
        <tr>
                <td align='right'>Old User(Merge From)</td>
                <td align='left'><input tabindex='1' type='text' size='20' name='olduser' id='olduser' value=\"$olduser_text\" onFocus=\"document.getElementById('olduser').select;\" /></td>
        </tr>
        <tr>
                <td align='right'>New User(Merge To)</td>
                <td align='left'><input tabindex='2' type='text' size='20' name='newuser' id='newuser' value=\"$newuser_text\" onFocus=\"document.getElementById('newuser').select;\" /></td>
        </tr>
        <tr>
                <td align='right'>Delete Old User?</td>
                <td align='left'><input tabindex='3' type='checkbox' name='deleteuser' id='deleteuser' $deleteUserCheck/></td>
        </tr>
        <tr>
                <td>&nbsp;</td>
                <td align='right'><input type='submit' name='submit' value=\"Merge User\" /></td>
        </tr>
</table>
<input type='hidden' name='token' value='$token' />
</form>");
 
                if ($validNewUser && $validOldUser) {
                  //go time, baby
                  if (!$wgUser->matchEditToken( $wgRequest->getVal( 'token' ) ) ) {
                    //bad editToken
                    $wgOut->addHTML( "<span style=\"color: red;\">Invalid Edit Token.</span><br>\n" );
                  } else {
                    //good editToken
                    $this->mergeUser($newuser_text,$newuserID,$olduser_text,$olduserID);
                    if ($wgRequest->getText( 'deleteuser' )) {
                      $this->deleteUser($olduserID, $olduser_text);
                    }
                  }
                }
        }
 
        function deleteUser ($olduserID, $olduser_text) {
                global $wgOut;
 
                $dbw =& wfGetDB( DB_MASTER );
                $dbw->delete( 'user_groups', array( 'ug_user' => $olduserID ));
                $dbw->delete( 'user', array( 'user_id' => $olduserID ));
                $wgOut->addHTML("$olduser_text($olduserID) has been deleted.");
        }
 
        function mergeUser ($newuser_text, $newuserID, $olduser_text, $olduserID) {
                global $wgOut;
 
                $textUpdateFields = array(array('archive','ar_user_text'),
                                          array('revision','rev_user_text'),
                                          array('filearchive','fa_user_text'),
                                          array('image','img_user_text'),
                                          array('oldimage','oi_user_text'),
                                          array('recentchanges','rc_user_text'),
                                          array('ipblocks','ipb_address'));
 
                $idUpdateFields = array(array('archive','ar_user'),
                                          array('revision','rev_user'),
                                          array('filearchive','fa_user'),
                                          array('image','img_user'),
                                          array('oldimage','oi_user'),
                                          array('recentchanges','rc_user'),
                                          array('logging','log_user'));
 
                $dbw =& wfGetDB( DB_MASTER );
 
                foreach ($idUpdateFields as $idUpdateField) {
                  $dbw->update($idUpdateField[0], array( $idUpdateField[1] => $newuserID ), array( $idUpdateField[1] => $olduserID ));
                  $wgOut->addHTML("Updating $idUpdateField[0] table ($olduserID to $newuserID)<br>\n");
                }
 
                foreach ($textUpdateFields as $textUpdateField) {
                  $dbw->update($textUpdateField[0], array( $textUpdateField[1] => $newuser_text ), array( $textUpdateField[1] => $olduser_text ));
                  $wgOut->addHTML("Updating $textUpdateField[0] table ($olduser_text to $newuser_text)<br>\n");
                }
 
 
                $dbw->delete( 'user_newtalk', array( 'user_ip' => $olduserID ));
 
                $wgOut->addHTML("<hr />\nMerge from $olduser_text($olduserID) to $newuser_text($newuserID) is complete.\n<br>");
        }
 
        function loadMessages() {
                static $messagesLoaded = false;
                global $wgMessageCache;
                if ( $messagesLoaded ) return true;
                $messagesLoaded = true;
 
                require( dirname( __FILE__ ) . '/UserMerge.i18n.php' );
                foreach ( $allMessages as $lang => $langMessages ) {
                        $wgMessageCache->addMessages( $langMessages, $lang );
                }
 
                                return true;
        }
}
