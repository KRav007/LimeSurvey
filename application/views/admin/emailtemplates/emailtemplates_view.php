<?php $surveyinfo = getSurveyInfo($surveyid); ?>
<script type='text/javascript'>
    var sReplaceTextConfirmation='<?php eT("This will replace the existing text. Continue?","js"); ?>';
    

$(document).ready(function () {
    $('button.add-attachment').click(function(e)
    {
        e.preventDefault();
        var target = $(this).parent().find('table');
        openKCFinder_singleFile(target); 
        
    });
    
    
    
});




</script>
<style type="text/css">

#emailtemplates table.attachments td, #emailtemplates button.add-attachment {
    text-align: left;
    margin: 2px 2px 2px 2px !important;
    
}

table.attachments td span{
    border: 1px solid #999999;
    display:block;
}

table.attachments img, table.attachments span{
    height: 16px;
    cursor: pointer;
}
ul.editor-parent {
    overflow: hidden;
}


</style>
<div class='header ui-widget-header'>
    <?php eT("Edit email templates"); ?>
</div>
<?php echo CHtml::form(array('admin/emailtemplates/sa/update/surveyid/'.$surveyid), 'post', array('name'=>'emailtemplates', 'class'=>'form30newtabs'));?>

    <div id='tabs'>
        <ul>
            <?php foreach ($grplangs as $grouplang): ?>
                <li><a href='#tab-<?php echo $grouplang; ?>'><?php echo getLanguageNameFromCode($grouplang,false); ?>
                        <?php if ($grouplang == Survey::model()->findByPk($surveyid)->language): ?>
                            <?php echo ' ('.gT("Base language").')'; ?>
                            <?php endif; ?>
                    </a></li>
                <?php endforeach; ?>
        </ul>
        <?php 
            foreach ($grplangs as $key => $grouplang)
            {
                $bplang = $bplangs[$key];
                $esrow = $attrib[$key];
                $aDefaultTexts = $defaulttexts[$key];
                if ($ishtml == true)
                {
                    $aDefaultTexts['admin_detailed_notification']=$aDefaultTexts['admin_detailed_notification_css'].conditionalNewlineToBreak($aDefaultTexts['admin_detailed_notification'],$ishtml);
                }
                $this->renderPartial('/admin/emailtemplates/email_language_tab', compact('surveyinfo', 'ishtml', 'surveyid', 'clang', 'grouplang', 'bplang', 'esrow', 'aDefaultTexts'));
            }
            ?>
    </div>
    <p>
        <input type='submit' class='standardbtn' value='<?php eT("Save"); ?>' />
        <input type='hidden' name='action' value='tokens' />
        <input type='hidden' name='language' value="<?php echo $esrow->surveyls_language; ?>" />
    </p>
    </form>
<div id="attachment-relevance-editor" style="display: none; overflow: hidden;">
    <textarea style="resize: none; height: 90%; width: 100%; box-sizing: border-box">

    </textarea>
    <button>Apply</button>
</div>
