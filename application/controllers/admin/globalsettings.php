<?php
/*
* LimeSurvey
* Copyright (C) 2007-2011 The LimeSurvey Project Team / Carsten Schmitz
* All rights reserved.
* License: GNU/GPL License v2 or later, see LICENSE.php
* LimeSurvey is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*
*   $Id$
*/

/**
* GlobalSettings Controller
*
*
* @package        LimeSurvey
* @subpackage    Backend
*/
class GlobalSettings extends Survey_Common_Action
{

    function __construct($controller, $id)
    {
        parent::__construct($controller, $id);

        if (Yii::app()->session['USER_RIGHT_CONFIGURATOR'] != 1) {
            die();
        }
    }

    /**
    * Shows the index page
    *
    * @access public
    * @return void
    */
    public function index()
    {
        if (!empty($_POST['action'])) {
            $this->_saveSettings();
        }
        $this->_displaySettings();
    }

    public function showphpinfo()
    {
        if (!Yii::app()->getConfig('demo_mode')) {
            phpinfo();
        }
    }

    public function updatecheck()
    {
        updateCheck();
        $this->getController()->redirect('admin/globalsettings');
    }

    private function _displaySettings()
    {
        Yii::app()->loadHelper('surveytranslator');

        //save refurl from where global settings screen is called!
        $refurl = Yii::app()->getRequest()->getUrlReferrer();

        // Some URLs are not to be allowed to refered back to.
        // These exceptions can be added to the $aReplacements array
        $aReplacements=array('admin/user/adduser'=>'admin/user/index',
                             'admin/user/sa/adduser'=>'admin/user/sa/index',
                             'admin/user/sa/setusertemplates'=>'admin/user/sa/index',
                             'admin/user/setusertemplates'=>'admin/user/sa/index'
                             
                            );
        $refurl= str_replace(array_keys($aReplacements),array_values($aReplacements),$refurl);
        Yii::app()->session['refurl'] = htmlspecialchars($refurl); //just to be safe!

        $data['title'] = "hi";
        $data['message'] = "message";
        foreach ($this->_checkSettings() as $key => $row)
        {
            $data[$key] = $row;
        }
        $data['thisupdatecheckperiod'] = getGlobalSetting('updatecheckperiod');
        $data['updatelastcheck'] = getGlobalSetting("updatelastcheck");
        $data['updateavailable'] = (getGlobalSetting("updateavailable") &&  Yii::app()->getConfig("updatable"));
        $data['updatable'] = Yii::app()->getConfig("updatable");
        $data['updateinfo'] = getGlobalSetting("updateinfo");
        $data['updatebuild'] = getGlobalSetting("updatebuild");
        $data['updateversion'] = getGlobalSetting("updateversion");
        $data['allLanguages'] = getLanguageData(false, Yii::app()->session['adminlang']);
        if (trim(Yii::app()->getConfig('restrict_to_languages')) == '') {
            $data['restrict_to_languages'] = array_keys($data['allLanguages']);
            $data['excludedLanguages'] = array();
        }
        else
        {
            $data['restrict_to_languages'] = explode(' ', trim(Yii::app()->getConfig('restrict_to_languages')));
            $data['excludedLanguages'] = array_diff(array_keys($data['allLanguages']), $data['restrict_to_languages']);
        }

        $this->_renderWrappedTemplate('', 'globalSettings_view', $data);
    }

    private function _saveSettings()
    {
        if ($_POST['action'] !== "globalsettingssave") {
            return;
        }

        if (Yii::app()->session['USER_RIGHT_CONFIGURATOR'] != 1) {
            $this->getController()->redirect($this->getController()->createUrl('/admin'));
        }
        
        Yii::app()->loadHelper('surveytranslator');

        $maxemails = $_POST['maxemails'];
        if (sanitize_int($_POST['maxemails']) < 1) {
            $maxemails = 1;
        }

        $defaultlang = sanitize_languagecode($_POST['defaultlang']);
        $arestrict_to_languages = explode(' ', sanitize_languagecodeS($_POST['restrict_to_languages']));
        if (!in_array($defaultlang,$arestrict_to_languages)){ // Force default language in restrict_to_languages
            $arestrict_to_languages[]=$defaultlang;
        }
        if (count(array_diff(array_keys(getLanguageData(false,Yii::app()->session['adminlang'])), $arestrict_to_languages)) == 0) {
            $arestrict_to_languages = '';
        } else {
            $arestrict_to_languages = implode(' ', $arestrict_to_languages);
        }

        setGlobalSetting('defaultlang', $defaultlang);
        setGlobalSetting('restrict_to_languages', trim($arestrict_to_languages));
        setGlobalSetting('sitename', strip_tags($_POST['sitename']));
        setGlobalSetting('updatecheckperiod', (int)($_POST['updatecheckperiod']));
        setGlobalSetting('defaulthtmleditormode', sanitize_paranoid_string($_POST['defaulthtmleditormode']));
        setGlobalSetting('defaultquestionselectormode', sanitize_paranoid_string($_POST['defaultquestionselectormode']));
        setGlobalSetting('defaulttemplateeditormode', sanitize_paranoid_string($_POST['defaulttemplateeditormode']));
        setGlobalSetting('defaulttemplate', sanitize_paranoid_string($_POST['defaulttemplate']));
        setGlobalSetting('admintheme', sanitize_paranoid_string($_POST['admintheme']));
        setGlobalSetting('adminthemeiconsize', trim(file_get_contents(Yii::app()->getConfig("styledir").DIRECTORY_SEPARATOR.sanitize_paranoid_string($_POST['admintheme']).DIRECTORY_SEPARATOR.'iconsize')));
        setGlobalSetting('emailmethod', strip_tags($_POST['emailmethod']));
        setGlobalSetting('emailsmtphost', strip_tags(returnGlobal('emailsmtphost')));
        if (returnGlobal('emailsmtppassword') != 'somepassword') {
            setGlobalSetting('emailsmtppassword', strip_tags(returnGlobal('emailsmtppassword')));
        }
        setGlobalSetting('bounceaccounthost', strip_tags(returnGlobal('bounceaccounthost')));
        setGlobalSetting('bounceaccounttype', strip_tags(returnGlobal('bounceaccounttype')));
        setGlobalSetting('bounceencryption', strip_tags(returnGlobal('bounceencryption')));
        setGlobalSetting('bounceaccountuser', strip_tags(returnGlobal('bounceaccountuser')));

        if (returnGlobal('bounceaccountpass') != 'enteredpassword') setGlobalSetting('bounceaccountpass', strip_tags(returnGlobal('bounceaccountpass')));

        setGlobalSetting('emailsmtpssl', sanitize_paranoid_string(Yii::app()->request->getPost('emailsmtpssl','')));
        setGlobalSetting('emailsmtpdebug', sanitize_int(Yii::app()->request->getPost('emailsmtpdebug','0')));
        setGlobalSetting('emailsmtpuser', strip_tags(returnGlobal('emailsmtpuser')));
        setGlobalSetting('filterxsshtml', strip_tags($_POST['filterxsshtml']));
        setGlobalSetting('allusercopymodel', strip_tags($_POST['allusercopymodel']));
        setGlobalSetting('siteadminbounce', strip_tags($_POST['siteadminbounce']));
        setGlobalSetting('siteadminemail', strip_tags($_POST['siteadminemail']));
        setGlobalSetting('siteadminname', strip_tags($_POST['siteadminname']));
        setGlobalSetting('shownoanswer', sanitize_int($_POST['shownoanswer']));
        setGlobalSetting('showxquestions', ($_POST['showxquestions']));
        setGlobalSetting('showgroupinfo', ($_POST['showgroupinfo']));
        setGlobalSetting('showqnumcode', ($_POST['showqnumcode']));
        $repeatheadingstemp = (int)($_POST['repeatheadings']);
        if ($repeatheadingstemp == 0) $repeatheadingstemp = 25;
        setGlobalSetting('repeatheadings', $repeatheadingstemp);

        setGlobalSetting('maxemails', sanitize_int($maxemails));
        $session_expiration_time = (int)($_POST['session_expiration_time']);
        if ($session_expiration_time == 0) $session_expiration_time = 3600;
        setGlobalSetting('session_expiration_time', $session_expiration_time);
        setGlobalSetting('ipinfodb_api_key', $_POST['ipinfodb_api_key']);
        setGlobalSetting('googlemaps_api_key', $_POST['googlemaps_api_key']);
        setGlobalSetting('googleanalyticsapikey',$_POST['googleanalyticsapikey']);
        setGlobalSetting('googletranslateapikey',$_POST['googletranslateapikey']);
        setGlobalSetting('characterset',$_POST['characterset']);
        setGlobalSetting('force_ssl', $_POST['force_ssl']);
        setGlobalSetting('survey_preview_admin_only', $_POST['survey_preview_admin_only']);
        setGlobalSetting('rpc_interface', $_POST['rpc_interface']);
        setGlobalSetting('rpc_publish_api', (bool) $_POST['rpc_publish_api']);
        $savetime = ((float)$_POST['timeadjust'])*60 . ' minutes'; //makes sure it is a number, at least 0
        if ((substr($savetime, 0, 1) != '-') && (substr($savetime, 0, 1) != '+')) {
            $savetime = '+' . $savetime;
        }
        setGlobalSetting('timeadjust', $savetime);
        setGlobalSetting('usercontrolSameGroupPolicy', strip_tags($_POST['usercontrolSameGroupPolicy']));

        Yii::app()->session['flashmessage'] = gT("Global settings were saved.");

        $url = htmlspecialchars_decode(Yii::app()->session['refurl']);
        if($url){Yii::app()->getController()->redirect($url);}
    }

    private function _checkSettings()
    {
        $surveycount = Survey::model()->count();

        $activesurveycount = Survey::model()->active()->count();

        $usercount = User::model()->count();

        if ($activesurveycount == false) {
            $activesurveycount = 0;
        }
        if ($surveycount == false) {
            $surveycount = 0;
        }

        $tablelist = Yii::app()->db->schema->getTableNames();
        foreach ($tablelist as $table)
        {
            if (strpos($table, Yii::app()->db->tablePrefix . "old_tokens_") !== false) {
                $oldtokenlist[] = $table;
            }
            elseif (strpos($table, Yii::app()->db->tablePrefix . "tokens_") !== false)
            {
                $tokenlist[] = $table;
            }
            elseif (strpos($table, Yii::app()->db->tablePrefix . "old_survey_") !== false)
            {
                $oldresultslist[] = $table;
            }
        }

        if (isset($oldresultslist) && is_array($oldresultslist)) {
            $deactivatedsurveys = count($oldresultslist);
        } else {
            $deactivatedsurveys = 0;
        }
        if (isset($oldtokenlist) && is_array($oldtokenlist)) {
            $deactivatedtokens = count($oldtokenlist);
        } else {
            $deactivatedtokens = 0;
        }
        if (isset($tokenlist) && is_array($tokenlist)) {
            $activetokens = count($tokenlist);
        } else {
            $activetokens = 0;
        }
        return array(
        'usercount' => $usercount,
        'surveycount' => $surveycount,
        'activesurveycount' => $activesurveycount,
        'deactivatedsurveys' => $deactivatedsurveys,
        'activetokens' => $activetokens,
        'deactivatedtokens' => $deactivatedtokens
        );
    }

}
