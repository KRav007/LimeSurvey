<?php if (!class_exists('Yii', false)) die('No direct script access allowed in ' . __FILE__);
/*
* LimeSurvey
* Copyright (C) 2007 The LimeSurvey Project Team / Carsten Schmitz
* All rights reserved.
* License: GNU/GPL License v2 or later, see LICENSE.php
* LimeSurvey is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

/**
* User Controller
*
* This controller performs user actions
*
* @package        LimeSurvey
* @subpackage    Backend
*/
class UserAction extends Survey_Common_Action
{

    function __construct($controller, $id)
    {
        parent::__construct($controller, $id);
        Yii::app()->loadHelper('database');
    }

    /**
    * Show users table
    */
    public function index()
    {
        App()->getClientScript()->registerScriptFile(Yii::app()->getConfig('generalscripts') . 'jquery/jquery.tablesorter.min.js');
        App()->getClientScript()->registerScriptFile(Yii::app()->getConfig('adminscripts').'users.js');

        $userlist = getUserList();
        $usrhimself = $userlist[0];
        unset($userlist[0]);

        if (Yii::app()->session['USER_RIGHT_SUPERADMIN'] == 1) {
            $noofsurveys = Survey::model()->countByAttributes(array("owner_id" => $usrhimself['uid']));
            $aData['noofsurveys'] = $noofsurveys;
        }

        $aData['row'] = 0;
        if (isset($usrhimself['parent_id']) && $usrhimself['parent_id'] != 0)
        {
            $aData['row'] = User::model()->findByAttributes(array('uid' => $usrhimself['parent_id']))->users_name;
        }


        $aData['usrhimself'] = $usrhimself;
        // other users
        $aData['usr_arr'] = $userlist;
        $noofsurveyslist = array();

        //This loops through for each user and checks the amount of surveys against them.
        for ($i = 1; $i <= count($userlist); $i++)
            $noofsurveyslist[$i] = $this->_getSurveyCountForUser($userlist[$i]);

        $aData['imageurl'] = Yii::app()->getConfig("adminimageurl");
        $aData['noofsurveyslist'] = $noofsurveyslist;

        $this->_renderWrappedTemplate('user', 'editusers', $aData);
    }

    private function _getSurveyCountForUser(array $user)
    {
        return Survey::model()->countByAttributes(array('owner_id' => $user['uid']));
    }

    function adduser()
    {
        if (!Yii::app()->session['USER_RIGHT_CREATE_USER']) {
            die(accessDenied('adduser'));
        }

        
        $new_user = flattenText(Yii::app()->request->getPost('new_user'), false, true);
        $new_email = flattenText(Yii::app()->request->getPost('new_email'), false, true);
        $new_full_name = flattenText(Yii::app()->request->getPost('new_full_name'), false, true);
        $aViewUrls = array();
        $valid_email = true;
        if (!validateEmailAddress($new_email)) {
            $valid_email = false;
            $aViewUrls['message'] = array('title' => gT("Failed to add user"), 'message' => gT("The email address is not valid."), 'class'=> 'warningheader');
        }
        if (empty($new_user)) {
            $aViewUrls['message'] = array('title' => gT("Failed to add user"), 'message' => gT("A username was not supplied or the username is invalid."), 'class'=> 'warningheader');
        }
        elseif (User::model()->find("users_name='$new_user'")) {
            $aViewUrls['message'] = array('title' => gT("Failed to add user"), 'message' => gT("The username already exists."), 'class'=> 'warningheader');
        }
        elseif ($valid_email)
        {
            $new_pass = createPassword();
            $iNewUID = User::model()->insertUser($new_user, $new_pass, $new_full_name, Yii::app()->session['loginID'], $new_email);

            if ($iNewUID) {
                // add default template to template rights for user
                Templates_rights::model()->insertRecords(array('uid' => $iNewUID, 'folder' => Yii::app()->getConfig("defaulttemplate"), 'use' => '1'));

                // add new user to userlist
                $sresult = User::model()->getAllRecords(array('uid' => $iNewUID));
                $srow = count($sresult);

                // send Mail
                $body = sprintf(gT("Hello %s,"), $new_full_name) . "<br /><br />\n";
                $body .= sprintf(gT("this is an automated email to notify that a user has been created for you on the site '%s'."), Yii::app()->getConfig("sitename")) . "<br /><br />\n";
                $body .= gT("You can use now the following credentials to log into the site:") . "<br />\n";
                $body .= gT("Username") . ": " . $new_user . "<br />\n";
                if (Yii::app()->getConfig("useWebserverAuth") === false) { // authent is not delegated to web server
                    // send password (if authorized by config)
                    if (Yii::app()->getConfig("display_user_password_in_email") === true) {
                        $body .= gT("Password") . ": " . $new_pass . "<br />\n";
                    }
                    else
                    {
                        $body .= gT("Password") . ": " . gT("Please contact your LimeSurvey administrator for your password.") . "<br />\n";
                    }
                }

                $body .= "<a href='" . $this->getController()->createAbsoluteUrl("/admin") . "'>" . gT("Click here to log in.") . "</a><br /><br />\n";
                $body .= sprintf(gT('If you have any questions regarding this mail please do not hesitate to contact the site administrator at %s. Thank you!'), Yii::app()->getConfig("siteadminemail")) . "<br />\n";

                $subject = sprintf(gT("User registration at '%s'", "unescaped"), Yii::app()->getConfig("sitename"));
                $to = $new_user . " <$new_email>";
                $from = Yii::app()->getConfig("siteadminname") . " <" . Yii::app()->getConfig("siteadminemail") . ">";
                $extra = '';
                $classMsg = '';
                if (SendEmailMessage($body, $subject, $to, $from, Yii::app()->getConfig("sitename"), true, Yii::app()->getConfig("siteadminbounce"))) {
                    $extra .= "<br />" . gT("Username") . ": $new_user<br />" . gT("Email") . ": $new_email<br />";
                    $extra .= "<br />" . gT("An email with a generated password was sent to the user.");
                    $classMsg = 'successheader';
                    $sHeader= gT("Success");
                }
                else
                {
                    // has to be sent again or no other way
                    $tmp = str_replace("{NAME}", "<strong>" . $new_user . "</strong>", gT("Email to {NAME} ({EMAIL}) failed."));
                    $extra .= "<br />" . str_replace("{EMAIL}", $new_email, $tmp) . "<br />";
                    $classMsg = 'warningheader';
                    $sHeader= gT("Warning");
                }

                $aViewUrls['mboxwithredirect'][] = $this->_messageBoxWithRedirect(gT("Add user"), $sHeader, $classMsg, $extra,
                $this->getController()->createUrl("admin/user/sa/setUserRights"), gT("Set user permissions"),
                array('action' => 'setUserRights', 'user' => $new_user, 'uid' => $iNewUID));
            }
            else
            {
                $aViewUrls['mboxwithredirect'][] = $this->_messageBoxWithRedirect(gT("Failed to add user"), gT("The user name already exists."), 'warningheader');
            }
        }

        $this->_renderWrappedTemplate('user', $aViewUrls);
    }

    /**
    * Delete user
    */
    function deluser()
    {
        if (!(Yii::app()->session['USER_RIGHT_SUPERADMIN'] == 1 || Yii::app()->session['USER_RIGHT_DELETE_USER'])) {
            die(accessDenied('deluser'));
        }
        
        $action = Yii::app()->request->getPost("action");
        $aViewUrls = array();

        // CAN'T DELETE ORIGINAL SUPERADMIN
        // Initial SuperAdmin has parent_id == 0
        $row = User::model()->findByAttributes(array('parent_id' => 0));

        $postuserid = Yii::app()->request->getPost("uid");
        $postuser = Yii::app()->request->getPost("user");
        if ($row['uid'] == $postuserid) // it's the original superadmin !!!
        {
            $aViewUrls['message'] = array('title' => gT('Initial Superadmin cannot be deleted!'), 'class' => 'warningheader');
        }
        else
        {
            if (isset($_POST['uid'])) {
                $sresultcount = 0; // 1 if I am parent of $postuserid
                if (Yii::app()->session['USER_RIGHT_SUPERADMIN'] != 1) {
                    $sresult = User::model()->findAllByAttributes(array('parent_id' => $postuserid, 'parent_id' => Yii::app()->session['loginID']));
                    $sresultcount = count($sresult);
                }

                if (Yii::app()->session['USER_RIGHT_SUPERADMIN'] == 1 || $sresultcount > 0 || $postuserid == Yii::app()->session['loginID']) {
                    $transfer_surveys_to = 0;
                    $ownerUser = User::model()->findAll();
                    $aData['users'] = $ownerUser;

                    $current_user = Yii::app()->session['loginID'];
                    if (count($ownerUser) == 2) {

                        $action = "finaldeluser";
                        foreach ($ownerUser as &$user)
                        {
                            if ($postuserid != $user['uid'])
                                $transfer_surveys_to = $user['uid'];
                        }
                    }

                    $ownerUser = Survey::model()->findAllByAttributes(array('owner_id' => $postuserid));
                    if (count($ownerUser) == 0) {
                        $action = "finaldeluser";
                    }

                    if ($action == "finaldeluser") {
                        $aViewUrls=$this->deleteFinalUser($ownerUser, $transfer_surveys_to);
                    }
                    else
                    {
                        $aData['postuserid'] = $postuserid;
                        $aData['postuser'] = $postuser;
                        $aData['current_user'] = $current_user;

                        $aViewUrls['deluser'][] = $aData;
                        $this->_renderWrappedTemplate('user', $aViewUrls);
                        
                    }
                }
                else
                {
                    echo accessDenied('deluser');
                    die();
                }
            }
            else
            {
                $aViewUrls['mboxwithredirect'][] = $this->_messageBoxWithRedirect("", gT("Could not delete user. User was not supplied."), "warningheader");
            }
        }

        return $aViewUrls;
    }

    function deleteFinalUser($result, $transfer_surveys_to)
    {
        
        $postuserid = Yii::app()->request->getPost("uid");
        $postuser = Yii::app()->request->getPost("user");

        if (isset($_POST['transfer_surveys_to'])) {
            $transfer_surveys_to = sanitize_int($_POST['transfer_surveys_to']);
        }
        if ($transfer_surveys_to > 0) {
            $iSurveysTransferred = Survey::model()->updateAll(array('owner_id' => $transfer_surveys_to), 'owner_id='.$postuserid);
        }
        $sresult = User::model()->findByAttributes(array('uid' => $postuserid));
        $fields = $sresult;
        if (isset($fields['parent_id'])) {
            $uresult = User::model()->updateAll(array('parent_id' => $fields['parent_id']), 'parent_id='.$postuserid);
        }

        //DELETE USER FROM TABLE
        $dresult = User::model()->deleteUser($postuserid);

        // Delete user rights
        $dresult = Survey_permissions::model()->deleteAllByAttributes(array('uid' => $postuserid));

        if ($postuserid == Yii::app()->session['loginID'])
        {
            session_destroy();    // user deleted himself
            $this->getController()->redirect($this->getController()->createUrl("admin/authentication/sa/logout"));
            die();
        }

        $extra = "<br />" . sprintf(gT("User '%s' was successfully deleted."),$postuser)."<br /><br />\n";
        if ($transfer_surveys_to > 0 && $iSurveysTransferred>0) {
            $user = User::model()->findByPk($transfer_surveys_to);
            $sTransferred_to = $user->users_name;
            //$sTransferred_to = $this->getController()->_getUserNameFromUid($transfer_surveys_to);
            $extra = sprintf(gT("All of the user's surveys were transferred to %s."), $sTransferred_to);
        }

        $aViewUrls['mboxwithredirect'][] = $this->_messageBoxWithRedirect("", gT("Success!"), "successheader", $extra);
        $this->_renderWrappedTemplate('user', $aViewUrls);
    }

    /**
    * Modify User
    */
    function modifyuser()
    {
        if (isset($_POST['uid'])) {
            $postuserid = sanitize_int($_POST['uid']);
            $sresult = User::model()->findAllByAttributes(array('uid' => $postuserid, 'parent_id' => Yii::app()->session['loginID']));
            $sresultcount = count($sresult);

            if (Yii::app()->session['USER_RIGHT_SUPERADMIN'] == 1 || Yii::app()->session['loginID'] == $postuserid ||
            (Yii::app()->session['USER_RIGHT_CREATE_USER'] && $sresultcount > 0) )
            {
                $sresult = User::model()->parentAndUser($postuserid);
                $aData['mur'] = $sresult;

                $this->_renderWrappedTemplate('user', 'modifyuser', $aData);
            }
            return;
        }
        echo accessDenied('modifyuser');
        die();
    }

    /**
    * Modify User POST
    */
    function moduser()
    {
        
        $postuser = Yii::app()->request->getPost("user");
        $postemail = Yii::app()->request->getPost("email");
        $postuserid = Yii::app()->request->getPost("uid");
        $postfull_name = Yii::app()->request->getPost("full_name");
        $display_user_password_in_html = Yii::app()->getConfig("display_user_password_in_html");
        $addsummary = '';
        $aViewUrls = array();

        $sresult = User::model()->findAllByAttributes(array('uid' => $postuserid, 'parent_id' => Yii::app()->session['loginID']));
        $sresultcount = count($sresult);

        if ((Yii::app()->session['USER_RIGHT_SUPERADMIN'] == 1 || $postuserid == Yii::app()->session['loginID'] ||
        ($sresultcount > 0 && Yii::app()->session['USER_RIGHT_CREATE_USER'])) && !(Yii::app()->getConfig("demo_mode") == true && $postuserid == 1)
        ) {
            $users_name = html_entity_decode($postuser, ENT_QUOTES, 'UTF-8');
            $email = html_entity_decode($postemail, ENT_QUOTES, 'UTF-8');
            $sPassword = html_entity_decode(Yii::app()->request->getPost('pass'), ENT_QUOTES, 'UTF-8');
            if ($sPassword == '%%unchanged%%')
                $sPassword = '';
            $full_name = html_entity_decode($postfull_name, ENT_QUOTES, 'UTF-8');

            if (!validateEmailAddress($email)) {
                $aViewUrls['mboxwithredirect'][] = $this->_messageBoxWithRedirect(gT("Editing user"), gT("Could not modify user data."), "warningheader", gT("Email address is not valid."),
                $this->getController()->createUrl('admin/user/modifyuser'), gT("Back"), array('uid' => $postuserid));
            }
            else
            {
                if (empty($sPassword))
                {
                    $uresult = User::model()->updateByPk($postuserid, array('email' => $this->escape($email), 'full_name' => $this->escape($full_name)));
                }
                else
                {
                    $uresult = User::model()->updateByPk($postuserid, array('email' => $this->escape($email), 'full_name' => $this->escape($full_name), 'password' => hash('sha256', $sPassword)));
                }

                if (empty($sPassword)) {
                    $extra = gT("Username") . ": $users_name<br />" . gT("Password") . ": (" . gT("Unchanged") . ")<br />\n";
                    $aViewUrls['mboxwithredirect'][] = $this->_messageBoxWithRedirect(gT("Editing user"), gT("Success!"), "successheader", $extra);
                }
                elseif ($uresult && !empty($sPassword))
                {
                    if ($sPassword != 'password')
                        Yii::app()->session['pw_notify'] = FALSE;
                    if ($sPassword == 'password')
                        Yii::app()->session['pw_notify'] = TRUE;

                    if ($display_user_password_in_html === true) {
                        $displayedPwd = $sPassword;
                    }
                    else
                    {
                        $displayedPwd = preg_replace('/./', '*', $sPassword);
                    }

                    $extra = gT("Username") . ": {$users_name}<br />" . gT("Password") . ": {$displayedPwd}<br />\n";
                    $aViewUrls['mboxwithredirect'][] = $this->_messageBoxWithRedirect(gT("Editing user"), gT("Success!"), "successheader", $extra);
                }
                else
                {
                    // Username and/or email adress already exists.
                    $aViewUrls['mboxwithredirect'][] = $this->_messageBoxWithRedirect(gT("Editing user"), gT("Could not modify user data. Email address already exists."), 'warningheader');
                }
            }
        }

        $this->_renderWrappedTemplate('user', $aViewUrls);
    }

    function setUserRights()
    {
        
        App()->getClientScript()->registerScriptFile(Yii::app()->getConfig('generalscripts') . 'jquery/jquery.tablesorter.min.js');
        App()->getClientScript()->registerScriptFile(Yii::app()->getConfig('adminscripts') . 'users.js');
        $postuserid = Yii::app()->request->getPost('uid');
#        $postuser = Yii::app()->request->getPost('user');
#        $postemail = Yii::app()->request->getPost('email');
#        $postfull_name = Yii::app()->request->getPost('full_name');
        if($postuserid==Yii::app()->session['loginID'])
        {
            $aData = $this->_messageBoxWithRedirect(gT("Set user permissions"), gT("You are not allowed to change your own permissions!"), 'warningheader');
            $aViewUrls['mboxwithredirect'][] = $aData;
        }
        else
        {
            $aData=array();
            if (isset($postuserid))
            {
                if(Yii::app()->session['USER_RIGHT_SUPERADMIN'] == 1)
                    $uresult = User::model()->findbyPk($postuserid);
                else
                    $uresult = User::model()->findbyPk($postuserid,'parent_id=:parent_id',array('parent_id' => Yii::app()->session['loginID']));
                if($uresult)
                {
                    $aData['users'][0]=$uresult->attributes;
                }
                else
                {
                    $aData = $this->_messageBoxWithRedirect(gT("Set user permissions"), gT("You are not allowed to change this user permissions!"), 'warningheader');
                    $aViewUrls['mboxwithredirect'][] = $aData;
                }
            }
            else
            {
                $uresult = User::model()->findAll('uid!=:uid AND parent_id=:parent_id',array( 'uid'=>Yii::app()->session['loginID'],'parent_id' => Yii::app()->session['loginID']));
                foreach($uresult as $user)
                {
                    $aData['users'][]=$user->attributes;
                }
            }
            if(isset($aData['users']))
            {
                // Get Parent right (this loginId rights)
                $thisUserRights=User::GetUserRights();
                // Fix some specific rights
                $thisUserRights['superadmin']=$thisUserRights['initialsuperadmin'];
                unset($thisUserRights['initialsuperadmin']);
                $aData['allowedRights']=array_keys(array_filter($thisUserRights));
                $aViewUrls['setuserrights'][]=$aData;
            }
            elseif(!isset($aViewUrls['mboxwithredirect']))
            {
                $aData = $this->_messageBoxWithRedirect(gT("Set user permissions"), gT("You are not allowed to change any user permissions!"), 'warningheader');
                $aViewUrls['mboxwithredirect'][] = $aData;
            }
        }
        $this->_renderWrappedTemplate('user', $aViewUrls);
    }

    /**
    * User Rights POST
    */
    function userrights()
    {
        
        $postuserid = Yii::app()->request->getPost("uid");
        $aViewUrls = array();

        // A user can't modify his own rights
        if ($postuserid != Yii::app()->session['loginID']) {
            $sresult = User::model()->findAllByAttributes(array('uid' => $postuserid, 'parent_id' => Yii::app()->session['loginID']));
            $sresultcount = count($sresult);

            if ($sresultcount > 0 || User::GetUserRights('superadmin')) 
            { // User (non super admin) can not modifiy other admin created user
                $thisUserRights=User::GetUserRights();
                $thisUserRights['superadmin']=$thisUserRights['initialsuperadmin'];
                $rights = array();
                foreach($thisUserRights as $userRight => $thisUserRight)
                {
                    $rights[$userRight]=(isset($_POST[$userRight]) && $thisUserRight) ? 1 : 0;
                }
                $rights['superadmin'] = ($rights['superadmin'] && $thisUserRights['initialsuperadmin']) ? 1 : 0; // ONLY Initial Superadmin can give this right


                if (!User::GetUserRights('initialsuperadmin',$postuserid))// This can not be happened
                    User::setUserRights($postuserid, $rights);
            }
            else
            {
                $aViewUrls['mboxwithredirect'][] = $this->_messageBoxWithRedirect(gT("Set user permissions"), gT("You are not allowed to change this user permissions!"), 'warningheader');
            }
            $aViewUrls['mboxwithredirect'][] = $this->_messageBoxWithRedirect(gT("Set user permissions"), gT("User permissions were updated successfully."), 'successheader');
        }
        else
        {
            $aViewUrls['mboxwithredirect'][] = $this->_messageBoxWithRedirect(gT("Set user permissions"), gT("You are not allowed to change your own permissions!"), 'warningheader');
        }

        $this->_renderWrappedTemplate('user', $aViewUrls);
    }

    function setusertemplates()
    {
        App()->getClientScript()->registerScriptFile(Yii::app()->getConfig('generalscripts') . 'jquery/jquery.tablesorter.min.js');
        App()->getClientScript()->registerScriptFile(Yii::app()->getConfig('adminscripts') . 'users.js');
        $aData['postuser']  = Yii::app()->request->getPost("user");
        $aData['postemail'] = Yii::app()->request->getPost("email");
        $postuserid = Yii::app()->request->getPost("uid");
        $aData['postuserid'] = $postuserid;
        $aData['postfull_name'] = Yii::app()->request->getPost("full_name");
        $this->_refreshtemplates();
        foreach (getUserList() as $usr)
        {
            if ($usr['uid'] == $postuserid)
            {
                $trights = Templates_rights::model()->findAllByAttributes(array('uid' => $usr['uid']));
                foreach ($trights as $srow)
                {
                    $templaterights[$srow["folder"]] = array("use"=>$srow["use"]);
                }
                $templates = Template::model()->findAll();
                $aData['list'][] = array('templaterights'=>$templaterights,'templates'=>$templates);
            }
        }
        $this->_renderWrappedTemplate('user', 'setusertemplates', $aData);
    }

    function usertemplates()
    {
        
        $postuserid = Yii::app()->request->getPost('uid');

        // SUPERADMINS AND MANAGE_TEMPLATE USERS CAN SET THESE RIGHTS
        if (Yii::app()->session['USER_RIGHT_SUPERADMIN'] == 1 || Yii::app()->session['USER_RIGHT_MANAGE_TEMPLATE'] == 1) {
            $templaterights = array();
            $tresult = Template::model()->findAll();
            $postvalue= array_flip($_POST);
            foreach ($tresult as $trow)
            {
                if (isset($postvalue[$trow["folder"] . "_use"]))
                    $templaterights[$trow["folder"]] = 1;
                else
                    $templaterights[$trow["folder"]] = 0;
            }
            foreach ($templaterights as $key => $value)
            {
                $rights = Templates_rights::model()->findByPk(array('folder' => $key, 'uid' => $postuserid));
                if (empty($rights))
                {
                    $rights = new Templates_rights;
                    $rights->uid = $postuserid;
                    $rights->folder = $key;
                }
                $rights->use = $value;
                $uresult = $rights->save();
            }
            if ($uresult !== false) {
                $aViewUrls['mboxwithredirect'][] = $this->_messageBoxWithRedirect(gT("Set template permissions"), gT("Template permissions were updated successfully."), "successheader");
            }
            else
            {
                $aViewUrls['mboxwithredirect'][] = $this->_messageBoxWithRedirect(gT("Set template permissions"), gT("Error while updating usertemplates."), "warningheader");
            }
        }
        else
        {
            die('access denied');
        }

        $this->_renderWrappedTemplate('user', $aViewUrls);
    }

    /**
    * Manage user personal settings
    */
    function personalsettings()
    {

        // Save Data
        if (Yii::app()->request->getPost("action")) {
            $aData = array(
            'lang' => Yii::app()->request->getPost('lang'),
            'dateformat' => Yii::app()->request->getPost('dateformat'),
            'htmleditormode' => Yii::app()->request->getPost('htmleditormode'),
            'questionselectormode' => Yii::app()->request->getPost('questionselectormode'),
            'templateeditormode' => Yii::app()->request->getPost('templateeditormode')
            );

            $uresult = User::model()->updateByPk(Yii::app()->session['loginID'], $aData);

            if (Yii::app()->request->getPost('lang')=='auto')
            {
                $sLanguage= getBrowserLanguage();
            }
            else
            {
                $sLanguage=Yii::app()->request->getPost('lang');
            }

            Yii::app()->session['adminlang'] = $sLanguage;
            
            Yii::app()->session['htmleditormode'] = Yii::app()->request->getPost('htmleditormode');
            Yii::app()->session['questionselectormode'] = Yii::app()->request->getPost('questionselectormode');
            Yii::app()->session['templateeditormode'] = Yii::app()->request->getPost('templateeditormode');
            Yii::app()->session['dateformat'] = Yii::app()->request->getPost('dateformat');
            Yii::app()->session['flashmessage'] = gT("Your personal settings were successfully saved.");
        }

        // Get user lang
        $user = User::model()->findByPk(Yii::app()->session['loginID']);
        $aData['sSavedLanguage'] = $user->lang;

        // Render personal settings view
        $this->getController()->render('/admin/user/personalsettings', $aData);
    }

    private function _getUserNameFromUid($uid)
    {
        $uid = sanitize_int($uid);
        $result = User::model()->findByPk($uid);

        if (!empty($result)) {
            return $result->users_name;
        }
        else
        {
            return false;
        }
    }

    private function _refreshtemplates()
    {
        $template_a = getTemplateList();
        foreach ($template_a as $tp => $fullpath)
        {
            // check for each folder if there is already an entry in the database
            // if not create it with current user as creator (user with rights "create user" can assign template rights)
            $result = Template::model()->findByPk($tp);

            if (count($result) == 0) {
                $post = new Template;
                $post->folder = $tp;
                $post->creator = Yii::app()->session['loginID'];
                $post->save();
            }
        }
        return true;
    }

    private function escape($str)
    {
        if (is_string($str)) {
            $str = $this->escape_str($str);
        }
        elseif (is_bool($str))
        {
            $str = ($str === true) ? 1 : 0;
        }
        elseif (is_null($str))
        {
            $str = 'NULL';
        }

        return $str;
    }

    private function escape_str($str, $like = FALSE)
    {
        if (is_array($str)) {
            foreach ($str as $key => $val)
            {
                $str[$key] = $this->escape_str($val, $like);
            }

            return $str;
        }

        // Escape single quotes
        $str = str_replace("'", "''", $this->remove_invisible_characters($str));

        return $str;
    }

    private function remove_invisible_characters($str, $url_encoded = TRUE)
    {
        $non_displayables = array();

        // every control character except newline (dec 10)
        // carriage return (dec 13), and horizontal tab (dec 09)

        if ($url_encoded) {
            $non_displayables[] = '/%0[0-8bcef]/'; // url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/'; // url encoded 16-31
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S'; // 00-08, 11, 12, 14-31, 127

        do
        {
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        } while ($count);

        return $str;
    }

    private function _messageBoxWithRedirect($title, $message, $classMsg, $extra = "", $url = "", $urlText = "", $hiddenVars = array(), $classMbTitle = "header ui-widget-header")
    {
        
        $url = (!empty($url)) ? $url : $this->getController()->createUrl('admin/user/index');
        $urlText = (!empty($urlText)) ? $urlText : gT("Continue");

        $aData['title'] = $title;
        $aData['message'] = $message;
        $aData['url'] = $url;
        $aData['urlText'] = $urlText;
        $aData['classMsg'] = $classMsg;
        $aData['classMbTitle'] = $classMbTitle;
        $aData['extra'] = $extra;
        $aData['hiddenVars'] = $hiddenVars;

        return $aData;
    }

    /**
    * Renders template(s) wrapped in header and footer
    *
    * @param string $sAction Current action, the folder to fetch views from
    * @param string|array $aViewUrls View url(s)
    * @param array $aData Data to be passed on. Optional.
    */
    protected function _renderWrappedTemplate($sAction = 'user', $aViewUrls = array(), $aData = array())
    {
        parent::_renderWrappedTemplate($sAction, $aViewUrls, $aData);
    }

}
