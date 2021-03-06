<div class='menubar'>
    <div class='menubar-title ui-widget-header'>
        <strong><?php echo $title; ?></strong>: (<?php echo $thissurvey['surveyls_title']; ?>)
    </div>
    <div class='menubar-main'>
        <div class='menubar-left'>
            <a href='<?php echo $this->createUrl("admin/survey/sa/view/surveyid/$surveyid"); ?>'>
                <img src='<?php echo $sImageURL; ?>home.png' title='' alt='<?php eT("Return to survey administration"); ?>' /></a>
            <img src='<?php echo $sImageURL; ?>blank.gif' alt='' width='11' />
            <img src='<?php echo $sImageURL; ?>separator.gif' class='separator' alt='' />

            <?php if (hasSurveyPermission($surveyid, 'responses', 'read'))
                { ?>
                <a href='<?php echo $this->createUrl("admin/responses/sa/index/surveyid/$surveyid"); ?>'>
                    <img src='<?php echo $sImageURL; ?>summary.png' title='' alt='<?php eT("Show summary information"); ?>' /></a>
                <?php if (count(Survey::model()->findByPk($surveyid)->additionalLanguages) == 0)
                    { ?>
                    <a href='<?php echo $this->createUrl("admin/responses/sa/browse/surveyid/$surveyid"); ?>'>
                        <img src='<?php echo $sImageURL; ?>document.png' title='' alt='<?php eT("Display responses"); ?>' /></a>
                    <?php }
                    else
                    { ?>
                    <a href="<?php echo $this->createUrl("admin/responses/sa/browse/surveyid/$surveyid"); ?>" accesskey='b' id='browseresponses'>
                        <img src='<?php echo $sImageURL; ?>document.png' alt='<?php eT("Display responses"); ?>' /></a>

                    <div class="langpopup" id="browselangpopup"><?php eT("Please select a language:"); ?><ul>
                            <?php foreach ($tmp_survlangs as $tmp_lang)
                                { ?>
                                <li><a href="<?php echo $this->createUrl("admin/responses/sa/index/surveyid/$surveyid/start/0/limit/50/order/asc/browselang/$tmp_lang"); ?>" accesskey='b'><?php echo getLanguageNameFromCode($tmp_lang, false); ?></a></li>
                                <?php } ?>
                        </ul></div>
                    <?php } ?>
                <a href='<?php echo $this->createUrl("admin/responses/sa/browse/surveyid/$surveyid/start/0/limit/50/order/desc"); ?>'>
                    <img src='<?php echo $sImageURL; ?>viewlast.png' alt='<?php eT("Display last 50 responses"); ?>' /></a>
                <?php }
                if (hasSurveyPermission($surveyid, 'responses', 'create'))
                { ?>
                <a href='<?php echo $this->createUrl("admin/dataentry/sa/view/surveyid/$surveyid"); ?>'>
                    <img src='<?php echo $sImageURL; ?>dataentry.png' alt='<?php eT("Data entry"); ?>' /></a>
                <?php }
                if (hasSurveyPermission($surveyid, 'statistics', 'read'))
                { ?>
                <a href='<?php echo $this->createUrl("admin/statistics/sa/index/surveyid/$surveyid"); ?>'>
                    <img src='<?php echo $sImageURL; ?>statistics.png' alt='<?php eT("Get statistics from these responses"); ?>' /></a>
                <?php if ($thissurvey['savetimings'] == "Y")
                    { ?>
                    <a href='<?php echo $this->createUrl("admin/responses/sa/time/surveyid/$surveyid"); ?>'>
                        <img src='<?php echo $sImageURL; ?>statistics_time.png' alt='<?php eT("Get time statistics from these responses"); ?>' /></a>
                    <?php }
            } ?>
            <img src='<?php echo $sImageURL; ?>separator.gif' class='separator' alt='' />
            <?php if (hasSurveyPermission($surveyid, 'responses', 'export'))
                { ?>
                <a href='<?php echo $this->createUrl("admin/export/sa/exportresults/surveyid/$surveyid"); ?>'>
                    <img src='<?php echo $sImageURL; ?>export.png' alt='<?php eT("Export results to application"); ?>' /></a>

                <a href='<?php echo $this->createUrl("admin/export/sa/exportspss/sid/$surveyid"); ?>'>
                    <img src='<?php echo $sImageURL; ?>exportspss.png' alt="<?php eT("Export results to a SPSS/PASW command file"); ?>" /></a>

                <a href='<?php echo $this->createUrl("admin/export/sa/exportr/sid/$surveyid"); ?>'>
                    <img src='<?php echo $sImageURL; ?>exportr.png' alt='<?php eT("Export results to a R data file"); ?>' /></a>
                <?php
                }
                if (hasSurveyPermission($surveyid, 'responses', 'create'))
                {
                ?>
                <a href='<?php echo $this->createUrl("admin/dataentry/sa/import/surveyid/$surveyid"); ?>'>
                    <img src='<?php echo $sImageURL; ?>importold.png' alt='<?php eT("Import responses from a deactivated survey table"); ?>' /></a>
                <?php } ?>
            <img src='<?php echo $sImageURL; ?>separator.gif' class='separator' alt='' />

            <?php if (hasSurveyPermission($surveyid, 'responses', 'read'))
                { ?>
                <a href='<?php echo $this->createUrl("admin/saved/sa/view/surveyid/$surveyid"); ?>'>
                    <img src='<?php echo $sImageURL; ?>saved.png' title='' alt='<?php eT("View Saved but not submitted Responses"); ?>' /></a>
                <?php }
                if (hasSurveyPermission($surveyid, 'responses', 'import'))
                { ?>
                <a href='<?php echo $this->createUrl("admin/dataentry/sa/vvimport/surveyid/$surveyid"); ?>'>
                    <img src='<?php echo $sImageURL; ?>importvv.png' alt='<?php eT("Import a VV survey file"); ?>' /></a>
                <?php }
                if (hasSurveyPermission($surveyid, 'responses', 'export'))
                { ?>
                <a href='<?php echo $this->createUrl("admin/export/sa/vvexport/surveyid/$surveyid"); ?>'>
                    <img src='<?php echo $sImageURL; ?>exportvv.png' title='' alt='<?php eT("Export a VV survey file"); ?>' /></a>
                <?php }
                if (hasSurveyPermission($surveyid, 'responses', 'delete') && $thissurvey['anonymized'] == 'N' && $thissurvey['tokenanswerspersistence'] == 'Y')
                { ?>
                <a href='<?php echo $this->createUrl("admin/dataentry/sa/iteratesurvey/surveyid/$surveyid"); ?>'>
                    <img src='<?php echo $sImageURL; ?>iterate.png' title='' alt='<?php eT("Iterate survey"); ?>' /></a>
                <?php } ?>
        </div>
    </div>
</div>
