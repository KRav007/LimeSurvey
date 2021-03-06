<?php
class ShortTextQuestion extends TextQuestion
{
    public function getAnswerHTML()
    {
        global $thissurvey;

        
        $googlemaps_api_key = Yii::app()->getConfig("googlemaps_api_key");
        $extraclass ="";
        $aQuestionAttributes = $this->getAttributeValues();

        if ($aQuestionAttributes['numbers_only']==1)
        {
            $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
            $sSeperator = $sSeperator['seperator'];
            $extraclass .=" numberonly";
            $checkconditionFunction = "fixnum_checkconditions";
        }
        else
        {
            $checkconditionFunction = "checkconditions";
        }
        if (intval(trim($aQuestionAttributes['maximum_chars']))>0)
        {
            // Only maxlength attribute, use textarea[maxlength] jquery selector for textarea
            $maximum_chars= intval(trim($aQuestionAttributes['maximum_chars']));
            $maxlength= "maxlength='{$maximum_chars}' ";
            $extraclass .=" maxchars maxchars-".$maximum_chars;
        }
        else
        {
            $maxlength= "";
        }
        if (trim($aQuestionAttributes['text_input_width'])!='')
        {
            $tiwidth=$aQuestionAttributes['text_input_width'];
            $extraclass .=" inputwidth-".trim($aQuestionAttributes['text_input_width']);
        }
        else
        {
            $tiwidth=50;
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
            $kpclass = "text-keypad";
            $extraclass .=" inputkeypad";
        }
        else
        {
            $kpclass = "";
        }
        if (trim($aQuestionAttributes['display_rows'])!='')
        {
            //question attribute "display_rows" is set -> we need a textarea to be able to show several rows
            $drows=$aQuestionAttributes['display_rows'];

            //if a textarea should be displayed we make it equal width to the long text question
            //this looks nicer and more continuous
            if($tiwidth == 50)
            {
                $tiwidth=40;
            }

            //NEW: textarea instead of input=text field

            // --> START NEW FEATURE - SAVE
            $answer ="<p class='question answer-item text-item {$extraclass}'><label for='answer{$this->fieldname}' class='hide label'>{gT('Answer')}</label>"
            . '<textarea class="textarea '.$kpclass.'" name="'.$this->fieldname.'" id="answer'.$this->fieldname.'" '
            .'rows="'.$drows.'" cols="'.$tiwidth.'" '.$maxlength.' onkeyup="'.$checkconditionFunction.'(this.value, this.name, this.type);">';
            // --> END NEW FEATURE - SAVE

            if ($_SESSION['survey_'.$this->surveyid][$this->fieldname]) {
                $dispVal = str_replace("\\", "", $_SESSION['survey_'.$this->surveyid][$this->fieldname]);
                if ($aQuestionAttributes['numbers_only']==1)
                {
                    $dispVal = str_replace('.',$sSeperator,$dispVal);
                }
                $answer .= $dispVal;
            }

            $answer .= "</textarea></p>\n";
        }
        elseif((int)($aQuestionAttributes['location_mapservice'])!=0){
            $mapservice = $aQuestionAttributes['location_mapservice'];
            $currentLocation = $_SESSION['survey_'.$this->surveyid][$this->fieldname];
            $currentLatLong = null;

            $floatLat = 0;
            $floatLng = 0;

            // Get the latitude/longtitude for the point that needs to be displayed by default
            if (strlen($currentLocation) > 2){
                $currentLatLong = explode(';',$currentLocation);
                $currentLatLong = array($currentLatLong[0],$currentLatLong[1]);
            }
            else{
                if ((int)($aQuestionAttributes['location_nodefaultfromip'])==0)
                    $currentLatLong = getLatLongFromIp(getIPAddress());
                if (!isset($currentLatLong) || $currentLatLong==false){
                    $floatLat = 0;
                    $floatLng = 0;
                    $LatLong = explode(" ",trim($aQuestionAttributes['location_defaultcoordinates']));

                    if (isset($LatLong[0]) && isset($LatLong[1])){
                        $floatLat = $LatLong[0];
                        $floatLng = $LatLong[1];
                    }

                    $currentLatLong = array($floatLat,$floatLng);
                }
            }
            // 2 - city; 3 - state; 4 - country; 5 - postal
            $strBuild = "";
            if ($aQuestionAttributes['location_city'])
                $strBuild .= "2";
            if ($aQuestionAttributes['location_state'])
                $strBuild .= "3";
            if ($aQuestionAttributes['location_country'])
                $strBuild .= "4";
            if ($aQuestionAttributes['location_postal'])
                $strBuild .= "5";

            $currentLocation = $currentLatLong[0] . " " . $currentLatLong[1];
            $answer = "
            <script type=\"text/javascript\">
            zoom['$this->fieldname'] = {$aQuestionAttributes['location_mapzoom']};
            </script>
            <div class=\"question answer-item geoloc-item {$extraclass}\">
            <input type=\"hidden\" name=\"$this->fieldname\" id=\"answer$this->fieldname\" value=\"{$_SESSION['survey_'.$this->surveyid][$this->fieldname]}\">

            <input class=\"text location ".$kpclass."\" type=\"text\" size=\"20\" name=\"$this->fieldname_c\"
            id=\"answer$this->fieldname_c\" value=\"$currentLocation\"
            onchange=\"$checkconditionFunction(this.value, this.name, this.type)\" />

            <input type=\"hidden\" name=\"boycott_$this->fieldname\" id=\"boycott_$this->fieldname\"
            value = \"{$strBuild}\" >
            <input type=\"hidden\" name=\"mapservice_$this->fieldname\" id=\"mapservice_$this->fieldname\"
            class=\"mapservice\" value = \"{$aQuestionAttributes['location_mapservice']}\" >
            <div id=\"gmap_canvas_$this->fieldname_c\" style=\"width: {$aQuestionAttributes['location_mapwidth']}px; height: {$aQuestionAttributes['location_mapheight']}px\"></div>
            </div>";

            if (isset($aQuestionAttributes['hide_tip']) && $aQuestionAttributes['hide_tip']==0)
            {
                $answer .= "<div class=\"questionhelp\">"
                . gT('Drag and drop the pin to the desired location. You may also right click on the map to move the pin.').'</div>';
                $question_text['help'] = gT('Drag and drop the pin to the desired location. You may also right click on the map to move the pin.');
            }
        }
        else
        {
            //no question attribute set, use common input text field
            $answer = "<p class=\"question answer-item text-item {$extraclass}\">\n"
            ."<label for='answer{$this->fieldname}' class='hide label'>{gT('Answer')}</label>"
            ."$prefix\t<input class=\"text $kpclass\" type=\"text\" size=\"$tiwidth\" name=\"$this->fieldname\" id=\"answer$this->fieldname\"";

            $dispVal = $_SESSION['survey_'.$this->surveyid][$this->fieldname];
            if ($aQuestionAttributes['numbers_only']==1)
            {
                $dispVal = str_replace('.',$sSeperator,$dispVal);
            }
            $dispVal = htmlspecialchars($dispVal,ENT_QUOTES,'UTF-8');
            $answer .= " value=\"$dispVal\"";

            $answer .=" {$maxlength} onkeyup=\"$checkconditionFunction(this.value, this.name, this.type)\"/>\n\t$suffix\n</p>\n";
        }

        if (trim($aQuestionAttributes['time_limit'])!='')
        {
            $answer .= return_timer_script($aQuestionAttributes, $this, "answer".$this->fieldname);
        }

        return $answer;
    }

    public function getDataEntry($idrow, &$fnames, $language)
    {
        return "\t<input type='text' name='{$this->fieldname}' value='"
        .htmlspecialchars($idrow[$this->fieldname], ENT_QUOTES) . "' />\n";
    }

    public function getHeaderIncludes()
    {
        $aQuestionAttributes = $this->getAttributeValues();
        if ($aQuestionAttributes['location_mapservice']==1 && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off")
            return array('https://maps.googleapis.com/maps/api/js?sensor=false' => 'js');
        elseif ($aQuestionAttributes['location_mapservice']==1)
            return array('http://maps.googleapis.com/maps/api/js?sensor=false' => 'js');
        elseif ($aQuestionAttributes['location_mapservice']==2)
            return array('http://www.openlayers.org/api/OpenLayers.js' => 'js');
        else
            return array();
    }

    public function getDBField()
    {
        if (Yii::app()->db->driverName == 'mysql' || Yii::app()->db->driverName == 'mysqli') return 'text';
        return 'string';
    }

    public function onlyNumeric()
    {
        $attributes = $this->getAttributeValues();
        return array_key_exists('numbers_only', $attributes) && $attributes['numbers_only'] == 1;
    }

    public function screenshotCount()
    {
        return 2;
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
            $tiwidth = 50;
        }

        if ($qidattributes['numbers_only']==1)
        {
            $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
            $sSeperator = $sSeperator['seperator'];
            $numbersonly = 'onkeypress="return goodchars(event,\'-0123456789'.$sSeperator.'\')"';
        }
        else
        {
            $numbersonly = '';
        }

        if (trim($qidattributes['display_rows'])!='')
        {
            //question attribute "display_rows" is set -> we need a textarea to be able to show several rows
            $drows=$qidattributes['display_rows'];

            //if a textarea should be displayed we make it equal width to the long text question
            //this looks nicer and more continuous
            if($tiwidth == 50)
            {
                $tiwidth=40;
            }
            return $prefix . "<textarea name='{$this->fieldname}' cols='{$tiwidth}' rows='{$drows}' {$numbersonly}></textarea>" . $suffix;
        } else {
            return $prefix . "<input type='text' name='{$this->fieldname}' size='{$tiwidth}' {$maxlength} {$numbersonly} />" . $suffix;
        }
    }

    public function getPrintAnswers($language)
    {
        return printablesurvey::input_type_image('text', $this->getTypeHelp($language), 50);
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
        $response->appendChild(QueXMLCreateFree("text",quexml_get_lengthth($this->id,"maximum_chars","240"),""));
        $question->appendChild($response);
    }

    public function availableAttributes($attr = false)
    {
        $attrs=array("display_rows","em_validation_q","em_validation_q_tip","em_validation_sq","em_validation_sq_tip","location_city","location_state","location_postal","location_country","statistics_showmap","statistics_showgraph","statistics_graphtype","location_mapservice","location_mapwidth","location_mapheight","location_nodefaultfromip","location_defaultcoordinates","location_mapzoom","hide_tip","hidden","maximum_chars","numbers_only","page_break","prefix","suffix","text_input_width","time_limit","time_limit_action","time_limit_disable_next","time_limit_disable_prev","time_limit_countdown_message","time_limit_timer_style","time_limit_message_delay","time_limit_message","time_limit_message_style","time_limit_warning","time_limit_warning_display_time","time_limit_warning_message","time_limit_warning_style","time_limit_warning_2","time_limit_warning_2_display_time","time_limit_warning_2_message","time_limit_warning_2_style","random_group");
        return $attr?in_array($attr,$attrs):$attrs;
    }

    public function questionProperties($prop = false)
    {
        
        $props=array('description' => gT("Short Free Text"),'group' => gT("Text questions"),'subquestions' => 0,'class' => 'text-short','hasdefaultvalues' => 1,'assessable' => 0,'answerscales' => 0,'enum' => 0);
        return $prop?$props[$prop]:$props;
    }
}
?>