<?php
class MultinumericalQuestion extends QuestionModule
{
    protected $children;
    public function getAnswerHTML()
    {
        global $thissurvey;

        
        $extraclass ="";
        $checkconditionFunction = "fixnum_checkconditions";
        $aQuestionAttributes = $this->getAttributeValues();
        $answer='';
        $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
        $sSeperator = $sSeperator['seperator'];
        //Must turn on the "numbers only javascript"
        $extraclass .=" numberonly";
        if (intval(trim($aQuestionAttributes['maximum_chars']))>0)
        {
            // Only maxlength attribute, use textarea[maxlength] jquery selector for textarea
            $maximum_chars= intval(trim($aQuestionAttributes['maximum_chars']));
            $maxlength= "maxlength='{$maximum_chars}' ";
            $extraclass .=" maxchars maxchars-".$maximum_chars;
        }
        else
        {
            $maxlength= "25";
        }

        if (trim($aQuestionAttributes['prefix'][$_SESSION['survey_'.$this->surveyid]['s_lang']])!='') {
            $prefix=$aQuestionAttributes['prefix'][$_SESSION['survey_'.$this->surveyid]['s_lang']];
            $extraclass .=" withprefix";
        }
        else
        {
            $prefix = '';
        }

        if (trim($aQuestionAttributes['suffix'][$_SESSION['survey_'.$this->surveyid]['s_lang']])!='') {
            $suffix=$aQuestionAttributes['suffix'][$_SESSION['survey_'.$this->surveyid]['s_lang']];
            $extraclass .=" withsuffix";
        }
        else
        {
            $suffix = '';
        }

        if ($thissurvey['nokeyboard']=='Y')
        {
            includeKeypad();
            $kpclass = "num-keypad";
            $extraclass .=" keypad";
        }
        else
        {
            $kpclass = "";
        }

        $numbersonly_slider = '';

        if (trim($aQuestionAttributes['text_input_width'])!='')
        {
            $tiwidth=$aQuestionAttributes['text_input_width'];
            $extraclass .=" inputwidth".trim($aQuestionAttributes['text_input_width']);
        }
        else
        {
            $tiwidth=10;
        }
        if ($aQuestionAttributes['slider_layout']==1)
        {
            $slider_layout=true;
            $extraclass .=" withslider";
            if (trim($aQuestionAttributes['slider_accuracy'])!='')
            {
                //$slider_divisor = 1 / $slider_accuracy['value'];
                $decimnumber = strlen($aQuestionAttributes['slider_accuracy']) - strpos($aQuestionAttributes['slider_accuracy'],'.') -1;
                $slider_divisor = pow(10,$decimnumber);
                $slider_stepping = $aQuestionAttributes['slider_accuracy'] * $slider_divisor;
            }
            else
            {
                $slider_divisor = 1;
                $slider_stepping = 1;
            }

            if (trim($aQuestionAttributes['slider_min'])!='')
            {
                $slider_mintext = $aQuestionAttributes['slider_min'];
                $slider_min = $aQuestionAttributes['slider_min'] * $slider_divisor;
            }
            else
            {
                $slider_mintext = 0;
                $slider_min = 0;
            }
            if (trim($aQuestionAttributes['slider_max'])!='')
            {
                $slider_maxtext = $aQuestionAttributes['slider_max'];
                $slider_max = $aQuestionAttributes['slider_max'] * $slider_divisor;
            }
            else
            {
                $slider_maxtext = "100";
                $slider_max = 100 * $slider_divisor;
            }
            if (trim($aQuestionAttributes['slider_default'])!='')
            {
                $slider_default = $aQuestionAttributes['slider_default'];
            }
            else
            {
                $slider_default = '';
            }
            if ($slider_default == '' && $aQuestionAttributes['slider_middlestart']==1)
            {
                $slider_middlestart = intval(($slider_max + $slider_min)/2);
            }
            else
            {
                $slider_middlestart = '';
            }

            if (trim($aQuestionAttributes['slider_separator'])!='')
            {
                $slider_separator = $aQuestionAttributes['slider_separator'];
            }
            else
            {
                $slider_separator = '';
            }
        }
        else
        {
            $slider_layout = false;
        }
        $hidetip=$aQuestionAttributes['hide_tip'];
        if ($slider_layout === true) // auto hide tip when using sliders
        {
            $hidetip=1;
        }

        $ansresult = $this->getChildren();
        $anscount = count($ansresult)*2;
        //$answer .= "\t<input type='hidden' name='MULTI$this->fieldname' value='$anscount'>\n";
        $fn = 1;

        $answer_main = '';

        if ($anscount==0)
        {
            $answer_main .= '    <li>'.gT('Error: This question has no answers.')."</li>\n";
        }
        else
        {
            $label_width = 0;
            foreach($ansresult as $ansrow)
            {
                $myfname = $this->fieldname.$ansrow['title'];
                if ($ansrow['question'] == "") {$ansrow['question'] = "&nbsp;";}
                if ($slider_layout === false || $slider_separator == '')
                {
                    $theanswer = $ansrow['question'];
                    $sliderleft='';
                    $sliderright='';
                }
                else
                {
                    $answer_and_slider_array=explode($slider_separator,$ansrow['question']);
                    if (isset($answer_and_slider_array[0]))
                        $theanswer=$answer_and_slider_array[0];
                    else
                        $theanswer = '';
                    if (isset($answer_and_slider_array[1]))
                        $sliderleft=$answer_and_slider_array[1];
                    else
                        $sliderleft = '';
                    if (isset($answer_and_slider_array[2]))
                        $sliderright=$answer_and_slider_array[2];
                    else
                        $sliderright = '';

                    $sliderleft="<div class=\"slider_lefttext\">$sliderleft</div>";
                    $sliderright="<div class=\"slider_righttext\">$sliderright</div>";
                }

                // color code missing mandatory questions red
                if ($this->mandatory=='Y' && (($_SESSION['survey_'.$this->surveyid]['step'] == $_SESSION['survey_'.$this->surveyid]['prevstep'])
                        || ($_SESSION['survey_'.$this->surveyid]['maxstep'] > $_SESSION['survey_'.$this->surveyid]['step']))
                        && $_SESSION['survey_'.$this->surveyid][$myfname] == '') {
                    $theanswer = "<span class='errormandatory'>{$theanswer}</span>";
                }

                list($htmltbody2, $hiddenfield)=return_array_filter_strings($this, $aQuestionAttributes, $thissurvey, $ansrow, $myfname, '', $myfname, "li","question-item answer-item text-item numeric-item".$extraclass);
                $answer_main .= "\t$htmltbody2\n";
                if ($slider_layout === false)
                {
                    $answer_main .= "<label for=\"answer$myfname\">{$theanswer}</label>\n";
                }
                else
                {
                    $answer_main .= "<label for=\"answer$myfname\" class=\"slider-label\">{$theanswer}</label>\n";
                }

                if($label_width < strlen(trim(strip_tags($ansrow['question']))))
                {
                    $label_width = strlen(trim(strip_tags($ansrow['question'])));
                }

                if ($slider_layout === false)
                {
                    $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
                    $sSeperator = $sSeperator['seperator'];

                    $answer_main .= "<span class=\"input\">\n\t".$prefix."\n\t<input class=\"text $kpclass\" type=\"text\" size=\"".$tiwidth.'" name="'.$myfname.'" id="answer'.$myfname.'" value="';
                    if (isset($_SESSION['survey_'.$this->surveyid][$myfname]))
                    {
                        $dispVal = str_replace('.',$sSeperator,$_SESSION['survey_'.$this->surveyid][$myfname]);
                        $answer_main .= $dispVal;
                    }

                    $answer_main .= '" onkeyup="'.$checkconditionFunction.'(this.value, this.name, this.type);" '." {$maxlength} />\n\t".$suffix."\n</span>\n\t</li>\n";
                }
                else
                {
                    if ($aQuestionAttributes['slider_showminmax']==1)
                    {
                        //$slider_showmin=$slider_min;
                        $slider_showmin= "\t<div id=\"slider-left-$myfname\" class=\"slider_showmin\">$slider_mintext</div>\n";
                        $slider_showmax= "\t<div id=\"slider-right-$myfname\" class=\"slider_showmax\">$slider_maxtext</div>\n";
                    }
                    else
                    {
                        $slider_showmin='';
                        $slider_showmax='';
                    }
                    if (isset($_SESSION['survey_'.$this->surveyid][$myfname]) && $_SESSION['survey_'.$this->surveyid][$myfname] != '')
                    {
                        $slider_startvalue = $_SESSION['survey_'.$this->surveyid][$myfname] * $slider_divisor;
                        $displaycallout_atstart=1;
                    }
                    elseif ($slider_default != "")
                    {
                        $slider_startvalue = $slider_default * $slider_divisor;
                        $displaycallout_atstart=1;
                    }
                    elseif ($slider_middlestart != '')
                    {
                        $slider_startvalue = $slider_middlestart;
                        $displaycallout_atstart=0;
                    }
                    else
                    {
                        $slider_startvalue = 'NULL';
                        $displaycallout_atstart=0;
                    }
                    $answer_main .= "$sliderleft<div id='container-$myfname' class='multinum-slider'>\n"
                    . "\t<input type=\"text\" id=\"slider-modifiedstate-$myfname\" value=\"$displaycallout_atstart\" style=\"display: none;\" />\n"
                    . "\t<input type=\"text\" id=\"slider-param-min-$myfname\" value=\"$slider_min\" style=\"display: none;\" />\n"
                    . "\t<input type=\"text\" id=\"slider-param-max-$myfname\" value=\"$slider_max\" style=\"display: none;\" />\n"
                    . "\t<input type=\"text\" id=\"slider-param-stepping-$myfname\" value=\"$slider_stepping\" style=\"display: none;\" />\n"
                    . "\t<input type=\"text\" id=\"slider-param-divisor-$myfname\" value=\"$slider_divisor\" style=\"display: none;\" />\n"
                    . "\t<input type=\"text\" id=\"slider-param-startvalue-$myfname\" value='$slider_startvalue' style=\"display: none;\" />\n"
                    . "\t<input type=\"text\" id=\"slider-onchange-js-$myfname\" value=\"$numbersonly_slider\" style=\"display: none;\" />\n"
                    . "\t<input type=\"text\" id=\"slider-prefix-$myfname\" value=\"$prefix\" style=\"display: none;\" />\n"
                    . "\t<input type=\"text\" id=\"slider-suffix-$myfname\" value=\"$suffix\" style=\"display: none;\" />\n"
                    . "<div id=\"slider-$myfname\" class=\"ui-slider-1\">\n"
                    .  $slider_showmin
                    . "<div class=\"slider_callout\" id=\"slider-callout-$myfname\"></div>\n"
                    . "<div class=\"ui-slider-handle\" id=\"slider-handle-$myfname\"></div>\n"
                    . $slider_showmax
                    . "\t</div>"
                    . "</div>$sliderright\n"
                    . "<input class=\"text\" type=\"text\" name=\"$myfname\" id=\"answer$myfname\" value=\"";
                    if (isset($_SESSION['survey_'.$this->surveyid][$myfname]) && $_SESSION['survey_'.$this->surveyid][$myfname] != '')
                    {
                        $answer_main .= $_SESSION['survey_'.$this->surveyid][$myfname];
                    }
                    elseif ($slider_default != "")
                    {
                        $answer_main .= $slider_default;
                    }
                    $answer_main .= "\"/>\n"
                    . "\t</li>\n";
                }

                $fn++;
            }
            $question_tip = '';
            if($hidetip == 0)
            {
                $question_tip .= '<p class="tip">'.gT('Only numbers may be entered in these fields')."</p>\n";
            }

            if (trim($aQuestionAttributes['equals_num_value']) != ''
            || trim($aQuestionAttributes['min_num_value']) != ''
            || trim($aQuestionAttributes['max_num_value']) != ''

            )
            {
                $qinfo = LimeExpressionManager::GetQuestionStatus($this->id);
                if (trim($aQuestionAttributes['equals_num_value']) != '')
                {
                    $answer_main .= "\t<li class='multiplenumerichelp help-item'>\n"
                    . "<span class=\"label\">".gT('Remaining: ')."</span>\n"
                    . "<span id=\"remainingvalue_{$this->id}\" class=\"dynamic_remaining\">$prefix\n"
                    . "{" . $qinfo['sumRemainingEqn'] . "}\n"
                    . "$suffix</span>\n"
                    . "\t</li>\n";
                }

                $answer_main .= "\t<li class='multiplenumerichelp  help-item'>\n"
                . "<span class=\"label\">".gT('Total: ')."</span>\n"
                . "<span id=\"totalvalue_{$this->id}\" class=\"dynamic_sum\">$prefix\n"
                . "{" . $qinfo['sumEqn'] . "}\n"
                . "$suffix</span>\n"
                . "\t</li>\n";
            }
            $answer .= $question_tip."<ul class=\"subquestions-list questions-list text-list numeric-list\">\n".$answer_main."</ul>\n";
        }
        //just added these here so its easy to change in one place
        $errorClass = 'tip problem';
        $goodClass = 'tip good';

        $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
        $sSeperator = $sSeperator['seperator'];

        return $answer;
    }

    public function getDataEntry($idrow, &$fnames, $language)
    {
        $output = $this->sq.'&nbsp;';
        $output .= CHtml::textField($this->fieldname, $idrow[$this->fieldname]);
        return $output;
    }

    protected function getChildren()
    {
        if ($this->children) return $this->children;
        $aQuestionAttributes = $this->getAttributeValues();
        if ($aQuestionAttributes['random_order']==1) {
            $ansquery = "SELECT * FROM {{questions}} WHERE parent_qid=$this->id AND scale_id=0 AND language='".$_SESSION['survey_'.$this->surveyid]['s_lang']."' ORDER BY ".dbRandom();
        }
        else
        {
            $ansquery = "SELECT * FROM {{questions}} WHERE parent_qid=$this->id AND scale_id=0 AND language='".$_SESSION['survey_'.$this->surveyid]['s_lang']."' ORDER BY question_order";
        }
        return $this->children = dbExecuteAssoc($ansquery)->readAll();  //Checked
    }

    public function getHeaderIncludes()
    {
        $aQuestionAttributes = $this->getAttributeValues();
        if ($aQuestionAttributes['slider_layout']==1)
        {
            return array(Yii::app()->getConfig("generalscripts").'jquery/lime-slider.js' => 'js');
        }
        else
        {
            return array();
        }
    }

    public function createFieldmap()
    {
        $map = array();
        $abrows = getSubQuestions($this);
        foreach ($abrows as $abrow)
        {
            $fieldname="{$this->surveyid}X{$this->gid}X{$this->id}{$abrow['title']}";
            $q = clone $this;
            if(isset($this->defaults) && isset($this->defaults[$abrow['qid']])) $q->default=$this->defaults[$abrow['qid']];
            $q->fieldname = $fieldname;
            $q->aid=$abrow['title'];
            $q->question=$abrow['question'];
            $q->sq=$abrow['question'];
            $q->sqid=$abrow['qid'];
            $q->preg=$this->haspreg;
            $map[$fieldname]=$q;
        }
        return $map;
    }

    public function statisticsFieldmap()
    {
        return true;
    }

    public function filter($value, $type)
    {
        if (trim($value)=='') {
            return NULL;
        }
        switch ($type)
        {
            case 'get':
            case 'post':
            return sanitize_float($value);
            case 'db':
            case 'dataentry':
            case 'dataentryinsert':
            return $value;
        }
    }

    public function loadAnswer($value)
    {
        return $value==null?'':$value;
    }

    public function getDBField()
    {
        return 'float';
    }

    public function adjustSize($size)
    {
        return $size . '.' . ($size-1);
    }

    public function onlyNumeric()
    {
        return true;
    }

    public function getCsuffix()
    {
        return $this->aid;
    }

    public function getSqsuffix()
    {
        return '_' . $this->aid;
    }

    public function getVarName()
    {
        return $this->title . '_' . $this->aid;
    }

    public function getQuestion()
    {
        return $this->sq;
    }

    public function getRowDivID()
    {
        return $this->fieldname;
    }

    public function getPregSQ($sgqaNaming, $sq)
    {
        $sgqa = substr($sq['jsVarName'],4);
        if ($sgqaNaming)
        {
            return '(if(is_empty('.$sgqa.'.NAOK),0,!regexMatch("' . $this->preg . '", ' . $sgqa . '.NAOK)))';
        }
        else
        {
            return '(if(is_empty('.$sq['varName'].'.NAOK),0,!regexMatch("' . $this->preg . '", ' . $sq['varName'] . '.NAOK)))';
        }
    }

    public function getPregEqn($sgqaNaming, $sq)
    {
        $sgqa = substr($sq['jsVarName'],4);
        if ($sgqaNaming)
        {
            return '(is_empty('.$sgqa.'.NAOK) || regexMatch("' . $this->preg . '", ' . $sgqa . '.NAOK))';
        }
        else
        {
            return '(is_empty('.$sq['varName'].'.NAOK) || regexMatch("' . $this->preg . '", ' . $sq['varName'] . '.NAOK))';
        }
    }

    public function getDataEntryView($language)
    {
        $deaquery = "SELECT question,title FROM {{questions}} WHERE parent_qid={$this->id} AND language='{Yii::app()->getLanguage()}' ORDER BY question_order";
        $dearesult = dbExecuteAssoc($deaquery);
        $dearesult = $dearesult->readAll();

        $output = "<table>";
        foreach ($dearesult as $dearow)
        {
            $output .= "<tr><td align='right'>";
            $output .= "{$dearow['question']}";
            $output .= "</td>";
            $output .= "<td><input type='text' name='{$this->fieldname}{$dearow['title']}' /></td>";
            $output .= "</tr>";
        }
        $output .= "</table>";
        return $output;
    }

    public function compareField($sgqa, $sq)
    {
        return $sgqa == $sq['rowdivid'] || $sgqa == ($sq['rowdivid'] . 'comment');
    }

    public function includeRelevanceStatus()
    {
        return true;
    }

    public function availableOptions()
    {
        return array('other' => false, 'valid' => true, 'mandatory' => true);
    }

    public function getTypeHelp($language)
    {
        return $language->gT("Please write your answer(s) here:");
    }

    public function getPrintAnswers($language)
    {
        $qidattributes = $this->getAttributeValues();
        $mearesult=Questions::model()->getAllRecords("parent_qid='{$this->id}' AND language='{Yii::app()->getLanguage()}'", array('question_order'));
        $output = '';

        foreach ($mearesult->readAll() as $mearow)
        {
            if (isset($qidattributes['slider_layout']) && $qidattributes['slider_layout']==1)
            {
              $mearow['question']=explode(':',$mearow['question']);
              $mearow['question']=$mearow['question'][0];
            }
            $output .=  "\t<li>\n\t\t<span>".$mearow['question']."</span>\n\t\t".printablesurvey::input_type_image('text',$mearow['question'],16);
            $output .= (Yii::app()->getConfig('showsgqacode') ? " (".$fieldname.$mearow['title'].") " : '')."\n\t</li>\n";
        }
        $output =  "\n<ul>\n".$output."</ul>\n";
        return $output;
    }

    public function getPrintPDF($language)
    {
        $mearesult=Questions::model()->getAllRecords("parent_qid='{$this->id}' AND language='{Yii::app()->getLanguage()}'", array('question_order'));

        $output = array();
        foreach ($mearesult->readAll() as $mearow)
        {
            $output = $mearow['question'].": ____________________";
        }
        return $output;
    }

    public function getConditionAnswers()
    {
        
        $canswers = array();

        $aresult = Questions::model()->findAllByAttributes(array('parent_qid' => $this->id, 'language' => Survey::model()->findByPk($this->surveyid)->language), array('order' => 'question_order desc'));

        foreach ($aresult as $arows)
        {
            // Only Show No-Answer if question is not mandatory
            if ($this->mandatory != 'Y')
            {
                $canswers[]=array($this->surveyid.'X'.$this->gid.'X'.$this->id.$arows['title'], "", gT("No answer"));
            }
        } //while

        return $canswers;
    }

    public function getConditionQuestions()
    {
        
        $cquestions = array();

        $aresult = Questions::model()->findAllByAttributes(array('parent_qid' => $this->id, 'language' => Survey::model()->findByPk($this->surveyid)->language), array('order' => 'question_order desc'));

        foreach ($aresult as $arows)
        {
            $shortanswer = "{$arows['title']}: [" . strip_tags($arows['question']) . "]";
            $shortquestion=$this->title.":$shortanswer ".strip_tags($this->text);
            $cquestions[]=array($shortquestion, $this->id, false, $this->surveyid.'X'.$this->gid.'X'.$this->id.$arows['title']);
        } //while

        return $cquestions;
    }

    public function QueXMLAppendAnswers(&$question)
    {
        global $dom;
        $response = $dom->createElement("response");
        $response->setAttribute("varName", $this->surveyid . 'X' . $this->gid . 'X' . $this->id);
        quexml_create_subQuestions($question,$this->id,$this->surveyid.'X'.$this->gid.'X'.$this->id);
        $response->appendChild(QueXMLCreateFree("integer",quexml_get_lengthth($this->id,"maximum_chars","10"),""));
        $question->appendChild($response);
    }

    public function getStatisticsFilter()
    {
        $result = Questions::model()->getQuestionsForStatistics('title as code, question as answer', "parent_qid=$flt[0] AND language = '{$language}'", 'question_order');
    }
    
    public function availableAttributes($attr = false)
    {
        $attrs=array("array_filter","array_filter_exclude","array_filter_style","equals_num_value","em_validation_q","em_validation_q_tip","em_validation_sq","em_validation_sq_tip","exclude_all_others","statistics_showgraph","statistics_graphtype","hide_tip","hidden","max_answers","max_num_value","max_num_value_n","maximum_chars","min_answers","min_num_value","min_num_value_n","page_break","prefix","public_statistics","random_order","parent_order","slider_layout","slider_min","slider_max","slider_accuracy","slider_default","slider_middlestart","slider_showminmax","slider_separator","suffix","text_input_width","random_group","value_range_allows_missing");
        return $attr?in_array($attr,$attrs):$attrs;
    }

    public function questionProperties($prop = false)
    {
        
        $props=array('description' => gT("Multiple Numerical Input"),'group' => gT("Mask questions"),'class' => 'numeric-multi','hasdefaultvalues' => 1,'subquestions' => 1,'assessable' => 1,'answerscales' => 0,'enum' => 0);
        return $prop?$props[$prop]:$props;
    }
}
?>