<?php
    $yii = Yii::app();
    $controller = $yii->getController();
?>
<div id='general'>
    <ul>
        <li>
            <label><?php eT("Base language:") ; ?></label>
            <?php echo getLanguageNameFromCode($esrow['language'],false) ?>
        </li>
        <li><label for='additional_languages'><?php eT("Additional Languages"); ?>:</label>
            <table><tr><td style='text-align:left'><select style='min-width:220px;' size='5' id='additional_languages' name='additional_languages'>
                            <?php $jsX=0;
                                $jsRemLang ="<script type=\"text/javascript\">
                                var mylangs = new Array();
                                standardtemplaterooturl='".$yii->getConfig('standardtemplaterooturl')."';
                                templaterooturl='".$yii->getConfig('usertemplaterooturl')."';\n";

                                foreach (Survey::model()->findByPk($surveyid)->additionalLanguages as $langname) {
                                    if ($langname && $langname != $esrow['language']) {
                                        $jsRemLang .=" mylangs[$jsX] = \"$langname\"\n"; ?>
                                    <option id='<?php echo $langname; ?>' value='<?php echo $langname; ?>'><?php echo getLanguageNameFromCode($langname,false); ?>
                                    </option>
                                    <?php $jsX++; ?>
                                    <?php }
                                }
                                $jsRemLang .= "</script>";
                            ?>

                        </select>
                        <?php echo $jsRemLang; ?>
                    </td>
                    <td style='text-align:left'><input type="button" value="<< <?php eT("Add"); ?>" onclick="DoAdd()" id="AddBtn" /><br /> <input type="button" value="<?php eT("Remove"); ?> >>" onclick="DoRemove(0,'')" id="RemoveBtn"  /></td>


                    <td style='text-align:left'><select size='5' style='min-width:220px;' id='available_languages' name='available_languages'>
                            <?php $tempLang=Survey::model()->findByPk($surveyid)->additionalLanguages;
                                foreach (getLanguageDataRestricted (false, Yii::app()->session['adminlang']) as $langkey2 => $langname) {
                                    if ($langkey2 != $esrow['language'] && in_array($langkey2, $tempLang) == false) {  // base languag must not be shown here ?>
                                    <option id='<?php echo $langkey2 ; ?>' value='<?php echo $langkey2; ?>'>
                                    <?php echo $langname['description']; ?></option>
                                    <?php }
                            } ?>
                        </select></td>
                </tr></table></li>


        <li><label for='admin'><?php eT("Administrator:"); ?></label>
            <input type='text' size='50' id='admin' name='admin' value="<?php echo $esrow['admin']; ?>" /></li>
        <li><label for='adminemail'><?php eT("Admin email:"); ?></label>
            <input type='email' size='50' id='adminemail' name='adminemail' value="<?php echo $esrow['adminemail']; ?>" /></li>
        <li><label for='bounce_email'><?php eT("Bounce email:"); ?></label>
            <input type='email' size='50' id='bounce_email' name='bounce_email' value="<?php echo $esrow['bounce_email']; ?>" /></li>
        <li><label for='faxto'><?php eT("Fax to:"); ?></label>
            <input type='text' size='50' id='faxto' name='faxto' value="<?php echo $esrow['faxto']; ?>" />
        </li>
        <?php if(User::GetUserRights('manage_model')) { ?>
            <li><label for='type'><?php eT("Survey type:"); ?></label>
                <select name="type" id="type">
                    <option value="N"><?php eT("None"); ?></option>
                    <option value="M" <?php if ($esrow['type']=='M') { ?> selected='selected' <?php } ?>><?php eT("Survey model"); ?></option>
                </select>
            </li>
        <?php } ?>
        <?php
            if (isset($pluginSettings))
            {
                Yii::import('application.helpers.PluginSettingsHelper');
                $PluginSettings = new PluginSettingsHelper();
                foreach ($pluginSettings as $id => $plugin)
                {
                    foreach ($plugin['settings'] as $name => $setting)
                    {
                        $name = "plugin[{$plugin['name']}][$name]";
                        echo CHtml::tag('li', array(), $PluginSettings->renderSetting($name, $setting, null, true));
                    }
                }
            }

        ?>
    </ul>
</div>
