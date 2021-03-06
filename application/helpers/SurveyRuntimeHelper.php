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

class SurveyRuntimeHelper {

    private $context = array();
    /**
    * Main function
    *
    * @param mixed $surveyid
    * @param mixed $args
    */
    function run($surveyid,$args) {
        global $errormsg;

        extract($args);
        $LEMsessid = 'survey_' . $surveyid;
        $sTemplatePath=getTemplatePath(Yii::app()->getConfig("defaulttemplate")).DIRECTORY_SEPARATOR;
        if (isset ($_SESSION['survey_'.$surveyid]['templatepath']))
        {
            $sTemplatePath=$_SESSION['survey_'.$surveyid]['templatepath'];
        }
       // $LEMdebugLevel - customizable debugging for Lime Expression Manager
        $LEMdebugLevel = 0;   // LEM_DEBUG_TIMING;    // (LEM_DEBUG_TIMING + LEM_DEBUG_VALIDATION_SUMMARY + LEM_DEBUG_VALIDATION_DETAIL);
        $LEMskipReprocessing=false; // true if used GetLastMoveResult to avoid generation of unneeded extra JavaScript
        switch ($thissurvey['format'])
        {
            case "A": //All in one
                $surveyMode = 'survey';
                break;
            default:
            case "S": //One at a time
                $surveyMode = 'question';
                break;
            case "G": //Group at a time
                $surveyMode = 'group';
                break;
        }
        $radix=getRadixPointData($thissurvey['surveyls_numberformat']);
        $radix = $radix['seperator'];

        $surveyOptions = array(
        'active' => ($thissurvey['active'] == 'Y'),
        'allowsave' => ($thissurvey['allowsave'] == 'Y'),
        'anonymized' => ($thissurvey['anonymized'] != 'N'),
        'assessments' => ($thissurvey['assessments'] == 'Y'),
        'datestamp' => ($thissurvey['datestamp'] == 'Y'),
        'deletenonvalues'=>Yii::app()->getConfig('deletenonvalues'),        
        'hyperlinkSyntaxHighlighting' => (($LEMdebugLevel & LEM_DEBUG_VALIDATION_SUMMARY) == LEM_DEBUG_VALIDATION_SUMMARY), // TODO set this to true if in admin mode but not if running a survey
        'ipaddr' => ($thissurvey['ipaddr'] == 'Y'),
        'radix'=>$radix,
        'refurl' => (($thissurvey['refurl'] == "Y" && isset($_SESSION[$LEMsessid]['refurl'])) ? $_SESSION[$LEMsessid]['refurl'] : NULL),
        'savetimings' => ($thissurvey['savetimings'] == "Y"),
        'surveyls_dateformat' => (isset($thissurvey['surveyls_dateformat']) ? $thissurvey['surveyls_dateformat'] : 1),
        'startlanguage'=> Yii::app()->getLanguage(),
        'target' => Yii::app()->getConfig('uploaddir').DIRECTORY_SEPARATOR.'surveys'.DIRECTORY_SEPARATOR.$thissurvey['sid'].DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR,
        'tempdir' => Yii::app()->getConfig('tempdir').DIRECTORY_SEPARATOR,
        'timeadjust' => (isset($timeadjust) ? $timeadjust : 0),
        'token' => (isset($clienttoken) ? $clienttoken : NULL),
        );
        //Security Checked: POST, GET, SESSION, REQUEST, returnGlobal, DB
        $previewgrp = false;
        if ($surveyMode == 'group' && isset($param['action']) && ($param['action'] == 'previewgroup'))
        {
            $previewgrp = true;
        }
        $previewquestion = false;
        if ($surveyMode == 'question' && isset($param['action']) && ($param['action'] == 'previewquestion'))
        {
            $previewquestion = true;
        }
        //        if (isset($param['newtest']) && $param['newtest'] == "Y")
        //            setcookie("limesurvey_timers", "0");   //@todo fix - sometimes results in headers already sent error
        $show_empty_group = false;

        if ($previewgrp || $previewquestion)
        {
            $_SESSION[$LEMsessid]['prevstep'] = 2;
            $_SESSION[$LEMsessid]['maxstep'] = 0;
        }
        else
        {
                                       
            //RUN THIS IF THIS IS THE FIRST TIME , OR THE FIRST PAGE ########################################
            if (!isset($_SESSION[$LEMsessid]['step'])) // || !$_SESSION[$LEMsessid]['step']) - don't do this for step0, else rebuild the session
            {
                
                buildsurveysession($surveyid);
                $sTemplatePath = $_SESSION[$LEMsessid]['templatepath'];

                if($surveyid != LimeExpressionManager::getLEMsurveyId())
                    LimeExpressionManager::SetDirtyFlag();

                LimeExpressionManager::StartSurvey($surveyid, $surveyMode, $surveyOptions, false, $LEMdebugLevel);
                $_SESSION[$LEMsessid]['step'] = 0;
                if ($surveyMode == 'survey')
                {
                    $move = "movenext"; // to force a call to NavigateForwards()
                }
                elseif (isset($thissurvey['showwelcome']) && $thissurvey['showwelcome'] == 'N')                
                {
                    $move = "movenext";
                    $_SESSION[$LEMsessid]['step']=1;
                }
            }
            elseif($surveyid != LimeExpressionManager::getLEMsurveyId()) 
            {
                LimeExpressionManager::StartSurvey($surveyid, $surveyMode, $surveyOptions, false, $LEMdebugLevel);
                LimeExpressionManager::JumpTo($_SESSION[$LEMsessid]['step'], false, false);
            }

            $totalquestions = $_SESSION['survey_'.$surveyid]['totalquestions'];

            if (!isset($_SESSION[$LEMsessid]['totalsteps']))
            {
                $_SESSION[$LEMsessid]['totalsteps'] = 0;
            }
            if (!isset($_SESSION[$LEMsessid]['maxstep']))
            {
                $_SESSION[$LEMsessid]['maxstep'] = 0;
            }

            if (isset($_SESSION[$LEMsessid]['LEMpostKey']) && isset($_POST['LEMpostKey']) && $_POST['LEMpostKey'] != $_SESSION[$LEMsessid]['LEMpostKey'])
            {
                // then trying to resubmit (e.g. Next, Previous, Submit) from a cached copy of the page
                // Does not try to save anything from the page to the database
                $moveResult = LimeExpressionManager::GetLastMoveResult(true);
                if (isset($_POST['thisstep']) && isset($moveResult['seq']) && $_POST['thisstep'] == $moveResult['seq'])
                {
                    // then pressing F5 or otherwise refreshing the current page, which is OK
                    $LEMskipReprocessing=true;
                    $move = "movenext"; // so will re-display the survey
                }
                else
                {
                    // trying to use browser back buttons, which may be disallowed if no 'previous' button is present
                    $LEMskipReprocessing=true;
                    $move = "movenext"; // so will re-display the survey
                    $invalidLastPage=true;
                    $vpopup="<script type=\"text/javascript\">\n
                    <!--\n $(document).ready(function(){
                    alert(\"".gT("Please use the LimeSurvey navigation buttons or index.  It appears you attempted to use the browser back button to re-submit a page.", "js")."\");});\n //-->\n
                    </script>\n";
                }
            }

            if (!(isset($_POST['saveall']) || isset($_POST['saveprompt']) || isset($_POST['loadall']) || isset($_GET['sid']) || $LEMskipReprocessing || (isset($move) && (preg_match('/^changelang_/',$move)))))
            {
                $_SESSION[$LEMsessid]['prevstep'] = $_SESSION[$LEMsessid]['step'];
            }
            if (!isset($_SESSION[$LEMsessid]['prevstep']))
            {
                $_SESSION[$LEMsessid]['prevstep']=-1;   // this only happens on re-load
            }
            if (isset($_SESSION[$LEMsessid]['LEMtokenResume']))
            {
                LimeExpressionManager::StartSurvey($thissurvey['sid'], $surveyMode, $surveyOptions, false,$LEMdebugLevel);
                if(isset($_SESSION[$LEMsessid]['maxstep']) && $_SESSION[$LEMsessid]['maxstep']>$_SESSION[$LEMsessid]['step'])
                {
                    LimeExpressionManager::JumpTo($_SESSION[$LEMsessid]['maxstep'], false, false);
                }
                $moveResult = LimeExpressionManager::JumpTo($_SESSION[$LEMsessid]['step'],false,false);   // if late in the survey, will re-validate contents, which may be overkill
                unset($_SESSION[$LEMsessid]['LEMtokenResume']);
            }
            else if (!$LEMskipReprocessing)
                {
                    //Move current step ###########################################################################
                    if (isset($move) && $move == 'moveprev' && ($thissurvey['allowprev'] == 'Y' || $thissurvey['allowjumps'] == 'Y'))
                    {
                        $moveResult = LimeExpressionManager::NavigateBackwards();
                        if ($moveResult['at_start'])
                        {
                            $_SESSION[$LEMsessid]['step'] = 0;
                            unset($moveResult); // so display welcome page again
                        }
                }
                if (isset($move) && $move == "movenext")
                {
                    $moveResult = LimeExpressionManager::NavigateForwards();
                }
                if (isset($move) && ($move == 'movesubmit'))
                {
                    if ($surveyMode == 'survey')
                    {
                        $moveResult = LimeExpressionManager::NavigateForwards();
                    }
                    else
                    {
                        // may be submitting from the navigation bar, in which case need to process all intervening questions
                        // in order to update equations and ensure there are no intervening relevant mandatory or relevant invalid questions
                        $moveResult = LimeExpressionManager::JumpTo($_SESSION[$LEMsessid]['totalsteps'] + 1, false);
                    }
                }
                if (isset($move) && (preg_match('/^changelang_/', $move)))
                {
                    // jump to current step using new language, processing POST values
                    $moveResult = LimeExpressionManager::JumpTo($_SESSION[$LEMsessid]['step'], false, true, false, true);  // do process the POST data
                }
                if (isset($move) && isNumericInt($move) && $thissurvey['allowjumps'] == 'Y')
                {
                    $move = (int) $move;
                    if ($move > 0 && (($move <= $_SESSION[$LEMsessid]['step']) || (isset($_SESSION[$LEMsessid]['maxstep']) && $move <= $_SESSION[$LEMsessid]['maxstep'])))
                    {
                        $moveResult = LimeExpressionManager::JumpTo($move, false);
                    }
                }
                if (!isset($moveResult) && !($surveyMode != 'survey' && $_SESSION[$LEMsessid]['step'] == 0))
                {
                    // Just in case not set via any other means, but don't do this if it is the welcome page
                    $moveResult = LimeExpressionManager::GetLastMoveResult(true);
                    $LEMskipReprocessing=true;
                }
            }

            if (isset($moveResult))
            {
                if ($moveResult['finished'] == true)
                {
                    $move = 'movesubmit';
                }
                else
                {
                    $_SESSION[$LEMsessid]['step'] = $moveResult['seq'] + 1;  // step is index base 1
                    $stepInfo = LimeExpressionManager::GetStepIndexInfo($moveResult['seq']);
                }
                if ($move == "movesubmit" && $moveResult['finished'] == false)
                {
                    // then there are errors, so don't finalize the survey
                    $move = "movenext"; // so will re-display the survey
                    $invalidLastPage = true;
                }
            }

            // We do not keep the participant session anymore when the same browser is used to answer a second time a survey (let's think of a library PC for instance).
            // Previously we used to keep the session and redirect the user to the
            // submit page.

            if ($surveyMode != 'survey' && $_SESSION[$LEMsessid]['step'] == 0)
            {
                $_SESSION[$LEMsessid]['test']=time();
                display_first_page();
                exit;
            }

            //CHECK IF ALL MANDATORY QUESTIONS HAVE BEEN ANSWERED ############################################
            //First, see if we are moving backwards or doing a Save so far, and its OK not to check:
            if (
            (isset($move) && ($move == "moveprev" || (is_int($move) && $_SESSION[$LEMsessid]['prevstep'] == $_SESSION[$LEMsessid]['maxstep']) || $_SESSION[$LEMsessid]['prevstep'] == $_SESSION[$LEMsessid]['step'])) ||
            (isset($_POST['saveall']) && $_POST['saveall'] == gT("Save your responses so far")))
            {
                if (Yii::app()->getConfig('allowmandbackwards'))
                {
                    $backok = "Y";
                }
                else
                {
                    $backok = "N";
                }
            }
            else
            {
                $backok = "N";    // NA, since not moving backwards
            }
            // TODO FIXME
            if ($thissurvey['active'] == "Y") {
                Yii::import("application.libraries.Save");
                $cSave = new Save();
            }
            if ($thissurvey['active'] == "Y" && isset($_POST['saveall']))
            {
                // must do this here to process the POSTed values
                $moveResult = LimeExpressionManager::JumpTo($_SESSION[$LEMsessid]['step'], false);   // by jumping to current step, saves data so far
                if ($thissurvey['tokenanswerspersistence'] != 'Y' || !isset($surveyid) || !Survey::model()->hasTokens($surveyid))
                {
                    $cSave->showsaveform(); // generates a form and exits, awaiting input
                }
                else 
                {
                    // TODO : update lastpage to $_SESSION[$LEMsessid]['step'] in Survey_dynamic
                }
            }

            if ($thissurvey['active'] == "Y" && isset($_POST['saveprompt']))
            {
                // The response from the save form
                // CREATE SAVED CONTROL RECORD USING SAVE FORM INFORMATION
                $flashmessage = $cSave->savedcontrol();

                if (isset($errormsg) && $errormsg != "")
                {
                    $cSave->showsaveform(); // reshow the form if there is an error
                }

                $moveResult = LimeExpressionManager::GetLastMoveResult(true);
                $LEMskipReprocessing=true;

                // TODO - does this work automatically for token answer persistence? Used to be savedsilent()
            }

            //Now, we check mandatory questions if necessary
            //CHECK IF ALL CONDITIONAL MANDATORY QUESTIONS THAT APPLY HAVE BEEN ANSWERED
            global $notanswered;


            if (isset($moveResult) && !$moveResult['finished'])
            {
                $unansweredSQList = $moveResult['unansweredSQs'];
                if (strlen($unansweredSQList) > 0 && $backok != "N")
                {
                    $notanswered = explode('|', $unansweredSQList);
                }
                else
                {
                    $notanswered = array();
                }

                //CHECK INPUT
                $invalidSQList = $moveResult['invalidSQs'];
                if (strlen($invalidSQList) > 0 && $backok != "N")
                {
                    $notvalidated = explode('|', $invalidSQList);
                }
                else
                {
                    $notvalidated = array();
                }
            }

            // CHECK UPLOADED FILES
            // TMSW - Move this into LEM::NavigateForwards?
            $filenotvalidated = checkUploadedFileValidity($surveyid, $move, $backok);

            //SEE IF THIS GROUP SHOULD DISPLAY
            $show_empty_group = false;

            if ($_SESSION[$LEMsessid]['step'] == 0)
                $show_empty_group = true;

            $redata = compact(array_keys(get_defined_vars()));

            //SUBMIT ###############################################################################
            if ((isset($move) && $move == "movesubmit"))
            {
                //                setcookie("limesurvey_timers", "", time() - 3600); // remove the timers cookies   //@todo fix - sometimes results in headers already sent error
                if ($thissurvey['refurl'] == "Y")
                {
                    if (!in_array("refurl", $_SESSION[$LEMsessid]['insertarray'])) //Only add this if it doesn't already exist
                    {
                        $_SESSION[$LEMsessid]['insertarray'][] = "refurl";
                    }
                }
                resetTimers();
                
                //Before doing the "templatereplace()" function, check the $thissurvey['url']
                //field for limereplace stuff, and do transformations!
                $thissurvey['surveyls_url'] = passthruReplace($thissurvey['surveyls_url'], $thissurvey);
                $thissurvey['surveyls_url'] = templatereplace($thissurvey['surveyls_url'], array(), $redata);   // to do INSERTANS substitutions
                
                //END PAGE - COMMIT CHANGES TO DATABASE
                if ($thissurvey['active'] != "Y") //If survey is not active, don't really commit
                {
                    if ($thissurvey['assessments'] == "Y")
                    {
                        $assessments = doAssessment($surveyid);
                    }
                    //Check for assessments
                    if ($thissurvey['assessments'] == "Y" && $assessments)
                    {
                        echo templatereplace(file_get_contents($sTemplatePath."assessment.pstpl"), array(), $redata);
                    }

                    // fetch all filenames from $_SESSIONS['files'] and delete them all
                    // from the /upload/tmp/ directory
                    /* echo "<pre>";print_r($_SESSION);echo "</pre>";
                    for($i = 1; isset($_SESSION[$LEMsessid]['files'][$i]); $i++)
                    {
                    unlink('upload/tmp/'.$_SESSION[$LEMsessid]['files'][$i]['filename']);
                    }
                    */
                    // can't kill session before end message, otherwise INSERTANS doesn't work.
                    $completed = templatereplace($thissurvey['surveyls_endtext'], array(), $redata);
                    $completed .= "<br /><strong><font size='2' color='red'>" . gT("Did Not Save") . "</font></strong><br /><br />\n\n";
                    $completed .= gT("Your survey responses have not been recorded. This survey is not yet active.") . "<br /><br />\n";
                    if ($thissurvey['printanswers'] == 'Y')
                    {
                        // 'Clear all' link is only relevant for survey with printanswers enabled
                        // in other cases the session is cleared at submit time
                        $completed .= "<a href='" . Yii::app()->getController()->createUrl("survey/index/sid/{$surveyid}/move/clearall") . "'>" . gT("Clear Responses") . "</a><br /><br />\n";
                    }
                    killSurveySession($surveyid);
                }
                else //THE FOLLOWING DEALS WITH SUBMITTING ANSWERS AND COMPLETING AN ACTIVE SURVEY
                {
                    if ($thissurvey['usecookie'] == "Y" && $tokensexist != 1) //don't use cookies if tokens are being used
                    {
                        setcookie("LS_" . $surveyid . "_STATUS", "COMPLETE", time() + 31536000); //Cookie will expire in 365 days   
                    }


                    $content = '';
                    $content .= $this->startpage($sTemplatePath, $redata);

                    //Check for assessments
                    if ($thissurvey['assessments'] == "Y")
                    {
                        $assessments = doAssessment($surveyid);
                        if ($assessments)
                        {
                            $content .= templatereplace(file_get_contents($sTemplatePath."assessment.pstpl"), array(), $redata);
                        }
                    }

                    //Update the token if needed and send a confirmation email
                    if (isset($_SESSION['survey_'.$surveyid]['thistoken']))
                    {
                        submittokens();
                    }

                    //Send notifications

                    sendSubmitNotifications($surveyid);


                    $content = '';

                    $content .= $this->startpage($sTemplatePath, $redata);

                    //echo $thissurvey['url'];
                    //Check for assessments
                    if ($thissurvey['assessments'] == "Y")
                    {
                        $assessments = doAssessment($surveyid);
                        if ($assessments)
                        {
                            $content .= templatereplace(file_get_contents($sTemplatePath."assessment.pstpl"), array(), $redata);
                        }
                    }

                    

                    if (trim(strip_tags($thissurvey['surveyls_endtext'])) == '')
                    {
                        $completed = "<br /><span class='success'>" . gT("Thank you!") . "</span><br /><br />\n\n"
                        . gT("Your survey responses have been recorded.") . "<br /><br />\n";
                        
                    }
                    else
                    {
                        $completed = templatereplace($thissurvey['surveyls_endtext'], array(), $redata);
                        
                    }

                    // Link to Print Answer Preview  **********
                    if ($thissurvey['printanswers'] == 'Y')
                    {
                        $url = Yii::app()->getController()->createUrl("printanswers/view/surveyid/{$surveyid}");
                        $completed .= "<br /><br />"
                        . "<a class='printlink' href='$url'  target='_blank'>"
                        . gT("Print your answers.")
                        . "</a><br />\n";
                    }
                    //*****************************************

                    if ($thissurvey['publicstatistics'] == 'Y' && $thissurvey['printanswers'] == 'Y')
                    {
                        $completed .='<br />' . gT("or");
                    }

                    // Link to Public statistics  **********
                    if ($thissurvey['publicstatistics'] == 'Y')
                    {
                        $url = Yii::app()->getController()->createUrl("statistics_user/action/surveyid/{$surveyid}/language/".$_SESSION[$LEMsessid]['s_lang']);
                        $completed .= "<br /><br />"
                        . "<a class='publicstatisticslink' href='$url' target='_blank'>"
                        . gT("View the statistics for this survey.")
                        . "</a><br />\n";
                    }
                    //*****************************************

                    $_SESSION[$LEMsessid]['finished'] = true;
                    $_SESSION[$LEMsessid]['sid'] = $surveyid;

                    
                    if (isset($thissurvey['autoredirect']) && $thissurvey['autoredirect'] == "Y" && $thissurvey['surveyls_url'])
                    {
                        //Automatically redirect the page to the "url" setting for the survey
                        header("Location: {$thissurvey['surveyls_url']}");
                    }

                    
                }
                $redata['completed'] = $completed;
                
                // @todo Remove direct session access.
                $event = new PluginEvent('afterSurveyCompleted');
                $event->set('responseId', $_SESSION[$LEMsessid]['srid']);
                $event->set('surveyId', $surveyid);
                App()->getPluginManager()->dispatchEvent($event);
                if ($event->get('blocks', null) != null)
                {
                    $blocks = array();
                    foreach ($event->get('blocks') as $blockData)
                    {
                        
                        $defaults = array(
                            'class' => array('pluginblock'),
                            'contents' => '',
                            'id' => ''
                        );
                        $blockData = array_merge($defaults, $blockData);
                        $blocks[] = CHtml::tag('div', array('id' => $blockData['id'], 'class' => implode(' ', $blockData['class'])), $blockData['contents']);
                    }
                }
                $redata['completed'] = implode("\n", $blocks) ."\n". $redata['completed'];
                
                
                echo templatereplace(file_get_contents($sTemplatePath."completed.pstpl"), array('completed' => $completed), $redata);
                echo "\n<br />\n";
                if ((($LEMdebugLevel & LEM_DEBUG_TIMING) == LEM_DEBUG_TIMING))
                {
                     $this->twig->getLoader()->addPath($sTemplatePath);
                     $context = array(
                         'survey' => array(
                             'completed' => $thissurvey['surveyls_endtext'],
                             'name' => $thissurvey['surveyls_title']
                         )
                     );
                     $this->twig->display('completed.twig', $context);
                
                }
                else
                {
                    doHeader();
                    echo $content;
                
                    $redata['completed'] = $completed;
                    echo templatereplace(file_get_contents($sTemplatePath."completed.pstpl"), array('completed' => $completed), $redata);
                    echo "\n<br />\n";
                    if ((($LEMdebugLevel & LEM_DEBUG_TIMING) == LEM_DEBUG_TIMING))
                    {
                        echo LimeExpressionManager::GetDebugTimingMessage();
                    }
                    if ((($LEMdebugLevel & LEM_DEBUG_VALIDATION_SUMMARY) == LEM_DEBUG_VALIDATION_SUMMARY))
                    {
                        echo "<table><tr><td align='left'><b>Group/Question Validation Results:</b>" . $moveResult['message'] . "</td></tr></table>\n";
                    }
                    echo templatereplace(file_get_contents($sTemplatePath."endpage.pstpl"), array(), $redata);
                    doFooter();
                }
                // The session cannot be killed until the page is completely rendered
                if ($thissurvey['printanswers'] != 'Y')
                {
                    killSurveySession($surveyid);
                }                
                exit;
            }
        }
        $redata = compact(array_keys(get_defined_vars()));

        // IF GOT THIS FAR, THEN DISPLAY THE ACTIVE GROUP OF QUESTIONSs
        //SEE IF $surveyid EXISTS ####################################################################
        if ($surveyExists < 1)
        {
            //SURVEY DOES NOT EXIST. POLITELY EXIT.
            echo $this->startpage($sTemplatePath, $redata);
            echo "\t<center><br />\n";
            echo "\t" . gT("Sorry. There is no matching survey.") . "<br /></center>&nbsp;\n";
            echo templatereplace(file_get_contents($sTemplatePath."endpage.pstpl"), array(), $redata);
            doFooter();
            exit;
        }
        createFieldMap($surveyid,false,false,$_SESSION[$LEMsessid]['s_lang']);
        //GET GROUP DETAILS

        if ($surveyMode == 'group' && $previewgrp)
        {
            //            setcookie("limesurvey_timers", "0"); //@todo fix - sometimes results in headers already sent error
            $_gid = sanitize_int($param['gid']);

            LimeExpressionManager::StartSurvey($thissurvey['sid'], 'group', $surveyOptions, false, $LEMdebugLevel);
            $gseq = LimeExpressionManager::GetGroupSeq($_gid);
            if ($gseq == -1)
            {
                echo gT('Invalid group number for this survey: ') . $_gid;
                exit;
            }
            $moveResult = LimeExpressionManager::JumpTo($gseq + 1, true);
            if (is_null($moveResult))
            {
                echo gT('This group contains no questions.  You must add questions to this group before you can preview it');
                exit;
            }
            if (isset($moveResult))
            {
                $_SESSION[$LEMsessid]['step'] = $moveResult['seq'] + 1;  // step is index base 1?
            }

            $stepInfo = LimeExpressionManager::GetStepIndexInfo($moveResult['seq']);
            $gid = $stepInfo['gid'];
            $groupname = $stepInfo['gname'];
            $groupdescription = $stepInfo['gtext'];
        }
        else
        {
            if (($show_empty_group) || !isset($_SESSION[$LEMsessid]['grouplist']))
            {
                $gid = -1; // Make sure the gid is unused. This will assure that the foreach (fieldarray as ia) has no effect.
                $groupname = gT("Submit your answers");
                $groupdescription = gT("There are no more questions. Please press the <Submit> button to finish this survey.");
            }
            else if ($surveyMode != 'survey')
                {
                    if ($previewquestion) {
                        $_qid = sanitize_int($param['qid']);
                        LimeExpressionManager::StartSurvey($surveyid, 'question', $surveyOptions, false, $LEMdebugLevel);
                        $qSec       = LimeExpressionManager::GetQuestionSeq($_qid);
                        $moveResult = LimeExpressionManager::JumpTo($qSec+1,true,false,true);
                        $stepInfo   = LimeExpressionManager::GetStepIndexInfo($moveResult['seq']);
                    } else {
                        $stepInfo = LimeExpressionManager::GetStepIndexInfo($moveResult['seq']);
                    }

                    $gid = $stepInfo['gid'];
                    $groupname = $stepInfo['gname'];
                    $groupdescription = $stepInfo['gtext'];
                }
        }
        if ($previewquestion)
        {
            $_SESSION[$LEMsessid]['step'] = 0; //maybe unset it after the question has been displayed?
        }

        if ($_SESSION[$LEMsessid]['step'] > $_SESSION[$LEMsessid]['maxstep'])
        {
            $_SESSION[$LEMsessid]['maxstep'] = $_SESSION[$LEMsessid]['step'];
        }

        // If the survey uses answer persistence and a srid is registered in SESSION
        // then loadanswers from this srid
        /* Only survey mode used this - should all?
        if ($thissurvey['tokenanswerspersistence'] == 'Y' &&
        $thissurvey['anonymized'] == "N" &&
        isset($_SESSION[$LEMsessid]['srid']) &&
        $thissurvey['active'] == "Y")
        {
        loadanswers();
        }
        */

        //******************************************************************************************************
        //PRESENT SURVEY
        //******************************************************************************************************

        $okToShowErrors = (!$previewgrp && (isset($invalidLastPage) || $_SESSION[$LEMsessid]['prevstep'] == $_SESSION[$LEMsessid]['step']));

        Yii::app()->getController()->loadHelper('qanda');
        setNoAnswerMode($thissurvey);

        foreach ($_SESSION[$LEMsessid]['grouplist'] as $gl)
        {
            $gid = $gl[0];
            $qnumber = 0;

            if ($surveyMode != 'survey')
            {
                $onlyThisGID = $stepInfo['gid'];
                if ($onlyThisGID != $gid)
                {
                    continue;
                }
            }

            // TMSW - could iterate through LEM::currentQset instead
            foreach ($_SESSION[$LEMsessid]['questions'] as $key => $q)
            {
                ++$qnumber;
                $q->questioncount = $qnumber; // incremental question count;

                if ((isset($q->randomgid) && $q->randomgid == $gid) || ((!isset($q->randomgid) || !$q->randomgid) && $q->gid == $gid))
                {
                    if ($surveyMode == 'question' && $q->id != $stepInfo['qid'])
                    {
                        continue;
                    }
                    $qidattributes = $q->getAttributeValues();
                    if (!$q->isEquation() && ($qidattributes === false || !isset($qidattributes['hidden']) || $qidattributes['hidden'] == 1))
                    {
                        continue;
                    }

                    foreach($q->getHeaderIncludes() as $key=>$value)
                    {
                        header_includes($key, $value);
                    }

                    $qanda[] = $q;

                    //Display the "mandatory" popup if necessary
                    // TMSW - get question-level error messages - don't call **_popup() directly
                    if ($okToShowErrors && $stepInfo['mandViolation'])
                    {
                        $mandatorypopup = $q->mandatoryPopup($notanswered);
                        $popup =  $q->getPopup($notanswered);
                    }

                    //Display the "validation" popup if necessary
                    if ($okToShowErrors && !$stepInfo['valid'])
                    {
                        list($validationpopup, $vpopup) = validation_popup($notvalidated);
                    }

                    // Display the "file validation" popup if necessary
                    if ($okToShowErrors && isset($filenotvalidated))
                    {
                        list($filevalidationpopup, $fpopup) = file_validation_popup($filenotvalidated);
                    }
                }
                if ($q->fileUpload())
                    $upload_file = TRUE;
            } //end iteration
        }

        if ($surveyMode != 'survey' && isset($thissurvey['showprogress']) && $thissurvey['showprogress'] == 'Y')
        {
            if ($show_empty_group)
            {
                $percentcomplete = makegraph($_SESSION[$LEMsessid]['totalsteps'] + 1, $_SESSION[$LEMsessid]['totalsteps']);
            }
            else
            {
                $percentcomplete = makegraph($_SESSION[$LEMsessid]['step'], $_SESSION[$LEMsessid]['totalsteps']);
            }
        }
        if (!(isset($languagechanger) && strlen($languagechanger) > 0) && function_exists('makeLanguageChangerSurvey'))
        {
            $languagechanger = makeLanguageChangerSurvey($_SESSION[$LEMsessid]['s_lang']);
        }

        //READ TEMPLATES, INSERT DATA AND PRESENT PAGE
        sendCacheHeaders();
        doHeader();

        $redata = compact(array_keys(get_defined_vars()));
        echo $this->startpage($sTemplatePath, $redata);
        //popup need jquery
        if (isset($popup))
        {
            echo $popup;
        }
        if (isset($vpopup))
        {
            echo $vpopup;
        }
        if (isset($fpopup))
        {
            echo $fpopup;
        }

        //ALTER PAGE CLASS TO PROVIDE WHOLE-PAGE ALTERNATION
        if ($surveyMode != 'survey' && $_SESSION[$LEMsessid]['step'] != $_SESSION[$LEMsessid]['prevstep'] ||
        (isset($_SESSION[$LEMsessid]['stepno']) && $_SESSION[$LEMsessid]['stepno'] % 2))
        {
            if (!isset($_SESSION[$LEMsessid]['stepno']))
                $_SESSION[$LEMsessid]['stepno'] = 0;
            if ($_SESSION[$LEMsessid]['step'] != $_SESSION[$LEMsessid]['prevstep'])
                ++$_SESSION[$LEMsessid]['stepno'];
            if ($_SESSION[$LEMsessid]['stepno'] % 2)
            {
                echo "<script type=\"text/javascript\">\n"
                . "  $(\"body\").addClass(\"page-odd\");\n"
                . "</script>\n";
            }
        }

        if (isset($upload_file) && $upload_file)
            echo CHtml::form(array("survey/index"), 'post',array('enctype'=>'multipart/form-data','id'=>'limesurvey','name'=>'limesurvey', 'autocomplete'=>'off'));
        else
            echo CHtml::form(array("survey/index"), 'post',array('id'=>'limesurvey', 'name'=>'limesurvey', 'autocomplete'=>'off'));
        echo sDefaultSubmitHandler();


        if ($surveyMode == 'survey')
        {
            if (isset($thissurvey['showwelcome']) && $thissurvey['showwelcome'] == 'N')
            {
                //Hide the welcome screen if explicitly set
            }
            else
            {
                echo templatereplace(file_get_contents($sTemplatePath."welcome.pstpl"), array(), $redata) . "\n";
            }

            if ($thissurvey['anonymized'] == "Y")
            {
                echo templatereplace(file_get_contents($sTemplatePath."privacy.pstpl"), array(), $redata) . "\n";
            }
        }

        // <-- START THE SURVEY -->
        if ($surveyMode != 'survey')
        {
            echo templatereplace(file_get_contents($sTemplatePath."survey.pstpl"), array(), $redata);
        }

        // the runonce element has been changed from a hidden to a text/display:none one
        // in order to workaround an not-reproduced issue #4453 (lemeur)
        
        echo $this->runOnceJs($radix, $previewgrp);
        
        //Display the "mandatory" message on page if necessary
        $showpopups = Yii::app()->getConfig('showpopups');
        if (!$showpopups && $stepInfo['mandViolation'] && $okToShowErrors)
        {
            echo "<p><span class='errormandatory'>" . gT("One or more mandatory questions have not been answered. You cannot proceed until these have been completed.") . "</span></p>";
        }

        //Display the "validation" message on page if necessary
        if (!$showpopups && !$stepInfo['valid'] && $okToShowErrors)
        {
            echo "<p><span class='errormandatory'>" . gT("One or more questions have not been answered in a valid manner. You cannot proceed until these answers are valid.") . "</span></p>";
        }

        //Display the "file validation" message on page if necessary
        if (!$showpopups && isset($filenotvalidated) && $filenotvalidated == true && $okToShowErrors)
        {
            echo "<p><span class='errormandatory'>" . gT("One or more uploaded files are not in proper format/size. You cannot proceed until these files are valid.") . "</span></p>";
        }

        $_gseq = -1;
        foreach ($_SESSION[$LEMsessid]['grouplist'] as $gl)
        {
            $gid = $gl[0];
            ++$_gseq;
            $groupname = $gl[1];
            $groupdescription = $gl[2];

            if ($surveyMode != 'survey' && $gid != $onlyThisGID)
            {
                continue;
            }

            $redata = compact(array_keys(get_defined_vars()));

            echo "\n\n<!-- START THE GROUP -->\n";
            echo "\n\n<div id='group-$_gseq'";
            $gnoshow = LimeExpressionManager::GroupIsIrrelevantOrHidden($_gseq);
            if  ($gnoshow && !$previewgrp)
            {
                echo " style='display: none;'";
            }
            echo ">\n";
            echo templatereplace(file_get_contents($sTemplatePath."startgroup.pstpl"), array(), $redata);
            echo "\n";

            if (!$previewquestion)
            {
                echo templatereplace(file_get_contents($sTemplatePath."groupdescription.pstpl"), array(), $redata);
            }
            echo "\n";

            echo "\n\n<!-- PRESENT THE QUESTIONS -->\n";

            foreach ($qanda as $q) // one entry per QID
            {
                if ($gid != $q->gid) {
                    continue;
                }

                $qinfo = LimeExpressionManager::GetQuestionStatus($q->id);
                $qqinfo = $qinfo['info']['q'];
                $lastgrouparray = explode("X", $q->fieldname);
                $lastgroup = $lastgrouparray[0] . "X" . $lastgrouparray[1]; // id of the last group, derived from question id
                $lastanswer = $q->fieldname;

                $q_class = $q->questionProperties('class');

                $man_class = '';
                if ($qinfo['info']['mandatory'] == 'Y')
                {
                    $man_class .= ' mandatory';
                }

                if ($qinfo['anyUnanswered'] && $_SESSION[$LEMsessid]['maxstep'] != $_SESSION[$LEMsessid]['step'])
                {
                    $man_class .= ' missing';
                }

                $n_q_display = '';
                if ($qinfo['hidden'] && !$q->isEquation())
                {
                    continue; // skip this one
                }

                if (!$qinfo['relevant'] || ($qinfo['hidden'] && $q->isEquation()))
                {
                    $n_q_display = ' style="display: none;"';
                }

                $question = retrieveAnswers($q);
                //===================================================================
                // The following four variables offer the templating system the
                // capacity to fully control the HTML output for questions making the
                // above echo redundant if desired.
                $question['essentials'] = 'id="question' . $q->id . '"' . $n_q_display;
                $question['class'] = $q_class;
                $question['man_class'] = $man_class;
                $question['code'] = $q->title;
                $question['sgq'] = $q->fieldname;
                $question['aid'] = !empty($qinfo['info']['aid']) ? $qinfo['info']['aid'] : 0;
                $question['sqid'] = !empty($qinfo['info']['sqid']) ? $qinfo['info']['sqid'] : 0;
                $answer = $q->getAnswerHTML();
                //===================================================================
                $help = $qinfo['info']['help'];   // $qa[2];

                $redata = compact(array_keys(get_defined_vars()));
                if (file_exists($sTemplatePath . 'question.twig'))
                {
                    $context = array(
                        'question' => array(
                            'code' => $q->title,
                            'id' => $q->id,
                            'text' => $q->text,
                            'mandatory' => ($q->mandatory =='Y'),
                            'message' => array(
                                'mandatory' => 'This needs to be the mandatory message.'
                            ),
                            'answer' => $answer
                        )
                    );
                    /*
                    echo '<pre>';
                    print_r($qinfo);
                    print_r($question);
                    echo '</pre>';
                     * 
                     */
                    $this->twig->getLoader()->addPath($sTemplatePath);
                    $out = $this->twig->render('question.twig', $context);
                    echo $out;
                }
                else 
                {
                    $question_template = file_get_contents($sTemplatePath.'question.pstpl');
                    if (preg_match('/\{QUESTION_ESSENTIALS\}/', $question_template) === false || preg_match('/\{QUESTION_CLASS\}/', $question_template) === false)
                    {
                        // if {QUESTION_ESSENTIALS} is present in the template but not {QUESTION_CLASS} remove it because you don't want id="" and display="" duplicated.
                        $question_template = str_replace('{QUESTION_ESSENTIALS}', '', $question_template);
                        $question_template = str_replace('{QUESTION_CLASS}', '', $question_template);
                        echo '
                        <!-- NEW QUESTION -->
                        <div id="question' . $q->id . '" class="' . $q_class . $man_class . '"' . $n_q_display . '>';
                        echo templatereplace($question_template, array(), $redata, false, false, $q->id);
                        echo '</div>';
                    }
                    else
                    {
                        // TMSW - eventually refactor so that only substitutes the QUESTION_** fields - doesn't need full power of template replace
                        // TMSW - also, want to return a string, and call templatereplace once on that result string once all done.
                        echo templatereplace($question_template, array(), $redata, false, false, $q->id);
                        if ($surveyMode == 'group') {
                            echo "<input type='hidden' name='lastgroup' value='$lastgroup' id='lastgroup' />\n"; // for counting the time spent on each group
                        }
                        if ($surveyMode == 'question') {
                            echo "<input type='hidden' name='lastanswer' value='$lastanswer' id='lastanswer' />\n";
                        }

                        echo "\n\n<!-- END THE GROUP -->\n";
                        echo templatereplace(file_get_contents($sTemplatePath."endgroup.pstpl"), array(), $redata);
                        echo "\n\n</div>\n";
                    }
                }
            }
        }


        LimeExpressionManager::FinishProcessingGroup($LEMskipReprocessing);
        echo LimeExpressionManager::GetRelevanceAndTailoringJavaScript();
        LimeExpressionManager::FinishProcessingPage();

        if (!$previewgrp && !$previewquestion)
        {
            echo $this->navigator($surveyid);
        }

        if (($LEMdebugLevel & LEM_DEBUG_TIMING) == LEM_DEBUG_TIMING)
        {
            echo LimeExpressionManager::GetDebugTimingMessage();
        }
        if (($LEMdebugLevel & LEM_DEBUG_VALIDATION_SUMMARY) == LEM_DEBUG_VALIDATION_SUMMARY)
        {
            echo "<table><tr><td align='left'><b>Group/Question Validation Results:</b>" . $moveResult['message'] . "</td></tr></table>\n";
        }
        echo "</form>\n";

        echo templatereplace(file_get_contents($sTemplatePath."endpage.pstpl"), array(), $redata);

        echo "\n";

        doFooter();

    }
    
    
    public function __construct() {
        App()->loadHelper('twig');
        $this->twig = Twig::getTwigEnvironment(array('language' => App()->lang->langcode));
                
    }
    protected function runOnceJs($radix, $previewgrp)
    {
        $out = <<<END
<input type='text' id='runonce' value='0' style='display: none;'/>
<!-- JAVASCRIPT FOR CONDITIONAL QUESTIONS -->
<script type='text/javascript'>
<!--
var LEMradix='$radix';
var numRegex = new RegExp('[^-' + LEMradix + '0-9]','g');
var intRegex = new RegExp('[^-0-9]','g');

function fixnum_checkconditions(value, name, type, evt_type, intonly)
{
    newval = new String(value);
    if (typeof intonly !=='undefined' && intonly==1) {
        newval = newval.replace(intRegex,'');
    }
    else {
        newval = newval.replace(numRegex,'');
    }
    if (LEMradix === ',') {
        newval = newval.split(',').join('.');
    }
    if (newval != '-' && newval != '.' && newval != '-.' && newval != parseFloat(newval)) {
        newval = '';
    }
    displayVal = newval;
    if (LEMradix === ',') {
        displayVal = displayVal.split('.').join(',');
    }
    if (name.match(/other$/)) {
        $('#answer'+name+'text').val(displayVal);
    }
    $('#answer'+name).val(displayVal);

    if (typeof evt_type === 'undefined')
    {
        evt_type = 'onchange';
    }
    checkconditions(newval, name, type, evt_type);
}

function checkconditions(value, name, type, evt_type)
{
    if (typeof evt_type === 'undefined')
    {
        evt_type = 'onchange';
    }
    if (type == 'radio' || type == 'select-one')
    {
        $('#java'+name).val(value);
    }
    else if (type == 'checkbox')
    {
        if ($('#answer'+name).is(':checked'))
        {
            $('#java'+name).val('Y');
        } else
        {
            $('#java'+name).val('');
        }
    }
    else if (type == 'text' && name.match(/other$/))
    {
        $('#java'+name).val(value);
    }
    ExprMgr_process_relevance_and_tailoring(evt_type,name,type);
}
END;
        if ($previewgrp)
        {
            $out .= <<<END
$('#relevanceG' + LEMgseq).val(1);
$(document).ready(function() {
    $('#group-' + LEMgseq).show();
});
$(document).change(function() {
    $('#group-' + LEMgseq).show();
});
$(document).bind('keydown',function(e) {
    if (e.keyCode == 9) {
        $('#group-' + LEMgseq).show();
        return true;
    }
    return true;
});
END;
        }
        $out .= <<<END
// -->
</script>   
END;
        return $out;
    }
    
    
    protected function startpage($sTemplatePath, $redata)
    {
        
        $out = templatereplace(file_get_contents($sTemplatePath."startpage.pstpl"), array(), $redata);
        App()->getClientScript()->registerScriptFile(App()->getConfig('third_party') . 'jquery/jquery.js');
        App()->getClientScript()->registerScriptFile(App()->getConfig('third_party') . 'jquery-ui/js/jquery-ui-1.9.2.custom.js');
        App()->getClientScript()->registerScriptFile(App()->getConfig('generalscripts'). 'jquery/jquery.ui.touch-punch.min.js');
        App()->getClientScript()->registerScriptFile(App()->getConfig('generalscripts'). 'survey_runtime.js');
        App()->getClientScript()->registerScriptFile(App()->getConfig('generalscripts'). 'survey_nav.js');
        
        App()->getClientScript()->renderHead($out);
        return $out;
    }
    
    
    protected function index($surveyId)
    {
        // Render it using twig also; for testing.
        $survey = getSurveyInfo($surveyId);
        if (file_exists(getTemplatePath($survey['template']) . '/index.twig'))
        {
            $this->twig->getLoader()->addPath(getTemplatePath($survey['template']));
            
            $context = array(
                'submittable' => ($_SESSION["survey_$surveyId"]['maxstep'] == $_SESSION["survey_$surveyId"]['totalsteps']),
                'groups' => array()
            );
            foreach (LimeExpressionManager::GetStepIndexInfo() as $index => $step)
            {
                // Check if the $step is in a new group.
                if (!isset($context['groups'][$step['gid']]))
                {
                    $context['groups'][$step['gid']] = array(
                        'questions' => array(),
                        'title' => $step['gname']
                    );
                }
                // Add the question info to the array.
                if (!isset($step['qtext']))
                {
                    $step['qtext'] = 'is not set';
                    
                }
                $context['groups'][$step['gid']]['questions'][] = array(
                    'step' => $index + 1,
                    'title' => LimeExpressionManager::ProcessString($step['qtext'])
                    
                );
            }
            $this->twig->display('index.twig', $context);
        }
    }
    
    public function navigator($surveyId)
    {
        $survey = getSurveyInfo($surveyId);
        $navigator = surveymover(); //This gets globalised in the templatereplace function
        $redata = compact(array_keys(get_defined_vars()));

        // Use Twig.
        if (file_exists(getTemplatePath($survey['template'])."/navigator.twig"))
        {
            $options = array(
                'move' => 'clearall',
                'lang' => $survey['language'],
            );
            if (returnGlobal('token'))
            {
                $options['token'] = urlencode(trim(sanitize_token(strip_tags(returnGlobal('token')))));
            }
            App()->getClientScript()->corePackages = array();

            //var_dump($_SESSION['survey_'.$surveyId]);
            $data = array(
                'navigator' => array(
                    'next' => !($_SESSION["survey_$surveyId"]['step'] == $_SESSION["survey_$surveyId"]['totalsteps']),
                    'submit' => ($_SESSION["survey_$surveyId"]['maxstep'] == $_SESSION["survey_$surveyId"]['totalsteps']),
                    'previous' => ($survey['allowprev'] == 'Y'),
                    'clearall' => true,
                    'save' => ($survey['allowsave'] == 'Y'),
                    'load' => ($survey['allowsave'] == 'Y') // @todo Add check to see if data can be loaded.
                ),
                'survey' => array(
                    'active' => ($survey['active'] == 'Y')
                )
            );
            $this->twig->getLoader()->addPath(getTemplatePath($survey['template']));
            $navigator = $this->twig->render("navigator.twig", $data);
            App()->getClientScript()->render($navigator);
            echo $navigator;
        }
        else
        {
            echo "\n\n<!-- PRESENT THE NAVIGATOR -->\n";
            echo templatereplace(file_get_contents($sTemplatePath."navigator.pstpl"), array(), $redata);
            echo "\n";
            if ($survey['active'] != "Y")
            {
                echo "<p style='text-align:center' class='error'>" . $this->lang->gT("This survey is currently not active. You will not be able to save your responses.") . "</p>\n";
            }
        }

        

        echo $this->index($surveyId);

        echo "<input type='hidden' name='thisstep' value='{$_SESSION["survey_$surveyId"]['step']}' id='thisstep' />\n";
        echo "<input type='hidden' name='sid' value=' $surveyId' id='sid' />\n";
        echo "<input type='hidden' name='start_time' value='" . time() . "' id='start_time' />\n";
        $_SESSION["survey_$surveyId"]['LEMpostKey'] = mt_rand();
        echo "<input type='hidden' name='LEMpostKey' value='{$_SESSION["survey_$surveyId"]['LEMpostKey']}' id='LEMpostKey' />\n";

        if (isset($token) && !empty($token))
        {
            echo "\n<input type='hidden' name='token' value='$token' id='token' />\n";
        }
    }
}
