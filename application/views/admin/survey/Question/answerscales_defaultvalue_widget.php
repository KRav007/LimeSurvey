<?php

    /**
     * @file
     * This widget outputs the form for the default value edit page
     *
     * Questiontypes: selectbox type: !
     *
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

            $aList = array();
            $aHtmlOptions = array();
            $sList_select = '';
            $sList_em_value = '';
            $sEmfield_css = '';
            $scale_id = 0;
            $sLabel = 'Default answer value:'; //clang->gT('Default answer value:');

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
            $aList['EM'] = 'EM Value'; // add EM option todo insert lang function
            foreach($aOpts['answers'] as $answer)
            {
                $answer = $answer->attributes;
                $aList[$answer['code']] = $answer['answer'];
            }
// set helper css
            if($sList_select != 'EM')
            {
                $sEmfield_css = 'hide'; // to helper class in twitter is missing - insert it - now in admin style!!.hide {display: none !important;visibility: hidden !important;
            }

            $aHtmlOptions = array(
                'id'       => $sElement_id,
                'empty'    => '<No default value>',
                // todo $clang->eT('<No default value>')

                'onchange' => '// show EM Value Field
                                   if ($(this).val() == "EM"){
                                       $("#"+$(this).closest("select").attr("id")+ "_EM").removeClass("hide");
                                   }else{
                                       $("#"+$(this).closest("select").attr("id")+ "_EM").addClass("hide");} '
            );

            echo '<li>';
            echo CHtml::label ($sLabel, $sElement_id); // write label of selectlist
            echo CHtml::dropDownList ($sElement_id, $sList_select, $aList, $aHtmlOptions); // write selectlist
            // show em value field
            echo CHtml::textField ($sElement_id . '_EM', $sList_em_value, array(
                'id'    => $sElement_id . '_EM',
                'class' => $sEmfield_css,
                'width' => 100
            ));
            echo '</li>';
        }
    }
