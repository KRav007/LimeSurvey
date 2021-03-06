<?php
class NumericalQuestion extends QuestionModule
{
    public function getAnswerHTML()
    {
        global $thissurvey;

        
        $extraclass ="";
        $answertypeclass = "numeric";
        $checkconditionFunction = "fixnum_checkconditions";
        $aQuestionAttributes = $this->getAttributeValues();
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
        if (intval(trim($aQuestionAttributes['maximum_chars']))>0 && intval(trim($aQuestionAttributes['maximum_chars']))<20)
        {
            // Only maxlength attribute, use textarea[maxlength] jquery selector for textarea
            $maximum_chars= intval(trim($aQuestionAttributes['maximum_chars']));
            $maxlength= "maxlength='{$maximum_chars}' ";
            $extraclass .=" maxchars maxchars-".$maximum_chars;
        }
        else
        {
            $maxlength= "maxlength='20' ";
        }
        if (trim($aQuestionAttributes['text_input_width'])!='')
        {
            $tiwidth=$aQuestionAttributes['text_input_width'];
            $extraclass .=" inputwidth-".trim($aQuestionAttributes['text_input_width']);
        }
        else
        {
            $tiwidth=10;
        }

        $fValue=$_SESSION['survey_'.$this->surveyid][$this->fieldname];
        if(strpos($fValue,"."))
        {
            $fValue=rtrim(rtrim($fValue,"0"),".");
        }
        $integeronly=0;
        if (trim($aQuestionAttributes['num_value_int_only'])==1)
        {
            $integeronly=1;
            $extraclass .=" integeronly";
            $answertypeclass .= " integeronly";
            if(is_numeric($fValue))
            {
                $fValue=number_format($fValue, 0, '', '');
            }
        }
        $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
        $sSeperator = $sSeperator['seperator'];
        $fValue = str_replace('.',$sSeperator,$fValue);

        if ($thissurvey['nokeyboard']=='Y')
        {
            includeKeypad();
            $extraclass .=" inputkeypad";
            $answertypeclass = "num-keypad";
        }
        else
        {
            $kpclass = "";
        }
        // --> START NEW FEATURE - SAVE
        $answer = "<p class='question answer-item text-item numeric-item {$extraclass}'>"
        . " <label for='answer{$this->fieldname}' class='hide label'>{gT('Answer')}</label>\n$prefix\t"
        . "<input class='text {$answertypeclass}' type=\"text\" size=\"$tiwidth\" name=\"$this->fieldname\"  title=\"".gT('Only numbers may be entered in this field.')."\" "
        . "id=\"answer{$this->fieldname}\" value=\"{$fValue}\" onkeyup=\"{$checkconditionFunction}(this.value, this.name, this.type,'onchange',{$integeronly})\" "
        . " {$maxlength} />\t{$suffix}\n</p>\n";
        if ($aQuestionAttributes['hide_tip']==0)
        {
            $answer .= "<p class=\"tip\">".gT('Only numbers may be entered in this field.')."</p>\n";
        }

        // --> END NEW FEATURE - SAVE

        return $answer;
    }

    public function getDataEntry($idrow, &$fnames, $language)
    {
        return "\t<input type='text' name='{$this->fieldname}' value='{$idrow[$this->fieldname]}' "
        ."onkeypress=\"return goodchars(event,'0123456789.,')\" />\n";
    }

    public function getExtendedAnswer($value, $language)
    {
        if(strpos($value,".")!==false)
        {
            $value=rtrim(rtrim($value,"0"),".");
        }
        $aQuestionAttributes = $this->getAttributeValues();
        if($aQuestionAttributes['num_value_int_only'])
        {
            $value=number_format($value, 0, '', '');
        }
        return $value;
    }

    public function filter($value, $type)
    {
        if (trim($value)=="")
        {
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
        return 'decimal (30,10)';
    }

    public function adjustSize($size)
    {
        return $size . '.' . ($size-1);
    }

    public function onlyNumeric()
    {
        return true;
    }

    public function generateQuestionInfo()
    {
        return array(
            'q' => $this,
            'qid' => $this->id,
            'qseq' => $this->questioncount,
            'gseq' => $this->groupcount,
            'sgqa' => $this->surveyid . 'X' . $this->gid . 'X' . $this->id,
            'mandatory'=>$this->mandatory,
            'varName' => $this->getVarName(),
            'fieldname' => $this->fieldname,
            'preg' => (isset($this->preg) && trim($this->preg) != '') ? $this->preg : NULL,
            'rootVarName' => $this->title,
            'subqs' => array()
            );
    }

    public function generateSQInfo($ansArray)
    {
        return array(array(
            'q' => $this,
            'varName' => $this->getVarName(),
            'rowdivid' => $this->surveyid . 'X' . $this->gid . 'X' . $this->id,
            'jsVarName' => 'java' . $this->surveyid . 'X' . $this->gid . 'X' . $this->id,
            'jsVarName_on' => $this->jsVarNameOn(),
            ));
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

    public function getAdditionalValParts()
    {
        $valParts[] = "\n  if(isValidOther" . $this->id . "){\n";
        $valParts[] = "    $('#question" . $this->id . " :input').addClass('em_sq_validation').removeClass('error').addClass('good');\n";
        $valParts[] = "  }\n  else {\n";
        $valParts[] = "    $('#question" . $this->id . " :input').addClass('em_sq_validation').removeClass('good').addClass('error');\n";
        $valParts[] = "  }\n";
        return $valParts;
    }

    public function availableOptions()
    {
        return array('other' => false, 'valid' => true, 'mandatory' => true);
    }

    public function getDataEntryView($language)
    {
        $qidattributes = $this->getAttributeValues();
        if (isset($qidattributes['prefix']) && trim($qidattributes['prefix'][Yii::app()->getLanguage()]) != '') {
            $prefix = $qidattributes['prefix'][Yii::app()->getLanguage()];
        } else {
            $prefix = '';
        }

        if (isset($qidattributes['suffix']) && trim($qidattributes['suffix'][Yii::app()->getLanguage()]) != '') {
            $suffix = $qidattributes['suffix'][Yii::app()->getLanguage()];
        } else {
            $suffix = '';
        }

        if (intval(trim($qidattributes['maximum_chars'])) > 0 && intval(trim($qidattributes['maximum_chars'])) < 20) { // Limt to 20 chars for numeric
            $maximum_chars = intval(trim($qidattributes['maximum_chars']));
            $maxlength = "maxlength='{$maximum_chars}' ";
        } else {
            $maxlength = "maxlength='20' ";
        }

        if (trim($qidattributes['text_input_width']) != '') {
            $tiwidth = $qidattributes['text_input_width'];
        } else {
            $tiwidth = 10;
        }

        if (trim($qidattributes['num_value_int_only']) == 1) {
            $acomma = "";
        } else {
            $thissurvey = getSurveyInfo($this->surveyid);
            $acomma = getRadixPointData($thissurvey['surveyls_numberformat']);
            $acomma = $acomma['seperator'];
        }
        $title = $language->gT('Only numbers may be entered in this field.');

        return $prefix . "<input type='text' name='{$this->fieldname}' size='{$tiwidth}' title='{$title}' {$maxlength} onkeypress=\"return goodchars(event,'-0123456789{$acomma}')\" />" . $suffix;
        return $output;
    }

    public function getTypeHelp($language)
    {
        return $language->gT("Please write your answer here:");
    }

    public function getPrintAnswers($language)
    {
        $qidattributes = $this->getAttributeValues();
        $prefix="";
        $suffix="";
        if($qidattributes['prefix'][Yii::app()->getLanguage()] != "") {
            $prefix=$qidattributes['prefix'][Yii::app()->getLanguage()];
        }
        if($qidattributes['suffix'][Yii::app()->getLanguage()] != "") {
            $suffix=$qidattributes['suffix'][Yii::app()->getLanguage()];
        }
        return "<ul>\n\t<li>\n\t\t<span>{$prefix}</span>\n\t\t".printablesurvey::input_type_image('text',$this->getTypeHelp($language),20)."\n\t\t<span>{$suffix}</span>\n\t\t</li>\n\t</ul>";
    }

    public function getPrintPDF($language)
    {
        return "____________________";
    }

    public function QueXMLAppendAnswers(&$question)
    {
        global $dom;
        $response = $dom->createElement("response");
        $response->setAttribute("varName", $this->surveyid . 'X' . $this->gid . 'X' . $this->id);
        $response->appendChild(QueXMLCreateFree("integer",quexml_get_lengthth($this->id,"maximum_chars","10"),""));
        $question->appendChild($response);
    }

    public function availableAttributes($attr = false)
    {
        $attrs=array("em_validation_q","em_validation_q_tip","em_validation_sq","em_validation_sq_tip","statistics_showgraph","statistics_graphtype","hide_tip","hidden","max_num_value_n","maximum_chars","min_num_value_n","num_value_int_only","page_break","prefix","public_statistics","suffix","text_input_width","random_group");
        return $attr?in_array($attr,$attrs):$attrs;
    }

    public function questionProperties($prop = false)
    {
        
        $props=array('description' => gT("Numerical Input"),'group' => gT("Mask questions"),'subquestions' => 0,'class' => 'numeric','hasdefaultvalues' => 1,'assessable' => 0,'answerscales' => 0,'enum' => 0);
        return $prop?$props[$prop]:$props;
    }
}
?>
