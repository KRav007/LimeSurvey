<?php
/*
* LimeSurvey
* Copyright (C) 2011 The LimeSurvey Project Team / Carsten Schmitz
* All rights reserved.
* License: GNU/GPL License v2 or later, see LICENSE.php
* LimeSurvey is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*
*   $Id$
*/

class User extends CActiveRecord
{

    /**
    * UserRights : available User rights
    * @static array
    */
    public static $UserRights=array('superadmin','configurator','manage_survey','create_survey','participant_panel','create_user','delete_user','manage_template','manage_label','copy_model','manage_model');

    /**
    * Returns the static model of Settings table
    *
    * @static
    * @access public
    * @param string $class
    * @return User
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
        return '{{users}}';
    }

    /**
    * Returns the primary key of this table
    *
    * @access public
    * @return string
    */
    public function primaryKey()
    {
        return 'uid';
    }

    /**
    * Defines several rules for this table
    *
    * @access public
    * @return array
    */
    public function rules()
    {
        $rightRules=array();
        foreach(self::$UserRights as $right)
        {
            $rightRules[]=array($right,'boolean', 'falseValue'=>0,'trueValue'=>1,'strict'=>false,'allowEmpty'=>true);
        }
        return array_merge ( 
        array(
            array('users_name, password, email', 'required'),
            array('email', 'email'),
        ),
        $rightRules
        );
    }

    /**
    * Returns all users
    *
    * @access public
    * @return string
    */
    public function getAllRecords($condition=FALSE)
    {
        $criteria = new CDbCriteria;

        if ($condition != FALSE)
        {
            foreach ($condition as $item => $value)
            {
                $criteria->addCondition($item.'='.Yii::app()->db->quoteValue($value));
            }
        }

        $data = $this->findAll($criteria);

        return $data;
    }
    function parentAndUser($postuserid)
    {
        $user = Yii::app()->db->createCommand()
        ->select('a.users_name, a.full_name, a.email, a.uid,  b.users_name AS parent')
        ->limit(1)
        ->where('a.uid = :postuserid')
        ->from("{{users}} a")
        ->leftJoin('{{users}} AS b', 'a.parent_id = b.uid')
        ->bindParam(":postuserid", $postuserid, PDO::PARAM_INT)
        ->queryRow();
        return $user;
    }

    /**
    * Returns onetime password
    *
    * @access public
    * @return string
    */
    public function getOTPwd($user)
    {
        $this->db->select('uid, users_name, password, one_time_pw, dateformat, full_name, htmleditormode');
        $this->db->where('users_name',$user);
        $data = $this->db->get('users',1);

        return $data;
    }

    /**
    * Deletes onetime password
    *
    * @access public
    * @return string
    */
    public function deleteOTPwd($user)
    {
        $data = array(
        'one_time_pw' => ''
        );
        $this->db->where('users_name',$user);
        $this->db->update('users',$data);
    }

    /**
    * Creates new user
    *
    * @access public
    * @return string
    */
    public static function insertUser($new_user, $new_pass,$new_full_name,$parent_user,$new_email)
    {
        $oUser = new self;
        $oUser->users_name = $new_user;
        $oUser->password = hash('sha256', $new_pass);
        $oUser->full_name = $new_full_name;
        $oUser->parent_id = $parent_user;
        $oUser->lang = 'auto';
        $oUser->email = $new_email;
        if ($oUser->save())
        {
            return $oUser->uid;
        }
        else{
            return false;
        }
    }

    /**
     * This method is invoked before saving a record (after validation, if any).
     * The default implementation raises the {@link onBeforeSave} event.
     * You may override this method to do any preparation work for record saving.
     * Use {@link isNewRecord} to determine whether the saving is
     * for inserting or updating record.
     * Make sure you call the parent implementation so that the event is raised properly.
     * @return boolean whether the saving should be executed. Defaults to true.
     */
    public function beforeSave()
    {
         // Postgres delivers bytea fields as streams :-o - if this is not done it looks like Postgres saves something unexpected
        if (gettype($this->password)=='resource')
        {
            $this->password=stream_get_contents($this->password,-1,0); 
        }
        return parent::beforeSave();
    }

    /**
    * Delete user
    *
    * @param int $iUserID The User ID to delete
    * @return mixed
    */
    function deleteUser($iUserID)
    {
        $iUserID= (int)$iUserID;
        $iRecordsAffected = Yii::app()->db->createCommand()->from('{{users}}')->delete('{{users}}', "uid={$iUserID}");
        return (bool) $iRecordsAffected;
    }

    /**
    * Returns user share settings
    *
    * @access public
    * @return string
    */
    public function getShareSetting()
    {
        $this->db->where(array("uid"=>$this->session->userdata('loginID')));
        $result= $this->db->get('users');
        return $result->row();
    }

    /**
    * Returns full name of user
    *
    * @access public
    * @return string
    */
    public function getName($userid)
    {
        static $aOwnerCache = array();
        
        if (array_key_exists($userid, $aOwnerCache)) {
            $result = $aOwnerCache[$userid];
        } else {
            $result = Yii::app()->db->createCommand()->select('full_name')->from('{{users}}')->where("uid = :userid")->bindParam(":userid", $userid, PDO::PARAM_INT)->queryAll();
            $aOwnerCache[$userid] = $result;
        }
        
        return $result;
    }

    public function getuidfromparentid($parentid)
    {
        return Yii::app()->db->createCommand()->select('uid')->from('{{users}}')->where('parent_id = :parent_id')->bindParam(":parent_id", $parentid, PDO::PARAM_INT)->queryRow();
    }
    /**
    * Returns id of user
    *
    * @access public
    * @return string
    */
    public function getID($fullname)
    {
        $this->db->select('uid');
        $this->db->from('users');
        $this->db->where(array("full_name"=>Yii::app()->db->quoteValue($fullname)));
        $result = $this->db->get();
        return $result->row();
    }

    /**
    * Updates user password
    *
    * @access public
    * @return string
    */
    public function updatePassword($uid,$password)
    {
        return $this->updateByPk($uid, array('password' => $password));
    }

    /**
    * Adds user record
    *
    * @access public
    * @return string
    */
    public function insertRecords($data)
    {
        return $this->db->insert('users',$data);
    }

    /**
    * Returns User ID common in Survey_Permissions and User_in_groups
    *
    * @access public
    * @return CDbDataReader Object
    */
    public function getCommonUID($surveyid, $postusergroupid)
    {
        $query2 = "SELECT b.uid FROM (SELECT uid FROM {{survey_permissions}} WHERE sid = :surveyid) AS c RIGHT JOIN {{user_in_groups}} AS b ON b.uid = c.uid WHERE c.uid IS NULL AND b.ugid = :postugid";
        return Yii::app()->db->createCommand($query2)->bindParam(":surveyid", $surveyid, PDO::PARAM_INT)->bindParam(":postugid", $postusergroupid, PDO::PARAM_INT)->query(); //Checked
    }

     /**
    * Set the user rights
    *
    * @access public
    * @return boolean
    */
    public static function setUserRights($iUserID, $rights=array())
    {
        $iUserID= (int)$iUserID;
        $oUser=self::model()->findByPk($iUserID);
        if(!$oUser)
            return false;
        $rights['create_survey']=($rights['create_survey'] || $rights['copy_model']);
        foreach($rights as $right=>$value)
        {
            if(in_array($right,self::$UserRights))
                $oUser->$right=$value;
        }
        if ($oUser->save())
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
    * Returns global user rights 
    * By default the right of the login user
    * By default the array of user right true or false
    *
    * @param string $user_right, by default false to return array of rights
    * @param int $userid, by default the session userid
    * @access public
    * @return array of userright by default
    * @return boolean if $right is set and a string
    */
    public static function GetUserRights($user_right=false,$userid=false)
    {
    
        if(!$userid)
        {
            $userid=Yii::app()->session['loginID'];
        }
        // If right and right is in session, return actual session
        if( $user_right && $userid==Yii::app()->session['loginID'] && isset(Yii::app()->session['USER_RIGHT_'.strtoupper($user_right)]))
        {
            return Yii::app()->session['USER_RIGHT_'.strtoupper($user_right)];
        }

        $user=self::model()->findByPk($userid);
        // is $user_right, return the corresponding attribute
        if($user_right)
        {
            if(strtoupper($user_right)=="INITIALSUPERADMIN")
            {
                return (!$user->parent_id);
            }
            else
            {
                return ($user->$user_right || $user->superadmin);
            }
        }
        // else array of user rights
        $userrights=array();
        foreach(self::$UserRights as $right)
        {
            $userrights[$right]=($user->$right || $user->superadmin);
        }
        $userrights['initialsuperadmin']=(!$user->parent_id);
        $userrights['superadmin']=($userrights['superadmin'] || $userrights['initialsuperadmin']);
        return $userrights;
    }
}
