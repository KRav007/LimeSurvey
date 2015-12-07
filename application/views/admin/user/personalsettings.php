<?php
/**
 * Personal settings edition
 */
?>

<div class="container-fluid welcome full-page-wrapper">
    <h3 class="pagetitle"><?php eT("Your personal settings"); ?></h3>

    <!-- form -->
    <?php echo CHtml::form($this->createUrl("/admin/user/sa/personalsettings"), 'post', array('class' => 'form44 form-horizontal', 'id'=>'personalsettings')); ?>

        <!-- E-mail adress -->
    <div class="form-group">
        <?php echo CHtml::label(gT("Full name").':', 'lang', array('class'=>"col-sm-2 control-label")); ?>
        <div class="col-sm-6">
        <?php echo CHtml::textField('full_name',$full_name, array('required' => true, 'class'=>"")); ?>

    </div>
    </div>

    <p>Password: <input type="password" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}" name="pwd1" onchange="form.pwd2.pattern = this.value;"></p>
    <p>Confirm Password: <input type="password" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}" name="pwd2"></p>

    <!-- E-mail adress -->
    <div class="form-group">

        <?php echo CHtml::label(gT("Email-Adress").':', 'lang', array('class'=>"col-sm-2 control-label")); ?>
        <div class="col-sm-6">
        <?php echo CHtml::emailField('email',$email, array('required' => true, 'class'=>"")); ?>

    </div>
    </div>

    <!-- Password -->
    <?php echo CHtml::label(gT("Password").':', 'lang', array('class'=>"col-sm-2 control-label")); ?>
    <?php echo CHtml::passwordField('password','', array('pattern'=>'(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}', 'class'=>"")); ?>
    <!-- Confirm password -->
    <?php echo CHtml::label(gT("Confirm password").':', 'lang', array('class'=>"col-sm-2 control-label")); ?>
    <?php echo CHtml::passwordField('confirm_password','',  array('pattern'=>'(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}', 'class'=>"")); ?>

        <!-- Interface language -->
        <div class="form-group">
            <?php echo CHtml::label(gT("Interface language").':', 'lang', array('class'=>"col-sm-2 control-label")); ?>
             <div class="col-sm-6">
                <select id='lang' name='lang' class="form-control">
                    <option value='auto'<?php if ($lang == 'auto') { echo " selected='selected'"; } ?>>
                        <?php eT("(Autodetect)"); ?>
                    </option>
                    <?php foreach (getLanguageData(true, Yii::app()->session['adminlang']) as $langkey => $languagekind)
                    { ?>
                    <option value='<?php echo $langkey; ?>'<?php if ($langkey == $lang) {
                        echo " selected='selected'";
                    } ?>>
                    <?php echo $languagekind['nativedescription']; ?> - <?php echo $languagekind['description']; ?>
                    </option>
                <?php } ?>
                </select>
            </div>
        </div>

        <!-- HTML editor mode -->
        <div class="form-group">
            <?php echo CHtml::label(gT("HTML editor mode").':', 'htmleditormode', array('class'=>"col-sm-2 control-label")); ?>
            <div class="col-sm-6">
                <?php
                    echo CHtml::dropDownList('htmleditormode', $htmleditormode, array(
                        'default' => gT("Default"),
                        'inline' => gT("Inline HTML editor"),
                        'popup' => gT("Popup HTML editor"),
                        'none' => gT("No HTML editor")
                    ), array('class'=>"form-control"));
                ?>
            </div>
        </div>

        <!-- Question type selector -->
        <div class="form-group">
            <?php echo CHtml::label(gT("Question type selector").':', 'questionselectormode', array('class'=>"col-sm-2 control-label")); ?>
            <div class="col-sm-6">
                <?php
                echo CHtml::dropDownList('questionselectormode', $questionselectormode, array(
                    'default' => gT("Default"),
                    'full' => gT("Full selector"),
                    'none' => gT("Simple selector")
                ), array('class'=>"form-control"));
                ?>
            </div>
        </div>

        <!-- Template editor mode -->
        <div class="form-group">
            <?php echo CHtml::label(gT("Template editor mode").':', 'templateeditormode', array('class'=>"col-sm-2 control-label")); ?>
            <div class="col-sm-6">
                <?php
                echo CHtml::dropDownList('templateeditormode', $templateeditormode, array(
                    'default' => gT("Default"),
                    'full' => gT("Full template editor"),
                    'none' => gT("Simple template editor")
                ), array('class'=>"form-control"));
                ?>
            </div>
        </div>

        <!-- Date format -->
        <div class="form-group">
            <?php echo CHtml::label( gT("Date format").':', 'dateformat', array('class'=>"col-sm-2 control-label")); ?>
             <div class="col-sm-6">
                 <select name='dateformat' id='dateformat' class="form-control">
                    <?php
                    foreach (getDateFormatData(0,Yii::app()->session['adminlang']) as $index => $dateformatdata)
                    {
                        echo "<option value='{$index}'";
                        if ($index == Yii::app()->session['dateformat'])
                        {
                            echo " selected='selected'";
                        }

                        echo ">" . $dateformatdata['dateformat'] . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Buttons -->
        <p>
            <?php echo CHtml::hiddenField('action', 'savepersonalsettings'); ?>
            <?php echo CHtml::submitButton(gT("Save settings"),array('class' => 'hidden')); ?>
        </p>
    <?php echo CHtml::endForm(); ?>
</div>
