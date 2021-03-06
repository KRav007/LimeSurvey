<?php if (!class_exists('Yii', false)) die('No direct script access allowed in ' . __FILE__);
    /*
    * LimeSurvey
    * Copyright (C) 2007 The LimeSurvey Project Team / Carsten Schmitz
    * All rights reserved.
    * License: GNU/GPL License v2 or later, see LICENSE.php
    * LimeSurvey is free software. This version may have been modified pursuant
    * to the GNU General Public License, and as distributed it includes or
    * is derivative of works licensed under the GNU General Public License or
    * other free or open source software licenses.
    * See COPYRIGHT.php for copyright notices and details.
    *
    *   $Id$
    *   Files Purpose: lots of common functions
    */

    class Questions extends LSActiveRecord
    {

        /**
        * Returns the static model of Settings table
        *
        * @static
        * @access public
        * @param string $class
        * @return LSActiveRecord
        */
        public static function model($class = __CLASS__)
        {
            return parent::model($class);
        }

        /**
        * Returns the setting's table name to be used by the model
        *
        * @access public
        * @return string
        */
        public function tableName()
        {
            return '{{questions}}';
        }

        /**
        * Returns the primary key of this table
        *
        * @access public
        * @return string
        */
        public function primaryKey()
        {
            return 'qid';
        }

        /**
        * Defines the relations for this model
        *
        * @access public
        * @return array
        */
        public function relations()
        {
            return array(
            'groups' => array(self::HAS_ONE, 'Groups', '',
            'on' => 't.gid = groups.gid'
            ),
            'parents' => array(self::HAS_ONE, 'Questions', '',
            'on' => 't.parent_id = parents.qid',
            ),
            'question_attributes' => array(self::HAS_MANY, 'Question_attributes', '',
            'on' => 't.qid = question_attributes.qid',
            ),
            );
        }

        /**
        * Returns this model's validation rules
        *
        */
        public function rules()
        {
            return array(
                array('code','required'),
                array('code','LSYii_Validators'),
                array('sortorder','numerical', 'integerOnly'=>true,'allowEmpty'=>true),
            );
        }


        /**
        * Rewrites sort order for questions in a group
        *
        * @static
        * @access public
        * @param int $gid
        * @param int $surveyid
        * @return void
        */
        public static function updateSortOrder($gid, $surveyid)
        {
            $questions = self::model()->findAllByAttributes(array('gid' => $gid, 'sid' => $surveyid));
            $p = 0;
            foreach ($questions as $question)
            {
                $question->question_order = $p;
                $question->save();
                $p++;
            }
        }
        /**
        * Fixe sort order for questions in a group
        *
        * @static
        * @access public
        * @param int $gid
        * @param int $surveyid
        * @return void
        */
        function updateQuestionOrder($gid,$language,$position=0)
        {
            $data=Yii::app()->db->createCommand()->select('qid')
            ->where(array('and','gid=:gid','language=:language'))
            ->order('question_order, title ASC')
            ->from('{{questions}}')
            ->bindParam(':gid', $gid, PDO::PARAM_INT)
            ->bindParam(':language', $language, PDO::PARAM_STR)
            ->query();

            $position = intval($position);
            foreach($data->readAll() as $row)
            {
                Yii::app()->db->createCommand()->update($this->tableName(),array('question_order' => $position),'qid='.$row['qid']);
                $position++;
            }
        }

        /**
        * This function returns an array of the advanced attributes for the particular question
        * including their values set in the database
        *
        * @access public
        * @param int $iQuestionID  The question ID - if 0 then all settings will use the default value
        * @param string $sQuestionType  The question type
        * @param int $iSurveyID
        * @param string $sLanguage  If you give a language then only the attributes for that language are returned
        * @return array
        */
        public function getAdvancedSettingsWithValues($q, $sLanguage=null)
        {
            if (is_null($sLanguage))
            {
                $aLanguages = array_merge(array(Survey::model()->findByPk($q->surveyid)->language), Survey::model()->findByPk($q->surveyid)->additionalLanguages);
            }
            else
            {
                $aLanguages = array($sLanguage);
            }
            if ($q->id != 0)
            {
                $aAttributeValues = $q->getAttributeValues();
            }

            $aAttributeNames = linkedAttributes($q);
            uasort($aAttributeNames, 'categorySort');
            foreach ($aAttributeNames as $iKey => $aAttribute)
            {
                if ($aAttribute['i18n'] == false)
                {
                    if (isset($aAttributeValues[$aAttribute['name']]))
                    {
                        $aAttributeNames[$iKey]['value'] = $aAttributeValues[$aAttribute['name']];
                    }
                    else
                    {
                        $aAttributeNames[$iKey]['value'] = $aAttribute['default'];
                    }
                }
                else
                {
                    foreach ($aLanguages as $sLanguage)
                    {
                        if (isset($aAttributeValues[$aAttribute['name']][$sLanguage]))
                        {
                            $aAttributeNames[$iKey][$sLanguage]['value'] = $aAttributeValues[$aAttribute['name']][$sLanguage];
                        }
                        else
                        {
                            $aAttributeNames[$iKey][$sLanguage]['value'] = $aAttribute['default'];
                        }
                    }
                }
            }
            return $aAttributeNames;
        }

        function getQuestions($sid, $gid)
        {
            return self::model()->with('question_types')->findAllByAttributes(array('sid'=>$sid, 'gid'=>$gid, 'parent_id'=>0));
        }

        function getSubQuestions($parent_id)
        {
            return Yii::app()->db->createCommand()
            ->select()
            ->from(self::tableName())
            ->where('parent_id=:parent_id')
            ->bindParam(":parent_id", $parent_id, PDO::PARAM_INT)
            ->order('question_order asc')
            ->query();
        }

        /**
        * Insert an array into the questions table
        * Returns false if insertion fails, otherwise the new QID
        *
        * @param array $data
        */
        function insertRecords($data)
        {
            $questions = new self;
            foreach ($data as $k => $v){
                $questions->$k = $v;
                }
            try
            {
                $questions->save();
                return $questions->qid;
            }
            catch(Exception $e)
            {
                return false;
            }
        }

        public static function deleteAllById($questionsIds)
        {
            if ( !is_array($questionsIds) )
            {
                $questionsIds = array($questionsIds);
            }

            Yii::app()->db->createCommand()->delete(Conditions::model()->tableName(), array('in', 'qid', $questionsIds));
            Yii::app()->db->createCommand()->delete(Question_attributes::model()->tableName(), array('in', 'qid', $questionsIds));
            Yii::app()->db->createCommand()->delete(Answers::model()->tableName(), array('in', 'qid', $questionsIds));
            Yii::app()->db->createCommand()->delete(Questions::model()->tableName(), array('in', 'parent_id', $questionsIds));
            Yii::app()->db->createCommand()->delete(Questions::model()->tableName(), array('in', 'qid', $questionsIds));
            Yii::app()->db->createCommand()->delete(Defaultvalues::model()->tableName(), array('in', 'qid', $questionsIds));
            Yii::app()->db->createCommand()->delete(Quota_members::model()->tableName(), array('in', 'qid', $questionsIds));
        }

        function getAllRecords($condition, $order=FALSE)
        {
            $command=Yii::app()->db->createCommand()->select('*')->from($this->tableName())->where($condition);
            if ($order != FALSE)
            {
                $command->order($order);
            }
            return $command->query();
        }

        public function getQuestionsForStatistics($fields, $condition, $orderby=FALSE)
        {
            $command = Yii::app()->db->createCommand()
            ->select($fields)
            ->from(self::tableName())
            ->where($condition);
            if ($orderby != FALSE)
            {
                $command->order($orderby);
            }
            return $command->queryAll();
        }

        public function getQuestionList($surveyid, $language)
        {
            $query = "SELECT questions.*, groups.group_name, groups.group_order"
            ." FROM {{questions}} as questions, {{groups}} as groups"
            ." WHERE groups.gid=questions.gid"
            ." AND groups.language=:language1"
            ." AND questions.language=:language2"
            ." AND questions.parent_id=0"
            ." AND questions.sid=:sid";
            return Yii::app()->db->createCommand($query)->bindParam(":language1", $language, PDO::PARAM_STR)
                                                        ->bindParam(":language2", $language, PDO::PARAM_STR)
                                                        ->bindParam(":sid", $surveyid, PDO::PARAM_INT)->queryAll();
        }

    }

?>
