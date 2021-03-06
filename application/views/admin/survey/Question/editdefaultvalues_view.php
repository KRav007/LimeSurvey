<div class='header ui-widget-header'><?php eT('Edit default answer values') ?></div>
<?php echo CHtml::form(array("admin/database/index"), 'post',array('class'=>'form30','id'=>'frmdefaultvalues','name'=>'frmdefaultvalues')); ?>
    <div id="tabs">
        <ul>
            <?php
                foreach ($questlangs as $language)
                {
                ?>
                <li><a href='#df_<?php echo $language ?>'><?php echo getLanguageNameFromCode($language, false) ?></a></li>
                <?php
                }
            ?>
        </ul>
        <?php
            foreach ($questlangs as $language)
            {
            ?>
            <div id='df_<?php echo $language ?>'>
                <ul>
                    <?php
                        if ($qproperties['answerscales'] > 0)
                        {
                            for ($scale_id = 0; $scale_id < $qproperties['answerscales']; $scale_id++)
                            {
                                $opts = $langopts[$language][$scale_id];
                            ?>
                            <li>
                                <label for='defaultanswerscale_<?php echo "{$scale_id}_{$language}" ?>'>
                                    <?php
                                        $qproperties['answerscales'] > 1 ? printf(gT('Default answer for scale %s:'), $scale_id) : printf(gT('Default answer value:'), $scale_id) ?>
                                </label>

                                <select name='defaultanswerscale_<?php echo "{$scale_id}_{$language}" ?>' id='defaultanswerscale_<?php echo "{$scale_id}_{$language}" ?>'>

                                    <option value=''<?php is_null($opts['defaultvalue']) ? ' selected="selected"' : '' ?>>
                                        <?php eT('<No default value>') ?>
                                    </option>
                                    <?php
                                        foreach ($opts['answers'] as $answer)
                                        {
                                            $answer = $answer->attributes;
                                        ?>                          <option<?php if ($answer['code'] == $opts['defaultvalue']){ ?> selected="selected" <?php } ?> value="<?php echo $answer['code'] ?>"><?php echo $answer['answer'] ?></option>
                                        <?php
                                        }
                                    ?>
                                </select>
                            </li>
                            <?php
                                if ($questionrow['other'] == 'Y')
                                {
                                ?>
                                <li>
                                    <label for='other_<?php echo "{$scale_id}_{$language}" ?>'>
                                        <?php eT("Default value for option 'Other':")?>
                                    </label>
                                    <input type='text' name='other_<?php echo "{$scale_id}_{$language}" ?>' value='<?php echo $langopts[$language]['Ydefaultvalue'] ?>' id='other_<?php echo "{$scale_id}_{$language}" ?>'>
                                </li>
                                <?php
                                }
                            }
                        }
                        else if ($qproperties['answerscales'] == 0 && $qproperties['subquestions'] > 0)
                        {
                            for ($scale_id = 0; $scale_id < $qproperties['subquestions']; $scale_id++)
                            {
                                $opts = $langopts[$language][$scale_id];

                                if ($qproperties['subquestions'] > 1)
                                {
                                ?>
                                <div class='header ui-widget-header'>
                                    <?php echo sprintf(gT('Default answer for scale %s:'), $scale_id) ?>
                                </div>
                                <?php
                                }
                            ?>
                            <ul>
                                <?php
                                    if ($qproperties['enum'] == 1)
                                    {
                                        foreach ($opts['sqresult'] as $aSubquestion)
                                        {
                                        ?>
                                        <li>
                                            <label for='defaultanswerscale_<?php echo "{$scale_id}_{$language}_{$aSubquestion['qid']}" ?>'>
                                                <?php echo "{$aSubquestion['title']}: " . flattenText($aSubquestion['question']) ?>
                                            </label>
                                            <select name='defaultanswerscale_<?php echo "{$scale_id}_{$language}_{$aSubquestion['qid']}" ?>'
                                                id='defaultanswerscale_<?php echo "{$scale_id}_{$language}_{$aSubquestion['qid']}" ?>'>
                                                <?php
                                                    foreach ($aSubquestion['options'] as $value => $label)
                                                    {
                                                    ?>
                                                    <option value="<?php echo $value ?>"<?php echo ($value == $aSubquestion['defaultvalue'] ? ' selected="selected"' : ''); ?>><?php echo $label ?></option>
                                                    <?php
                                                    }
                                                ?>
                                            </select>
                                        </li>
                                        <?php
                                        }
                                    }
                                    else
                                    {
                                        foreach ($opts['sqresult'] as $aSubquestion)
                                        {
                                        ?>
                                        <li>
                                            <label for='defaultanswerscale_<?php echo "{$scale_id}_{$language}_{$aSubquestion['qid']}" ?>'>
                                                <?php echo "{$aSubquestion['title']}: " . flattenText($aSubquestion['question']) ?>
                                            </label>
                                            <textarea cols='50' name='defaultanswerscale_<?php echo "{$scale_id}_{$language}_{$aSubquestion['qid']}" ?>'
                                                id='defaultanswerscale_<?php echo "{$scale_id}_{$language}_{$aSubquestion['qid']}" ?>'><?php echo $aSubquestion['defaultvalue'] ?></textarea>
                                        </li>
                                        <?php
                                        }
                                    }
                                ?>
                            </ul>
                            <?php
                            }
                        }
                        else if ($qproperties['answerscales']==0 && $qproperties['subquestions']==0)
                        {
                        ?>
                        <li>
                            <label for='defaultanswerscale_<?php echo "0_{$language}_0" ?>'>
                                <?php eT("Default value:")?>
                            </label>

                            <textarea cols='50' name='defaultanswerscale_<?php echo "0_{$language}_0" ?>'
                                id='defaultanswerscale_<?php echo "0_{$language}_0" ?>'><?php
                                echo htmlspecialchars($langopts[$language][0]); ?></textarea>
                        </li>
                        <?php
                        }

                        if ($language == $baselang && count($questlangs) > 1)
                        {
                        ?>
                        <li>
                            <label for='samedefault'>
                                <?php eT('Use same default value across languages:') ?>
                            </label>
                            <input type='checkbox' name='samedefault' id='samedefault'<?php $questionrow['same_default'] ? ' checked="checked"' : '' ?> />
                        </li>
                        <?php
                        }
                    ?>
                </ul>
            </div>
            <?php
            }
        ?>
    </div>
    <input type='hidden' id='action' name='action' value='updatedefaultvalues' />
    <input type='hidden' id='sid' name='sid' value='<?php echo $surveyid ?>' />
    <input type='hidden' id='gid' name='gid' value='<?php echo $gid ?>' />
    <input type='hidden' id='qid' name='qid' value='<?php echo $qid ?>' />
    <p><input type='submit' value='<?php eT('Save') ?>'/></p>
    </form>
