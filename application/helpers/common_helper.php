<?php if (!class_exists('Yii', false)) die('No direct script access allowed in ' . __FILE__);
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
*/
Yii::import('application.helpers.sanitize_helper', true);

class common_helper
{
    /**
     * Sorts an array of subarrays by a key in the subarray
     *
     * When having multiple db records, this function can sort them for you
     * on one of the fields. Ofcourse this is done better and more efficient
     * by the Db.
     *
     * @param array $inArray array of subarrays
     * @param mixed $sortKey key of subarray to sort on
     * @param string $order asc|desc default when omitted is desc
     * @return array
     */
    public static function subval_sort($inArray, $sortKey, $order = 'desc')
    {
        $tmpArray = array();
        $outArray = array();
        // First create an array with the value we like to sort on
        // and preserve the index
        foreach ($inArray as $index => $row) {
            $tmpArray[$index] = strtolower($row[$sortKey]);
        }

        // Find out desired sortorder and sort accordingly
        if ($order == "asc") {
            asort($tmpArray, SORT_REGULAR);
        } else {
            arsort($tmpArray, SORT_REGULAR);
        }

        // Now create the output array using the sorted keys
        foreach ($tmpArray as $index => $row) {
            $outArray[] = $inArray[$index];
        }

        return $outArray;
    }

}

/**
* Simple function to sort the permissions by title
*
* @param mixed $aPermissionA  Permission A to compare
* @param mixed $aPermissionB  Permission B to compare
*/
function comparePermission($aPermissionA,$aPermissionB)
{
    if($aPermissionA['title'] >$aPermissionB['title']) {
        return 1;
    }
    else {
        return -1;
    }
}

/**
 * Helper function to replace calls to Yii::app() and enable correct code completion.
 * @return LSYii_Application
 */
function App()
{
    return Yii::app();
}


/**
 * Translation helper function.
 * @param string $string
 * @param string $escapemode
 */
function gT($string, $params)
{
    return Yii::t(null, $string, $params);
}

function eT($string, $params)
{
    echo gT($string, $params);
}
/**
* isStandardTemplate returns true if a template is a standard template
* This function does not check if a template actually exists
*
* @param mixed $sTemplateName template name to look for
* @return bool True if standard template, otherwise false
*/
function isStandardTemplate($sTemplateName)
{
    return in_array($sTemplateName,array('basic',
    'bluengrey',
    'business_grey',
    'citronade',
    'clear_logo',
    'default',
    'eirenicon',
    'limespired',
    'mint_idea',
    'sherpa',
    'vallendar'));
}

/**
* getSurveyList() Queries the database (survey table) for a list of existing surveys
*
* @param boolean $returnarray if set to true an array instead of an HTML option list is given back
* @return string This string is returned containing <option></option> formatted list of existing surveys
*
*/
function getSurveyList($returnarray=false, $surveyid=false)
{
    static $cached = null;

    $timeadjust = getGlobalSetting('timeadjust');
    if(is_null($cached)) {
        if(User::GetUserRights('manage_survey'))
            $surveyidresult = Survey::model()->with(array('languagesettings'=>array('condition'=>'surveyls_language=language')))->findAll();
        elseif(User::GetUserRights('manage_model'))
        {
            $surveyidresult = Survey::model()->permission(Yii::app()->user->getId(),false)->with(array('languagesettings'=>array('condition'=>'surveyls_language=language')));
            $surveyidresult->getDBCriteria()->mergeWith(array('condition'=>"type='M'"),false);
            $surveyidresult->findAll();
        }
        else
            $surveyidresult = Survey::model()->permission(Yii::app()->user->getId())->with(array('languagesettings'=>array('condition'=>'surveyls_language=language')))->findAll();

        $surveynames = array();
        foreach ($surveyidresult as $result)
        {
            $surveynames[] = array_merge($result->attributes, $result->languagesettings[0]->attributes);

        }

        $cached = $surveynames;
    } else {
        $surveynames = $cached;
    }
    $surveyselecter = "";
    if ($returnarray===true) return $surveynames;
    $activesurveys='';
    $inactivesurveys='';
    $expiredsurveys='';
    $surveysmodel='';
    if ($surveynames)
    {
        foreach($surveynames as $sv)
        {

            $surveylstitle=flattenText($sv['surveyls_title']);
            if (strlen($surveylstitle)>45)
            {
                $surveylstitle = htmlspecialchars(mb_strcut(html_entity_decode($surveylstitle,ENT_QUOTES,'UTF-8'), 0, 45, 'UTF-8'))."...";
            }

            if($sv['active']!='Y' && $sv['type']!="M") // Remove survey model
            {
                $inactivesurveys .= "<option ";
                if(Yii::app()->user->getId() == $sv['owner_id'])
                {
                    $inactivesurveys .= " style=\"font-weight: bold;\"";
                }
                if ($sv['sid'] == $surveyid)
                {
                    $inactivesurveys .= " selected='selected'"; $svexist = 1;
                }
                $inactivesurveys .=" value='{$sv['sid']}'>{$surveylstitle}</option>\n";
            }
            elseif($sv['expires']!='' && $sv['expires'] < dateShift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", $timeadjust))
            {
                $expiredsurveys .="<option ";
                if (Yii::app()->user->getId() == $sv['owner_id'])
                {
                    $expiredsurveys .= " style=\"font-weight: bold;\"";
                }
                if ($sv['sid'] == $surveyid)
                {
                    $expiredsurveys .= " selected='selected'"; $svexist = 1;
                }
                $expiredsurveys .=" value='{$sv['sid']}'>{$surveylstitle}</option>\n";
            }
            elseif($sv['active']=='Y')
            {
                $activesurveys .= "<option ";
                if(Yii::app()->user->getId() == $sv['owner_id'])
                {
                    $activesurveys .= " style=\"font-weight: bold;\"";
                }
                if ($sv['sid'] == $surveyid)
                {
                    $activesurveys .= " selected='selected'"; $svexist = 1;
                }
                $activesurveys .=" value='{$sv['sid']}'>{$surveylstitle}</option>\n";
            }
            elseif($sv['type']=="M")
            {
                $surveysmodel .= "<option ";
                if(Yii::app()->user->getId() == $sv['owner_id'])
                {
                    $surveysmodel .= " style=\"font-weight: bold;\"";
                }
                if ($sv['sid'] == $surveyid)
                {
                    $surveysmodel .= " selected='selected'"; $svexist = 1;
                }
                $surveysmodel .=" value='{$sv['sid']}'>{$surveylstitle}</option>\n";
            }
        } // End Foreach
    }
    
    //Only show each survey group if there are some
    if ($activesurveys!='')
    {
        $surveyselecter .= "<optgroup label='".gT("Active")."' class='activesurveyselect'>\n";
        $surveyselecter .= $activesurveys . "</optgroup>";
    }
    if ($expiredsurveys!='')
    {
        $surveyselecter .= "<optgroup label='".gT("Expired")."' class='expiredsurveyselect'>\n";
        $surveyselecter .= $expiredsurveys . "</optgroup>";
    }
    if ($inactivesurveys!='')
    {
        $surveyselecter .= "<optgroup label='".gT("Inactive")."' class='inactivesurveyselect'>\n";
        $surveyselecter .= $inactivesurveys . "</optgroup>";
    }
    if ($surveysmodel!='')
    {
        $surveyselecter .= "<optgroup label='".gT("Survey model")."' class='surveymodel'>\n";
        $surveyselecter .= $surveysmodel . "</optgroup>";
    }

    if (!isset($svexist))
    {
        $surveyselecter = "<option selected='selected' value=''>".gT("Please choose...")."</option>\n".$surveyselecter;
    } else
    {
        $surveyselecter = "<option value=''>".gT("None")."</option>\n".$surveyselecter;
    }
    return $surveyselecter;
}

/**
* Returns true if a user has permissions in the particular survey
*
* @param $iSID The survey ID
* @param $sPermission
* @param $sCRUD
* @param $iUID User ID - if not given the one of the current user is used
* @return bool
*/
function hasSurveyPermission($iSID, $sPermission, $sCRUD, $iUID=null)
{
    if (!in_array($sCRUD,array('create','read','update','delete','import','export'))) return false;
    $sCRUD=$sCRUD.'_p';
    $thissurvey=getSurveyInfo($iSID);
    if (!$thissurvey) return false;

    if (is_null($iUID))
    {
        if (!Yii::app()->user->getIsGuest()) $iUID = Yii::app()->user->getId();
        else return false;
        // Some user don't need to be in Survey_permissions
        if (User::GetUserRights('superadmin')) return true; //Superadmin has all access to all survey
        if ($iUID==$thissurvey['owner_id']) return true; //Survey owner has all access to survey
        if (User::GetUserRights('manage_survey')) return true; //Survey manager has all access to all survey
        if (User::GetUserRights('manage_model') && $thissurvey['type']=="M") return true; //Survey model manager has all access to survey model
    }

    $aSurveyPermissionCache = Yii::app()->getConfig("aSurveyPermissionCache");
    if (!isset($aSurveyPermissionCache[$iSID][$iUID][$sPermission][$sCRUD]))
    {
        $oPermissions = Survey_permissions::model()->findByAttributes(array("sid"=> $iSID,"uid"=> $iUID,"permission"=>$sPermission));

        $bPermission = !$oPermissions ? array() : $oPermissions->attributes;
        if (!isset($bPermission[$sCRUD]) || $bPermission[$sCRUD]==0)
        {
            $bPermission=false;
        }
        else
        {
            $bPermission=true;
        }
        $aSurveyPermissionCache[$iSID][$iUID][$sPermission][$sCRUD]=$bPermission;
    }
    Yii::app()->setConfig("aSurveyPermissionCache", $aSurveyPermissionCache);
    return $aSurveyPermissionCache[$iSID][$iUID][$sPermission][$sCRUD];
}

/**
* Returns true if a user has global permission for a certain action. Available permissions are
*
* USER_RIGHT_CREATE_SURVEY
* USER_RIGHT_CONFIGURATOR
* USER_RIGHT_CREATE_USER
* USER_RIGHT_DELETE_USER
* USER_RIGHT_SUPERADMIN
* USER_RIGHT_MANAGE_TEMPLATE
* USER_RIGHT_MANAGE_LABEL
*
* @param $sPermission
* @return bool
*/
function hasGlobalPermission($sPermission)
{
    if (!Yii::app()->user->getIsGuest()) $iUID = !Yii::app()->user->getId();
    else return false;
    $sPermission=substr($sPermission,11);// Remove "USER_RIGHT_"
    return User::GetUserRights($sPermission);

}

function getTemplateList()
{
    $usertemplaterootdir=Yii::app()->getConfig("usertemplaterootdir");
    $standardtemplaterootdir=Yii::app()->getConfig("standardtemplaterootdir");

    if (!$usertemplaterootdir) {die("getTemplateList() no template directory");}
    if ($handle = opendir($standardtemplaterootdir))
    {
        while (false !== ($file = readdir($handle)))
        {
            if (!is_file("$standardtemplaterootdir/$file") && $file != "." && $file != ".." && $file!=".svn" && isStandardTemplate($file))
            {
                $list_of_files[$file] = $standardtemplaterootdir.DIRECTORY_SEPARATOR.$file;
            }
        }
        closedir($handle);
    }

    if ($handle = opendir($usertemplaterootdir))
    {
        while (false !== ($file = readdir($handle)))
        {
            if (!is_file("$usertemplaterootdir/$file") && $file != "." && $file != ".." && $file!=".svn")
            {
                $list_of_files[$file] = $usertemplaterootdir.DIRECTORY_SEPARATOR.$file;
            }
        }
        closedir($handle);
    }
    ksort($list_of_files);

    return $list_of_files;
}

function getAdminThemeList()
{
    // $usertemplaterootdir=Yii::app()->getConfig("usertemplaterootdir");
    $standardtemplaterootdir=Yii::app()->getConfig("styledir");

    //    if (!$usertemplaterootdir) {die("getTemplateList() no template directory");}
    if ($handle = opendir($standardtemplaterootdir))
    {
        while (false !== ($file = readdir($handle)))
        {
            if (!is_file("$standardtemplaterootdir/$file") && $file != "." && $file != ".." && $file!=".svn")
            {
                $list_of_files[$file] = $standardtemplaterootdir.DIRECTORY_SEPARATOR.$file;
            }
        }
        closedir($handle);
    }

    /*    if ($handle = opendir($usertemplaterootdir))
    {
    while (false !== ($file = readdir($handle)))
    {
    if (!is_file("$usertemplaterootdir/$file") && $file != "." && $file != ".." && $file!=".svn")
    {
    $list_of_files[$file] = $usertemplaterootdir.DIRECTORY_SEPARATOR.$file;
    }
    }
    closedir($handle);
    }         */
    ksort($list_of_files);

    return $list_of_files;
}


/**
* getQuestions() queries the database for an list of all questions matching the current survey and group id
*
* @return This string is returned containing <option></option> formatted list of questions in the current survey and group
*/
function getQuestions($surveyid,$gid,$selectedqid)
{
    
    $s_lang = Survey::model()->findByPk($surveyid)->language;
    $qrows = Questions::model()->findAllByAttributes(array('sid' => $surveyid, 'gid' => $gid, 'parent_id' => null),array('order'=>'sortorder'));

    if (!isset($sQuestionselecter)) {$sQuestionselecter="";}
    foreach ($qrows as $qrow)
    {
        $qrow = $qrow->attributes;
        $qrow['title'] = strip_tags($qrow['code']);
        $link = Yii::app()->getController()->createUrl("/questions/update/", array('qid' =>  $qrow['qid']));
        $sQuestionselecter .= "<option value='{$link}'";
        if ($selectedqid == $qrow['qid'])
        {
            $sQuestionselecter .= " selected='selected'";
            $qexists=true;
        }
        $sQuestionselecter .=">{$qrow['title']}:";
        $sQuestionselecter .= " ";
        $question=flattenText($qrow['question']);
        if (strlen($question)<35)
        {
            $sQuestionselecter .= $question;
        }
        else
        {
            $sQuestionselecter .= htmlspecialchars(mb_strcut(html_entity_decode($question,ENT_QUOTES,'UTF-8'), 0, 35, 'UTF-8'))."...";
        }
        $sQuestionselecter .= "</option>\n";
    }

    if (!isset($qexists))
    {
        $sQuestionselecter = "<option selected='selected'>".gT("Please choose...")."</option>\n".$sQuestionselecter;
    }
    else
    {
        $link = Yii::app()->getController()->createUrl("/admin/survey/sa/view/surveyid/".$surveyid."/gid/".$gid);
        $sQuestionselecter = "<option value='{$link}'>".gT("None")."</option>\n".$sQuestionselecter;
    }
    return $sQuestionselecter;
}

/**
* getGidPrevious() returns the Gid of the group prior to the current active group
*
* @param string $surveyid
* @param string $gid
*
* @return The Gid of the previous group
*/
function getGidPrevious($surveyid, $gid)
{
    

    if (!$surveyid) {$surveyid=returnGlobal('sid');}
    $s_lang = Survey::model()->findByPk($surveyid)->language;
    $qresult = Groups::model()->findAllByAttributes(array('sid' => $surveyid, 'language' => $s_lang), array('order'=>'group_order'));

    $i = 0;
    $iPrev = -1;
    foreach ($qresult as $qrow)
    {
        $qrow = $qrow->attributes;
        if ($gid == $qrow['gid']) {$iPrev = $i - 1;}
        $i += 1;
    }

    if ($iPrev >= 0) {$GidPrev = $qresult[$iPrev]->gid;}
    else {$GidPrev = "";}
    return $GidPrev;
}

/**
* getQidPrevious() returns the Qid of the question prior to the current active question
*
* @param string $surveyid
* @param string $gid
* @param string $qid
*
* @return This Qid of the previous question
*/
function getQidPrevious($surveyid, $gid, $qid)
{
    
    $s_lang = Survey::model()->findByPk($surveyid)->language;
    $qrows = Questions::model()->findAllByAttributes(array('gid' => $gid, 'sid' => $surveyid, 'parent_id'=>null),array('order'=>'sortorder'));

    $i = 0;
    $iPrev = -1;
    if (count($qrows) > 0)
    {

        foreach ($qrows as $qrow)
        {
            $qrow = $qrow->attributes;
            if ($qid == $qrow['qid']) {$iPrev = $i - 1;}
            $i += 1;
        }
    }
    if ($iPrev >= 0) {$QidPrev = $qrows[$iPrev]->qid;}
    else {$QidPrev = "";}


    return $QidPrev;
}

/**
* getGidNext() returns the Gid of the group next to the current active group
*
* @param string $surveyid
* @param string $gid
*
* @return The Gid of the next group
*/
function getGidNext($surveyid, $gid)
{
    
    if (!$surveyid) {$surveyid=returnGlobal('sid');}
    $s_lang = Survey::model()->findByPk($surveyid)->language;

    //$gquery = "SELECT gid FROM ".db_table_name('groups')." WHERE sid=$surveyid AND language='{$s_lang}' ORDER BY group_order";

    $qresult = Groups::model()->findAllByAttributes(array('sid' => $surveyid, 'language' => $s_lang), array('order'=>'group_order'));

    $GidNext="";
    $i = 0;
    $iNext = 1;

    foreach ($qresult as $qrow)
    {
        $qrow = $qrow->attributes;

        if ($gid == $qrow['gid']) {$iNext = $i + 1;}
        $i += 1;
    }

    if ($iNext < count($qresult)) {$GidNext = $qresult[$iNext]->gid;}
    else {$GidNext = "";}
    return $GidNext;
}

/**
* getQidNext() returns the Qid of the question prior to the current active question
*
* @param string $surveyid
* @param string $gid
* @param string $qid
*
* @return This Qid of the previous question
*/
function getQidNext($surveyid, $gid, $qid)
{
    
    $s_lang = Survey::model()->findByPk($surveyid)->language;
    $qrows = Questions::model()->findAllByAttributes(array('gid' => $gid, 'sid' => $surveyid, 'parent_id' => null), array('order'=>'sortorder'));


    $i = 0;
    $iNext = 1;
    if (count($qrows) > 0)
    {
        foreach ($qrows as $qrow)
        {
            if ($qid == $qrow->qid) {$iNext = $i + 1;}
            $i += 1;
        }
    }
    if ($iNext < count($qrows)) {$QidNext = $qrows[$iNext]->qid;}
    else {$QidNext = "";}
    return $QidNext;
}

function convertGETtoPOST($url)
{
    $url = preg_replace('/&amp;/i','&',$url);
    $stack = explode('?',$url);
    $calledscript = array_shift($stack);
    $query = array_shift($stack);
    $aqueryitems = explode('&',$query);
    $arrayParam = Array();
    $arrayVal = Array();

    foreach ($aqueryitems as $queryitem)
    {
        $stack =  explode ('=', $queryitem);
        $paramname = array_shift($stack);
        $value = array_shift($stack);
        $arrayParam[] = "'".$paramname."'";
        $arrayVal[] = substr($value, 0, 9) != "document." ? "'".$value."'" : $value;
    }
    // $Paramlist = "[" . implode(",",$arrayParam) . "]";
    // $Valuelist = "[" . implode(",",$arrayVal) . "]";
    $Paramlist = "new Array(" . implode(",",$arrayParam) . ")";
    $Valuelist = "new Array(" . implode(",",$arrayVal) . ")";
    $callscript = "sendPost('$calledscript','',$Paramlist,$Valuelist);";
    return $callscript;
}


/**
* This function calculates how much space is actually used by all files uploaded
* using the File Upload question type
*
* @returns integer Actual space used in MB
*/
function calculateTotalFileUploadUsage(){
    global $uploaddir;
    $sQuery='select sid from {{surveys}}';
    $oResult = dbExecuteAssoc($sQuery); //checked
    $aRows = $oResult->readAll();
    $iTotalSize=0.0;
    foreach ($aRows as $aRow)
    {
        $sFilesPath=$uploaddir.'/surveys/'.$aRow['sid'].'/files';
        if (file_exists($sFilesPath))
        {
            $iTotalSize+=(float)getDirectorySize($sFilesPath);
        }
    }
    return (float)$iTotalSize/1024/1024;
}

function getDirectorySize($directory) {
    $size = 0;
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file){
        $size+=$file->getSize();
    }
    return $size;
}


/**
* Gets number of groups inside a particular survey
*
* @param string $surveyid
* @param mixed $lang
*/
function getGroupSum($surveyid, $lang)
{
    //$condn = "WHERE sid=".$surveyid." AND language='".$lang."'"; //Getting a count of questions for this survey
    $condn = array('sid'=>$surveyid,'language'=>$lang);
    $sumresult3 = count(Groups::model()->findAllByAttributes($condn)); //Checked)

    return $sumresult3 ;
}


/**
* getMaxGroupOrder($surveyid) queries the database for the maximum sortorder of a group and returns the next higher one.
*
* @param mixed $surveyid
*/
function getMaxGroupOrder($surveyid)
{
    $s_lang = Survey::model()->findByPk($surveyid)->language;

    //$max_sql = "SELECT max( group_order ) AS max FROM ".db_table_name('groups')." WHERE sid =$surveyid AND language='{$s_lang}'" ;
    $query = Groups::model()->find(array('order' => 'group_order desc'));
    $current_max = !is_null($query) ? $query->group_order : '';

    if($current_max!="")
    {
        return ++$current_max ;
    }
    else return "0" ;
}


/**
* getGroupOrder($surveyid,$gid) queries the database for the sortorder of a group.
*
* @param mixed $surveyid
* @param mixed $gid
* @return mixed
*/
function getGroupOrder($surveyid,$gid)
{

    $s_lang = Survey::model()->findByPk($surveyid)->language;

    //$grporder_sql = "SELECT group_order FROM ".db_table_name('groups')." WHERE sid =$surveyid AND language='{$s_lang}' AND gid=$gid" ;
    $grporder_result = Groups::model()->findByAttributes(array('sid' => $surveyid, 'gid' => $gid, 'language' => $s_lang)); //Checked
    $grporder_row = $grporder_result->attributes ;
    $group_order = $grporder_row['group_order'];
    if($group_order=="")
    {
        return "0" ;
    }
    else return $group_order ;
}

/**
* getMaxQuestionOrder($gid) queries the database for the maximum sortorder of a question.
*
*/
function getMaxQuestionOrder($gid,$surveyid)
{
    $gid=sanitize_int($gid);
    $s_lang = Survey::model()->findByPk($surveyid)->language;
    $max_sql = "SELECT max( question_order ) AS max FROM {{questions}} WHERE gid='$gid' AND language='$s_lang'";

    $max_result = Yii::app()->db->createCommand($max_sql)->query(); //Checked
    $maxrow = $max_result->read() ;
    $current_max = $maxrow['max'];
    if($current_max=="")
    {
        return "0" ;
    }
    else return $current_max ;
}

    /**
* setupColumns() defines all the html tags to be wrapped around
* various list type answers.
*
* @param integer $columns - the number of columns, usually supplied by $dcols
* @param integer $answer_count - the number of answers to a question, usually supplied by $anscount
* @param string $wrapperclass - a global class for the wrapper
* @param string $itemclass - a class for the item
* @return array with all the various opening and closing tags to generate a set of columns.
*
* It returns an array with the following items:
*    $wrapper['whole-start']   = Opening wrapper for the whole list
*    $wrapper['whole-end']     = closing wrapper for the whole list
*    $wrapper['col-devide']    = normal column devider
*    $wrapper['col-devide-last'] = the last column devider (to allow
*                                for different styling of the last
*                                column
*    $wrapper['item-start']    = opening wrapper tag for individual
*                                option
*    $wrapper['item-start-other'] = opening wrapper tag for other
*                                option
*    $wrapper['item-start-noanswer'] = opening wrapper tag for no answer
*                                option
*    $wrapper['item-end']      = closing wrapper tag for individual
*                                option
*    $wrapper['maxrows']       = maximum number of rows in each
*                                column
*    $wrapper['cols']          = Number of columns to be inserted
*                                (and checked against)
*
*
* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
* Columns are a problem.
* Really there is no perfect solution to columns at the moment.
*
* -  Using Tables is problematic semanticly.
* -  Using inline or float to create columns, causes the answers
*    flows horizontally, not vertically which is not ideal visually.
* -  Using CSS3 columns is also a problem because of browser support
*    and also because if you have answeres split across two or more
*    lines, and those answeres happen to fall at the bottom of a
*    column, the answer might be split across columns as well as
*    lines.
* -  Using nested unordered list with the first level of <LI>s
*    floated is the same as using tables and so is bad semantically
*    for the same reason tables are bad.
* -  Breaking the unordered lists into consecutive floated unordered
*    lists is not great semantically but probably not as bad as
*    using tables.
*
* Because I haven't been able to decide which option is the least
* bad, I have handed over that responsibility to the admin who sets
* LimeSurvey up on their server.
*
* There are four options:
*    'css'   using one of the various CSS only methods for
*            rendering columns.
*            (Check the CSS file for your chosen template to see
*             how columns are defined.)
*    'ul'    using multiple floated unordered lists. (DEFAULT)
*    'table' using conventional tables based layout.
*     NULL   blocks the use of columns
*
* 'ul' is the default because it's the best possible compromise
* between semantic markup and visual layout.
*/
function setupColumns($columns, $answer_count,$wrapperclass="",$itemclass="")
{

    $column_style = Yii::app()->getConfig('column_style');
    if ( !in_array($column_style,array('css','ul','table')) && !is_null($column_style) )
    {
        $column_style = 'ul';
    };

    if($columns < 2)
    {
        $column_style = null;
        $columns = 1;
    }

    if(($columns > $answer_count) && $answer_count>0)
    {
        $columns = $answer_count;
    };


    $class_first = ' class="'.$wrapperclass.'"';
    if($columns > 1 && !is_null($column_style))
    {
        if($column_style == 'ul')
        {
            $ul = '-ul';
        }
        else
        {
            $ul = '';
        }
        $class_first = ' class="'.$wrapperclass.' cols-'.$columns . $ul.' first"';
        $class = ' class="'.$wrapperclass.' cols-'.$columns . $ul.'"';
        $class_last_ul = ' class="'.$wrapperclass.' cols-'.$columns . $ul.' last"';
        $class_last_table = ' class="'.$wrapperclass.' cols-'.$columns.' last"';
    }
    else
    {
        $class = ' class="'.$wrapperclass.'"';
        $class_last_ul = ' class="'.$wrapperclass.'"';
        $class_last_table = ' class="'.$wrapperclass.'"';
    };

    $wrapper = array(
    'whole-start'  => "\n<ul$class_first>\n"
    ,'whole-end'    => "</ul>\n"
    ,'col-devide'   => ''
    ,'col-devide-last' => ''
    ,'item-start'   => "\t<li class=\"{$itemclass}\">\n"
    ,'item-start-other' => "\t<li class=\"{$itemclass} other other-item\">\n"
    ,'item-start-noanswer' => "\t<li class=\"{$itemclass} noanswer-item\">\n"
    ,'item-end' => "\t</li>\n"
    ,'maxrows'  => ceil($answer_count/$columns) //Always rounds up to nearest whole number
    ,'cols'     => $columns
    );

    switch($column_style)
    {
        case 'ul':  if($columns > 1)
            {
                $wrapper['col-devide']  = "\n</ul>\n\n<ul$class>\n";
                $wrapper['col-devide-last'] = "\n</ul>\n\n<ul$class_last_ul>\n";
            }
            break;

        case 'table':   $table_cols = '';
            for($cols = $columns ; $cols > 0 ; --$cols)
            {
                switch($cols)
                {
                    case $columns:  $table_cols .= "\t<col$class_first />\n";
                        break;
                    case 1:     $table_cols .= "\t<col$class_last_table />\n";
                        break;
                    default:    $table_cols .= "\t<col$class />\n";
                };
            };

            if($columns > 1)
            {
                $wrapper['col-devide']  = "\t</ul>\n</td>\n\n<td>\n\t<ul>\n";
                $wrapper['col-devide-last'] = "\t</ul>\n</td>\n\n<td class=\"last\">\n\t<ul>\n";
            };
            $wrapper['whole-start'] = "\n<table$class>\n$table_cols\n\t<tbody>\n<tr>\n<td>\n\t<ul>\n";
            $wrapper['whole-end']   = "\t</ul>\n</td>\n</tr>\n\t</tbody>\n</table>\n";
            $wrapper['item-start']  = "<li class=\"{$itemclass}\">\n";
            $wrapper['item-end']    = "</li class=\"{$itemclass}\">\n";
    };

    return $wrapper;
};

function alternation($alternate = '' , $type = 'col')
{
    /**
    * alternation() Returns a class identifyer for alternating between
    * two options. Used to style alternate elements differently. creates
    * or alternates between the odd string and the even string used in
    * as column and row classes for array type questions.
    *
    * @param string $alternate = '' (empty) (default) , 'array2' ,  'array1' , 'odd' , 'even'
    * @param string  $type = 'col' (default) or 'row'
    *
    * @return string representing either the first alternation or the opposite alternation to the one supplied..
    */
    /*
    // The following allows type to be left blank for row in subsequent
    // function calls.
    // It has been left out because 'row' must be defined the first time
    // alternation() is called. Since it is only ever written once for each
    // while statement within a function, 'row' is always defined.
    if(!empty($alternate) && $type != 'row')
    {   if($alternate == ('array2' || 'array1'))
    {
    $type = 'row';
    };
    };
    // It has been left in case it becomes useful but probably should be
    // removed.
    */
    if($type == 'row')
    {
        $odd  = 'array2'; // should be row_odd
        $even = 'array1'; // should be row_even
    }
    else
    {
        $odd  = 'odd';  // should be col_odd
        $even = 'even'; // should be col_even
    };
    if($alternate == $odd)
    {
        $alternate = $even;
    }
    else
    {
        $alternate = $odd;
    };
    return $alternate;
}


/**
* longestString() returns the length of the longest string past to it.
* @peram string $new_string
* @peram integer $longest_length length of the (previously) longest string passed to it.
* @return integer representing the length of the longest string passed (updated if $new_string was longer than $longest_length)
*
* usage should look like this: $longest_length = longestString( $new_string , $longest_length );
*
*/
function longestString( $new_string , $longest_length )
{
    if($longest_length < strlen(trim(strip_tags($new_string))))
    {
        $longest_length = strlen(trim(strip_tags($new_string)));
    };
    return $longest_length;
};



/**
* getNotificationList() returns different options for notifications
*
* @param string $notificationcode - the currently selected one
*
* @return This string is returned containing <option></option> formatted list of notification methods for current survey
*/
function getNotificationList($notificationcode)
{
    
    $ntypes = array(
    "0"=>gT("No email notification"),
    "1"=>gT("Basic email notification"),
    "2"=>gT("Detailed email notification with result codes")
    );
    if (!isset($ntypeselector)) {$ntypeselector="";}
    foreach($ntypes as $ntcode=>$ntdescription)
    {
        $ntypeselector .= "<option value='$ntcode'";
        if ($notificationcode == $ntcode) {$ntypeselector .= " selected='selected'";}
        $ntypeselector .= ">$ntdescription</option>\n";
    }
    return $ntypeselector;
}

function getGroupList3($gid,$surveyid)
{
    //
    $gid=sanitize_int($gid);
    $surveyid=sanitize_int($surveyid);

    if (!$surveyid) {$surveyid=returnGlobal('sid');}
    $groupselecter = "";
    $s_lang = Survey::model()->findByPk($surveyid)->language;


    //$gidquery = "SELECT gid, group_name FROM ".db_table_name('groups')." WHERE sid=$surveyid AND language='{$s_lang}' ORDER BY group_order";

    $gidresult = Groups::model()->findAllByAttributes(array('sid' => $surveyid, 'language' => $s_lang), array('order'=>'group_order'));

    foreach ($gidresult as $gv)
    {
        $gv = $gv->attributes;
        $groupselecter .= "<option";
        if ($gv['gid'] == $gid) {$groupselecter .= " selected='selected'"; }
        $groupselecter .= " value='".$gv['gid']."'>".htmlspecialchars($gv['group_name'])."</option>\n";
    }


    return $groupselecter;
}

/**
* put your comment there...
*
* @param mixed $gid
* @param mixed $language
*/
function getGroupListLang($gid, $language, $surveyid)
{

    

    $groupselecter="";
    if (!$surveyid) {$surveyid=returnGlobal('sid');}

    $gidresult = Groups::model()->findAll(array('condition'=>'sid=:surveyid AND language=:language',
    'order'=>'group_order',
    'params'=>array(':surveyid'=>$surveyid,':language'=>$language)));   //Checked)
    foreach ($gidresult as $gv)
    {
        $gv = $gv->attributes;
        $groupselecter .= "<option";
        if ($gv['gid'] == $gid) {$groupselecter .= " selected='selected'"; $gvexist = 1;}
        $link = Yii::app()->getController()->createUrl("/admin/survey/sa/view/surveyid/".$surveyid."/gid/".$gv['gid']);
        $groupselecter .= " value='{$link}'>";
        if (strip_tags($gv['group_name']))
        {
            $groupselecter .= htmlspecialchars(strip_tags($gv['group_name']));
        } else {
            $groupselecter .= htmlspecialchars($gv['group_name']);
        }
        $groupselecter .= "</option>\n";
    }
    if ($groupselecter)
    {
        $link = Yii::app()->getController()->createUrl("/admin/survey/sa/view/surveyid/".$surveyid);
        if (!isset($gvexist)) {$groupselecter = "<option selected='selected'>".gT("Please choose...")."</option>\n".$groupselecter;}
        else {$groupselecter .= "<option value='{$link}'>".gT("None")."</option>\n";}
    }
    return $groupselecter;
}


function getUserList($outputformat='fullinfoarray')
{
    

    if (!empty(Yii::app()->session['loginID']))
    {
        $myuid=sanitize_int(Yii::app()->session['loginID']);
    }
    $usercontrolSameGroupPolicy = Yii::app()->getConfig('usercontrolSameGroupPolicy');
    if (Yii::app()->session['USER_RIGHT_SUPERADMIN'] != 1 && isset($usercontrolSameGroupPolicy) &&
    $usercontrolSameGroupPolicy == true)
    {
        if (isset($myuid))
        {
            $sSelectFields = 'users_name,uid,email,full_name,parent_id';
            // List users from same group as me + all my childs
            // a subselect is used here because MSSQL does not like to group by text
            // also Postgres does like this one better
            $uquery = " SELECT {$sSelectFields} from {{users}} where uid in (
                SELECT uid from {{user_in_groups}} where ugid in (
                    SELECT ugid from {{user_in_groups}} where uid={$myuid}
                    )
                )
            UNION
            SELECT {$sSelectFields} from {{users}} v where v.parent_id={$myuid}
            UNION
            SELECT {$sSelectFields} from {{users}} v where uid={$myuid}";
        }
        else
        {
            return array(); // Or die maybe
        }
    }
    else
    {
        $uquery = "SELECT * FROM {{users}} ORDER BY uid";
    }

    $uresult = Yii::app()->db->createCommand($uquery)->query()->readAll(); //Checked

    if (count($uresult)==0)
    //user is not in a group and usercontrolSameGroupPolicy is activated - at least show his own userinfo
    {
        $uquery = "SELECT u.* FROM {{users}} AS u WHERE u.uid=".$myuid;
        $uresult = Yii::app()->db->createCommand($uquery)->query()->readAll();//Checked
    }

    $userlist = array();
    $userlist[0] = "Reserved for logged in user";
    //while ($srow = $uresult->readAll())
    foreach ($uresult as $srow)
    {
        if ($outputformat != 'onlyuidarray')
        {
            if ($srow['uid'] != Yii::app()->session['loginID'])
            {
                $userlist[] = array("user"=>$srow['users_name'], "uid"=>$srow['uid'], "email"=>$srow['email'], "full_name"=>$srow['full_name'], "parent_id"=>$srow['parent_id']);
            }
            else
            {
                $userlist[0] = array("user"=>$srow['users_name'], "uid"=>$srow['uid'], "email"=>$srow['email'], "full_name"=>$srow['full_name'], "parent_id"=>$srow['parent_id']);
            }
        }
        else
        {
            if ($srow['uid'] != Yii::app()->session['loginID'])
            {
                $userlist[] = $srow['uid'];
            }
            else
            {
                $userlist[0] = $srow['uid'];
            }
        }

    }
    return $userlist;
}


/**
* Gets all survey infos in one big array including the language specific settings
*
* @param string $surveyid  The survey ID
* @param string $languagecode The language code - if not given the base language of the particular survey is used
* @return array Returns array with survey info or false, if survey does not exist
*/
function getSurveyInfo($surveyid, $languagecode='')
{
    static $staticSurveyInfo = array();// Use some static
    $surveyid=sanitize_int($surveyid);
    $languagecode=sanitize_languagecode($languagecode);
    $thissurvey=false;
    // Do job only if this survey exist
    if(!Survey::model()->findByPk($surveyid))
    {
        return false;
    }
    // if no language code is set then get the base language one
    if (!isset($languagecode) || $languagecode=='')
    {
        $languagecode=Survey::model()->findByPk($surveyid)->language;
    }
    if(isset($staticSurveyInfo[$surveyid][$languagecode]) )
    {
        $thissurvey=$staticSurveyInfo[$surveyid][$languagecode];
    }
    else
    {
        $result = Surveys_languagesettings::model()->with('survey')->findByPk(array('surveyls_survey_id' => $surveyid, 'surveyls_language' => $languagecode));
        if($result)
        {
            $thissurvey=array_merge($result->survey->attributes,$result->attributes);
            $thissurvey['name']=$thissurvey['surveyls_title'];
            $thissurvey['description']=$thissurvey['surveyls_description'];
            $thissurvey['welcome']=$thissurvey['surveyls_welcometext'];
            $thissurvey['templatedir']=$thissurvey['template'];
            $thissurvey['adminname']=$thissurvey['admin'];
            $thissurvey['tablename']='{{survey_'.$thissurvey['sid'] . '}}';
            $thissurvey['urldescrip']=$thissurvey['surveyls_urldescription'];
            $thissurvey['url']=$thissurvey['surveyls_url'];
            $thissurvey['expiry']=$thissurvey['expires'];
            $thissurvey['email_invite_subj']=$thissurvey['surveyls_email_invite_subj'];
            $thissurvey['email_invite']=$thissurvey['surveyls_email_invite'];
            $thissurvey['email_remind_subj']=$thissurvey['surveyls_email_remind_subj'];
            $thissurvey['email_remind']=$thissurvey['surveyls_email_remind'];
            $thissurvey['email_confirm_subj']=$thissurvey['surveyls_email_confirm_subj'];
            $thissurvey['email_confirm']=$thissurvey['surveyls_email_confirm'];
            $thissurvey['email_register_subj']=$thissurvey['surveyls_email_register_subj'];
            $thissurvey['email_register']=$thissurvey['surveyls_email_register'];
            $thissurvey['attributedescriptions'] = $result->survey->tokenAttributes;
            $thissurvey['attributecaptions'] = $result->attributeCaptions;
            if (!isset($thissurvey['adminname'])) {$thissurvey['adminname']=Yii::app()->getConfig('siteadminemail');}
            if (!isset($thissurvey['adminemail'])) {$thissurvey['adminemail']=Yii::app()->getConfig('siteadminname');}
            if (!isset($thissurvey['urldescrip']) || $thissurvey['urldescrip'] == '' ) {$thissurvey['urldescrip']=$thissurvey['surveyls_url'];}
        }
        $staticSurveyInfo[$surveyid][$languagecode]=$thissurvey;
    }

    return $thissurvey;
}

/**
* Returns the default email template texts as array
*
* @param mixed $oLanguage Required language translationb object
* @param string $mode Escape mode for the translation function
* @return array
*/
function templateDefaultTexts($oLanguage, $mode='html'){
    return array(
    'admin_detailed_notification_subject'=>$oLanguage->gT("Response submission for survey {SURVEYNAME} with results",$mode),
    'admin_detailed_notification'=>$oLanguage->gT("Hello,\n\nA new response was submitted for your survey '{SURVEYNAME}'.\n\nClick the following link to reload the survey:\n{RELOADURL}\n\nClick the following link to see the individual response:\n{VIEWRESPONSEURL}\n\nClick the following link to edit the individual response:\n{EDITRESPONSEURL}\n\nView statistics by clicking here:\n{STATISTICSURL}\n\n\nThe following answers were given by the participant:\n{ANSWERTABLE}",$mode),
    'admin_detailed_notification_css'=>'<style type="text/css">
    .printouttable {
    margin:1em auto;
    }
    .printouttable th {
    text-align: center;
    }
    .printouttable td {
    border-color: #ddf #ddf #ddf #ddf;
    border-style: solid;
    border-width: 1px;
    padding:0.1em 1em 0.1em 0.5em;
    }

    .printouttable td:first-child {
    font-weight: 700;
    text-align: right;
    padding-right: 5px;
    padding-left: 5px;

    }
    .printouttable .printanswersquestion td{
    background-color:#F7F8FF;
    }

    .printouttable .printanswersquestionhead td{
    text-align: left;
    background-color:#ddf;
    }

    .printouttable .printanswersgroup td{
    text-align: center;
    font-weight:bold;
    padding-top:1em;
    }
    </style>',
    'admin_notification_subject'=>$oLanguage->gT("Response submission for survey {SURVEYNAME}",$mode),
    'admin_notification'=>$oLanguage->gT("Hello,\n\nA new response was submitted for your survey '{SURVEYNAME}'.\n\nClick the following link to reload the survey:\n{RELOADURL}\n\nClick the following link to see the individual response:\n{VIEWRESPONSEURL}\n\nClick the following link to edit the individual response:\n{EDITRESPONSEURL}\n\nView statistics by clicking here:\n{STATISTICSURL}",$mode),
    'confirmation_subject'=>$oLanguage->gT("Confirmation of your participation in our survey"),
    'confirmation'=>$oLanguage->gT("Dear {FIRSTNAME},\n\nthis email is to confirm that you have completed the survey titled {SURVEYNAME} and your response has been saved. Thank you for participating.\n\nIf you have any further questions about this email, please contact {ADMINNAME} on {ADMINEMAIL}.\n\nSincerely,\n\n{ADMINNAME}",$mode),
    'invitation_subject'=>$oLanguage->gT("Invitation to participate in a survey",$mode),
    'invitation'=>$oLanguage->gT("Dear {FIRSTNAME},\n\nyou have been invited to participate in a survey.\n\nThe survey is titled:\n\"{SURVEYNAME}\"\n\n\"{SURVEYDESCRIPTION}\"\n\nTo participate, please click on the link below.\n\nSincerely,\n\n{ADMINNAME} ({ADMINEMAIL})\n\n----------------------------------------------\nClick here to do the survey:\n{SURVEYURL}",$mode)."\n\n".$oLanguage->gT("If you do not want to participate in this survey and don't want to receive any more invitations please click the following link:\n{OPTOUTURL}",$mode)."\n\n".$oLanguage->gT("If you are blacklisted but want to participate in this survey and want to receive invitations please click the following link:\n{OPTINURL}",$mode),
    'reminder_subject'=>$oLanguage->gT("Reminder to participate in a survey",$mode),
    'reminder'=>$oLanguage->gT("Dear {FIRSTNAME},\n\nRecently we invited you to participate in a survey.\n\nWe note that you have not yet completed the survey, and wish to remind you that the survey is still available should you wish to take part.\n\nThe survey is titled:\n\"{SURVEYNAME}\"\n\n\"{SURVEYDESCRIPTION}\"\n\nTo participate, please click on the link below.\n\nSincerely,\n\n{ADMINNAME} ({ADMINEMAIL})\n\n----------------------------------------------\nClick here to do the survey:\n{SURVEYURL}",$mode)."\n\n".$oLanguage->gT("If you do not want to participate in this survey and don't want to receive any more invitations please click the following link:\n{OPTOUTURL}",$mode),
    'registration_subject'=>$oLanguage->gT("Survey registration confirmation",$mode),
    'registration'=>$oLanguage->gT("Dear {FIRSTNAME},\n\nYou, or someone using your email address, have registered to participate in an online survey titled {SURVEYNAME}.\n\nTo complete this survey, click on the following URL:\n\n{SURVEYURL}\n\nIf you have any questions about this survey, or if you did not register to participate and believe this email is in error, please contact {ADMINNAME} at {ADMINEMAIL}.",$mode)
    );
}

/**
* Compares two elements from an array (passed by the usort function)
* and returns -1, 0 or 1 depending on the result of the comparison of
* the sort order of the group_order and question_order field
*
* @param mixed $a
* @param mixed $b
* @return int
*/
function groupOrderThenQuestionOrder($a, $b)
{
    if (isset($a['group_order']) && isset($b['group_order']))
    {
        $GroupResult = strnatcasecmp($a['group_order'], $b['group_order']);
    }
    else
    {
        $GroupResult = "";
    }
    if ($GroupResult == 0)
    {
        $TitleResult = strnatcasecmp($a["question_order"], $b["question_order"]);
        return $TitleResult;
    }
    return $GroupResult;
}


function fixSortOrderAnswers($qid,$surveyid=null) //Function rewrites the sortorder for a group of answers
{
    $qid=sanitize_int($qid);
    $baselang = Survey::model()->findByPk($surveyid)->language;

    Answers::model()->updateSortOrder($qid,$baselang);
}

/**
* This function rewrites the sortorder for questions inside the named group
* REMOVED the 2012-08-08 : replaced by Questions::model()->updateQuestionOrder
* @param integer $groupid the group id
* @param integer $surveyid the survey id
*/
/**
function fixSortOrderQuestions($groupid, $surveyid) //Function rewrites the sortorder for questions
{
    $gid = sanitize_int($groupid);
    $surveyid = sanitize_int($surveyid);
    $baselang = Survey::model()->findByPk($surveyid)->language;

    $questions = Questions::model()->findAllByAttributes(array('gid' => $gid, 'sid' => $surveyid, 'language' => $baselang));
    $p = 0;
    foreach ($questions as $question)
    {
        $question->question_order = $p;
        $question->save();
        $p++;
    }
}
*/

function shiftOrderQuestions($sid,$gid,$shiftvalue) //Function shifts the sortorder for questions
{
    $sid=sanitize_int($sid);
    $gid=sanitize_int($gid);
    $shiftvalue=sanitize_int($shiftvalue);

    $baselang = Survey::model()->findByPk($sid)->language;

    Questions::model()->updateQuestionOrder($gid,$baselang,$shiftvalue);
}

function fixSortOrderGroups($surveyid) //Function rewrites the sortorder for groups
{
    $baselang = Survey::model()->findByPk($surveyid)->language;
    Groups::model()->updateGroupOrder($surveyid,$baselang);
}

function fixMovedQuestionConditions($qid,$oldgid,$newgid) //Function rewrites the cfieldname for a question after group change
{
    $surveyid = Yii::app()->getConfig('sid');
    $qid=sanitize_int($qid);
    $oldgid=sanitize_int($oldgid);
    $newgid=sanitize_int($newgid);
    Conditions::model()->updateCFieldName($surveyid,$qid,$oldgid,$newgid);
    // TMSW Conditions->Relevance:  Call LEM->ConvertConditionsToRelevance() when done
}


/**
* This function returns POST/REQUEST vars, for some vars like SID and others they are also sanitized
*
* @param mixed $stringname
* @param mixed $urlParam
*/
function returnGlobal($stringname)
{
    if ($stringname=='sid') // don't read SID from a Cookie
    {
        if (isset($_GET[$stringname])) $urlParam = $_GET[$stringname];
        if (isset($_POST[$stringname])) $urlParam = $_POST[$stringname];
        }
    elseif (isset($_REQUEST[$stringname]))
        {
        $urlParam = $_REQUEST[$stringname];
        }

    if (isset($urlParam))
    {
        if ($stringname == 'sid' || $stringname == "gid" || $stringname == "oldqid" ||
        $stringname == "qid" || $stringname == "tid" ||
        $stringname == "lid" || $stringname == "ugid"||
        $stringname == "thisstep" || $stringname == "scenario" ||
        $stringname == "cqid" || $stringname == "cid" ||
        $stringname == "qaid" || $stringname == "scid" ||
        $stringname == "loadsecurity")
        {
            return sanitize_int($urlParam);
        }
        elseif ($stringname =="lang" || $stringname =="adminlang")
        {
            return sanitize_languagecode($urlParam);
        }
        elseif ($stringname =="htmleditormode" ||
        $stringname =="subaction" ||
        $stringname =="questionselectormode" ||
        $stringname =="templateeditormode"
        )
        {
            return sanitize_paranoid_string($urlParam);
        }
        elseif ( $stringname =="cquestions")
        {
            return sanitize_cquestions($urlParam);
        }
        return $urlParam;
    }
    else
    {
        return NULL;
    }

}


function sendCacheHeaders()
{
    global $embedded;
    if ( $embedded ) return;
    if (!headers_sent())
    {
        header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');  // this line lets IE7 run LimeSurvey in an iframe
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
        header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Content-Type: text/html; charset=utf-8');
    }
}

function getQuestion($fieldcode)
{
    list($sid, $gid, $qid) = explode('X', $fieldcode);
    $fields=createFieldMap($sid,false,false,getBaseLanguageFromSurveyID($sid));
    foreach($fields as $q)
    {
        if($q->id==$qid && $q->surveyid==$sid && $q->gid==$gid) return $q;
    }
    return false;
}

/**
*
* @param type $iSurveyID The Survey ID
* @param type $sFieldCode Field code of the particular field
* @param type $sValue The stored response value
* @return string
*/
function getExtendedAnswer($iSurveyID, $sFieldCode, $sValue)
{
    if (is_null($sValue) || trim($sValue)=='') return '';
    $sLanguage = $oLanguage->langcode;
    //Fieldcode used to determine question, $sValue used to match against answer code
    //Returns NULL if question type does not suit
    if (strpos($sFieldCode, "{$iSurveyID}X")!==0) //Only check if it looks like a real fieldcode
    {
        if($sFieldCode=='submitdate' && trim($sValue)!='') {
            $dateformatdetails = getDateFormatDataForQID(array('date_format'=>''), $iSurveyID);
            return convertDateTimeFormat($sValue,"Y-m-d H:i:s",$dateformatdetails['phpdate'].' H:i:s');
        }
        return $sValue;
    }
    $fieldmap = createFieldMap($iSurveyID,false,false,$sLanguage);
    if (isset($fieldmap[$sFieldCode]))
        $q = $fieldmap[$sFieldCode];
    else
        return false;

    return $q->getExtendedAnswer($sValue, $oLanguage);
}

/*function validateEmailAddress($email)
{
// Create the syntactical validation regular expression
// Validate the syntax

// see http://data.iana.org/TLD/tlds-alpha-by-domain.txt
$maxrootdomainlength = 6;
return ( ! preg_match("/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*\.(([0-9]{1,3})|([a-zA-Z]{2,".$maxrootdomainlength."}))$/ix", $email)) ? FALSE : TRUE;
}*/

function validateEmailAddress($email){


    $no_ws_ctl    = "[\\x01-\\x08\\x0b\\x0c\\x0e-\\x1f\\x7f]";
    $alpha        = "[\\x41-\\x5a\\x61-\\x7a]";
    $digit        = "[\\x30-\\x39]";
    $cr        = "\\x0d";
    $lf        = "\\x0a";
    $crlf        = "(?:$cr$lf)";


    $obs_char    = "[\\x00-\\x09\\x0b\\x0c\\x0e-\\x7f]";
    $obs_text    = "(?:$lf*$cr*(?:$obs_char$lf*$cr*)*)";
    $text        = "(?:[\\x01-\\x09\\x0b\\x0c\\x0e-\\x7f]|$obs_text)";


    $text        = "(?:$lf*$cr*$obs_char$lf*$cr*)";
    $obs_qp        = "(?:\\x5c[\\x00-\\x7f])";
    $quoted_pair    = "(?:\\x5c$text|$obs_qp)";


    $wsp        = "[\\x20\\x09]";
    $obs_fws    = "(?:$wsp+(?:$crlf$wsp+)*)";
    $fws        = "(?:(?:(?:$wsp*$crlf)?$wsp+)|$obs_fws)";
    $ctext        = "(?:$no_ws_ctl|[\\x21-\\x27\\x2A-\\x5b\\x5d-\\x7e])";
    $ccontent    = "(?:$ctext|$quoted_pair)";
    $comment    = "(?:\\x28(?:$fws?$ccontent)*$fws?\\x29)";
    $cfws        = "(?:(?:$fws?$comment)*(?:$fws?$comment|$fws))";


    $outer_ccontent_dull    = "(?:$fws?$ctext|$quoted_pair)";
    $outer_ccontent_nest    = "(?:$fws?$comment)";
    $outer_comment        = "(?:\\x28$outer_ccontent_dull*(?:$outer_ccontent_nest$outer_ccontent_dull*)+$fws?\\x29)";



    $atext        = "(?:$alpha|$digit|[\\x21\\x23-\\x27\\x2a\\x2b\\x2d\\x2f\\x3d\\x3f\\x5e\\x5f\\x60\\x7b-\\x7e])";
    $atext_domain     = "(?:$alpha|$digit|[\\x2b\\x2d\\x5f])";

    $atom        = "(?:$cfws?(?:$atext)+$cfws?)";
    $atom_domain       = "(?:$cfws?(?:$atext_domain)+$cfws?)";


    $qtext        = "(?:$no_ws_ctl|[\\x21\\x23-\\x5b\\x5d-\\x7e])";
    $qcontent    = "(?:$qtext|$quoted_pair)";
    $quoted_string    = "(?:$cfws?\\x22(?:$fws?$qcontent)*$fws?\\x22$cfws?)";


    $quoted_string    = "(?:$cfws?\\x22(?:$fws?$qcontent)+$fws?\\x22$cfws?)";
    $word        = "(?:$atom|$quoted_string)";


    $obs_local_part    = "(?:$word(?:\\x2e$word)*)";


    $obs_domain    = "(?:$atom_domain(?:\\x2e$atom_domain)*)";

    $dot_atom_text     = "(?:$atext+(?:\\x2e$atext+)*)";
    $dot_atom_text_domain    = "(?:$atext_domain+(?:\\x2e$atext_domain+)*)";


    $dot_atom          = "(?:$cfws?$dot_atom_text$cfws?)";
    $dot_atom_domain   = "(?:$cfws?$dot_atom_text_domain$cfws?)";


    $dtext        = "(?:$no_ws_ctl|[\\x21-\\x5a\\x5e-\\x7e])";
    $dcontent    = "(?:$dtext|$quoted_pair)";
    $domain_literal    = "(?:$cfws?\\x5b(?:$fws?$dcontent)*$fws?\\x5d$cfws?)";


    $local_part    = "(($dot_atom)|($quoted_string)|($obs_local_part))";
    $domain        = "(($dot_atom_domain)|($domain_literal)|($obs_domain))";
    $addr_spec    = "$local_part\\x40$domain";


    if (strlen($email) > 256) return FALSE;


    $email = stripComments($outer_comment, $email, "(x)");



    if (!preg_match("!^$addr_spec$!", $email, $m)){

        return FALSE;
    }

    $bits = array(
    'local'            => isset($m[1]) ? $m[1] : '',
    'local-atom'        => isset($m[2]) ? $m[2] : '',
    'local-quoted'        => isset($m[3]) ? $m[3] : '',
    'local-obs'        => isset($m[4]) ? $m[4] : '',
    'domain'        => isset($m[5]) ? $m[5] : '',
    'domain-atom'        => isset($m[6]) ? $m[6] : '',
    'domain-literal'    => isset($m[7]) ? $m[7] : '',
    'domain-obs'        => isset($m[8]) ? $m[8] : '',
    );



    $bits['local']    = stripComments($comment, $bits['local']);
    $bits['domain']    = stripComments($comment, $bits['domain']);




    if (strlen($bits['local']) > 64) return FALSE;
    if (strlen($bits['domain']) > 255) return FALSE;



    if (strlen($bits['domain-literal'])){

        $Snum            = "(\d{1,3})";
        $IPv4_address_literal    = "$Snum\.$Snum\.$Snum\.$Snum";

        $IPv6_hex        = "(?:[0-9a-fA-F]{1,4})";

        $IPv6_full        = "IPv6\:$IPv6_hex(:?\:$IPv6_hex){7}";

        $IPv6_comp_part        = "(?:$IPv6_hex(?:\:$IPv6_hex){0,5})?";
        $IPv6_comp        = "IPv6\:($IPv6_comp_part\:\:$IPv6_comp_part)";

        $IPv6v4_full        = "IPv6\:$IPv6_hex(?:\:$IPv6_hex){5}\:$IPv4_address_literal";

        $IPv6v4_comp_part    = "$IPv6_hex(?:\:$IPv6_hex){0,3}";
        $IPv6v4_comp        = "IPv6\:((?:$IPv6v4_comp_part)?\:\:(?:$IPv6v4_comp_part\:)?)$IPv4_address_literal";



        if (preg_match("!^\[$IPv4_address_literal\]$!", $bits['domain'], $m)){

            if (intval($m[1]) > 255) return FALSE;
            if (intval($m[2]) > 255) return FALSE;
            if (intval($m[3]) > 255) return FALSE;
            if (intval($m[4]) > 255) return FALSE;

        }else{


            while (1){

                if (preg_match("!^\[$IPv6_full\]$!", $bits['domain'])){
                    break;
                }

                if (preg_match("!^\[$IPv6_comp\]$!", $bits['domain'], $m)){
                    list($a, $b) = explode('::', $m[1]);
                    $folded = (strlen($a) && strlen($b)) ? "$a:$b" : "$a$b";
                    $groups = explode(':', $folded);
                    if (count($groups) > 6) return FALSE;
                    break;
                }

                if (preg_match("!^\[$IPv6v4_full\]$!", $bits['domain'], $m)){

                    if (intval($m[1]) > 255) return FALSE;
                    if (intval($m[2]) > 255) return FALSE;
                    if (intval($m[3]) > 255) return FALSE;
                    if (intval($m[4]) > 255) return FALSE;
                    break;
                }

                if (preg_match("!^\[$IPv6v4_comp\]$!", $bits['domain'], $m)){
                    list($a, $b) = explode('::', $m[1]);
                    $b = substr($b, 0, -1); # remove the trailing colon before the IPv4 address
                    $folded = (strlen($a) && strlen($b)) ? "$a:$b" : "$a$b";
                    $groups = explode(':', $folded);
                    if (count($groups) > 4) return FALSE;
                    break;
                }

                return FALSE;
            }
        }
    }else{


        $labels = explode('.', $bits['domain']);


        if (count($labels) == 1) return FALSE;


        foreach ($labels as $label){

            if (strlen($label) > 63) return FALSE;
            if (substr($label, 0, 1) == '-') return FALSE;
            if (substr($label, -1) == '-') return FALSE;
        }

        if (preg_match('!^[0-9]+$!', array_pop($labels))) return FALSE;
    }


    return TRUE;
}

##################################################################################

function stripComments($comment, $email, $replace=''){

    while (1){
        $new = preg_replace("!$comment!", $replace, $email);
        if (strlen($new) == strlen($email)){
            return $email;
        }
        $email = $new;
    }
}


function validateTemplateDir($sTemplateName)
{
    $usertemplaterootdir = Yii::app()->getConfig('usertemplaterootdir');
    $standardtemplaterootdir = Yii::app()->getConfig('standardtemplaterootdir');
    $sDefaultTemplate = Yii::app()->getConfig('defaulttemplate');
    if (is_dir("$usertemplaterootdir/{$sTemplateName}/"))
    {
        return $sTemplateName;
    }
    elseif (is_dir("$standardtemplaterootdir/{$sTemplateName}/"))
    {
        return $sTemplateName;
    }
    elseif (is_dir("$standardtemplaterootdir/{$sDefaultTemplate}/"))
    {
        return $sDefaultTemplate;
    }
    elseif (is_dir("$usertemplaterootdir/{$sDefaultTemplate}/"))
    {
        return $sDefaultTemplate;
    }
    else
    {
        return 'default';
    }
}



/**
 *This functions generates a a summary containing the SGQA for questions of a survey, enriched with options per question
 * It can be used for the generation of statistics. Derived from Statistics_userController
 * @param int $iSurveyID Id of the Survey in question
 * @param array $aFilters an array which is the result of a query in Questions model
 * @param string $sLanguage
 * @return array The summary
 */
function createCompleteSGQA($iSurveyID, $sLanguage, $public  = false)
{
    $allfields = array();
    foreach(createFieldMap($iSurveyID, false, false, $sLanguage) as $q)
    {
        if (!is_a($q, 'QuestionModule') && $public)
        {
            continue;
        }
        elseif (is_a($q, 'QuestionModule') &&$q->statisticsFieldmap() && $public)
        {
            $attributes = $q->getAttributeValues();
            if ($q->availableAttributes('public_statistics') && trim($attributes['public_statistics']) == 1)
                $allfields[$q->fieldname] = $q;
        }
        elseif (!is_a($q, 'QuestionModule') || $q->statisticsFieldmap())
        {
            $allfields[$q->fieldname] = $q;
        }
    }
    return $allfields;
}

/**
* This function generates an array containing the fieldcode, and matching data in the same order as the activate script
*
* @param string $surveyid The Survey ID
* @param mixed $force_refresh - Forces to really refresh the array, not just take the session copy
* @param int $questionid Limit to a certain qid only (for question preview) - default is false
* @param string $sQuestionLanguage The language to use
* @return array
*/

function createFieldMap($surveyid, $force_refresh=false, $questionid=false, $sLanguage) {
    
    $sLanguage = sanitize_languagecode($sLanguage);
    $surveyid = sanitize_int($surveyid);
    //checks to see if fieldmap has already been built for this page.
    if (isset(Yii::app()->session['fieldmap-' . $surveyid . $sLanguage]) && !$force_refresh && $questionid == false) {
        return Yii::app()->session['fieldmap-' . $surveyid . $sLanguage];
    }

    $q = new StdClass;
    $q->fieldname="id";
    $q->surveyid=$surveyid;
    $q->gid="";
    $q->id="";
    $q->aid="";
    $q->title="";
    $q->text=gT("Response ID");
    $q->group_name="";
    $fieldmap["id"] = $q;

    $q = new StdClass;
    $q->fieldname="submitdate";
    $q->surveyid=$surveyid;
    $q->gid="";
    $q->id="";
    $q->aid="";
    $q->title="";
    $q->text=gT("Date submitted");
    $q->group_name="";
    $fieldmap["submitdate"] = $q;

    $q = new StdClass;
    $q->fieldname="lastpage";
    $q->surveyid=$surveyid;
    $q->gid="";
    $q->id="";
    $q->aid="";
    $q->title="";
    $q->text=gT("Last page");
    $q->group_name="";
    $fieldmap["lastpage"] = $q;

    $q = new StdClass;
    $q->fieldname="startlanguage";
    $q->surveyid=$surveyid;
    $q->gid="";
    $q->id="";
    $q->aid="";
    $q->title="";
    $q->text=gT("Start language");
    $q->group_name="";
    $fieldmap["startlanguage"] = $q;
    //Check for any additional fields for this survey and create necessary fields (token and datestamp and ipaddr)
    $prow = Survey::model()->findByPk($surveyid)->getAttributes(); //Checked
    if ($prow['anonymized'] == "N" && Survey::model()->hasTokens($surveyid)) 
    {
        $q = new StdClass;
        $q->fieldname="token";
        $q->surveyid=$surveyid;
        $q->gid="";
        $q->id="";
        $q->aid="";
        $q->title="";
        $q->text=gT("Token");
        $q->group_name="";
        $fieldmap["token"] = $q;
    }
    if ($prow['datestamp'] == "Y")
    {
        $q = new StdClass;
        $q->fieldname="startdate";
        $q->surveyid=$surveyid;
        $q->gid="";
        $q->id="";
        $q->aid="";
        $q->title="";
        $q->text=gT("Date started");
        $q->group_name="";
        $fieldmap["startdate"] = $q;

        $q = new StdClass;
        $q->fieldname="datestamp";
        $q->surveyid=$surveyid;
        $q->gid="";
        $q->id="";
        $q->aid="";
        $q->title="";
        $q->text=gT("Date last action");
        $q->group_name="";
        $fieldmap["datestamp"] = $q;
    }
    if ($prow['ipaddr'] == "Y")
    {
        $q = new StdClass;
        $q->fieldname="ipaddr";
        $q->surveyid=$surveyid;
        $q->gid="";
        $q->id="";
        $q->aid="";
        $q->title="";
        $q->text=gT("IP address");
        $q->group_name="";
        $fieldmap["ipaddr"] = $q;
    }
    // Add 'refurl' to fieldmap.
    if ($prow['refurl'] == "Y")
    {
        $q = new StdClass;
        $q->fieldname="refurl";
        $q->surveyid=$surveyid;
        $q->gid="";
        $q->id="";
        $q->aid="";
        $q->title="";
        $q->text=gT("Referrer URL");
        $q->group_name="";
        $fieldmap["refurl"] = $q;
    }
    return $fieldmap;
    $cond = "t.sid=$surveyid AND groups.language='$sLanguage'";
    if ($questionid!==false)
    {
        $cond.=" AND t.qid=$questionid";
    }
    $aresult = Questions::model()->with('groups')->findAll(array('condition'=>$cond, 'order'=>'groups.group_order, sortorder', 'index' => 'qid'));
    $questionSeq=-1; // this is incremental question sequence across all groups
    $groupSeq=-1;
    $_groupOrder=-1;

    foreach ($aresult as $arow) //With each question, create the appropriate field(s))
    {
        ++$questionSeq;

        // fix fact that group_order may have gaps
        if ($_groupOrder != $arow->groups['group_order']) {
            $_groupOrder = $arow->groups['group_order'];
            ++$groupSeq;
        }
        // Conditions indicators are obsolete with EM.  However, they are so tightly coupled into LS code that easider to just set values to 'N' for now and refactor later.
        $conditions = 'N';
        $usedinconditions = 'N';

        // Field identifier
        // GXQXSXA
        // G=Group  Q=Question S=Subquestion A=Answer Option
        // If S or A don't exist then set it to 0
        // Implicit (subqestion intermal to a question type ) or explicit qubquestions/answer count starts at 1

        $fieldname="{$arow['sid']}X{$arow['gid']}X{$arow['qid']}";
        if (isset($arow->questiontype))
        {
            $pq = createQuestion($arow->questiontype);
            /*, array('surveyid'=>$surveyid,
                'id'=>$arow['qid'], 'fieldname'=>$fieldname,
                'title'=>$arow['title'], 'text'=>$arow['question'],
                'gid'=>$arow['gid'], 'mandatory'=>$arow['mandatory'],
                'conditionsexist'=>$conditions, 'usedinconditions'=>$usedinconditions,
                'questioncount'=>$questionSeq, 'language'=>$sLanguage));
            $pq->aid = '';
            if(isset($defaults[$arow['qid']])) $pq->defaults = $defaults[$arow['qid']];

            $pq->haspreg = $arow['preg'];
            $pq->isother = $arow['other'];
            $pq->groupname = $arow->groups['group_name'];
            $pq->groupcount = $groupSeq;
            $add = $pq->createFieldmap();
            */
            if (count($add))
            {
                $tmp=array_values($add);
                $q = $tmp[count($add)-1];
                $q->relevance=$arow['relevance'];
                $q->grelevance=$arow->groups['grelevance'];
                $q->preg=$arow['preg'];
                $q->other=$arow['other'];
                $q->help=$arow['help'];
                $fieldmap=array_merge($fieldmap, $add);
            }
            else
            {
                --$questionSeq; // didn't generate a valid $fieldmap entry, so decrement the question counter to ensure they are sequential
            }
        }
    }

    if ($questionid == false)
    {
        // If the fieldmap was randomized, the master will contain the proper order.  Copy that fieldmap with the new language settings.
        if (isset(Yii::app()->session['fieldmap-' . $surveyid . '-randMaster']))
        {
            $masterFieldmap = Yii::app()->session['fieldmap-' . $surveyid . '-randMaster'];
            $mfieldmap = Yii::app()->session[$masterFieldmap];

            foreach ($mfieldmap as $fieldname => $mq)
            {
                if (isset($fieldmap[$fieldname]))
                {
                    $q = $fieldmap[$fieldname];
                    if (isset($q->text))
                    {
                        $mq->text = $q->text;
                    }
                    if (isset($q->sq))
                    {
                        $mq->sq = $q->sq;
                    }
                    if (isset($q->sq1))
                    {
                        $mq->sq1 = $q->sq1;
                    }
                     if (isset($q->sq2))
                    {
                        $mq->sq2 = $q->sq2;
                    }
                    if (isset($q->groupname))
                    {
                        $mq->groupname = $q->groupname;
                    }
                    if (isset($q->default))
                    {
                        $mq->default = $q->default;
                    }
                    if (isset($q->help))
                    {
                        $mq->help = $q->help;
                    }
                }
            }
            $fieldmap = $mfieldmap;
        }
        Yii::app()->session['fieldmap-' . $surveyid . $sLanguage]=$fieldmap;
    }
    return $fieldmap;
}

/**
* Returns true if the given survey has a File Upload Question Type
* @param $surveyid The survey ID
* @return bool
*/
function hasFileUploadQuestion($surveyid) {
    $fieldmap = createFieldMap($surveyid,false,false,getBaseLanguageFromSurveyID($surveyid));

    foreach ($fieldmap as $q) {
        if (substr(get_class($q),-8)=="Question" && $q->fileUpload()) return true;
    }
}

/**
* set the rights of a user and his children
*
* @param int $uid the user id
* @param mixed $rights rights array
*/
function setUserRights($uid, $rights)
{
    User::model()->setUserRights($uid, $rights);
}

/**
* This function returns a count of the number of saved responses to a survey
*
* @param mixed $surveyid Survey ID
*/
function getSavedCount($surveyid)
{
    $surveyid=(int)$surveyid;

    return Saved_control::model()->getCountOfAll($surveyid);
}

/**
* Returns the base language from a survey id
*
* @deprecated Use Survey::model()->findByPk($surveyid)->language
* @param int $surveyid
* @return string
*/
function getBaseLanguageFromSurveyID($surveyid)
{
    return Survey::model()->findByPk($surveyid)->language;
}


function buildLabelSetCheckSumArray()
{
    // BUILD CHECKSUMS FOR ALL EXISTING LABEL SETS

    /**$query = "SELECT lid
    FROM ".db_table_name('labelsets')."
    ORDER BY lid"; */
    $result = Labelsets::model()->getLID();//($query) or safeDie("safe_died collecting labelset ids<br />$query<br />");  //Checked)
    $csarray=array();
    foreach($result as $row)
    {
        $thisset="";
        $query2 = "SELECT code, title, sortorder, language, assessment_value
        FROM {{labels}}
        WHERE lid={$row['lid']}
        ORDER BY language, sortorder, code";
        $result2 = Yii::app()->db->createCommand($query2)->query();
        foreach ($result2->readAll() as $row2)
        {
            $thisset .= implode('.', $row2);
        } // while
        $csarray[$row['lid']]=dechex(crc32($thisset)*1);
    }

    return $csarray;
}


/**
* Returns a flat array with all question attributes for the question only (and the qid we gave it)!
* @param $iQID The question ID
* @return array$bOrderByNative=>value, attribute=>value} or false if the question ID does not exist (anymore)
*/
function getQuestionAttributeValues($qid) //AJSL
{
    static $cache;
    $qid = sanitize_int($qid);
    if (isset($cache[$qid])) return $cache[$qid];
    $row = Questions::model()->with('question_types')->findByAttributes(array('qid' => $qid));
    $q = createQuestion($row->question_types['class'], array('id'=>$qid));

    return $cache[$q->id] = $q->getAttributeValues();
}

/**
*
* Returns the questionAttribtue value set or '' if not set
* @author: lemeur
* @param $questionAttributeArray
* @param $attributeName
* @param $language string Optional: The language if the particualr attributes is localizable
* @return string
*/
function getQuestionAttributeValue($questionAttributeArray, $attributeName, $language='')
{
    if ($language=='' && isset($questionAttributeArray[$attributeName]))
    {
        return $questionAttributeArray[$attributeName];
    }
    elseif ($language!='' && isset($questionAttributeArray[$attributeName][$language]))
    {
        return $questionAttributeArray[$attributeName][$language];
    }
    else
    {
        return '';
    }
}

/**
* Returns array of attributes
*
* @param mixed $returnByName If set to true the array will be by attribute name
*/
function questionAttributes()
{
    
    //For each question attribute include a key:
    // name - the display name
    // help - a short explanation

    // If you insert a new attribute please do it in correct alphabetical order!

    $qattributes["alphasort"]=array(
    'category'=>gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>0,
    "help"=>gT("Sort the answer options alphabetically"),
    "caption"=>gT('Sort answers alphabetically'));

    $qattributes["answer_width"]=array(
    'category'=>gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'integer',
    'min'=>'1',
    'max'=>'100',
    "help"=>gT('Set the percentage width of the (sub-)question column (1-100)'),
    "caption"=>gT('(Sub-)question width'));

    $qattributes["repeat_headings"]=array(
    'category'=>gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'integer',
     'default'=>'',
    "help"=>gT('Repeat headings every X subquestions (Set to 0 to deactivate heading repeat, deactivate minimum repeat headings from config).'),
    "caption"=>gT('Repeat headers'));

    $qattributes["array_filter"]=array(
    'category'=>gT('Logic'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>gT("Enter the code(s) of Multiple choice question(s) (separated by semicolons) to only show the matching answer options in this question."),
    "caption"=>gT('Array filter'));

    $qattributes["array_filter_exclude"]=array(
    'category'=>gT('Logic'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>gT("Enter the code(s) of Multiple choice question(s) (separated by semicolons) to exclude the matching answer options in this question."),
    "caption"=>gT('Array filter exclusion'));

    $qattributes["array_filter_style"]=array(
    'category'=>gT('Logic'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('Hidden'),
    1=>gT('Disabled')),
    'default'=>0,
    "help"=>gT("Specify how array-filtered sub-questions should be displayed"),
    "caption"=>gT('Array filter style'));

    $qattributes["assessment_value"]=array(
    'category'=>gT('Logic'),
    'sortorder'=>100,
    'default'=>'1',
    'inputtype'=>'integer',
    "help"=>gT("If one of the subquestions is marked then for each marked subquestion this value is added as assessment."),
    "caption"=>gT('Assessment value'));

    $qattributes["category_separator"]=array(
    'category'=>gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>gT('Category separator'),
    "caption"=>gT('Category separator'));

    $qattributes["display_columns"]=array(
    'category'=>gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'integer',
    'default'=>'1',
    'min'=>'1',
    'max'=>'100',
    "help"=>gT('The answer options will be distributed across the number of columns set here'),
    "caption"=>gT('Display columns'));

    $qattributes["display_rows"]=array(
    'category'=>gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>gT('How many rows to display'),
    "caption"=>gT('Display rows'));

    $qattributes["dropdown_dates"]=array(
    'category'=>gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>0,
    "help"=>gT('Use accessible dropdown boxes instead of calendar popup'),
    "caption"=>gT('Display dropdown boxes'));

    $qattributes["dropdown_dates_year_min"]=array(
    'category'=>gT('Display'),
    'sortorder'=>110,
    'inputtype'=>'text',
    "help"=>gT('Minimum year value in calendar'),
    "caption"=>gT('Minimum year'));

    $qattributes["dropdown_dates_year_max"]=array(
    'category'=>gT('Display'),
    'sortorder'=>111,
    'inputtype'=>'text',
    "help"=>gT('Maximum year value for calendar'),
    "caption"=>gT('Maximum year'));

    $qattributes["dropdown_prepostfix"]=array(
    'category'=>gT('Display'),
    'sortorder'=>112,
    'inputtype'=>'text',
    'i18n'=>true,
    "help"=>gT('Prefix|Suffix for dropdown lists'),
    "caption"=>gT('Dropdown prefix/suffix'));

    $qattributes["dropdown_separators"]=array(
    'category'=>gT('Display'),
    'sortorder'=>120,
    'inputtype'=>'text',
    "help"=>gT('Post-Answer-Separator|Inter-Dropdownlist-Separator for dropdown lists'),
    "caption"=>gT('Dropdown separator'));

    $qattributes["dualscale_headerA"]=array(
    'category'=>gT('Display'),
    'sortorder'=>110,
    'inputtype'=>'text',
    'i18n'=>true,
    "help"=>gT('Enter a header text for the first scale'),
    "caption"=>gT('Header for first scale'));

    $qattributes["dualscale_headerB"]=array(
    'category'=>gT('Display'),
    'sortorder'=>111,
    'inputtype'=>'text',
    'i18n'=>true,
    "help"=>gT('Enter a header text for the second scale'),
    "caption"=>gT('Header for second scale'));

    $qattributes["equals_num_value"]=array(
    'category'=>gT('Input'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>gT('Multiple numeric inputs sum must equal this value'),
    "caption"=>gT('Equals sum value'));

    $qattributes["em_validation_q"]=array(
    'category'=>gT('Logic'),
    'sortorder'=>200,
    'inputtype'=>'textarea',
    "help"=>gT('Enter a boolean equation to validate the whole question.'),
    "caption"=>gT('Question validation equation'));

    $qattributes["em_validation_q_tip"]=array(
    'category'=>gT('Logic'),
    'sortorder'=>210,
    'inputtype'=>'textarea',
    "help"=>gT('This is a hint text that will be shown to the participant describing the question validation equation.'),
    "caption"=>gT('Question validation tip'));

    $qattributes["em_validation_sq"]=array(
    'category'=>gT('Logic'),
    'sortorder'=>220,
    'inputtype'=>'textarea',
    "help"=>gT('Enter a boolean equation to validate each sub-question.'),
    "caption"=>gT('Sub-question validation equation'));

    $qattributes["em_validation_sq_tip"]=array(
    'category'=>gT('Logic'),
    'sortorder'=>230,
    'inputtype'=>'textarea',
    "help"=>gT('This is a tip shown to the participant describing the sub-question validation equation.'),
    "caption"=>gT('Sub-question validation tip'));

    $qattributes["exclude_all_others"]=array(
    'category'=>gT('Logic'),
    'sortorder'=>130,
    'inputtype'=>'text',
    "help"=>gT('Excludes all other options if a certain answer is selected - just enter the answer code(s) seperated with a semikolon.'),
    "caption"=>gT('Exclusive option'));

    $qattributes["exclude_all_others_auto"]=array(
    'category'=>gT('Logic'),
    'sortorder'=>131,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>0,
    "help"=>gT('If the participant marks all options, uncheck all and check the option set in the "Exclusive option" setting'),
    "caption"=>gT('Auto-check exclusive option if all others are checked'));

    // Map Options

    $qattributes["location_city"]=array(
    'readonly_when_active'=>true,
    'category'=>gT('Location'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'default'=>0,
    'options'=>array(0=>gT('Yes'),
    1=>gT('No')),
    "help"=>gT("Store the city?"),
    "caption"=>gT("Save city"));

    $qattributes["location_state"]=array(
    'readonly_when_active'=>true,
    'category'=>gT('Location'),
    'sortorder'=>100,
    'default'=>0,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('Yes'),
    1=>gT('No')),
    "help"=>gT("Store the state?"),
    "caption"=>gT("Save state"));

    $qattributes["location_postal"]=array(
    'readonly_when_active'=>true,
    'category'=>gT('Location'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'default'=>0,
    'options'=>array(0=>gT('Yes'),
    1=>gT('No')),
    "help"=>gT("Store the postal code?"),
    "caption"=>gT("Save postal code"));

    $qattributes["location_country"]=array(
    'readonly_when_active'=>true,
    'category'=>gT('Location'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'default'=>0,
    'options'=>array(0=>gT('Yes'),
    1=>gT('No')),
    "help"=>gT("Store the country?"),
    "caption"=>gT("Save country"));

    $qattributes["statistics_showmap"]=array(
    'category'=>gT('Statistics'),
    'inputtype'=>'singleselect',
    'sortorder'=>100,
    'options'=>array(1=>gT('Yes'), 0=>gT('No')),
    'help'=>gT("Show a map in the statistics?"),
    'caption'=>gT("Display map"),
    'default'=>1
    );

    $qattributes["statistics_showgraph"]=array(
    'category'=>gT('Statistics'),
    'inputtype'=>'singleselect',
    'sortorder'=>101,
    'options'=>array(1=>gT('Yes'), 0=>gT('No')),
    'help'=>gT("Display a chart in the statistics?"),
    'caption'=>gT("Display chart"),
    'default'=>1
    );

    $qattributes["statistics_graphtype"]=array(
    'category'=>gT('Statistics'),
    'inputtype'=>'singleselect',
    'sortorder'=>102,
    'options'=>array(0=>gT('Bar chart'), 1=>gT('Pie chart')),
    'help'=>gT("Select the type of chart to be displayed"),
    'caption'=>gT("Chart type"),
    'default'=>0
    );

    $qattributes["location_mapservice"]=array(
    'category'=>gT('Location'),
    'sortorder'=>90,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('Off'),
    1=>gT('Google Maps')),
    'default' => 0,
    "help"=>gT("Activate this to show a map above the input field where the user can select a location"),
    "caption"=>gT("Use mapping service"));

    $qattributes["location_mapwidth"]=array(
    'category'=>gT('Location'),
    'sortorder'=>102,
    'inputtype'=>'text',
    'default'=>'500',
    "help"=>gT("Width of the map in pixel"),
    "caption"=>gT("Map width"));

    $qattributes["location_mapheight"]=array(
    'category'=>gT('Location'),
    'sortorder'=>103,
    'inputtype'=>'text',
    'default'=>'300',
    "help"=>gT("Height of the map in pixel"),
    "caption"=>gT("Map height"));

    $qattributes["location_nodefaultfromip"]=array(
    'category'=>gT('Location'),
    'sortorder'=>91,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('Yes'),
    1=>gT('No')),
    'default' => 0,
    "help"=>gT("Get the default location using the user's IP address?"),
    "caption"=>gT("IP as default location"));

    $qattributes["location_defaultcoordinates"]=array(
    'category'=>gT('Location'),
    'sortorder'=>101,
    'inputtype'=>'text',
    "help"=>gT('Default coordinates of the map when the page first loads. Format: latitude [space] longtitude'),
    "caption"=>gT('Default position'));

    $qattributes["location_mapzoom"]=array(
    'category'=>gT('Location'),
    'sortorder'=>101,
    'inputtype'=>'text',
    'default'=>'11',
    "help"=>gT("Map zoom level"),
    "caption"=>gT("Zoom level"));

    // End Map Options

    $qattributes["hide_tip"]=array(
    'category'=>gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>0,
    "help"=>gT('Hide the tip that is normally shown with a question'),
    "caption"=>gT('Hide tip'));

    $qattributes['hidden']=array(
    'category'=>gT('Display'),
    'sortorder'=>101,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>0,
    'help'=>gT('Hide this question at any time. This is useful for including data using answer prefilling.'),
    'caption'=>gT('Always hide this question'));

    $qattributes["max_answers"]=array(
    'category'=>gT('Logic'),
    'sortorder'=>11,
    'inputtype'=>'integer',
    "help"=>gT('Limit the number of possible answers'),
    "caption"=>gT('Maximum answers'));

    $qattributes["max_num_value"]=array(
    'category'=>gT('Input'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>gT('Maximum sum value of multiple numeric input'),
    "caption"=>gT('Maximum sum value'));

    $qattributes["max_num_value_n"]=array(
    'category'=>gT('Input'),
    'sortorder'=>110,
    'inputtype'=>'integer',
    "help"=>gT('Maximum value of the numeric input'),
    "caption"=>gT('Maximum value'));

    $qattributes["maximum_chars"]=array(
    'category'=>gT('Input'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>gT('Maximum characters allowed'),
    "caption"=>gT('Maximum characters'));

    $qattributes["min_answers"]=array(
    'category'=>gT('Logic'),
    'sortorder'=>10,
    'inputtype'=>'integer',
    "help"=>gT('Ensure a minimum number of possible answers (0=No limit)'),
    "caption"=>gT('Minimum answers'));

    $qattributes["min_num_value"]=array(
    'category'=>gT('Input'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>gT('The sum of the multiple numeric inputs must be greater than this value'),
    "caption"=>gT('Minimum sum value'));

    $qattributes["min_num_value_n"]=array(
    'category'=>gT('Input'),
    'sortorder'=>100,
    'inputtype'=>'integer',
    "help"=>gT('Minimum value of the numeric input'),
    "caption"=>gT('Minimum value'));

    $qattributes["multiflexible_max"]=array(
    'category'=>gT('Display'),
    'sortorder'=>112,
    'inputtype'=>'text',
    "help"=>gT('Maximum value for array(mult-flexible) question type'),
    "caption"=>gT('Maximum value'));

    $qattributes["multiflexible_min"]=array(
    'category'=>gT('Display'),
    'sortorder'=>110,
    'inputtype'=>'text',
    "help"=>gT('Minimum value for array(multi-flexible) question type'),
    "caption"=>gT('Minimum value'));

    $qattributes["multiflexible_step"]=array(
    'category'=>gT('Display'),
    'sortorder'=>111,
    'inputtype'=>'text',
    "help"=>gT('Step value'),
    "caption"=>gT('Step value'));

    $qattributes["multiflexible_checkbox"]=array(
    'category'=>gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>0,
    "help"=>gT('Use checkbox layout'),
    "caption"=>gT('Checkbox layout'));

    $qattributes["reverse"]=array(
    'category'=>gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>0,
    "help"=>gT('Present answer options in reverse order'),
    "caption"=>gT('Reverse answer order'));

    $qattributes["num_value_int_only"]=array(
    'category'=>gT('Input'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(
    0=>gT('No'),
    1=>gT('Yes')),
    'default'=>0,
    "help"=>gT('Restrict input to integer values'),
    "caption"=>gT('Integer only'));

    $qattributes["numbers_only"]=array(
    'category'=>gT('Other'),
    'sortorder'=>150,
    'inputtype'=>'singleselect',
    'options'=>array(
    0=>gT('No'),
    1=>gT('Yes')
    ),
    'default'=>0,
    "help"=>gT('Allow only numerical input'),
    "caption"=>gT('Numbers only')
    );

    $qattributes['show_totals'] = array(
    'category' => gT('Other'),
    'sortorder' => 151,
    'inputtype' => 'singleselect',
    'options' => array(
    'X' => gT('Off'),
    'R' => gT('Rows'),
    'C' => gT('Columns'),
    'B' => gT('Both rows and columns')
    ),
    'default' => 'X',
    'help' => gT('Show totals for either rows, columns or both rows and columns'),
    'caption' => gT('Show totals for')
    );

    $qattributes['show_grand_total'] = array(
    'category' => gT('Other'),
    'sortorder' => 152,
    'inputtype' => 'singleselect',
    'options' => array(
    0 => gT('No'),
    1 => gT('Yes')
    ),
    'default' => 0,
    'help' => gT('Show grand total for either columns or rows'),
    'caption' => gT('Show grand total')
    );

    $qattributes["input_boxes"]=array(
    'category'=>gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>0,
    "help"=>gT("Present as text input boxes instead of dropdown lists"),
    "caption"=>gT("Text inputs"));

    $qattributes["other_comment_mandatory"]=array(
    'category'=>gT('Logic'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>0,
    "help"=>gT("Make the 'Other:' comment field mandatory when the 'Other:' option is active"),
    "caption"=>gT("'Other:' comment mandatory"));

    $qattributes["other_numbers_only"]=array(
    'category'=>gT('Logic'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>0,
    "help"=>gT("Allow only numerical input for 'Other' text"),
    "caption"=>gT("Numbers only for 'Other'"));

    $qattributes["other_replace_text"]=array(
    'category'=>gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'text',
    'i18n'=>true,
    "help"=>gT("Replaces the label of the 'Other:' answer option with a custom text"),
    "caption"=>gT("Label for 'Other:' option"));

    $qattributes["page_break"]=array(
    'category'=>gT('Other'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>0,
    "help"=>gT('Insert a page break before this question in printable view by setting this to Yes.'),
    "caption"=>gT('Insert page break in printable view'));

    $qattributes["prefix"]=array(
    'category'=>gT('Display'),
    'sortorder'=>10,
    'inputtype'=>'text',
    'i18n'=>true,
    "help"=>gT('Add a prefix to the answer field'),
    "caption"=>gT('Answer prefix'));

    $qattributes["printable_help"]=array(
    "types"=>"15ABCEFGHKLMNOPRWYZ!:*",
    'category'=>gT('Display'),
    'sortorder'=>201,
    "inputtype"=>"text",
    'i18n'=>true,
    'default'=>"",
    "help"=>gT('In the printable version replace the relevance equation with this explanation text.'),
    "caption"=>gT("Relevance help for printable survey"));    
    
    $qattributes["public_statistics"]=array(
    'category'=>gT('Statistics'),
    'sortorder'=>80,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>0,
    "help"=>gT('Show statistics of this question in the public statistics page'),
    "caption"=>gT('Show in public statistics'));

    $qattributes["random_order"]=array(
    'category'=>gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('Off'),
    1=>gT('Randomize on each page load')
    //,2=>gT('Randomize once on survey start')  //Mdekker: commented out as code to handle this was removed in refactoring
    ),
    'default'=>0,
    "help"=>gT('Present answers in random order'),
    "caption"=>gT('Random answer order'));

    $qattributes["showpopups"]=array(
    'category'=>gT('Display'),
    'sortorder'=>110,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>1,
    "caption"=>gT('Show javascript alert'),
    "help"=>gT('Show an alert if answers exceeds the number of max answers'));
    $qattributes["samechoiceheight"]=array(
    'category'=>gT('Display'),
    'sortorder'=>120,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>1,
    "caption"=>gT('Same height for all choice'),
    "help"=>gT('Force each choice to have the same height'));
    $qattributes["samelistheight"]=array(
    'category'=>gT('Display'),
    'sortorder'=>121,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>1,
    "caption"=>gT('Same height for lists'),
    "help"=>gT('Force the choice list and the rank list to have the same height'));

    $qattributes["parent_order"]=array(
    'category'=>gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "caption"=>gT('Get order from previous question'),
    "help"=>gT('Enter question ID to get subquestion order from a previous question'));

    $qattributes["slider_layout"]=array(
    'category'=>gT('Slider'),
    'sortorder'=>1,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>0,
    "help"=>gT('Use slider layout'),
    "caption"=>gT('Use slider layout'));

    $qattributes["slider_min"]=array(
    'category'=>gT('Slider'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>gT('Slider minimum value'),
    "caption"=>gT('Slider minimum value'));

    $qattributes["slider_max"]=array(
    'category'=>gT('Slider'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>gT('Slider maximum value'),
    "caption"=>gT('Slider maximum value'));

    $qattributes["slider_accuracy"]=array(
    'category'=>gT('Slider'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>gT('Slider accuracy'),
    "caption"=>gT('Slider accuracy'));

    $qattributes["slider_default"]=array(
    'category'=>gT('Slider'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>gT('Slider initial value'),
    "caption"=>gT('Slider initial value'));

    $qattributes["slider_middlestart"]=array(
    'category'=>gT('Slider'),
    'sortorder'=>10,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>0,
    "help"=>gT('The handle is displayed at the middle of the slider (this will not set the initial value)'),
    "caption"=>gT('Slider starts at the middle position'));

    $qattributes["slider_rating"]=array(
    'category'=>gT('Display'),
    'sortorder'=>90,
    'inputtype'=>'singleselect',
    'options'=>array(
    0=>gT('No'),
    1=>gT('Yes - stars'),
    2=>gT('Yes - slider with emoticon'),
    ),
    'default'=>0,
    "help"=>gT('Use slider layout'),
    "caption"=>gT('Use slider layout'));


    $qattributes["slider_showminmax"]=array(
    'category'=>gT('Slider'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>0,
    "help"=>gT('Display min and max value under the slider'),
    "caption"=>gT('Display slider min and max value'));

    $qattributes["slider_separator"]=array(
    'category'=>gT('Slider'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>gT('Answer|Left-slider-text|Right-slider-text separator character'),
    "caption"=>gT('Slider left/right text separator'));

    $qattributes["suffix"]=array(
    'category'=>gT('Display'),
    'sortorder'=>11,
    'inputtype'=>'text',
    'i18n'=>true,
    "help"=>gT('Add a suffix to the answer field'),
    "caption"=>gT('Answer suffix'));

    $qattributes["text_input_width"]=array(
    'category'=>gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>gT('Width of text input box'),
    "caption"=>gT('Input box width'));

    $qattributes["use_dropdown"]=array(
    'category'=>gT('Display'),
    'sortorder'=>112,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>0,
    "help"=>gT('Present dropdown control(s) instead of list of radio buttons'),
    "caption"=>gT('Use dropdown presentation'));


    $qattributes["dropdown_size"]=array(
    'category'=>gT('Display'),
    'sortorder'=>200,
    'inputtype'=>'text',
    'default'=>0,
    "help"=>gT('For list dropdown boxes, show up to this many rows'),
    "caption"=>gT('Height of dropdown'));

    $qattributes["dropdown_prefix"]=array(
    'category'=>gT('Display'),
    'sortorder'=>201,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('None'),
    1=>gT('Order - like 3)'),
    ),
    'default'=>0,
    "help"=>gT('Accelerator keys for list items'),
    "caption"=>gT('Prefix for list items'));

    $qattributes["scale_export"]=array(
    'category'=>gT('Other'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>gT('Default'),
    1=>gT('Nominal'),
    2=>gT('Ordinal'),
    3=>gT('Scale')),
    'default'=>0,
    "help"=>gT("Set a specific SPSS export scale type for this question"),
    "caption"=>gT('SPSS export scale type'));

    $qattributes["choice_title"]=array(
    'category'=>gT('Other'),
    'sortorder'=>200,
    "inputtype"=>"text",
    'i18n'=>true,
    'default'=>"",
    "help"=>sprintf(gT("Replace choice header (default: \"%s\")",'js'),gT("Your Choices")),
    "caption"=>gT("Choice header"));

    $qattributes["rank_title"]=array(
    'category'=>gT('Other'),
    'sortorder'=>201,
    "inputtype"=>"text",
    'i18n'=>true,
    'default'=>"",
    "help"=>sprintf(gT("Replace rank header (default: \"%s\")",'js'),gT("Your Ranking")),
    "caption"=>gT("Rank header"));

    //Timer attributes
    $qattributes["time_limit"]=array(
    'category'=>gT('Timer'),
    'sortorder'=>90,
    "inputtype"=>"integer",
    "help"=>gT("Limit time to answer question (in seconds)"),
    "caption"=>gT("Time limit"));

    $qattributes["time_limit_action"]=array(
    'category'=>gT('Timer'),
    'sortorder'=>92,
    'inputtype'=>'singleselect',
    'options'=>array(1=>gT('Warn and move on'),
    2=>gT('Move on without warning'),
    3=>gT('Disable only')),
    "default" => 1,
    "help"=>gT("Action to perform when time limit is up"),
    "caption"=>gT("Time limit action"));

    $qattributes["time_limit_disable_next"]=array(
    'category'=>gT('Timer'),
    'sortorder'=>94,
    "inputtype"=>"singleselect",
    'default'=>0,
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    "help"=>gT("Disable the next button until time limit expires"),
    "caption"=>gT("Time limit disable next"));

    $qattributes["time_limit_disable_prev"]=array(
    'category'=>gT('Timer'),
    'sortorder'=>96,
    "inputtype"=>"singleselect",
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>0,
    "help"=>gT("Disable the prev button until the time limit expires"),
    "caption"=>gT("Time limit disable prev"));

    $qattributes["time_limit_countdown_message"]=array(
    'category'=>gT('Timer'),
    'sortorder'=>98,
    "inputtype"=>"textarea",
    'i18n'=>true,
    "help"=>gT("The text message that displays in the countdown timer during the countdown"),
    "caption"=>gT("Time limit countdown message"));

    $qattributes["time_limit_timer_style"]=array(
    'category'=>gT('Timer'),
    'sortorder'=>100,
    "inputtype"=>"textarea",
    "help"=>gT("CSS Style for the message that displays in the countdown timer during the countdown"),
    "caption"=>gT("Time limit timer CSS style"));

    $qattributes["time_limit_message_delay"]=array(
    'category'=>gT('Timer'),
    'sortorder'=>102,
    "inputtype"=>"integer",
    "help"=>gT("Display the 'time limit expiry message' for this many seconds before performing the 'time limit action' (defaults to 1 second if left blank)"),
    "caption"=>gT("Time limit expiry message display time"));

    $qattributes["time_limit_message"]=array(
    'category'=>gT('Timer'),
    'sortorder'=>104,
    "inputtype"=>"textarea",
    'i18n'=>true,
    "help"=>gT("The message to display when the time limit has expired (a default message will display if this setting is left blank)"),
    "caption"=>gT("Time limit expiry message"));

    $qattributes["time_limit_message_style"]=array(
    'category'=>gT('Timer'),
    'sortorder'=>106,
    "inputtype"=>"textarea",
    "help"=>gT("CSS style for the 'time limit expiry message'"),
    "caption"=>gT("Time limit message CSS style"));

    $qattributes["time_limit_warning"]=array(
    'category'=>gT('Timer'),
    'sortorder'=>108,
    "inputtype"=>"integer",
    "help"=>gT("Display a 'time limit warning' when there are this many seconds remaining in the countdown (warning will not display if left blank)"),
    "caption"=>gT("1st time limit warning message timer"));

    $qattributes["time_limit_warning_display_time"]=array(
    'category'=>gT('Timer'),
    'sortorder'=>110,
    "inputtype"=>"integer",
    "help"=>gT("The 'time limit warning' will stay visible for this many seconds (will not turn off if this setting is left blank)"),
    "caption"=>gT("1st time limit warning message display time"));

    $qattributes["time_limit_warning_message"]=array(
    'category'=>gT('Timer'),
    'sortorder'=>112,
    "inputtype"=>"textarea",
    'i18n'=>true,
    "help"=>gT("The message to display as a 'time limit warning' (a default warning will display if this is left blank)"),
    "caption"=>gT("1st time limit warning message"));

    $qattributes["time_limit_warning_style"]=array(
    'category'=>gT('Timer'),
    'sortorder'=>114,
    "inputtype"=>"textarea",
    "help"=>gT("CSS style used when the 'time limit warning' message is displayed"),
    "caption"=>gT("1st time limit warning CSS style"));

    $qattributes["time_limit_warning_2"]=array(
    'category'=>gT('Timer'),
    'sortorder'=>116,
    "inputtype"=>"integer",
    "help"=>gT("Display the 2nd 'time limit warning' when there are this many seconds remaining in the countdown (warning will not display if left blank)"),
    "caption"=>gT("2nd time limit warning message timer"));

    $qattributes["time_limit_warning_2_display_time"]=array(
    'category'=>gT('Timer'),
    'sortorder'=>118,
    "inputtype"=>"integer",
    "help"=>gT("The 2nd 'time limit warning' will stay visible for this many seconds (will not turn off if this setting is left blank)"),
    "caption"=>gT("2nd time limit warning message display time"));

    $qattributes["time_limit_warning_2_message"]=array(
    'category'=>gT('Timer'),
    'sortorder'=>120,
    "inputtype"=>"textarea",
    'i18n'=>true,
    "help"=>gT("The 2nd message to display as a 'time limit warning' (a default warning will display if this is left blank)"),
    "caption"=>gT("2nd time limit warning message"));

    $qattributes["time_limit_warning_2_style"]=array(
    'category'=>gT('Timer'),
    'sortorder'=>122,
    "inputtype"=>"textarea",
    "help"=>gT("CSS style used when the 2nd 'time limit warning' message is displayed"),
    "caption"=>gT("2nd time limit warning CSS style"));

    $qattributes["date_format"]=array(
    'category'=>gT('Input'),
    'sortorder'=>100,
    "inputtype"=>"text",
    "help"=>gT("Specify a custom date/time format (the <i>d/dd m/mm yy/yyyy H/HH M/MM</i> formats and \"-./: \" characters are allowed for day/month/year/hour/minutes without or with leading zero respectively. Defaults to survey's date format"),
    "caption"=>gT("Date/Time format"));

    $qattributes["dropdown_dates_minute_step"]=array(
    'category'=>gT('Input'),
    'sortorder'=>100,
    "inputtype"=>"integer",
    'default'=>1,
    "help"=>gT("Minute step interval when using select boxes"),
    "caption"=>gT("Minute step interval"));

    $qattributes["dropdown_dates_month_style"]=array(
    'category'=>gT('Display'),
    'sortorder'=>100,
    "inputtype"=>"singleselect",
    'options'=>array(0=>gT('Short names'),
    1=>gT('Full names'),
    2=>gT('Numbers')),
    'default'=>0,
    "help"=>gT("Change the display style of the month when using select boxes"),
    "caption"=>gT("Month display style"));

    $qattributes["show_title"]=array(
    'category'=>gT('File metadata'),
    'sortorder'=>124,
    "inputtype"=>"singleselect",
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>1,
    "help"=>gT("Is the participant required to give a title to the uploaded file?"),
    "caption"=>gT("Show title"));

    $qattributes["show_comment"]=array(
    'category'=>gT('File metadata'),
    'sortorder'=>126,
    "inputtype"=>"singleselect",
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>1,
    "help"=>gT("Is the participant required to give a comment to the uploaded file?"),
    "caption"=>gT("Show comment"));


    $qattributes["max_filesize"]=array(
    'category'=>gT('Other'),
    'sortorder'=>128,
    "inputtype"=>"integer",
    'default'=>10240,
    "help"=>gT("The participant cannot upload a single file larger than this size"),
    "caption"=>gT("Maximum file size allowed (in KB)"));

    $qattributes["max_num_of_files"]=array(
    'category'=>gT('Other'),
    'sortorder'=>130,
    "inputtype"=>"text",
    'default'=>'1',
    "help"=>gT("Maximum number of files that the participant can upload for this question"),
    "caption"=>gT("Max number of files"));

    $qattributes["min_num_of_files"]=array(
    'category'=>gT('Other'),
    'sortorder'=>132,
    "inputtype"=>"text",
    'default'=>'0',
    "help"=>gT("Minimum number of files that the participant must upload for this question"),
    "caption"=>gT("Min number of files"));

    $qattributes["allowed_filetypes"]=array(
    'category'=>gT('Other'),
    'sortorder'=>134,
    "inputtype"=>"text",
    'default'=>"png, gif, doc, odt",
    "help"=>gT("Allowed file types in comma separated format. e.g. pdf,doc,odt"),
    "caption"=>gT("Allowed file types"));

    $qattributes["random_group"]=array(
    'category'=>gT('Logic'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>gT("Place questions into a specified randomization group, all questions included in the specified group will appear in a random order"),
    "caption"=>gT("Randomization group name"));

    // This is added to support historical behavior.  Early versions of 1.92 used a value of "No", so if there was a min_sum_value or equals_sum_value, the question was not valid
    // unless those criteria were met.  In later releases of 1.92, the default was changed so that missing values were allowed even if those attributes were set
    // This attribute lets authors control whether missing values should be allowed in those cases without needing to set min_answers
    // Existing surveys will use the old behavior, but if the author edits the question, the default will be the new behavior.
    $qattributes["value_range_allows_missing"]=array(
    'category'=>gT('Input'),
    'sortorder'=>100,
    "inputtype"=>"singleselect",
    'options'=>array(0=>gT('No'),
    1=>gT('Yes')),
    'default'=>1,
    "help"=>gT("Is no answer (missing) allowed when either 'Equals sum value' or 'Minimum sum value' are set?"),
    "caption"=>gT("Value range allows missing"));
    return $qattributes;
}

function linkedAttributes($q)
{
    $available = $q->availableAttributes();
    $attributes = questionAttributes();
    foreach($available as $qname)
    {
        $result[$qname]=array("name"=>$qname,
        "inputtype"=>$attributes[$qname]['inputtype'],
        "category"=>$attributes[$qname]['category'],
        "sortorder"=>$attributes[$qname]['sortorder'],
        "i18n"=>isset($attributes[$qname]['i18n'])?$attributes[$qname]['i18n']:false,
        "readonly"=>isset($attributes[$qname]['readonly_when_active'])?$attributes[$qname]['readonly_when_active']:false,
        "options"=>isset($attributes[$qname]['options'])?$attributes[$qname]['options']:'',
        "default"=>isset($attributes[$qname]['default'])?$attributes[$qname]['default']:'',
        "help"=>$attributes[$qname]['help'],
        "caption"=>$attributes[$qname]['caption']);
    }
    return $result;
}

function categorySort($a, $b)
{
    $result=strnatcasecmp($a['category'], $b['category']);
    if ($result==0)
    {
        $result=$a['sortorder']-$b['sortorder'];
    }
    return $result;
}

// make sure the given string (which comes from a POST or GET variable)
// is safe to use in MySQL.  This does nothing if gpc_magic_quotes is on.
function autoEscape($str) {
    if (!get_magic_quotes_gpc()) {
        return addslashes ($str);
    }
    return $str;
}

// the opposite of the above: takes a POST or GET variable which may or
// may not have been 'auto-quoted', and return the *unquoted* version.
// this is useful when the value is destined for a web page (eg) not
// a SQL query.
function autoUnescape($str) {
    if (!isset($str)) {return null;};
    if (!get_magic_quotes_gpc())
        return $str;
    return stripslashes($str);
}

// make a string safe to include in an HTML 'value' attribute.
function HTMLEscape($str) {
    // escape newline characters, too, in case we put a value from
    // a TEXTAREA  into an <input type="hidden"> value attribute.
    return str_replace(array("\x0A","\x0D"),array("&#10;","&#13;"),
    htmlspecialchars( $str, ENT_QUOTES ));
}

/**
* Escapes a text value for db
*
* @param string $value
* @return string
*/
function dbQuoteAll($value)
{
    return Yii::app()->db->quoteValue($value);
}

// make a string safe to include in a JavaScript String parameter.
function javascriptEscape($str, $strip_tags=false, $htmldecode=false) {
    $new_str ='';

    if ($htmldecode==true) {
        $str=html_entity_decode($str,ENT_QUOTES,'UTF-8');
    }
    if ($strip_tags==true)
    {
        $str=strip_tags($str);
    }
    return str_replace(array('\'','"', "\n", "\r"),
    array("\\'",'\u0022', "\\n",'\r'),
    $str);
}

/**
* This function mails a text $body to the recipient $to.
* You can use more than one recipient when using a semikolon separated string with recipients.
*
* @param string $body Body text of the email in plain text or HTML
* @param mixed $subject Email subject
* @param mixed $to Array with several email addresses or single string with one email address
* @param mixed $from
* @param mixed $sitename
* @param mixed $ishtml
* @param mixed $bouncemail
* @param array $attachments
* @return bool If successful returns true
*/
function SendEmailMessage($body, $subject, $to, $from, $sitename, $ishtml=false, $bouncemail=null, $attachments=null, $customheaders="")
{

    global $maildebug, $maildebugbody;

    
    $emailmethod = Yii::app()->getConfig('emailmethod');
    $emailsmtphost = Yii::app()->getConfig("emailsmtphost");
    $emailsmtpuser = Yii::app()->getConfig("emailsmtpuser");
    $emailsmtppassword = Yii::app()->getConfig("emailsmtppassword");
    $emailsmtpdebug = Yii::app()->getConfig("emailsmtpdebug");
    $emailsmtpssl = Yii::app()->getConfig("emailsmtpssl");
    $defaultlang = Yii::app()->getConfig("defaultlang");
    $emailcharset = Yii::app()->getConfig("emailcharset");

    if ($emailcharset!='utf-8')
    {
        $body=mb_convert_encoding($body,$emailcharset,'utf-8');
        $subject=mb_convert_encoding($subject,$emailcharset,'utf-8');
        $sitename=mb_convert_encoding($sitename,$emailcharset,'utf-8');
    }

    if (!is_array($to)){
        $to=array($to);
    }



    if (!is_array($customheaders) && $customheaders == '')
    {
        $customheaders=array();
    }
    if (Yii::app()->getConfig('demo_mode'))
    {
        $maildebug=gT('Email was not sent because demo-mode is activated.');
        $maildebugbody='';
        return false;
    }

    if (is_null($bouncemail) )
    {
        $sender=$from;
    }
    else
    {
        $sender=$bouncemail;
    }


    require_once(Yii::app()->basePath.'/third_party/phpmailer/class.phpmailer.php');
    $mail = new PHPMailer;
    if (!$mail->SetLanguage($defaultlang,Yii::app()->basePath.'/third_party/phpmailer/language/'))
    {
        $mail->SetLanguage('en',Yii::app()->basePath.'/third_party/phpmailer/language/');
    }
    $mail->CharSet = $emailcharset;
    if (isset($emailsmtpssl) && trim($emailsmtpssl)!=='' && $emailsmtpssl!==0) {
        if ($emailsmtpssl===1) {$mail->SMTPSecure = "ssl";}
        else {$mail->SMTPSecure = $emailsmtpssl;}
    }

    $fromname='';
    $fromemail=$from;
    if (strpos($from,'<'))
    {
        $fromemail=substr($from,strpos($from,'<')+1,strpos($from,'>')-1-strpos($from,'<'));
        $fromname=trim(substr($from,0, strpos($from,'<')-1));
    }

    $sendername='';
    $senderemail=$sender;
    if (strpos($sender,'<'))
    {
        $senderemail=substr($sender,strpos($sender,'<')+1,strpos($sender,'>')-1-strpos($sender,'<'));
        $sendername=trim(substr($sender,0, strpos($sender,'<')-1));
    }

    switch ($emailmethod) {
        case "qmail":
            $mail->IsQmail();
            break;
        case "smtp":
            $mail->IsSMTP();
            if ($emailsmtpdebug>0)
            {
                $mail->SMTPDebug = $emailsmtpdebug;
            }
            if (strpos($emailsmtphost,':')>0)
            {
                $mail->Host = substr($emailsmtphost,0,strpos($emailsmtphost,':'));
                $mail->Port = substr($emailsmtphost,strpos($emailsmtphost,':')+1);
            }
            else {
                $mail->Host = $emailsmtphost;
            }
            $mail->Username =$emailsmtpuser;
            $mail->Password =$emailsmtppassword;
            if (trim($emailsmtpuser)!="")
            {
                $mail->SMTPAuth = true;
            }
            break;
        case "sendmail":
            $mail->IsSendmail();
            break;
        default:
            //Set to the default value to rule out incorrect settings.
            $emailmethod="mail";
            $mail->IsMail();
    }

    $mail->SetFrom($fromemail, $fromname);
    $mail->Sender = $senderemail; // Sets Return-Path for error notifications
    foreach ($to as $singletoemail)
    {
        if (strpos($singletoemail, '<') )
        {
            $toemail=substr($singletoemail,strpos($singletoemail,'<')+1,strpos($singletoemail,'>')-1-strpos($singletoemail,'<'));
            $toname=trim(substr($singletoemail,0, strpos($singletoemail,'<')-1));
            $mail->AddAddress($toemail,$toname);
        }
        else
        {
            $mail->AddAddress($singletoemail);
        }
    }
    if (is_array($customheaders))
    {
        foreach ($customheaders as $key=>$val) {
            $mail->AddCustomHeader($val);
        }
    }
    $mail->AddCustomHeader("X-Surveymailer: $sitename Emailer (LimeSurvey.sourceforge.net)");
    if (get_magic_quotes_gpc() != "0") {$body = stripcslashes($body);}
    if ($ishtml) {
        $mail->IsHTML(true);
        $mail->Body = $body;
        $mail->AltBody = strip_tags(breakToNewline(html_entity_decode($body,ENT_QUOTES,$emailcharset)));
    } else
    {
        $mail->IsHTML(false);
        $mail->Body = $body;
    }

    // Add attachments if they are there.
    if (is_array($attachments))
    {
        foreach ($attachments as $attachment)
        {
            // Attachment is either an array with filename and attachment name.
            if (is_array($attachment))
            {
                $mail->AddAttachment($attachment[0], $attachment[1]);
            }
            else 
            { // Or a string with the filename.
                $mail->AddAttachment($attachment);
            }
        }
    }

    if (trim($subject)!='') {$mail->Subject = "=?$emailcharset?B?" . base64_encode($subject) . "?=";}
    if ($emailsmtpdebug>0) {
        ob_start();
    }
    $sent=$mail->Send();
    $maildebug=$mail->ErrorInfo;
    if ($emailsmtpdebug>0) {
        $maildebug .= '<li>'.gT('SMTP debug output:').'</li><pre>'.strip_tags(ob_get_contents()).'</pre>';
        ob_end_clean();
    }
    $maildebugbody=$mail->Body;
    return $sent;
}


/**
*  This functions removes all HTML tags, Javascript, CRs, linefeeds and other strange chars from a given text
*
* @param string $sTextToFlatten  Text you want to clean
* @param boolan $keepSpan set to true for keep span, used for expression manager. Default: false
* @param boolan $bDecodeHTMLEntities If set to true then all HTML entities will be decoded to the specified charset. Default: false
* @param string $sCharset Charset to decode to if $decodeHTMLEntities is set to true. Default: UTF-8
* @param string $bStripNewLines strip new lines if true, if false replace all new line by \r\n. Default: true
*
* @return string  Cleaned text
*/
function flattenText($sTextToFlatten, $keepSpan=false, $bDecodeHTMLEntities=false, $sCharset='UTF-8', $bStripNewLines=true)
{
    $sNicetext = stripJavaScript($sTextToFlatten);
    // When stripping tags, add a space before closing tags so that strings with embedded HTML tables don't get concatenated
    $sNicetext = str_replace(array('</td','</th'),array(' </td',' </th'), $sNicetext);
    if ($keepSpan) {
        // Keep <span> so can show EM syntax-highlighting; add space before tags so that word-wrapping not destroyed when remove tags.
        $sNicetext = strip_tags($sNicetext,'<span><table><tr><td><th>');
    }
    else {
        $sNicetext = strip_tags($sNicetext);
    }
    // ~\R~u : see "What \R matches" and "Newline sequences" in http://www.pcre.org/pcre.txt
    if ($bStripNewLines ){  // strip new lines
        $sNicetext = preg_replace(array('~\R~u'),array(' '), $sNicetext);
    }
    else // unify newlines to \r\n
    {
        $sNicetext = preg_replace(array('~\R~u'), array("\r\n"), $sNicetext);
    }
    if ($bDecodeHTMLEntities==true)
    {
        $sNicetext = str_replace('&nbsp;',' ', $sNicetext); // html_entity_decode does not convert &nbsp; to spaces
        $sNicetext = html_entity_decode($sNicetext, ENT_QUOTES, $sCharset);
    }
    $sNicetext = trim($sNicetext);
    return  $sNicetext;
}


/**
* getArrayFilterExcludesCascadesForGroup() queries the database and produces a list of array_filter_exclude questions and targets with in the same group
* @return returns a keyed nested array, keyed by the qid of the question, containing cascade information
*/
function getArrayFilterExcludesCascadesForGroup($surveyid, $gid="", $output="qid")
{
    $surveyid=sanitize_int($surveyid);
    $gid=sanitize_int($gid);

    $cascaded=array();
    $sources=array();
    $qidtotitle=array();
    $fieldmap = createFieldMap($surveyid,false,false,getBaseLanguageFromSurveyID($surveyid));

    $attrmach = array(); // Stores Matches of filters that have their values as questions within current group
    foreach ($fieldmap as $q) // Cycle through questions to see if any have list_filter attributes
    {
        if (isset($q->gid) && !empty($q->gid) && (!$gid || $q->gid == $gid))
        {
            $qidtotitle[$qrow->id]=$qrow->title;
            $qresult = $q->getAttributeValues();
            if (isset($qresult['array_filter_exclude'])) // We Found a array_filter attribute
            {
                $val = $qresult['array_filter_exclude']; // Get the Value of the Attribute ( should be a previous question's title in same group )
                foreach ($fieldmap as $qq) // Cycle through all the other questions in this group until we find the source question for this array_filter
                {
                    if (isset($qq->gid) && !empty($qq->gid) && (!$gid || $qq->gid == $gid) && $qq->title == $val)
                    {
                        /* This question ($avalue) is the question that provides the source information we use
                        * to determine which answers show up in the question we're looking at, which is $qrow['qid']
                        * So, in other words, we're currently working on question $qrow['qid'], trying to find out more
                        * information about question $avalue['qid'], because that's the source */
                        $sources[$q->id]=$qq->id; /* This question ($qrow['qid']) relies on answers in $avalue['qid'] */
                        if(isset($cascades)) {unset($cascades);}
                        $cascades=array();                     /* Create an empty array */

                        /* At this stage, we know for sure that this question relies on one other question for the filter */
                        /* But this function wants to send back information about questions that rely on multiple other questions for the filter */
                        /* So we don't want to do anything yet */

                        /* What we need to do now, is check whether the question this one relies on, also relies on another */

                        /* The question we are now checking is $avalue['qid'] */
                        $keepgoing=1;
                        $questiontocheck=$qq->id;
                        /* If there is a key in the $sources array that is equal to $avalue['qid'] then we want to add that
                        * to the $cascades array */
                        while($keepgoing > 0)
                        {
                            if(!empty($sources[$questiontocheck]))
                            {
                                $cascades[] = $sources[$questiontocheck];
                                /* Now we need to move down the chain */
                                /* We want to check the $sources[$questiontocheck] question */
                                $questiontocheck=$sources[$questiontocheck];
                            } else {
                                /* Since it was empty, there must not be any more questions down the cascade */
                                $keepgoing=0;
                            }
                        }
                        /* Now add all that info */
                        if(count($cascades) > 0) {
                            $cascaded[$q->id]=$cascades;
                        }
                    }
                }
            }
        }
    }
    $cascade2=array();
    if($output == "title")
    {
        foreach($cascaded as $key=>$cascade) {
            foreach($cascade as $item)
            {
                $cascade2[$key][]=$qidtotitle[$item];
            }
        }
        $cascaded=$cascade2;
    }
    return $cascaded;
}



/**
* getArrayFiltersForQuestion($q) finds out if a question has an array_filter attribute and what codes where selected on target question
* @return returns an array of codes that were selected else returns false
*/
function getArrayFiltersForQuestion($q)
{
    static $cache = array();

    // TODO: Check list_filter values to make sure questions are previous?
    $qid=sanitize_int($qid->id);
    if (isset($cache[$qid])) return $cache[$qid];

    $attributes = $q->getAttributeValues();
    if (isset($attributes['array_filter']) && Yii::app()->session['questions']) {
        $val = $attributes['array_filter']; // Get the Value of the Attribute ( should be a previous question's title in same group )
        foreach (Yii::app()->session['questions'] as $q)
        {
            if ($q->title == $val)
            {
                // we found the target question, now we need to know what the answers where, we know its a multi!
                $q->id=sanitize_int($q->id);
                //$query = "SELECT title FROM ".db_table_name('questions')." where parent_qid='{$fields[0]}' AND language='".Yii::app()->session[$surveyid]['s_lang']."' order by question_order";
                $qresult=Questions::model()->findAllByAttributes(array("parent_qid"=> $q->id, "language"=> Yii::app()->session[$surveyid]['s_lang']), array('order' => "question_order"));
                $selected = array();
                //while ($code = $qresult->fetchRow())
                foreach ($qresult->readAll() as $code)
                {
                    if (Yii::app()->session[$id->fieldname.$code['title']] == "Y"
                    || Yii::app()->session[$id->fieldname] == $code['title']) array_push($selected,$code['title']);
                }

                //Now we also need to find out if (a) the question had "other" enabled, and (b) if that was selected
                //$query = "SELECT other FROM ".db_table_name('questions')." where qid='{$fields[0]}'";
                $qresult=Questions::model()->findAllByAttributes(array("qid"=>$q->id));
                foreach ($qresult->readAll() as $row) {$other=$row['other'];}
                if($other == "Y")
                {
                    if(Yii::app()->session[$id->fieldname.'other'] && Yii::app()->session[$id->fieldname.'other'] !="") {array_push($selected, "other");}
                }
                $cache[$qid] = $selected;
                return $cache[$qid];
            }
        }
        $cache[$qid] = false;
        return $cache[$qid];
    }
    $cache[$qid] = false;
    return $cache[$qid];
}

/**
* getGroupsByQuestion($surveyid)
* @return returns a keyed array of groups to questions ie: array([1]=>[2]) question qid 1, is in group gid 2.
*/
function getGroupsByQuestion($surveyid) {
    $output=array();

    $surveyid=sanitize_int($surveyid);
    $result=Questions::model()->findAllByAttributes(array("sid"=>$surveyid));

    foreach ($qresult->readAll() as $val)
    {
        $output[$val['qid']]=$val['gid'];
    }
    return $output;
}


/**
* getArrayFilterExcludesForQuestion($q) finds out if a question has an array_filter_exclude attribute and what codes where selected on target question
* @return returns an array of codes that were selected else returns false
*/
function getArrayFilterExcludesForQuestion($qid)
{
    static $cascadesCache = array();
    static $cache = array();

    // TODO: Check list_filter values to make sure questions are previous?
    // $surveyid = Yii::app()->getConfig('sid');
    $surveyid=returnGlobal('sid');
    $qid=sanitize_int($q->id);

    if (isset($cache[$qid])) return $cache[$qid];

    $attributes = $q->getAttributeValues();
    $excludevals=array();
    if (isset($attributes['array_filter_exclude'])) // We Found a array_filter_exclude attribute
    {
        $selected=array();
        $excludevals[] = $attributes['array_filter_exclude']; // Get the Value of the Attribute ( should be a previous question's title in same group )
        /* Find any cascades and place them in the $excludevals array*/
        if (!isset($cascadesCache[$surveyid])) {
            $cascadesCache[$surveyid] = getArrayFilterExcludesCascadesForGroup($surveyid, "", "title");
        }
        $array_filterXqs_cascades = $cascadesCache[$surveyid];

        if(isset($array_filterXqs_cascades[$qid]))
        {
            foreach($array_filterXqs_cascades[$qid] as $afc)
            {
                $excludevals[]=array("value"=>$afc);

            }
        }
        /* For each $val (question title) that applies to this, check what values exist and add them to the $selected array */
        foreach ($excludevals as $val)
        {
            foreach (Yii::app()->session['questions'] as $q) //iterate through every question in the survey
            {
                if ($this->title == $val)
                {
                    // we found the target question, now we need to know what the answers were!
                    $this->id=sanitize_int($this->id);
                    $query = "SELECT title FROM {{questions}} where parent_qid='{$this->id}' AND language='".Yii::app()->session[$surveyid]['s_lang']."' order by question_order";
                    $qresult = dbExecuteAssoc($query);  //Checked
                    foreach ($qresult->readAll() as $code)
                    {
                        if (isset(Yii::app()->session[$id->fieldname]))
                            if ((isset(Yii::app()->session[$id->fieldname.$code['title']]) && Yii::app()->session[$id->fieldname.$code['title']] == "Y")
                            || Yii::app()->session[$id->fieldname] == $code['title'])
                                array_push($selected,$code['title']);
                    }
                    //Now we also need to find out if (a) the question had "other" enabled, and (b) if that was selected
                    $query = "SELECT other FROM {{questions}} where qid='{$q->id}'";
                    $qresult = dbExecuteAssoc($query);
                    foreach ($qresult->readAll() as $row) {$other=$row['other'];}
                    if($other == "Y")
                    {
                        if(Yii::app()->session[$id->fieldname.'other'] != "") {array_push($selected, "other");}
                    }
                }
            }
        }
        if(count($selected) > 0)
        {
            $cache[$qid] = $selected;
            return $cache[$qid];
        } else {
            $cache[$qid] = false;
            return $cache[$qid];
        }
    }
    $cache[$qid] = false;
    return $cache[$qid];
}



function CSVEscape($str)
{
    $str= str_replace('\n','\%n',$str);
    return '"' . str_replace('"','""', $str) . '"';
}

function convertCSVRowToArray($string, $seperator, $quotechar)
{
    $fields=preg_split('/' . $seperator . '(?=([^"]*"[^"]*")*(?![^"]*"))/',trim($string));
    $fields=array_map('CSVUnquote',$fields);
    return $fields;
}

function createPassword()
{
    $pwchars = "abcdefhjmnpqrstuvwxyz23456789";
    $password_length = 12;
    $passwd = '';

    for ($i=0; $i<$password_length; $i++)
    {
        $passwd .= $pwchars[(int)floor(rand(0,strlen($pwchars)-1))];
    }
    return $passwd;
}

function languageDropdown($surveyid,$selected)
{

    $homeurl = Yii::app()->getConfig('homeurl');
    $slangs = Survey::model()->findByPk($surveyid)->additionalLanguages;
    $baselang = Survey::model()->findByPk($surveyid)->language;
    array_unshift($slangs,$baselang);
    $html = "<select class='listboxquestions' name='langselect' onchange=\"window.open(this.options[this.selectedIndex].value, '_top')\">\n";

    foreach ($slangs as $lang)
    {
        $link = Yii::app()->homeUrl.("/admin/dataentry/sa/view/surveyid/".$surveyid."/lang/".$lang);
        if ($lang == $selected) $html .= "\t<option value='{$link}' selected='selected'>".getLanguageNameFromCode($lang,false)."</option>\n";
        if ($lang != $selected) $html .= "\t<option value='{$link}'>".getLanguageNameFromCode($lang,false)."</option>\n";
    }
    $html .= "</select>";
    return $html;
}

function languageDropdownClean($surveyid,$selected)
{
    $slangs = Survey::model()->findByPk($surveyid)->additionalLanguages;
    $baselang = Survey::model()->findByPk($surveyid)->language;
    array_unshift($slangs,$baselang);
    $html = "<select class='listboxquestions' id='language' name='language'>\n";
    foreach ($slangs as $lang)
    {
        if ($lang == $selected) $html .= "\t<option value='$lang' selected='selected'>".getLanguageNameFromCode($lang,false)."</option>\n";
        if ($lang != $selected) $html .= "\t<option value='$lang'>".getLanguageNameFromCode($lang,false)."</option>\n";
    }
    $html .= "</select>";
    return $html;
}

/**
* This function removes a directory recursively
*
* @param mixed $dirname
* @return bool
*/
function rmdirr($dirname)
{
    // Sanity check
    if (!file_exists($dirname)) {
        return false;
    }

    // Simple delete for a file
    if (is_file($dirname) || is_link($dirname)) {
        return @unlink($dirname);
    }

    // Loop through the folder
    $dir = dir($dirname);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Recurse
        rmdirr($dirname . DIRECTORY_SEPARATOR . $entry);
    }

    // Clean up
    $dir->close();
    return @rmdir($dirname);
}

/**
* This function removes surrounding and masking quotes from the CSV field
*
* @param mixed $field
* @return mixed
*/
function CSVUnquote($field)
{
    //print $field.":";
    $field = preg_replace ("/^\040*\"/", "", $field);
    $field = preg_replace ("/\"\040*$/", "", $field);
    $field= str_replace('""','"',$field);
    //print $field."\n";
    return $field;
}

/**
* This function return actual completion state
*
* @return string (complete|incomplete|all) or false
*/
function incompleteAnsFilterState()
{
    $letsfilter='';
    $letsfilter = returnGlobal('completionstate'); //read get/post completionstate

    // first let's initialize the incompleteanswers session variable
    if ($letsfilter != '')
    { // use the read value if not empty
        Yii::app()->session['incompleteanswers'] = $letsfilter;
    }
    elseif (empty(Yii::app()->session['incompleteanswers']))
    { // sets default variable value from config file
        Yii::app()->session['incompleteanswers'] = Yii::app()->getConfig('filterout_incomplete_answers');
    }

    if  (Yii::app()->session['incompleteanswers']=='complete' || Yii::app()->session['incompleteanswers']=='all' || Yii::app()->session['incompleteanswers']=='incomplete') {
        return Yii::app()->session['incompleteanswers'];
    }
    else
    { // last resort is to prevent filtering
        return false;
    }
}


/**
* isCaptchaEnabled($screen, $usecaptchamode)
* @param string $screen - the screen name for which to test captcha activation
*
* @return boolean - returns true if captcha must be enabled
**/
function isCaptchaEnabled($screen, $captchamode='')
{
    switch($screen)
    {
        case 'registrationscreen':
            if ($captchamode == 'A' ||
            $captchamode == 'B' ||
            $captchamode == 'D' ||
            $captchamode == 'R')
            {
                return true;
            }
            else
            {
                return false;
            }
            break;
        case 'surveyaccessscreen':
            if ($captchamode == 'A' ||
            $captchamode == 'B' ||
            $captchamode == 'C' ||
            $captchamode == 'X')
            {
                return true;
            }
            else
            {
                return false;
            }
            break;
        case 'saveandloadscreen':
            if ($captchamode == 'A' ||
            $captchamode == 'C' ||
            $captchamode == 'D' ||
            $captchamode == 'S')
            {
                return true;
            }
            else
            {
                return false;
            }
            return true;
            break;
        default:
            return true;
            break;
    }
}

/**
* used for import[survey|questions|groups]
*
* @param mixed $string
* @return mixed
*/
function convertCSVReturnToReturn($string)
{
    $string= str_replace('\n', "\n", $string);
    return str_replace('\%n', '\n', $string);
}

/**
* Check if a table does exist in the database
*
* @param string $sTableName Table name to check for (without dbprefix!))
* @return boolean True or false if table exists or not
*/
function tableExists($sTableName)
{
    $sTableName=Yii::app()->db->tablePrefix.str_replace(array('{','}'),array('',''),$sTableName);
    return in_array($sTableName,Yii::app()->db->schema->getTableNames());
}

// Returns false if the survey is anonymous,
// and a token table exists: in this case the completed field of a token
// will contain 'Y' instead of the submitted date to ensure privacy
// Returns true otherwise
function isTokenCompletedDatestamped($thesurvey)
{
    if ($thesurvey['anonymized'] == 'Y' &&  tableExists('tokens_'.$thesurvey['sid']))
    {
        return false;
    }
    else
    {
        return true;
    }
}

/**
* example usage
* $date = "2006-12-31 21:00";
* $shift "+6 hours"; // could be days, weeks... see function strtotime() for usage
*
* echo sql_date_shift($date, "Y-m-d H:i:s", $shift);
*
* will output: 2007-01-01 03:00:00
*
* @param mixed $date
* @param mixed $dformat
* @param mixed $shift
* @return string
*/
function dateShift($date, $dformat, $shift)
{
    return date($dformat, strtotime($shift, strtotime($date)));
}


// getBounceEmail: returns email used to receive error notifications
function getBounceEmail($surveyid)
{
    $surveyInfo=getSurveyInfo($surveyid);

    if ($surveyInfo['bounce_email'] == '')
    {
        return null; // will be converted to from in MailText
    }
    else
    {
        return $surveyInfo['bounce_email'];
    }
}

// getEmailFormat: returns email format for the survey
// returns 'text' or 'html'
function getEmailFormat($surveyid)
{
    $surveyInfo=getSurveyInfo($surveyid);
    if ($surveyInfo['htmlemail'] == 'Y')
    {
        return 'html';
    }
    else
    {
        return 'text';
    }

}

// Check if user has manage rights for a template
function hasTemplateManageRights($userid, $templatefolder) {
    $userid=sanitize_int($userid);
    $templatefolder=sanitize_paranoid_string($templatefolder);
    $criteria = new CDbCriteria;
    $criteria->addColumnCondition(array('uid' => $userid));
    $criteria->addSearchCondition('folder', $templatefolder);
    $query=Templates_rights::model()->find($criteria);
    //if ($result->RecordCount() == 0)  return false;
    if (is_null($query))  return false;

    $row = $query;
    //$row = $result->FetchRow();

    return $row["use"];
}

/**
* This function creates an incrementing answer code based on the previous source-code
*
* @param mixed $sourcecode The previous answer code
*/
function getNextCode($sourcecode)
{
    $i=1;
    $found=true;
    $foundnumber=-1;
    while ($i<=strlen($sourcecode) && $found)
    {
        $found=is_numeric(substr($sourcecode,-$i));
        if ($found)
        {
            $foundnumber=substr($sourcecode,-$i);
            $i++;
        }
    }
    if ($foundnumber==-1)
    {
        return($sourcecode);
    }
    else
    {
        $foundnumber++;
        $result=substr($sourcecode,0,strlen($sourcecode)-strlen($foundnumber)).$foundnumber;
        return($result);
    }

}

/**
* Translate links which are in any answer/question/survey/email template/label set to their new counterpart
*
* @param mixed $sType 'survey' or 'label'
* @param mixed $iOldSurveyID
* @param mixed $iNewSurveyID
* @param mixed $sString
* @return string
*/
function translateLinks($sType, $iOldSurveyID, $iNewSurveyID, $sString)
{
    if ($sType == 'survey')
    {
        $sPattern = "([^'\"]*)/upload/surveys/{$iOldSurveyID}/";
        $sReplace = Yii::app()->getConfig("publicurl")."upload/surveys/{$iNewSurveyID}/";
        return preg_replace('#'.$sPattern.'#', $sReplace, $sString);
    }
    elseif ($sType == 'label')
    {
        $pattern = "([^'\"]*)/upload/labels/{$iOldSurveyID}/";
        $replace = Yii::app()->getConfig("publicurl")."upload/labels/{$iNewSurveyID}/";
        return preg_replace('#'.$pattern.'#', $replace, $sString);
    }
    else // unkown type
    {
        return $sString;
    }
}

/**
* This function creates the old fieldnames for survey import
*
* @param mixed $iOldSID  The old survey id
* @param mixed $iNewSID  The new survey id
* @param array $aGIDReplacements An array with group ids (oldgid=>newgid)
* @param array $aQIDReplacements An array with question ids (oldqid=>newqid)
*/
function reverseTranslateFieldNames($iOldSID,$iNewSID,$aGIDReplacements,$aQIDReplacements)
{
    $aGIDReplacements=array_flip($aGIDReplacements);
    $aQIDReplacements=array_flip($aQIDReplacements);
    if ($iOldSID==$iNewSID) {
        $forceRefresh=true; // otherwise grabs the cached copy and throws undefined index exceptions
    }
    else {
        $forceRefresh=false;
    }
    $aFieldMap = createFieldMap($iNewSID,$forceRefresh,false,getBaseLanguageFromSurveyID($iNewSID));

    $aFieldMappings=array();
    foreach ($aFieldMap as $q)
    {
        if ($q->id!=null)
        {
            $aFieldMappings[$q->fieldname]=$iOldSID.'X'.$aGIDReplacements[$q->gid].'X'.$aQIDReplacements[$q->id].$q->aid;
            if (isset($q->scale))
            {
                $aFieldMappings[$q->fieldname].= '#' . $q->scale;
            }
            // now also add a shortened field mapping which is needed for certain kind of condition mappings
            $aFieldMappings[$q->surveyid.'X'.$q->gid.'X'.$q->id]=$iOldSID.'X'.$aGIDReplacements[$q->gid].'X'.$aQIDReplacements[$q->id];
            // Shortened field mapping for timings table
            $aFieldMappings[$q->surveyid.'X'.$q->gid]=$iOldSID.'X'.$aGIDReplacements[$q->gid];
        }
    }
    return array_flip($aFieldMappings);
}

/**
* put your comment there...
*
* @param mixed $id
* @param mixed $type
*/
function hasResources($id,$type='survey')
{
    $dirname = Yii::app()->getConfig("uploaddir");

    if ($type == 'survey')
    {
        $dirname .= "/surveys/$id";
    }
    elseif ($type == 'label')
    {
        $dirname .= "/labels/$id";
    }
    else
    {
        return false;
    }

    if (is_dir($dirname) && $dh=opendir($dirname))
    {
        while(($entry = readdir($dh)) !== false)
        {
            if($entry !== '.' && $entry !== '..')
            {
                return true;
                break;
            }
        }
        closedir($dh);
    }
    else
    {
        return false;
    }

    return false;
}

/**
* Creates a random sequence of characters
*
* @param mixed $length Length of resulting string
* @param string $pattern To define which characters should be in the resulting string
*/
function randomChars($length,$pattern="23456789abcdefghijkmnpqrstuvwxyz")
{
    $patternlength = strlen($pattern)-1;
    for($i=0;$i<$length;$i++)
    {
        if(isset($key))
            $key .= $pattern{rand(0,$patternlength)};
        else
            $key = $pattern{rand(0,$patternlength)};
    }
    return $key;
}

/**
* used to translate simple text to html (replacing \n with <br />
*
* @param mixed $mytext
* @param mixed $ishtml
* @return mixed
*/
function conditionalNewlineToBreak($mytext,$ishtml,$encoded='')
{
    if ($ishtml === true)
    {
        // and thus \n has already been translated to &#10;
        if ($encoded == '')
        {
            $mytext=str_replace('&#10;', '<br />',$mytext);
        }
        return str_replace("\n", '<br />',$mytext);
    }
    else
    {
        return $mytext;
    }
}


function breakToNewline( $data ) {
    return preg_replace( '!<br.*>!iU', "\n", $data );
}

function safeDie($text)
{
    //Only allowed tag: <br />
    $textarray=explode('<br />',$text);
    $textarray=array_map('htmlspecialchars',$textarray);
    die(implode( '<br />',$textarray));
}

function fixCKeditorText($str)
{
    $str = str_replace('<br type="_moz" />','',$str);
    if ($str == "<br />" || $str == " " || $str == "&nbsp;")
    {
        $str = "";
    }
    if (preg_match("/^[\s]+$/",$str))
    {
        $str='';
    }
    if ($str == "\n")
    {
        $str = "";
    }
    if (trim($str) == "&nbsp;" || trim($str)=='')
    { // chrome adds a single &nbsp; element to empty fckeditor fields
        $str = "";
    }

    return $str;
}


/**
* This is a helper function for getAttributeFieldNames
*
* @param mixed $fieldname
*/
function filterForAttributes ($fieldname)
{
    if (strpos($fieldname,'attribute_')===false) return false; else return true;
}

/**
* Retrieves the attribute field names from the related token table
*
* @param mixed $iSurveyID  The survey ID
* @return array The fieldnames
*/
function GetAttributeFieldNames($iSurveyID)
{
    if (!tableExists("{{tokens_{$iSurveyID}}}") || !$table = Yii::app()->db->schema->getTable('{{tokens_'.$iSurveyID.'}}'))
        return Array();

    return array_filter(array_keys($table->columns), 'filterForAttributes');

}

/**
* Returns the full list of attribute token fields including the properties for each field
* Use this instead of plain Survey::model()->findByPk($iSurveyID)->tokenAttributes calls because Survey::model()->findByPk($iSurveyID)->tokenAttributes may contain old descriptions where the fields does not physically exist
* 
* @param integer $iSurveyID The Survey ID
*/
function GetParticipantAttributes($iSurveyID)
{
    if (!tableExists("{{tokens_{$iSurveyID}}}") || !$table = Yii::app()->db->schema->getTable('{{tokens_'.$iSurveyID.'}}'))
        return Array();
    $aFields= array_filter(array_keys($table->columns), 'filterForAttributes');
    $aTokenAttributes=Survey::model()->findByPk($iSurveyID)->tokenAttributes;
    if (count($aFields)==0) return  array();
    return array_intersect_key($aTokenAttributes,array_flip($aFields));
}





/**
* Retrieves the token field names usable for conditions from the related token table
*
* @param mixed $surveyid  The survey ID
* @return array The fieldnames
*/
function getTokenConditionsFieldNames($surveyid)
{
    $extra_attrs=getAttributeFieldNames($surveyid);
    $basic_attrs=Array('firstname','lastname','email','token','language','sent','remindersent','remindercount');
    return array_merge($basic_attrs,$extra_attrs);
}

/**
* Retrieves the attribute names from the related token table
*
* @param mixed $surveyid  The survey ID
* @param boolean $bOnlyAttributes Set this to true if you only want the fieldnames of the additional attribue fields - defaults to false
* @return array The fieldnames as key and names as value in an Array
*/
function getTokenFieldsAndNames($surveyid, $bOnlyAttributes = false)
{
    

    $aBasicTokenFields=array('firstname'=>array(
        'description'=>gT('First name'),
        'mandatory'=>'N',
        'showregister'=>'Y'
        ),
        'lastname'=>array(
            'description'=>gT('Last name'),
            'mandatory'=>'N',
            'showregister'=>'Y'
        ),                                                
        'email'=>array(
            'description'=>gT('Email address'),
            'mandatory'=>'N',
            'showregister'=>'Y'
        ),                                                
        'token'=>array(
            'description'=>gT('Token'),
            'mandatory'=>'N',
            'showregister'=>'Y'
        ),                                                
        'language'=>array(
            'description'=>gT('Language code'),
            'mandatory'=>'N',
            'showregister'=>'Y'
        ),                                                
        'sent'=>array(
            'description'=>gT('Invitation sent date'),
            'mandatory'=>'N',
            'showregister'=>'Y'
        ),                                                
        'remindersent'=>array(
            'description'=>gT('Last reminder sent date'),
            'mandatory'=>'N',
            'showregister'=>'Y'
        ),                                                
        'remindercount'=>array(
            'description'=>gT('Total numbers of sent reminders'),
            'mandatory'=>'N',
            'showregister'=>'Y'
        ),                                                
        'usesleft'=>array(
            'description'=>gT('Uses left'),
            'mandatory'=>'N',
            'showregister'=>'Y'
        ),                                                
    );

    $aExtraTokenFields=getAttributeFieldNames($surveyid);  
    $aSavedExtraTokenFields = Survey::model()->findByPk($surveyid)->tokenAttributes;

    // Drop all fields that are in the saved field description but not in the table definition
    $aSavedExtraTokenFields=array_intersect_key($aSavedExtraTokenFields,array_flip($aExtraTokenFields));
    
    // Now add all fields that are in the table but not in the field description
    foreach ($aExtraTokenFields as $sField)
    {
        if (!isset($aSavedExtraTokenFields[$sField]))
        {
            $aSavedExtraTokenFields[$sField]=array(
            'description'=>$sField,
            'mandatory'=>'N',
            'showregister'=>'N'
            );
        }
    }
    if ($bOnlyAttributes)
    {
        return $aSavedExtraTokenFields;
    }
    else
    {
        return array_merge($aBasicTokenFields,$aSavedExtraTokenFields);
    }
}

/**
* Retrieves the token attribute value from the related token table
*
* @param mixed $surveyid  The survey ID
* @param mixed $attrName  The token-attribute field name
* @param mixed $token  The token code
* @return string The token attribute value (or null on error)
*/
function getAttributeValue($surveyid,$attrName,$token)
{
    $attrName=strtolower($attrName);
    if (!tableExists('tokens_'.$surveyid) || !in_array($attrName,getTokenConditionsFieldNames($surveyid)))
    {
        return null;
    }
    $surveyid=sanitize_int($surveyid);

    Tokens_dynamic::sid($surveyid);
    $query=Tokens_dynamic::model()->find(array("token"=>$token));

    $count=$query->count(); // OK  - AR count
    if ($count != 1)
    {
        return null;
    }
    else
    {
        return $row->$attrName;//[0]
    }
}

/**
* This function strips any content between and including <javascript> tags
*
* @param string $sContent String to clean
* @return string  Cleaned string
*/
function stripJavaScript($sContent){
    $text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $sContent);
    return $text;
}

/**
* This function converts emebedded Javascript to Text
*
* @param string $sContent String to clean
* @return string  Cleaned string
*/
function showJavaScript($sContent){
    $text = preg_replace_callback ('@<script[^>]*?>.*?</script>@si',         create_function(
            // single quotes are essential here,
            // or alternative escape all $ as \$
            '$matches',
            'return htmlspecialchars($matches[0]);'
        ), $sContent);
    return $text;
}

/**
* This function cleans files from the temporary directory being older than 1 day
* @todo Make the days configurable
*/
function cleanTempDirectory()
{
    $dir =  Yii::app()->getConfig('tempdir').DIRECTORY_SEPARATOR;
    $dp = opendir($dir) or show_error('Could not open temporary directory');
    while ($file = readdir($dp)) {
        if (is_file($dir.$file) && (filemtime($dir.$file)) < (strtotime('-1 days')) && $file!='index.html' && $file!='.gitignore' && $file!='readme.txt') {
            @unlink($dir.$file);
        }
    }
    $dir=  Yii::app()->getConfig('tempdir').DIRECTORY_SEPARATOR.'upload'.DIRECTORY_SEPARATOR;
    $dp = opendir($dir) or die ('Could not open temporary upload directory');
    while ($file = readdir($dp)) {
        if (is_file($dir.$file) && (filemtime($dir.$file)) < (strtotime('-1 days')) && $file!='index.html' && $file!='.gitignore' && $file!='readme.txt') {
            @unlink($dir.$file);
        }
    }
    closedir($dp);
}

function useFirebug()
{
    if(FIREBUG == true)
    {
        return '<script type="text/javascript" src="http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js"></script>';
    };
};

/**
* This is a convenience function for the coversion of datetime values
*
* @param mixed $value
* @param mixed $fromdateformat
* @param mixed $todateformat
* @return string
*/
function convertDateTimeFormat($value, $fromdateformat, $todateformat)
{
    Yii::import('application.libraries.Date_Time_Converter', true);
    $date = new Date_Time_Converter($value, $fromdateformat);
    return $date->convert($todateformat);
}

/**
* This function removes the UTF-8 Byte Order Mark from a string
*
* @param string $str
* @return string
*/
function removeBOM($str=""){
    if(substr($str, 0,3) == pack("CCC",0xef,0xbb,0xbf)) {
        $str=substr($str, 3);
    }
    return $str;
}

/**
* This function requests the latest update information from the LimeSurvey.org website
*
* @returns array Contains update information or false if the request failed for some reason
*/
/**********************************************/
/* This function needs ported still.          */
/**********************************************/
function getUpdateInfo()
{
    Yii::import('application.libraries.admin.http.httpRequestIt');
    $http=new httpRequestIt;

    $http->timeout=0;
    $http->data_timeout=0;
    $http->user_agent="Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
    $http->GetRequestArguments("http://update.limesurvey.org?build=".Yii::app()->getConfig("buildnumber").'&id='.md5(getGlobalSetting('SessionName')),$arguments);

    $updateinfo=false;
    $error=$http->Open($arguments);
    $error=$http->SendRequest($arguments);

    $http->ReadReplyHeaders($headers);


    if($error=="") {
        $body=''; $full_body='';
        for(;;){
            $error = $http->ReadReplyBody($body,10000);
            if($error != "" || strlen($body)==0) break;
            $full_body .= $body;
        }
        $updateinfo=json_decode($full_body,true);
        if ($http->response_status!='200')
        {
            $updateinfo['errorcode']=$http->response_status;
            $updateinfo['errorhtml']=$full_body;
        }
    }
    else
    {
        $updateinfo['errorcode']=$error;
        $updateinfo['errorhtml']=$error;
    }
    unset( $http );
    return $updateinfo;
}

/**
* This function updates the actual global variables if an update is available after using getUpdateInfo
* @return Array with update or error information
*/
function updateCheck()
{
    $updateinfo=getUpdateInfo();
    if (isset($updateinfo['Targetversion']['build']) && (int)$updateinfo['Targetversion']['build']>(int)Yii::app()->getConfig('buildnumber') && trim(Yii::app()->getConfig('buildnumber'))!='')
    {
        setGlobalSetting('updateavailable',1);
        setGlobalSetting('updatebuild',$updateinfo['Targetversion']['build']);
        setGlobalSetting('updateversion',$updateinfo['Targetversion']['versionnumber']);
    }
    else
    {
        setGlobalSetting('updateavailable',0);
    }
    setGlobalSetting('updatelastcheck',date('Y-m-d H:i:s'));
    return $updateinfo;
}

/**
* Return the goodchars to be used when filtering input for numbers.
*
* @param $lang      string  language used, for localisation
* @param $integer   bool    use only integer
* @param $negative  bool    allow negative values
*/
function getNumericalFormat($lang = 'en', $integer = false, $negative = true) {
    $goodchars = "0123456789";
    if ($integer === false) $goodchars .= ".";    //Todo, add localisation
    if ($negative === true) $goodchars .= "-";    //Todo, check databases
    return $goodchars;
}


/**
* Return array with token attribute.
*
* @param $surveyid 	int	the surveyid
* @param $token	string	token code
*
* @return Array of token data
*/
function getTokenData($surveyid, $token) // TODO : move it to token model
{
    $thistoken = Tokens_dynamic::model($surveyid)->find('token = :token',array(':token' => $token));
    $thistokenarray=array(); // so has default value
    if($thistoken)
    {
        $thistokenarray =$thistoken->attributes;
    }// Did we fill with empty string if not exist ?

    return $thistokenarray;
}

/**
* This function returns the complete directory path to a given template name
*
* @param mixed $sTemplateName
*/
function getTemplatePath($sTemplateName = false)
{
    if (!$sTemplateName)
    {
        $sTemplateName=Yii::app()->getConfig('defaulttemplate'); // if $sTemplateName is NULL or false or ""
    }
    if (isStandardTemplate($sTemplateName))
    {
        return Yii::app()->getConfig("standardtemplaterootdir").DIRECTORY_SEPARATOR.$sTemplateName;
    }
    else
    {
        if (is_dir(Yii::app()->getConfig("usertemplaterootdir").DIRECTORY_SEPARATOR.$sTemplateName))
        {
            return Yii::app()->getConfig("usertemplaterootdir").DIRECTORY_SEPARATOR.$sTemplateName;
        }
        elseif (isStandardTemplate(Yii::app()->getConfig('defaulttemplate')))
        {
            return Yii::app()->getConfig("standardtemplaterootdir").DIRECTORY_SEPARATOR.$sTemplateName;
        }
        elseif (file_exists(Yii::app()->getConfig("usertemplaterootdir").DIRECTORY_SEPARATOR.Yii::app()->getConfig('defaulttemplate')))
        {
            return Yii::app()->getConfig("usertemplaterootdir").DIRECTORY_SEPARATOR.Yii::app()->getConfig('defaulttemplate');
        }
        else
        {
            return Yii::app()->getConfig("standardtemplaterootdir").DIRECTORY_SEPARATOR.'default';
        }
    }
}

/**
* This function returns the complete URL path to a given template name
*
* @param mixed $sTemplateName
*/
function getTemplateURL($sTemplateName)
{
    if (isStandardTemplate($sTemplateName))
    {
        return Yii::app()->getConfig("standardtemplaterooturl").'/'.$sTemplateName;
    }
    else
    {
        if (file_exists(Yii::app()->getConfig("usertemplaterootdir").'/'.$sTemplateName))
        {
            return Yii::app()->getConfig("usertemplaterooturl").'/'.$sTemplateName;
        }
        elseif (file_exists(Yii::app()->getConfig("usertemplaterootdir").'/'.Yii::app()->getConfig('defaulttemplate')))
        {
            return Yii::app()->getConfig("usertemplaterooturl").'/'.Yii::app()->getConfig('defaulttemplate');
        }
        elseif (file_exists(Yii::app()->getConfig("standardtemplaterootdir").'/'.Yii::app()->getConfig('defaulttemplate')))
        {
            return Yii::app()->getConfig("standardtemplaterooturl").'/'.Yii::app()->getConfig('defaulttemplate');
        }
        else
        {
            return Yii::app()->getConfig("standardtemplaterooturl").'/default';
        }
    }
}

/**
* Return an array of subquestions for a given sid/qid
*
* @param int $sid
* @param int $qid
* @param $sLanguage Language of the subquestion text
*/
function getSubQuestions($q) {

    static $subquestions;

    if (!isset($subquestions[$q->surveyid]))
    {
        $subquestions[$q->surveyid]=array();
    }
    if (!isset($subquestions[$q->surveyid][$q->language])) {

        $query = "SELECT sq.*, q.other FROM {{questions}} as sq, {{questions}} as q"
        ." WHERE sq.parent_qid=q.qid AND q.sid=".$q->surveyid
        ." AND sq.language='".$q->language. "' "
        ." AND q.language='".$q->language. "' "
        ." ORDER BY sq.parent_qid, q.question_order,sq.scale_id , sq.question_order";

        $query = Yii::app()->db->createCommand($query)->query();

        $resultset=array();
        //while ($row=$result->FetchRow())
        foreach ($query->readAll() as $row)
        {
            $resultset[$row['parent_qid']][] = $row;
        }
        $subquestions[$q->surveyid][$q->language] = $resultset;
    }
    if (isset($subquestions[$q->surveyid][$q->language][$q->id])) return $subquestions[$q->surveyid][$q->language][$q->id];
    return array();
}

/**
* Wrapper function to retrieve an xmlwriter object and do error handling if it is not compiled
* into PHP
*/
function getXMLWriter() {
    if (!extension_loaded('xmlwriter')) {
        safeDie('XMLWriter class not compiled into PHP, please contact your system administrator');
    } else {
        $xmlwriter = new XMLWriter();
    }
    return $xmlwriter;
}


/**
* Returns true when a token can not be used (either doesn't exist, has less then one usage left )
*
* @param mixed $tid Token
*/
function usedTokens($token, $surveyid)
{
    $utresult = true;
    $query=Tokens_dynamic::model($surveyid)->findAllByAttributes(array("token"=>$token));
    if (count($query) > 0) {
        $row = $query[0];
        if ($row->usesleft > 0) $utresult = false;
    }
    return $utresult;
}

/**
* SSLRedirect() generates a redirect URL for the appropriate SSL mode then applies it.
* (Was redirect() before CodeIgniter port.)
*
* @param $enforceSSLMode string 's' or '' (empty).
*/
function SSLRedirect($enforceSSLMode)
{
    $url = 'http'.$enforceSSLMode.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    if (!headers_sent())
    { // If headers not sent yet... then do php redirect
        //ob_clean();
        header('Location: '.$url);
        //ob_flush();
        exit;
    };
};

/**
* enforceSSLMode() $force_ssl is on or off, it checks if the current
* request is to HTTPS (or not). If $force_ssl is on, and the
* request is not to HTTPS, it redirects the request to the HTTPS
* version of the URL, if the request is to HTTPS, it rewrites all
* the URL variables so they also point to HTTPS.
*/
function enforceSSLMode()
{
    $bSSLActive = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off");
    if (Yii::app()->getConfig('ssl_emergency_override') !== true )
    {
        $force_ssl = strtolower(getGlobalSetting('force_ssl'));
    }
    else
    {
        $force_ssl = 'off';
    };
    if( $force_ssl == 'on' && !$bSSLActive )
    {
        SSLRedirect('s');
    }
    if( $force_ssl == 'off' && $bSSLActive)
    {
        SSLRedirect('');
    };
};

/**
* Returns the number of answers matching the quota
*
* @param int $iSurveyId - Survey identification number
* @param int $quotaid - quota id for which you want to compute the completed field
* @return mixed - Integer of matching entries in the result DB or 'N/A'
*/
function getQuotaCompletedCount($iSurveyId, $quotaid)
{
    $result = "N/A";
    $quota = getQuotaInformation($iSurveyId, Survey::model()->findByPk($iSurveyId)->language, $quotaid);

    if (Yii::app()->db->schema->getTable('{{survey_' . $iSurveyId . '}}') &&
    count($quota['members']) > 0)
    {
        // Keep a list of fields for easy reference
        $fields_list = array();

        // Construct an array of value for each $quota['members']['fieldnames']
        $fields_query = array();

        foreach ($quota['members'] as $fieldname => $member)
        {
            $criteria = new CDbCriteria;

            if (!in_array($fieldname, $fields_list)) $fields_list[] = $fieldname;
            {
                // Yii does not quote column names (duh!) so we have to do it.
                $criteria->addColumnCondition(array(Yii::app()->db->quoteColumnName($fieldname) => $member['value']), 'OR');
            }

            $fields_query[$fieldname] = $criteria;
        }

        $criteria = new CDbCriteria;

        foreach ($fields_list as $fieldname)
        {
            $criteria->mergeWith($fields_query[$fieldname]);
        }
        $result = Survey_dynamic::model($iSurveyId)->count($criteria);
    }
    return $result;
}

/**
* Creates an array with details on a particular response for display purposes
* Used in Print answers, Detailed response view and Detailed admin notification email
*
* @param mixed $iSurveyID
* @param mixed $iResponseID
* @param mixed $sLanguageCode
* @param boolean $bHonorConditions Apply conditions
*/
function getFullResponseTable($iSurveyID, $iResponseID, $sLanguageCode, $bHonorConditions=false)
{
    $aFieldMap = createFieldMap($iSurveyID,false,false,$sLanguageCode);
    //Get response data
    $idrow = Survey_dynamic::model($iSurveyID)->findByAttributes(array('id'=>$iResponseID));

    // Create array of non-null values - those are the relevant ones
    $aRelevantFields = array();

    foreach ($aFieldMap as $q)
    {
        if (!is_null($idrow[$q->fieldname]))
        {
            $aRelevantFields[$q->fieldname]=$q;
        }
    }

    $aResultTable=array();
    $oldgid = 0;
    $oldqid = 0;
    foreach ($aRelevantFields as $q)
    {
        if (!empty($q->id))
        {
            $attributes = $q->getAttributeValues();
            if (getQuestionAttributeValue($attributes, 'hidden') == 1)
            {
                continue;
            }
        }
        $question = $q->text;
        $subquestion='';
        if (isset($q->gid) && !empty($q->gid)) {
            //Check to see if gid is the same as before. if not show group name
            if ($oldgid !== $q->gid)
            {
                $oldgid = $q->gid;
                $aResultTable['gid_'.$q->gid]=array($q->groupname);
            }
        }
        if (!empty($q->id))
        {
            if ($oldqid !== $q->id)
            {
                $oldqid = $q->id;
                if (isset($q->sq) || isset($q->sq1) || isset($q->sq2))
                {
                    $aResultTable['qid_'.$q->surveyid.'X'.$q->gid.'X'.$q->id]=array($q->text,'','');
                }
                else
                {
                    $answer = getExtendedAnswer($iSurveyID,$q->fieldname, $idrow[$q->fieldname],$oLanguage);
                    $aResultTable[$q->fieldname]=array($question,'',$answer);
                    continue;
                }
            }
        }
        else
        {
            $answer=getExtendedAnswer($iSurveyID,$q->fieldname, $idrow[$q->fieldname],$oLanguage);
            $aResultTable[$q->fieldname]=array($question,'',$answer);
            continue;
        }
        if (isset($q->sq))
            $subquestion = "{$q->sq}";

        if (isset($q->sq1))
            $subquestion = "{$q->sq1}";

        if (isset($q->sq2))
            $subquestion .= "[{$q->sq2}]";

        $answer = getExtendedAnswer($iSurveyID,$q->fieldname, $idrow[$q->fieldname],$oLanguage);
        $aResultTable[$q->fieldname]=array('',$subquestion,$answer);
    }
    return $aResultTable;
}

/**
* Check if $str is an integer, or string representation of an integer
*
* @param mixed $mStr
*/
function isNumericInt($mStr)
{
    if(is_int($mStr))
        return true;
    elseif(is_string($mStr))
        return preg_match("/^[0-9]+$/", $mStr);
    return false;
}


/**
* Include Keypad headers
*/
function includeKeypad()
{
    
    header_includes(Yii::app()->getConfig('generalscripts').'jquery/jquery.keypad.min.js');
    if (Yii::app()->getLanguage() != 'en_us')
    {
        header_includes(Yii::app()->getConfig('generalscripts').'jquery/locale/jquery.ui.keypad-'.Yii::app()->getLanguage().'.js');
    }
    header_includes('jquery.keypad.alt.css','css');
}

/**
* getQuotaInformation() returns quota information for the current survey
* @param string $surveyid - Survey identification number
* @param string $quotaid - Optional quotaid that restricts the result to a given quota
* @return array - nested array, Quotas->Members->Fields
*/
function getQuotaInformation($surveyid,$language,$iQuotaID='all')
{
    global $clienttoken;
    $baselang = Survey::model()->findByPk($surveyid)->language;
    $aAttributes=array('sid' => $surveyid);
    if ($iQuotaID != 'all')
    {
        $aAttributes['id'] = $iQuotaID;
    }

    $quotas = Quota::model()->with(array('languagesettings' => array('condition' => "quotals_language='$language'")))->findByAttributes($aAttributes);

    $surveyinfo=getSurveyInfo($surveyid);

    // Check all quotas for the current survey
    if (count($quotas) > 0)
    {
        $survey_quotas = $quotas->attributes;
        foreach ($quotas->languagesettings[0]->attributes as $k => $v)
            $survey_quotas[$k] = $v;

        $quota_info=array('Name' => $survey_quotas['name'],
        'Limit' => $survey_quotas['qlimit'],
        'Action' => $survey_quotas['action'],
        'Message' => $survey_quotas['quotals_message'],
        'Url' => $survey_quotas['quotals_url'],
        'UrlDescrip' => $survey_quotas['quotals_urldescrip'],
        'AutoloadUrl' => $survey_quotas['autoload_url']);

        $result_qe = Quota_members::model()->findAllByAttributes(array('quota_id'=>$survey_quotas['id']));
        $quota_info['members'] = array();
        foreach ($result_qe as $quota_entry)
        {
            $quota_entry = $quota_entry->attributes;
            $result_quest=Questions::model()->with('question_types')->findByAttributes(array('qid'=>$quota_entry['qid'], 'language'=>$baselang));
            $qtype=$result_quest->attributes;

            $q = createQuestion($result_quest->question_types['class'], array('surveyid'=>$surveyid, 'id'=>$quota_entry['qid'], 'gid'=>$qtype['gid']));
            if ($member = $q->getQuotaValue($quota_entry['code'])) $quota_info['members'] = array_merge($quota_info['members'], $member);
        }
        return $quota_info;
    }
    return false;
}

/**
* This function replaces the old insertans tags with new ones across a survey
*
* @param string $newsid  Old SID
* @param string $oldsid  New SID
* @param mixed $fieldnames Array  array('oldfieldname'=>'newfieldname')
*/
function translateInsertansTags($newsid,$oldsid,$fieldnames)
{
    uksort($fieldnames, create_function('$a,$b', 'return strlen($a) < strlen($b);'));

    Yii::app()->loadHelper('database');
    $newsid=sanitize_int($newsid);
    $oldsid=sanitize_int($oldsid);

    # translate 'surveyls_urldescription' and 'surveyls_url' INSERTANS tags in surveyls
    $sql = "SELECT surveyls_survey_id, surveyls_language, surveyls_urldescription, surveyls_url from {{surveys_languagesettings}}
    WHERE surveyls_survey_id=".$newsid." AND (surveyls_urldescription LIKE '%{$oldsid}X%' OR surveyls_url LIKE '%{$oldsid}X%')";
    $result = dbExecuteAssoc($sql) or show_error("Can't read groups table in transInsertAns ");     // Checked

    //while ($qentry = $res->FetchRow())
    foreach ($result->readAll() as $qentry)
    {
        $urldescription = $qentry['surveyls_urldescription'];
        $endurl  = $qentry['surveyls_url'];
        $language = $qentry['surveyls_language'];

        foreach ($fieldnames as $sOldFieldname=>$sNewFieldname)
        {
            $pattern = $sOldFieldname;
            $replacement = $sNewFieldname;
            $urldescription=preg_replace('/'.$pattern.'/', $replacement, $urldescription);
            $endurl=preg_replace('/'.$pattern.'/', $replacement, $endurl);
        }

        if (strcmp($urldescription,$qentry['surveyls_urldescription']) !=0  ||
        (strcmp($endurl,$qentry['surveyls_url']) !=0))
        {

            // Update Field

            $data = array(
            'surveyls_urldescription' => $urldescription,
            'surveyls_url' => $endurl
            );

            $where = array(
            'surveyls_survey_id' => $newsid,
            'surveyls_language' => $language
            );

            Surveys_languagesettings::model()->updateRecord($data,$where);

        } // Enf if modified
    } // end while qentry

    # translate 'quotals_urldescrip' and 'quotals_url' INSERTANS tags in quota_languagesettings
    $sql = "SELECT quotals_id, quotals_urldescrip, quotals_url from {{quota_languagesettings}} qls, {{quota}} q
    WHERE sid=".$newsid." AND q.id=qls.quotals_quota_id AND (quotals_urldescrip LIKE '%{$oldsid}X%' OR quotals_url LIKE '%{$oldsid}X%')";
    $result = dbExecuteAssoc($sql) or safeDie("Can't read quota table in transInsertAns");     // Checked

    foreach ($result->readAll() as $qentry)
    {
        $urldescription = $qentry['quotals_urldescrip'];
        $endurl  = $qentry['quotals_url'];

        foreach ($fieldnames as $sOldFieldname=>$sNewFieldname)
        {
            $pattern = $sOldFieldname;
            $replacement = $sNewFieldname;
            $urldescription=preg_replace('/'.$pattern.'/', $replacement, $urldescription);
            $endurl=preg_replace('/'.$pattern.'/', $replacement, $endurl);
        }

        if (strcmp($urldescription,$qentry['quotals_urldescrip']) !=0  || (strcmp($endurl,$qentry['quotals_url']) !=0))
        {
            // Update Field
            $sqlupdate = "UPDATE {{quota_languagesettings}} SET quotals_urldescrip='".$urldescription."', quotals_url='".$endurl."' WHERE quotals_id={$qentry['quotals_id']}";
            $updateres=dbExecuteAssoc($sqlupdate) or safeDie ("Couldn't update INSERTANS in quota_languagesettings<br />$sqlupdate<br />");    //Checked
        } // Enf if modified
    } // end while qentry

    # translate 'description' INSERTANS tags in groups
    $sql = "SELECT gid, language, group_name, description from {{groups}}
    WHERE sid=".$newsid." AND description LIKE '%{$oldsid}X%' OR group_name LIKE '%{$oldsid}X%'";
    $res = dbExecuteAssoc($sql) or show_error("Can't read groups table in transInsertAns");     // Checked

    //while ($qentry = $res->FetchRow())
    foreach ($res->readAll() as $qentry)
    {
        $gpname = $qentry['group_name'];
        $description = $qentry['description'];
        $gid = $qentry['gid'];
        $language = $qentry['language'];

        foreach ($fieldnames as $sOldFieldname=>$sNewFieldname)
        {
            $pattern = $sOldFieldname;
            $replacement = $sNewFieldname;
            $gpname = preg_replace('/'.$pattern.'/', $replacement, $gpname);
            $description=preg_replace('/'.$pattern.'/', $replacement, $description);
        }

        if (strcmp($description,$qentry['description']) !=0  || strcmp($gpname,$qentry['group_name']) !=0)
        {
            // Update Fields
            $where = array(
            'gid' => $gid,
            'language' => $language
            );
            $oGroup = Groups::model()->findByAttributes($where);
            $oGroup->description= $description;
            $oGroup->group_name= $gpname;
            $oGroup->save();

        } // Enf if modified
    } // end while qentry

    # translate 'question' and 'help' INSERTANS tags in questions
    $sql = "SELECT qid, language, question, help from {{questions}}
    WHERE sid=".$newsid." AND (question LIKE '%{$oldsid}X%' OR help LIKE '%{$oldsid}X%')";
    $result = dbExecuteAssoc($sql) or die("Can't read question table in transInsertAns ");     // Checked

    //while ($qentry = $res->FetchRow())
    $aResultData=$result->readAll() ;
    foreach ($aResultData as $qentry)
    {
        $question = $qentry['question'];
        $help = $qentry['help'];
        $qid = $qentry['qid'];
        $language = $qentry['language'];

        foreach ($fieldnames as $sOldFieldname=>$sNewFieldname)
        {
            $pattern = $sOldFieldname;
            $replacement = $sNewFieldname;
            $question=preg_replace('/'.$pattern.'/', $replacement, $question);
            $help=preg_replace('/'.$pattern.'/', $replacement, $help);
        }

        if (strcmp($question,$qentry['question']) !=0 ||
        strcmp($help,$qentry['help']) !=0)
        {
            // Update Field

            $data = array(
            'question' => $question,
            'help' => $help
            );

            $where = array(
            'qid' => $qid,
            'language' => $language
            );

            Questions::model()->updateByPk($where,$data);

        } // Enf if modified
    } // end while qentry

    # translate 'answer' INSERTANS tags in answers
    $result=Answers::model()->oldNewInsertansTags($newsid,$oldsid);

    //while ($qentry = $res->FetchRow())
    foreach ($result as $qentry)
    {
        $answer = $qentry['answer'];
        $code = $qentry['code'];
        $qid = $qentry['qid'];
        $language = $qentry['language'];

        foreach ($fieldnames as $sOldFieldname=>$sNewFieldname)
        {
            $pattern = $sOldFieldname;
            $replacement = $sNewFieldname;
            $answer=preg_replace('/'.$pattern.'/', $replacement, $answer);
        }

        if (strcmp($answer,$qentry['answer']) !=0)
        {
            // Update Field

            $data = array(
            'answer' => $answer,
            'qid' => $qid
            );

            $where = array(
            'code' => $code,
            'language' => $language
            );

            Answers::model()->update($data,$where);

        } // Enf if modified
    } // end while qentry
}

/**
* This function is a replacement of accessDenied.php which return appropriate error message which is then displayed.
*
* @params string $action - action for which acces denied error message is to be returned
* @params string sid - survey id
* @return $accesssummary - proper access denied error message
*/
function accessDenied($action,$sid='')
{
    
    if (Yii::app()->session['loginID'])
    {
        $ugid = Yii::app()->getConfig('ugid');
        $accesssummary = "<p><strong>".gT("Access denied!")."</strong><br />\n";
        $scriptname = Yii::app()->getController()->createUrl('/admin');
        //$action=returnGlobal('action');
        if  (  $action == "dumpdb"  )
        {
            $accesssummary .= "<p>".gT("You are not allowed dump the database!")."<br />";
            $accesssummary .= "<a href='$scriptname'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        elseif($action == "dumplabel")
        {
            $accesssummary .= "<p>".gT("You are not allowed export a label set!")."<br />";
            $accesssummary .= "<a href='$scriptname'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        elseif($action == "edituser")
        {
            $accesssummary .= "<p>".gT("You are not allowed to change user data!");
            $accesssummary .= "<br /><br /><a href='$scriptname?action=editusers'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        elseif($action == "newsurvey")
        {
            $accesssummary .= "<p>".gT("You are not allowed to create new surveys!")."<br />";
            $accesssummary .= "<a href='$scriptname'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        elseif($action == "deletesurvey")
        {
            $accesssummary .= "<p>".gT("You are not allowed to delete this survey!")."<br />";
            $accesssummary .= "<a href='$scriptname?sid={$sid}'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        elseif($action == "addquestion")
        {
            $accesssummary .= "<p>".gT("You are not allowed to add new questions for this survey!")."<br />";
            $accesssummary .= "<a href='$scriptname?sid={$sid}'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        elseif($action == "activate")
        {
            $accesssummary .= "<p>".gT("You are not allowed to activate this survey!")."<br />";
            $accesssummary .= "<a href='$scriptname?sid={$sid}'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        elseif($action == "deactivate")
        {
            $accesssummary .= "<p>".gT("You are not allowed to stop this survey!")."<br />";
            $accesssummary .= "<a href='$scriptname?sid={$sid}'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        elseif($action == "addgroup")
        {
            $accesssummary .= "<p>".gT("You are not allowed to add a group to this survey!")."<br />";
            $accesssummary .= "<a href='$scriptname?sid={$sid}'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        elseif($action == "ordergroups")
        {
            $link = Yii::app()->getController()->createUrl("/admin/survey/sa/view/surveyid/$sid");
            $accesssummary .= "<p>".gT("You are not allowed to order groups in this survey!")."<br />";
            $accesssummary .= "<a href='$link'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        elseif($action == "editsurvey")
        {
            $link = Yii::app()->getController()->createUrl("/admin/survey/sa/view/surveyid/$sid");
            $accesssummary .= "<p>".gT("You are not allowed to edit this survey!")."</p>";
            $accesssummary .= "<a href='$link'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        elseif($action == "editgroup")
        {
            $accesssummary .= "<p>".gT("You are not allowed to edit groups in this survey!")."</p>";
            $accesssummary .= "<a href='$scriptname?sid={$sid}'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        elseif($action == "browse_response" || $action == "listcolumn" || $action == "vvexport" || $action == "vvimport")
        {
            $accesssummary .= "<p>".gT("You are not allowed to browse responses!")."</p>";
            $accesssummary .= "<a href='$scriptname?sid={$sid}'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        elseif($action == "assessment")
        {
            $accesssummary .= "<p>".gT("You are not allowed to set assessment rules!")."</p>";
            $accesssummary .= "<a href='$scriptname?sid={$sid}'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        elseif($action == "delusergroup")
        {
            $accesssummary .= "<p>".gT("You are not allowed to delete this group!")."</p>";
            $accesssummary .= "<a href='$scriptname?action=editusergroups'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        elseif($action == "importsurvey")
        {
            $accesssummary .= "<p>".gT("You are not allowed to import a survey!")."</p>";
            $accesssummary .= "<a href='$scriptname?sid={$sid}'>".gT("Continue")."</a><br />&nbsp;\n";
        }

        elseif($action == "importgroup")
        {
            $accesssummary .= "<p>".gT("You are not allowed to import a group!")."</p>";
            $accesssummary .= "<a href='$scriptname?sid={$sid}'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        elseif($action == "importquestion")
        {
            $accesssummary .= "<p>".gT("You are not allowed to to import a question!")."</p>";
            $accesssummary .= "<a href='$scriptname?sid={$sid}'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        elseif($action == "CSRFwarn") //won't be used.
        {
            $sURLID='';
            if (isset($sid)) {
                $sURLID="?sid={$sid}";
            }
            $accesssummary .= "<p><span color='errortitle'>".gT("Security alert")."</span>: ".gT("Someone may be trying to use your LimeSurvey session (CSRF attack suspected). If you just clicked on a malicious link, please report this to your system administrator.").'<br>'.gT('Also this problem can occur when you are working/editing in LimeSurvey in several browser windows/tabs at the same time.')."</p>";
            $accesssummary .= "<a href='{$scriptname}{$sURLID}'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        elseif($action == "FakeGET")
        {
            $accesssummary .= "<p><span class='errortitle'>".gT("Security alert")."</span>: ".gT("Someone may be trying to use your LimeSurvey session (CSRF attack suspected). If you just clicked on a malicious link, please report this to your system administrator.").'<br>'.gT('Also this problem can occur when you are working/editing in LimeSurvey in several browser windows/tabs at the same time.')."</p>";
            $accesssummary .= "<a href='$scriptname?sid={$sid}'>".gT("Continue")."</a><br />&nbsp;\n";
        }
        else
        {
            $accesssummary .= "<br />".gT("You are not allowed to perform this operation!")."<br />\n";
            if(!empty($sid))
            {
                $accesssummary .= "<br /><br /><a href='$scriptname?sid=$sid>".gT("Continue")."</a><br />&nbsp;\n";
            }
            elseif(!empty($ugid))
            {
                $accesssummary .= "<br /><br /><a href='$scriptname?action=editusergroups&ugid={$ugid}'>".gT("Continue")."</a><br />&nbsp;\n";
            }
            else
            {
                $accesssummary .= "<br /><br /><a href='$scriptname'>".gT("Continue")."</a><br />&nbsp;\n";
            }
        }
        return $accesssummary;
    }

}

/**
* cleanLanguagesFromSurvey() removes any languages from survey tables that are not in the passed list
* @param string $sid - the currently selected survey
* @param string $availlangs - space seperated list of additional languages in survey
* @return bool - always returns true
*/
function cleanLanguagesFromSurvey($sid, $availlangs)
{
    /** 
     * @todo Update to reflect changes to question localized attributes.
     */
    return;
    Yii::app()->loadHelper('database');
    //
    $sid=sanitize_int($sid);
    $baselang = Survey::model()->findByPk($sid)->language;

    if (!empty($availlangs) && $availlangs != " ")
    {
        $availlangs=sanitize_languagecodeS($availlangs);
        $langs = explode(" ",$availlangs);
        if($langs[count($langs)-1] == "") array_pop($langs);
    }

    $sqllang = "language <> '".$baselang."' ";

    if (!empty($availlangs) && $availlangs != " ")
    {
        foreach ($langs as $lang)
        {
            $sqllang .= "AND language <> '".$lang."' ";
        }
    }

    // Remove From Answers Table
    $query = "SELECT qid FROM {{questions}} WHERE sid='{$sid}' AND $sqllang";
    $qidresult = dbExecuteAssoc($query);

    foreach ($qidresult->readAll() as $qrow)
    {

        $myqid = $qrow['qid'];
        $query = "DELETE FROM {{answers}} WHERE qid='$myqid' AND $sqllang";
        dbExecuteAssoc($query);
    }

    // Remove From Questions Table
    $query = "DELETE FROM {{questions}} WHERE sid='{$sid}' AND $sqllang";
    dbExecuteAssoc($query);

    // Remove From Groups Table
    $query = "DELETE FROM {{groups}} WHERE sid='{$sid}' AND $sqllang";
    dbExecuteAssoc($query);

    return true;
}

/**
* fixLanguageConsistency() fixes missing groups, questions, answers, quotas & assessments for languages on a survey
* @param string $sid - the currently selected survey
* @param string $availlangs - space seperated list of additional languages in survey - if empty all additional languages of a survey are checked against the base language
* @return bool - always returns true
*/
function fixLanguageConsistency($sid, $availlangs='')
{
    /**
     * @todo This must be updated to correctly handle the new database structure.
     * Also these raw SQL queries should be removed, and the documentation should be made more precice.
     */
    return;
    $sid=sanitize_int($sid);
    

    if (trim($availlangs)!='')
    {
        $availlangs=sanitize_languagecodeS($availlangs);
        $langs = explode(" ",$availlangs);
        if($langs[count($langs)-1] == "") array_pop($langs);
    } else {
        $langs=Survey::model()->findByPk($sid)->additionalLanguages;
    }

    $baselang = Survey::model()->findByPk($sid)->language;
    $query = "SELECT * FROM {{groups}} WHERE sid='{$sid}' AND language='{$baselang}'  ORDER BY group_order";
    $result = Yii::app()->db->createCommand($query)->query();
    foreach($result->readAll() as $group)
    {
        foreach ($langs as $lang)
        {

            $query = "SELECT count(gid) FROM {{groups}} WHERE sid='{$sid}' AND gid='{$group['gid']}' AND language='{$lang}'";
            $gresult = Yii::app()->db->createCommand($query)->queryScalar();
            if ($gresult < 1)
            {
                $data = array(
                'gid' => $group['gid'],
                'sid' => $group['sid'],
                'group_name' => $group['group_name'],
                'group_order' => $group['group_order'],
                'description' => $group['description'],
                'randomization_group' => $group['randomization_group'],
                'grelevance' => $group['grelevance'],
                'language' => $lang

                );
                switchMSSQLIdentityInsert('groups',true);
                Yii::app()->db->createCommand()->insert('{{groups}}', $data);
                switchMSSQLIdentityInsert('groups',false);
            }
        }
        reset($langs);
    }

    $quests = array();
    $query = "SELECT * FROM {{questions}} WHERE sid='{$sid}' AND language='{$baselang}' ORDER BY question_order";
    $result = Yii::app()->db->createCommand($query)->query()->readAll();
    if (count($result) > 0)
    {
        foreach($result as $question)
        {
            array_push($quests,$question['qid']);
            foreach ($langs as $lang)
            {
                $query = "SELECT count(qid) FROM {{questions}} WHERE sid='{$sid}' AND qid='{$question['qid']}' AND language='{$lang}' AND scale_id={$question['scale_id']}";
                $gresult = Yii::app()->db->createCommand($query)->queryScalar();
                if ($gresult < 1)
                {
                    switchMSSQLIdentityInsert('questions',true);
                    $data = array(
                    'qid' => $question['qid'],
                    'sid' => $question['sid'],
                    'gid' => $question['gid'],
                    'tid' => $question['tid'],
                    'title' => $question['title'],
                    'question' => $question['question'],
                    'preg' => $question['preg'],
                    'help' => $question['help'],
                    'other' => $question['other'],
                    'mandatory' => $question['mandatory'],
                    'question_order' => $question['question_order'],
                    'language' => $lang,
                    'scale_id' => $question['scale_id'],
                    'parent_qid' => $question['parent_qid'],
                    'relevance' => $question['relevance']
                    );
                    Yii::app()->db->createCommand()->insert('{{questions}}', $data);
                }
            }
            reset($langs);
        }

        $sqlans = "";
        foreach ($quests as $quest)
        {
            $sqlans .= " OR qid = '".$quest."' ";
        }
        $query = "SELECT * FROM {{answers}} WHERE language='{$baselang}' and (".trim($sqlans,' OR').") ORDER BY qid, code";
        $result = Yii::app()->db->createCommand($query)->query();
        foreach($result->readAll() as $answer)
        {
            foreach ($langs as $lang)
            {
                $query = "SELECT count(qid) FROM {{answers}} WHERE code='{$answer['code']}' AND qid='{$answer['qid']}' AND language='{$lang}' AND scale_id={$answer['scale_id']}";
                $gresult = Yii::app()->db->createCommand($query)->queryScalar();
                if ($gresult < 1)
                {
                    $data = array(
                    'qid' => $answer['qid'],
                    'code' => $answer['code'],
                    'answer' => $answer['answer'],
                    'scale_id' => $answer['scale_id'],
                    'sortorder' => $answer['sortorder'],
                    'language' => $lang,
                    'assessment_value' =>  $answer['assessment_value']
                    );
                    Yii::app()->db->createCommand()->insert('{{answers}}', $data);
                }
            }
            reset($langs);
        }
    }


    $query = "SELECT * FROM {{assessments}} WHERE sid='{$sid}' AND language='{$baselang}'";
    $result = Yii::app()->db->createCommand($query)->query();
    foreach($result->readAll() as $assessment)
    {
        foreach ($langs as $lang)
        {
            $query = "SELECT count(id) FROM {{assessments}} WHERE sid='{$sid}' AND id='{$assessment['id']}' AND language='{$lang}'";
            $gresult = Yii::app()->db->createCommand($query)->queryScalar();
            if ($gresult < 1)
            {
                $data = array(
                'id' => $assessment['id'],
                'sid' => $assessment['sid'],
                'scope' => $assessment['scope'],
                'gid' => $assessment['gid'],
                'name' => $assessment['name'],
                'minimum' => $assessment['minimum'],
                'maximum' => $assessment['maximum'],
                'message' => $assessment['message'],
                'language' => $lang
                );
                Yii::app()->db->createCommand()->insert('{{assessments}}', $data);
            }
        }
        reset($langs);
    }


    $query = "SELECT * FROM {{quota_languagesettings}} join {{quota}} q on quotals_quota_id=q.id WHERE q.sid='{$sid}' AND quotals_language='{$baselang}'";
    $result = Yii::app()->db->createCommand($query)->query();
    foreach($result->readAll() as $qls)
    {
        foreach ($langs as $lang)
        {
            $query = "SELECT count(quotals_id) FROM {{quota_languagesettings}} WHERE quotals_quota_id='{$qls['quotals_quota_id']}' AND quotals_language='{$lang}'";
            $gresult = Yii::app()->db->createCommand($query)->queryScalar();
            if ($gresult < 1)
            {
                $data = array(
                'quotals_quota_id' => $qls['quotals_quota_id'],
                'quotals_name' => $qls['quotals_name'],
                'quotals_message' => $qls['quotals_message'],
                'quotals_url' => $qls['quotals_url'],
                'quotals_urldescrip' => $qls['quotals_urldescrip'],
                'quotals_language' => $lang
                );
                Yii::app()->db->createCommand()->insert('{{quota_languagesettings}}', $data);
            }
        }
        reset($langs);
    }

    return true;
}

/**
* This function switches identity insert on/off for the MSSQL database
*
* @param string $table table name (without prefix)
* @param mixed $state  Set to true to activate ID insert, or false to deactivate
*/
function switchMSSQLIdentityInsert($table,$state)
{
    if (in_array(Yii::app()->db->getDriverName(), array('mssql', 'sqlsrv')))
    {
        if ($state == true)
        {
            // This needs to be done directly on the PDO object because when using CdbCommand or similar it won't have any effect
            Yii::app()->db->pdoInstance->exec('SET IDENTITY_INSERT '.Yii::app()->db->tablePrefix.$table.' ON');  
        }
        else
        {
            // This needs to be done directly on the PDO object because when using CdbCommand or similar it won't have any effect
            Yii::app()->db->pdoInstance->exec('SET IDENTITY_INSERT '.Yii::app()->db->tablePrefix.$table.' OFF'); 
        }
    }
}

/**
* Retrieves the last Insert ID realiable for cross-DB applications
* 
* @param string $sTableName Needed for Postgres and MSSQL
*/
function getLastInsertID($sTableName)
{
    $sDBDriver=Yii::app()->db->getDriverName();
    if ($sDBDriver=='mysql' || $sDBDriver=='mysqli')
    {
        return Yii::app()->db->getLastInsertID();
    }
    else
    {
        return Yii::app()->db->getCommandBuilder()->getLastInsertID($sTableName);
    }
}

// TMSW Conditions->Relevance:  This function is not needed?  Optionally replace this with call to EM to get similar info
/**
* getGroupDepsForConditions() get Dependencies between groups caused by conditions
* @param string $sid - the currently selected survey
* @param string $depgid - (optionnal) get only the dependencies applying to the group with gid depgid
* @param string $targgid - (optionnal) get only the dependencies for groups dependents on group targgid
* @param string $index-by - (optionnal) "by-depgid" for result indexed with $res[$depgid][$targgid]
*                   "by-targgid" for result indexed with $res[$targgid][$depgid]
* @return array - returns an array describing the conditions or NULL if no dependecy is found
*
* Example outupt assumin $index-by="by-depgid":
*Array
*(
*    [125] => Array             // Group Id 125 is dependent on
*        (
*            [123] => Array         // Group Id 123
*                (
*                    [depgpname] => G3      // GID-125 has name G3
*                    [targetgpname] => G1   // GID-123 has name G1
*                    [conditions] => Array
*                        (
*                            [189] => Array // Because Question Id 189
*                                (
*                                    [0] => 9   // Have condition 9 set
*                                    [1] => 10  // and condition 10 set
*                                    [2] => 14  // and condition 14 set
*                                )
*
*                        )
*
*                )
*
*            [124] => Array         // GID 125 is also dependent on GID 124
*                (
*                    [depgpname] => G3
*                    [targetgpname] => G2
*                    [conditions] => Array
*                        (
*                            [189] => Array // Because Question Id 189 have conditions set
*                                (
*                                    [0] => 11
*                                )
*
*                            [215] => Array // And because Question Id 215 have conditions set
*                                (
*                                    [0] => 12
*                                )
*
*                        )
*
*                )
*
*        )
*
*)
*
* Usage example:
*   * Get all group dependencies for SID $sid indexed by depgid:
*       $result=getGroupDepsForConditions($sid);
*   * Get all group dependencies for GID $gid in survey $sid indexed by depgid:
*       $result=getGroupDepsForConditions($sid,$gid);
*   * Get all group dependents on group $gid in survey $sid indexed by targgid:
*       $result=getGroupDepsForConditions($sid,"all",$gid,"by-targgid");
*/
function getGroupDepsForConditions($sid,$depgid="all",$targgid="all",$indexby="by-depgid")
{
    $sid=sanitize_int($sid);
    $condarray = Array();
    $sqldepgid="";
    $sqltarggid="";
    if ($depgid != "all") { $depgid = sanitize_int($depgid); $sqldepgid="AND tq.gid=$depgid";}
    if ($targgid != "all") {$targgid = sanitize_int($targgid); $sqltarggid="AND tq2.gid=$targgid";}

    $baselang = Survey::model()->findByPk($sid)->language;
    $condquery = "SELECT tg.gid as depgid, tg.group_name as depgpname, "
    . "tg2.gid as targgid, tg2.group_name as targgpname, tq.qid as depqid, tc.cid FROM "
    . "{{conditions}} AS tc, "
    . "{{questions}} AS tq, "
    . "{{questions}} AS tq2, "
    . "{{groups}} AS tg ,"
    . "{{groups}} AS tg2 "
    . "WHERE tg.language='{$baselang}' AND tg2.language='{$baselang}' AND tc.qid = tq.qid AND tq.sid=$sid "
    . "AND tq.gid = tg.gid AND tg2.gid = tq2.gid "
    . "AND tq2.qid=tc.cqid AND tq.gid != tg2.gid $sqldepgid $sqltarggid";
    $condresult = Yii::app()->db->createCommand($condquery)->query()->readAll();
    if (count($condresult) > 0) {
        foreach ($condresult as $condrow)
        {

            switch ($indexby)
            {
                case "by-depgid":
                    $depgid=$condrow['depgid'];
                    $targetgid=$condrow['targgid'];
                    $depqid=$condrow['depqid'];
                    $cid=$condrow['cid'];
                    $condarray[$depgid][$targetgid]['depgpname'] = $condrow['depgpname'];
                    $condarray[$depgid][$targetgid]['targetgpname'] = $condrow['targgpname'];
                    $condarray[$depgid][$targetgid]['conditions'][$depqid][]=$cid;
                    break;

                case "by-targgid":
                    $depgid=$condrow['depgid'];
                    $targetgid=$condrow['targgid'];
                    $depqid=$condrow['depqid'];
                    $cid=$condrow['cid'];
                    $condarray[$targetgid][$depgid]['depgpname'] = $condrow['depgpname'];
                    $condarray[$targetgid][$depgid]['targetgpname'] = $condrow['targgpname'];
                    $condarray[$targetgid][$depgid]['conditions'][$depqid][] = $cid;
                    break;
            }
        }
        return $condarray;
    }
    return null;
}

// TMSW Conditions->Relevance:  This function is not needed?  Optionally replace this with call to EM to get similar info
/**
* getQuestDepsForConditions() get Dependencies between groups caused by conditions
* @param string $sid - the currently selected survey
* @param string $gid - (optionnal) only search dependecies inside the Group Id $gid
* @param string $depqid - (optionnal) get only the dependencies applying to the question with qid depqid
* @param string $targqid - (optionnal) get only the dependencies for questions dependents on question Id targqid
* @param string $index-by - (optionnal) "by-depqid" for result indexed with $res[$depqid][$targqid]
*                   "by-targqid" for result indexed with $res[$targqid][$depqid]
* @return array - returns an array describing the conditions or NULL if no dependecy is found
*
* Example outupt assumin $index-by="by-depqid":
*Array
*(
*    [184] => Array     // Question Id 184
*        (
*            [183] => Array // Depends on Question Id 183
*                (
*                    [0] => 5   // Because of condition Id 5
*                )
*
*        )
*
*)
*
* Usage example:
*   * Get all questions dependencies for Survey $sid and group $gid indexed by depqid:
*       $result=getQuestDepsForConditions($sid,$gid);
*   * Get all questions dependencies for question $qid in survey/group $sid/$gid indexed by depqid:
*       $result=getGroupDepsForConditions($sid,$gid,$qid);
*   * Get all questions dependents on question $qid in survey/group $sid/$gid indexed by targqid:
*       $result=getGroupDepsForConditions($sid,$gid,"all",$qid,"by-targgid");
*/
function getQuestDepsForConditions($sid,$gid="all",$depqid="all",$targqid="all",$indexby="by-depqid", $searchscope="samegroup")
{
    
    $condarray = Array();

    $baselang = Survey::model()->findByPk($sid)->language;
    $sqlgid="";
    $sqldepqid="";
    $sqltargqid="";
    $sqlsearchscope="";
    if ($gid != "all") {$gid = sanitize_int($gid); $sqlgid="AND tq.gid=$gid";}
    if ($depqid != "all") {$depqid = sanitize_int($depqid); $sqldepqid="AND tq.qid=$depqid";}
    if ($targqid != "all") {$targqid = sanitize_int($targqid); $sqltargqid="AND tq2.qid=$targqid";}
    if ($searchscope == "samegroup") {$sqlsearchscope="AND tq2.gid=tq.gid";}

    $condquery = "SELECT tq.qid as depqid, tq2.qid as targqid, tc.cid
    FROM {{conditions}} AS tc, {{questions}} AS tq, {{questions}} AS tq2
    WHERE tq.language='{$baselang}' AND tq2.language='{$baselang}' AND tc.qid = tq.qid AND tq.sid='$sid'
    AND  tq2.qid=tc.cqid $sqlsearchscope $sqlgid $sqldepqid $sqltargqid";
    $condresult=Yii::app()->db->createCommand($condquery)->query()->readAll();
    if (count($condresult) > 0) {
        foreach ($condresult as $condrow)
        {
            $depqid=$condrow['depqid'];
            $targetqid=$condrow['targqid'];
            $condid=$condrow['cid'];
            switch ($indexby)
            {
                case "by-depqid":
                    $condarray[$depqid][$targetqid][] = $condid;
                    break;

                case "by-targqid":
                    $condarray[$targetqid][$depqid][] = $condid;
                    break;
            }
        }
        return $condarray;
    }
    return null;
}

// TMSW Conditions->Relevance:  This function is not needed - could replace with a message from EM output.
/**
* checkMoveQuestionConstraintsForConditions()
* @param string $sid - the currently selected survey
* @param string $qid - qid of the question you want to check possible moves
* @param string $newgid - (optionnal) get only constraints when trying to move to this particular GroupId
*                                     otherwise, get all moves constraints for this question
*
* @return array - returns an array describing the conditions
*                 Array
*                 (
*                   ['notAbove'] = null | Array
*                       (
*                         Array ( gid1, group_order1, qid1, cid1 )
*                       )
*                   ['notBelow'] = null | Array
*                       (
*                         Array ( gid2, group_order2, qid2, cid2 )
*                       )
*                 )
*
* This should be read as:
*    - this question can't be move above group gid1 in position group_order1 because of the condition cid1 on question qid1
*    - this question can't be move below group gid2 in position group_order2 because of the condition cid2 on question qid2
*
*/
function checkMoveQuestionConstraintsForConditions($sid,$qid,$newgid="all")
{
    
    $resarray=Array();
    $resarray['notAbove']=null; // defaults to no constraint
    $resarray['notBelow']=null; // defaults to no constraint
    $sid=sanitize_int($sid);
    $qid=sanitize_int($qid);

    if ($newgid != "all")
    {
        $newgid=sanitize_int($newgid);
        $newgorder=getGroupOrder($sid,$newgid);
    }
    else
    {
        $neworder=""; // Not used in this case
    }

    $baselang = Survey::model()->findByPk($sid)->language;

    // First look for 'my dependencies': questions on which I have set conditions
    $condquery = "SELECT tq.qid as depqid, tq.gid as depgid, tg.group_order as depgorder, "
    . "tq2.qid as targqid, tq2.gid as targgid, tg2.group_order as targgorder, "
    . "tc.cid FROM "
    . "{{conditions}} AS tc, "
    . "{{questions}} AS tq, "
    . "{{questions}} AS tq2, "
    . "{{groups}} AS tg, "
    . "{{groups}} AS tg2 "
    . "WHERE tq.language='{$baselang}' AND tq2.language='{$baselang}' AND tc.qid = tq.qid AND tq.sid=$sid "
    . "AND  tq2.qid=tc.cqid AND tg.gid=tq.gid AND tg2.gid=tq2.gid AND tq.qid=$qid ORDER BY tg2.group_order DESC";

    $condresult=Yii::app()->db->createCommand($condquery)->query();

    foreach ($condresult->readAll() as $condrow )
    {
        // This Question can go up to the minimum GID on the 1st row
        $depqid=$condrow['depqid'];
        $depgid=$condrow['depgid'];
        $depgorder=$condrow['depgorder'];
        $targetqid=$condrow['targqid'];
        $targetgid=$condrow['targgid'];
        $targetgorder=$condrow['targgorder'];
        $condid=$condrow['cid'];
        //echo "This question can't go above to GID=$targetgid/order=$targetgorder because of CID=$condid";
        if ($newgid != "all")
        { // Get only constraints when trying to move to this group
            if ($newgorder < $targetgorder)
            {
                $resarray['notAbove'][]=Array($targetgid,$targetgorder,$depqid,$condid);
            }
        }
        else
        { // get all moves constraints
            $resarray['notAbove'][]=Array($targetgid,$targetgorder,$depqid,$condid);
        }
    }

    // Secondly look for 'questions dependent on me': questions that have conditions on my answers
    $condquery = "SELECT tq.qid as depqid, tq.gid as depgid, tg.group_order as depgorder, "
    . "tq2.qid as targqid, tq2.gid as targgid, tg2.group_order as targgorder, "
    . "tc.cid FROM {{conditions}} AS tc, "
    . "{{questions}} AS tq, "
    . "{{questions}} AS tq2, "
    . "{{groups}} AS tg, "
    . "{{groups}} AS tg2 "
    . "WHERE tq.language='{$baselang}' AND tq2.language='{$baselang}' AND tc.qid = tq.qid AND tq.sid=$sid "
    . "AND  tq2.qid=tc.cqid AND tg.gid=tq.gid AND tg2.gid=tq2.gid AND tq2.qid=$qid ORDER BY tg.group_order";

    $condresult=Yii::app()->db->createCommand($condquery)->query();

    foreach ($condresult->readAll() as $condrow)
    {
        // This Question can go down to the maximum GID on the 1st row
        $depqid=$condrow['depqid'];
        $depgid=$condrow['depgid'];
        $depgorder=$condrow['depgorder'];
        $targetqid=$condrow['targqid'];
        $targetgid=$condrow['targgid'];
        $targetgorder=$condrow['targgorder'];
        $condid=$condrow['cid'];
        //echo "This question can't go below to GID=$depgid/order=$depgorder because of CID=$condid";
        if ($newgid != "all")
        { // Get only constraints when trying to move to this group
            if ($newgorder > $depgorder)
            {
                $resarray['notBelow'][]=Array($depgid,$depgorder,$depqid,$condid);
            }
        }
        else
        { // get all moves constraints
            $resarray['notBelow'][]=Array($depgid,$depgorder,$depqid,$condid);
        }
    }
    return $resarray;
}

function getUserGroupList($ugid=NULL,$outputformat='optionlist')
{
    
    //$squery = "SELECT ugid, name FROM ".db_table_name('user_groups') ." WHERE owner_id = {Yii::app()->session['loginID']} ORDER BY name";
    $sQuery = "SELECT distinct a.ugid, a.name, a.owner_id FROM {{user_groups}} AS a LEFT JOIN {{user_in_groups}} AS b ON a.ugid = b.ugid WHERE 1=1 ";
    if (!hasGlobalPermission('USER_RIGHT_SUPERADMIN'))
    {
        $sQuery .="AND uid = ".Yii::app()->session['loginID'];
    }
    $sQuery .=  " ORDER BY name";

    $sresult = Yii::app()->db->createCommand($sQuery)->query(); //Checked
    if (!$sresult) {return "Database Error";}
    $selecter = "";
    foreach ($sresult->readAll() as $row)
    {
        $groupnames[] = $row;
    }


    //$groupnames = $sresult->GetRows();
    $simplegidarray=array();
    if (isset($groupnames))
    {
        foreach($groupnames as $gn)
        {
            $selecter .= "<option ";
            if(Yii::app()->session['loginID'] == $gn['owner_id']) {$selecter .= " style=\"font-weight: bold;\"";}
            //if (isset($_GET['ugid']) && $gn['ugid'] == $_GET['ugid']) {$selecter .= " selected='selected'"; $svexist = 1;}

            if ($gn['ugid'] == $ugid) {$selecter .= " selected='selected'"; $svexist = 1;}
            $link = Yii::app()->getController()->createUrl("/admin/usergroups/sa/view/ugid/".$gn['ugid']);
            $selecter .=" value='{$link}'>{$gn['name']}</option>\n";
            $simplegidarray[] = $gn['ugid'];
        }
    }

    if (!isset($svexist)) {$selecter = "<option value='-1' selected='selected'>".gT("Please choose...")."</option>\n".$selecter;}
    //else {$selecter = "<option value='-1'>".gT("None")."</option>\n".$selecter;}

    if ($outputformat == 'simplegidarray')
    {
        return $simplegidarray;
    }
    else
    {
        return $selecter;
    }
}

function getGroupUserList($ugid)
{
    Yii::app()->loadHelper('database');
    

    $ugid=sanitize_int($ugid);
    $surveyidquery = "SELECT a.uid, a.users_name FROM {{users}} AS a LEFT JOIN (SELECT uid AS id FROM {{user_in_groups}} WHERE ugid = {$ugid}) AS b ON a.uid = b.id WHERE id IS NULL ORDER BY a.users_name";

    $surveyidresult = dbExecuteAssoc($surveyidquery);  //Checked
    if (!$surveyidresult) {return "Database Error";}
    $surveyselecter = "";
    foreach ($surveyidresult->readAll() as $row)
    {
        $surveynames[] = $row;
    }
    //$surveynames = $surveyidresult->GetRows();
    if (isset($surveynames))
    {
        foreach($surveynames as $sv)
        {
            $surveyselecter .= "<option";
            $surveyselecter .=" value='{$sv['uid']}'>{$sv['users_name']}</option>\n";
        }
    }
    $surveyselecter = "<option value='-1' selected='selected'>".gT("Please choose...")."</option>\n".$surveyselecter;
    return $surveyselecter;
}

/**
* Run an arbitrary sequence of semicolon-delimited SQL commands
*
* Assumes that the input text (file or string) consists of
* a number of SQL statements ENDING WITH SEMICOLONS.  The
* semicolons MUST be the last character in a line.
* Lines that are blank or that start with "#" or "--" (postgres) are ignored.
* Only tested with mysql dump files (mysqldump -p -d limesurvey)
* Function kindly borrowed by Moodle
* @param string $sqlfile The path where a file with sql commands can be found on the server.
* @param string $sqlstring If no path is supplied then a string with semicolon delimited sql
* commands can be supplied in this argument.
* @return bool Returns true if database was modified successfully.
*/
function modifyDatabase($sqlfile='', $sqlstring='')
{
    Yii::app()->loadHelper('database');
    

    global $siteadminemail;
    global $siteadminname;
    global $codeString;
    global $modifyoutput;

    $success = true;  // Let's be optimistic
    $modifyoutput='';

    if (!empty($sqlfile)) {
        if (!is_readable($sqlfile)) {
            $success = false;
            echo '<p>Tried to modify database, but "'. $sqlfile .'" doesn\'t exist!</p>';
            return $success;
        } else {
            $lines = file($sqlfile);
        }
    } else {
        $sqlstring = trim($sqlstring);
        if ($sqlstring{strlen($sqlstring)-1} != ";") {
            $sqlstring .= ";"; // add it in if it's not there.
        }
        $lines[] = $sqlstring;
    }

    $command = '';

    foreach ($lines as $line) {
        $line = rtrim($line);
        $length = strlen($line);

        if ($length and $line[0] <> '#' and substr($line,0,2) <> '--') {
            if (substr($line, $length-1, 1) == ';') {
                $line = substr($line, 0, $length-1);   // strip ;
                $command .= $line;
                $command = str_replace('prefix_', Yii::app()->db->tablePrefix, $command); // Table prefixes
                $command = str_replace('$defaultuser', Yii::app()->getConfig('defaultuser'), $command);
                $command = str_replace('$defaultpass', hash('sha256',Yii::app()->getConfig('defaultpass')), $command);
                $command = str_replace('$siteadminname', $siteadminname, $command);
                $command = str_replace('$siteadminemail', $siteadminemail, $command);
                $command = str_replace('$defaultlang', Yii::app()->getConfig('defaultlang'), $command);
                $command = str_replace('$sessionname', 'ls'.randomChars(20,'123456789'), $command);
                $command = str_replace('$databasetabletype', Yii::app()->db->getDriverName(), $command);

                try
                {   Yii::app()->db->createCommand($command)->query(); //Checked
                    $command=htmlspecialchars($command);
                    $modifyoutput .=". ";
                }
                catch(CDbException $e)
                {
                    $command=htmlspecialchars($command);
                    $modifyoutput .="<br />".sprintf(gT("SQL command failed: %s"),"<span style='font-size:10px;'>".$command."</span>","<span style='color:#ee0000;font-size:10px;'></span><br/>");
                    $success = false;
                }

                $command = '';
            } else {
                $command .= $line;
            }
        }
    }

    return $success;

}

/**
* Returns labelsets for given language(s), or for all if null
*
* @param string $languages
* @return array
*/
function getLabelSets($languages = null)
{

    
    $languagesarray = array();
    if ($languages)
    {
        $languages=sanitize_languagecodeS($languages);
        $languagesarray=explode(' ',trim($languages));
    }

    $criteria = new CDbCriteria;
    foreach ($languagesarray as $k => $item)
    {
        $criteria->params[':lang_like1_' . $k] = "% $item %";
        $criteria->params[':lang_' . $k] = $item;
        $criteria->params[':lang_like2_' . $k] = "% $item";
        $criteria->params[':lang_like3_' . $k] = "$item %";
        $criteria->addCondition("
        ((languages like :lang_like1_$k) or
        (languages = :lang_$k) or
        (languages like :lang_like2_$k) or
        (languages like :lang_like3_$k))");
    }

    $result = Labelsets::model()->findAll($criteria);
    $labelsets=array();
    foreach ($result as $row)
        $labelsets[] = array($row->lid, $row->label_name);
    return $labelsets;
}

function getHeader($meta = false)
{
    global $embedded,$surveyid ;
    Yii::app()->loadHelper('surveytranslator');

    // Set Langage // TODO remove one of the Yii::app()->session see bug #5901
    if (Yii::app()->session['s_lang'] )
    {
        $languagecode =  Yii::app()->session['s_lang'];
    }
    elseif (Yii::app()->session['survey_'.$surveyid]['s_lang'] )
    {
        $languagecode =  Yii::app()->session['survey_'.$surveyid]['s_lang'];
    }
    elseif (isset($surveyid) && $surveyid && Survey::model()->findByPk($surveyid))
    {
        $languagecode=Survey::model()->findByPk($surveyid)->language;
    }
    else
    {
        $languagecode = Yii::app()->getConfig('defaultlang');
    }

    $header=  "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n"
    . "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"{$languagecode}\" lang=\"{$languagecode}\"";
    if (getLanguageRTL($languagecode))
    {
        $header.=" dir=\"rtl\" ";
    }
    $header.= ">\n\t<head>\n";

    if ($meta)
        $header .= $meta;

    if ( !$embedded )
    {
        return $header;
    }

    global $embedded_headerfunc;

    if ( function_exists( $embedded_headerfunc ) )
        return $embedded_headerfunc($header);
}


function doHeader()
{
    echo getHeader();
}

/**
* This function returns the header for the printable survey
* @return String
*
*/
function getPrintableHeader()
{
    global $rooturl,$homeurl;
    $headelements = '
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <script type="text/javascript" src="'.Yii::app()->getConfig('generalscripts').'jquery/jquery.js"></script>
    <script type="text/javascript" src="'.Yii::app()->getConfig('adminscripts').'printablesurvey.js"></script>
    ';
    return $headelements;
}

// This function returns the Footer as result string
// If you want to echo the Footer use doFooter() !
function getFooter()
{
    global $embedded;

    if ( !$embedded )
    {
        return "\n\n\t</body>\n</html>\n";
    }

    global $embedded_footerfunc;

    if ( function_exists( $embedded_footerfunc ) )
        return $embedded_footerfunc();
}

/**
 * Debugging function.
 * @param type $msg
 */
function debug($msg)
{
    echo CHtml::tag('div', array('style' => 'white-space: pre; background-color: #FFFF99; padding: 10px; border: 2px solid black; margin-bottom: 5px;'), json_encode($msg,  JSON_FORCE_OBJECT+ JSON_PRETTY_PRINT));
}
function doFooter()
{
    echo getFooter();
}

function getDBTableUsage($surveyid){
    Yii::app()->loadHelper('admin/activate');
    $arrCols = activateSurvey($surveyid,$surveyid,'admin.php',true);

    $length = 1;
    foreach ($arrCols['fields'] as $col){
        switch ($col[0]){
            case 'C':
                $length = $length + ($col[1]*3) + 1;
                break;
            case 'X':
            case 'B':
                $length = $length + 12;
                break;
            case 'D':
                $length = $length + 3;
                break;
            case 'T':
            case 'TS':
            case 'N':
                $length = $length + 8;
                break;
            case 'L':
                $legth++;
                break;
            case 'I':
            case 'I4':
            case 'F':
                $length = $length + 4;
                break;
            case 'I1':
                $length = $length + 1;
                break;
            case 'I2':
                $length = $length + 2;
                break;
            case 'I8':
                $length = $length + 8;
                break;
        }
    }
    if ($arrCols['dbtype'] == 'mysql' || $arrCols['dbtype'] == 'mysqli'){
        if ($arrCols['dbengine']=='myISAM'){
            $hard_limit = 4096;
        }
        elseif ($arrCols['dbengine'] == "InnoDB"){
            $hard_limit = 1000;
        }
        else{
            return false;
        }

        $size_limit = 65535;
    }
    elseif ($arrCols['dbtype'] == 'postgre'){
        $hard_limit = 1600;
        $size_limit = 0;
    }
    elseif ($arrCols['dbtype'] == 'mssql'){
        $hard_limit = 1024;
        $size_limit = 0;
    }
    else{
        return false;
    }

    $columns_used = count($arrCols['fields']);



    return (array( 'dbtype'=>$arrCols['dbtype'], 'column'=>array($columns_used,$hard_limit) , 'size' => array($length, $size_limit) ));
}

/**
*  Checks that each object from an array of CSV data [question-rows,answer-rows,labelsets-row] supports at least a given language
*
* @param mixed $csvarray array with a line of csv data per row
* @param mixed $idkeysarray  array of integers giving the csv-row numbers of the object keys
* @param mixed $langfieldnum  integer giving the csv-row number of the language(s) filed
*        ==> the language field  can be a single language code or a
*            space separated language code list
* @param mixed $langcode  the language code to be tested
* @param mixed $hasheader  if we should strip off the first line (if it contains headers)
*/
function  doesImportArraySupportLanguage($csvarray,$idkeysarray,$langfieldnum,$langcode, $hasheader = false)
{
    // An array with one row per object id and langsupport status as value
    $objlangsupportarray=Array();
    if ($hasheader === true)
    { // stripping first row to skip headers if any
        array_shift($csvarray);
    }

    foreach ($csvarray as $csvrow)
    {
        $rowcontents = convertCSVRowToArray($csvrow,',','"');
        $rowid = "";
        foreach ($idkeysarray as $idfieldnum)
        {
            $rowid .= $rowcontents[$idfieldnum]."-";
        }
        $rowlangarray = explode (" ", @$rowcontents[$langfieldnum]);
        if (!isset($objlangsupportarray[$rowid]))
        {
            if (array_search($langcode,$rowlangarray)!== false)
            {
                $objlangsupportarray[$rowid] = "true";
            }
            else
            {
                $objlangsupportarray[$rowid] = "false";
            }
        }
        else
        {
            if ($objlangsupportarray[$rowid] == "false" &&
            array_search($langcode,$rowlangarray) !== false)
            {
                $objlangsupportarray[$rowid] = "true";
            }
        }
    } // end foreach rown

    // If any of the object doesn't support the given language, return false
    if (array_search("false",$objlangsupportarray) === false)
    {
        return true;
    }
    else
    {
        return false;
    }
}


/**
* Retrieve a HTML <OPTION> list of survey admin users
*
* @param mixed $bIncludeOwner If the survey owner should be included
* @param mixed $bIncludeSuperAdmins If Super admins should be included
* @param int surveyid
* @return string
*/
function getSurveyUserList($bIncludeOwner=true, $bIncludeSuperAdmins=true,$surveyid)
{
    
    $surveyid=sanitize_int($surveyid);

    $sSurveyIDQuery = "SELECT a.uid, a.users_name, a.full_name FROM {{users}} AS a
    LEFT OUTER JOIN (SELECT uid AS id FROM {{survey_permissions}} WHERE sid = {$surveyid}) AS b ON a.uid = b.id
    WHERE id IS NULL ";
    if (!$bIncludeSuperAdmins)
    {
        $sSurveyIDQuery.='and superadmin=0 ';
    }
    $sSurveyIDQuery.= 'ORDER BY a.users_name';
    $oSurveyIDResult = Yii::app()->db->createCommand($sSurveyIDQuery)->query();  //Checked
    $aSurveyIDResult = $oSurveyIDResult->readAll();
    
    $surveyselecter = "";

    if (Yii::app()->getConfig('usercontrolSameGroupPolicy') == true)
    {
        $authorizedUsersList = getUserList('onlyuidarray');
    }

    foreach($aSurveyIDResult as $sv)
    {
        if (Yii::app()->getConfig('usercontrolSameGroupPolicy') == false ||
        in_array($sv['uid'],$authorizedUsersList))
        {
            $surveyselecter .= "<option";
            $surveyselecter .=" value='{$sv['uid']}'>{$sv['users_name']} {$sv['full_name']}</option>\n";
        }
    }
    if (!isset($svexist)) {$surveyselecter = "<option value='-1' selected='selected'>".gT("Please choose...")."</option>\n".$surveyselecter;}
    else {$surveyselecter = "<option value='-1'>".gT("None")."</option>\n".$surveyselecter;}

    return $surveyselecter;
}

function getSurveyUserGroupList($outputformat='htmloptions',$surveyid)
{
    
    $surveyid=sanitize_int($surveyid);

    $surveyidquery = "SELECT a.ugid, a.name, MAX(d.ugid) AS da
    FROM {{user_groups}} AS a
    LEFT JOIN (
    SELECT b.ugid
    FROM {{user_in_groups}} AS b
    LEFT JOIN (SELECT * FROM {{survey_permissions}}
    WHERE sid = {$surveyid}) AS c ON b.uid = c.uid WHERE c.uid IS NULL
    ) AS d ON a.ugid = d.ugid GROUP BY a.ugid, a.name HAVING MAX(d.ugid) IS NOT NULL";
    $surveyidresult = Yii::app()->db->createCommand($surveyidquery)->query();  //Checked
    $aResult=$surveyidresult->readAll();

    $surveyselecter = "";

    if (Yii::app()->getConfig('usercontrolSameGroupPolicy') == true)
    {
        $authorizedGroupsList=getUserGroupList(NULL, 'simplegidarray');
    }

    foreach($aResult as $sv)
    {
        if (Yii::app()->getConfig('usercontrolSameGroupPolicy') == false ||
        in_array($sv['ugid'],$authorizedGroupsList))
        {
            $surveyselecter .= "<option";
            $surveyselecter .=" value='{$sv['ugid']}'>{$sv['name']}</option>\n";
            $simpleugidarray[] = $sv['ugid'];
        }
    }

    if (!isset($svexist)) {$surveyselecter = "<option value='-1' selected='selected'>".gT("Please choose...")."</option>\n".$surveyselecter;}
    else {$surveyselecter = "<option value='-1'>".gT("None")."</option>\n".$surveyselecter;}

    if ($outputformat == 'simpleugidarray')
    {
        return $simpleugidarray;
    }
    else
    {
        return $surveyselecter;
    }
}

/*
* Emit the standard (last) onsubmit handler for the survey.
*
* This code in injected in the three questionnaire modes right after the <form> element,
* before the individual questions emit their own onsubmit replacement code.
*/
function sDefaultSubmitHandler()
{
    return <<<EOS
    <script type='text/javascript'>
    <!--
        // register the standard (last) onsubmit handler *first*
        document.limesurvey.onsubmit = std_onsubmit_handler;
    -->
    </script>
EOS;
}

/**
* This function fixes the group ID and type on all subquestions
*
*/
function fixSubquestions()
{
    $surveyidresult=Yii::app()->db->createCommand("select sq.qid, sq.parent_qid, sq.gid as sqgid, q.gid, sq.tid as sqtid,q.tid
    from {{questions}} sq JOIN {{questions}} q on sq.parent_qid=q.qid
    where sq.parent_qid>0 and  (sq.gid!=q.gid or sq.tid!=q.tid)")->query();
    foreach($surveyidresult->readAll() as $sv)
    {
        Yii::app()->db->createCommand("update {{questions}} set gid={$sv['gid']}, tid={$sv['tid']} where qid={$sv['qid']}")->query();
    }

}

/**
* Must use ls_json_encode to json_encode content, otherwise LimeExpressionManager will think that the associative arrays are expressions and try to parse them.
*/
function ls_json_encode($content)
{
    $ans = json_encode($content);
    $ans = str_replace(array('{','}'),array('{ ',' }'), $ans);
    return $ans;
}

/**
 * Decode a json string, sometimes needs stripslashes
 *
 * @param type $jsonString
 * @return type
 */
function json_decode_ls($jsonString)
{
   $decoded = json_decode($jsonString, true);

    if (is_null($decoded) && !empty($jsonString))
    {
        // probably we need stipslahes
        $decoded = json_decode(stripslashes($jsonString), true);
    }

    return $decoded;
}

/**
* Swaps two positions in an array
*
* @param mixed $key1
* @param mixed $key2
* @param mixed $array
*/
function arraySwapAssoc($key1, $key2, $array) {
    $newArray = array ();
    foreach ($array as $key => $value) {
        if ($key == $key1) {
            $newArray[$key2] = $array[$key2];
        } elseif ($key == $key2) {
            $newArray[$key1] = $array[$key1];
        } else {
            $newArray[$key] = $value;
        }
    }
    return $newArray;
}


/**
* Ellipsize String
*
* This public static function will strip tags from a string, split it at its max_length and ellipsize
*
* @param    string      string to ellipsize
* @param    integer     max length of string
* @param    mixed       int (1|0) or float, .5, .2, etc for position to split
* @param    string      ellipsis ; Default '...'
* @return   string      ellipsized string
*/
function ellipsize($str, $max_length, $position = 1, $ellipsis = '&hellip;')
{
    // Strip tags
    $str = trim(strip_tags($str));

    // Is the string long enough to ellipsize?
    if (strlen($str) <= $max_length+3)
    {
        return $str;
    }

    $beg = substr($str, 0, floor($max_length * $position));
    $position = ($position > 1) ? 1 : $position;

    if ($position === 1)
    {
        $end = substr($str, 0, -($max_length - strlen($beg)));
    }
    else
    {
        $end = substr($str, -($max_length - strlen($beg)));
    }

    return $beg.$ellipsis.$end;
}

/**
* This function returns the real IP address under all configurations
*
*/
function getIPAddress()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    elseif (!empty($_SERVER['REMOTE_ADDR']))
    {
        return $_SERVER['REMOTE_ADDR'];
    }
    else
    {
        return '127.0.0.1';
    }
}


/**
* This function tries to find out a valid language code for the language of the browser used
* If it cannot find it it will return the default language from global settings
*
*/
function getBrowserLanguage()
{
    $sLanguage=Yii::app()->getRequest()->getPreferredLanguage();
    Yii::app()->loadHelper("surveytranslator");
    $aLanguages=getLanguageData();
    if (!isset($aLanguages[$sLanguage]))
    {
        $sLanguage=str_replace('_','-',$sLanguage);
        if (!isset($aLanguages[$sLanguage]))
        {
            $sLanguage=substr($sLanguage,0,strpos($sLanguage,'-'));
            if (!isset($aLanguages[$sLanguage]))
            {
                $sLanguage=Yii::app()->getConfig('defaultlang');
            }
        }
    }
    return $sLanguage;
}

function tidToQuestion($tid, $data=array())
{
    if (is_numeric($tid))
    {
        $type = Question_types::model()->findByPk($tid);
        return createQuestion($type['class'], $data);
    }
    elseif (is_string($tid) && strlen($tid) == 32)
    { // The new question objects use GUIDs.
        // @todo Add more parameters in case applicable.
        return App()->getPluginManager()->constructQuestionFromGUID($tid);
    }
}

function createQuestion($name, $data=array())
{
    /**
     * @todo Remove ugly fix that assumes "old" question object names are not 32 chars long.
     */
    if (strlen($name) == 32)
    {
        return App()->getPluginManager()->constructQuestionFromGUID($name);
    }
    else
    {
        $class = $name.'Question';
        Yii::import('application.modules.*');
        return new $class($data);
    }
}

/**
* This function add string to css or js header for public surevy
* @param    string      string to ellipsize
* @param    string      max length of string
* @return   array       array of string for js or css to be included
*
*/

function header_includes($includes = false, $method = "js" )
{
    $header_includes = (array) Yii::app()->getConfig("{$method}_header_includes");
    $header_includes[] = $includes;
    $header_includes = array_filter($header_includes);
    $header_includes = array_unique($header_includes);
    Yii::app()->setConfig("{$method}_header_includes", $header_includes);
    return $header_includes;
}
// Closing PHP tag intentionally omitted - yes, it is okay

