<?php

/**
 * @file
 *
 * This file holds the widget for the multiple choice question type, to edit it's default values.
 *
 * Features:
 * - Marker preselection
 * - EM integration to insert an em expression like {TOKEN:ATTRIBUTE_6}. At this state there is no validation implemented. Attributes must hold Y or N.
 *
 * DEV MEMO:
 * Validation could be difficult cause if you using tokens and you don't had setup a working token dataset
 *
 * For this feature you need editDefaultvalues.php, database.php, adminstyle.css
 */

    class answerscales_defaultvalue_widget extends CWidget
    {
        public $widgetOptions;

        //init() method is called automatically before all others
        public function init()
        {
        }

        public function run()
        {
            $qtproperties = $this->widgetOptions['qtproperties'];
            $questionrow = $this->widgetOptions['questionrow'];
            $langopts = $this->widgetOptions['langopts'];
            $language = $this->widgetOptions['language'];
            $clang = $this->widgetOptions['clang'];

            $aList = array();
            $aHtmlOptions = array();
            $sList_select = '';
            $sList_em_value = '';
            $sEmfield_css_class = '';
            $scale_id = 0;
            $aOpts = $langopts[$language][$questionrow['type']][$scale_id];

            if(is_numeric ($aOpts['defaultvalue']) || empty($aOpts['defaultvalue']))
            {
                $sList_select = $aOpts['defaultvalue'];
            }
            else
            {
                $sList_select = 'EM';
                $sList_em_value = $aOpts['defaultvalue'];
            }

            $sElement_id = 'defaultanswerscale_' . $scale_id . '_' . $language;
            // create option list
            $aList['EM'] = $clang->gT('< EM Value >'); // add EM option todo insert lang function
            foreach($aOpts['answers'] as $answer)
            {
                $answer = $answer->attributes;
                $aList[$answer['code']] = $answer['answer'];
            }
            // set helper css
            if($sList_select != 'EM')
            {
                $sEmfield_css_class = 'hide';
            }

            $aHtmlOptions = array(
                'id'       => $sElement_id,
                'empty'    => $clang->gT('<No default value>'),
                'onchange' => '// show EM Value Field
                                   if ($(this).val() == "EM"){
                                       $("#"+$(this).closest("select").attr("id")+ "_EM").removeClass("hide");
                                   }else{
                                       $("#"+$(this).closest("select").attr("id")+ "_EM").addClass("hide");} '
            );
// todo ad a help popover??
            echo '<li>';
            echo CHtml::label ($clang->gT('Default answer value:'), $sElement_id); // write label of selectlist
            echo CHtml::dropDownList ($sElement_id, $sList_select, $aList, $aHtmlOptions); // write selectlist
            echo CHtml::textField ($sElement_id . '_EM', $sList_em_value, array( // insert em value field
                'id'    => $sElement_id . '_EM',
                'class' => $sEmfield_css_class,
                'width' => 100
            ));
            echo '</li>';
        }
    }