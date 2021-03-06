<?php 
/*
* LimeSurvey
* Copyright (C) 2007-2011 The LimeSurvey Project Team / Carsten Schmitz
* All rights reserved.
* License: GNU/GPL License v2 or later, see LICENSE.php
* LimeSurvey is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

/**
* Implements global  config
*/
class LSYii_Application extends CWebApplication
{
    protected $config = array();
    /**
     *
     * @var PluginManager
     */
    protected $pluginManager;
    
    /**
     * @var SurveySession
     */
    protected $surveySession;
    /**
    * Initiates the application
    *
    * @access public
    * @param array $config
    * @return void
    */
    public function __construct($config = null)
    {
        if (is_string($config) && !file_exists($config))
        {
            $config = Yii::app()->basePath . 'config/config-sample-mysql' . EXT;
        } 
        if(is_string($config)) {
            $config = require($config);
        }
        
        if (YII_DEBUG)
        {
            // If debug = 2 we add firebug / console logging for all trace messages
            // If you want to var_dump $config you could do:
            // 
            // Yii::trace(CVarDumper::dumpAsString($config), 'vardump');
            // 
            // or shorter:
            // 
            //traceVar($config);
            // 
            // This statement won't cause any harm or output when debug is 1 or 0             
            $config['preload'][] = 'log';
            if (array_key_exists('components', $config) && array_key_exists('log', $config['components'])) {
                // We already have some custom logging, only add our own
            } else {
                // No logging yet, set it up
                $config['components']['log'] = array(
                    'class' => 'CLogRouter');
            }
            // Add logging of trace
            $config['components']['log']['routes'][] = array(
                'class'                      => 'CWebLogRoute', // you can include more levels separated by commas... trace is shown on debug only
                'levels'                     => 'trace',        // you can include more separated by commas
                'categories'                 => 'vardump',      // show in firebug/console
                'showInFireBug'              => true
            );
            
            // if debugsql = 1 we add sql logging to the output
            if (array_key_exists('debugsql', $config['config']) && $config['config']['debugsql'] == 1) {
                // Add logging of trace
                $config['components']['log']['routes'][] = array(
                    'class'                      => 'CWebLogRoute', // you can include more levels separated by commas... trace is shown on debug only
                    'levels'                     => 'trace',        // you can include more separated by commas
                    'categories'                 => 'system.db.*',      // show in firebug/console
                    'showInFireBug'              => true
                );
                $config['components']['db']['enableProfiling'] = true;
                $config['components']['db']['enableParamLogging'] = true;
            }
        }

        if (!isset($config['components']['request']))
        {
            $config['components']['request']=array();
        }
        $config['components']['request']=array_merge_recursive($config['components']['request'],array(
            'class'=>'LSHttpRequest',
            'noCsrfValidationRoutes'=>array(
//              '^services/wsdl.*$'   // Set here additional regex rules for routes not to be validate 
                'getTokens_json',
                'getSurveys_json',
                'remotecontrol'
            ),
            'enableCsrfValidation'=>false,    // Enable to activate CSRF protection
            'enableCookieValidation'=>false   // Enable to activate cookie protection
        ));

        parent::__construct($config);

        $this->language = 'nl';
        // Load the default and environmental settings from different files into self.
        Yii::setPathOfAlias('bootstrap' , Yii::getPathOfAlias('ext.bootstrap'));
        $ls_config = require(Yii::getPathOfAlias('application.config') . '/config-defaults.php');
        $email_config = require(Yii::getPathOfAlias('application.config') . '/email.php');
        $version_config = require(Yii::getPathOfAlias('application.config') . '/version.php');
        $settings = array_merge($ls_config, $version_config, $email_config);
        
        if(file_exists(Yii::getPathOfAlias('application.config') . '/config.php'))
        {
            $ls_config = require(Yii::getPathOfAlias('application.config') . '/config.php');
            if(is_array($ls_config['config']))
            {
                $settings = array_merge($settings, $ls_config['config']);
            }
        }

        foreach ($settings as $key => $value)
            $this->setConfig($key, $value);
        
        // Now initialize the plugin manager
        $this->initPluginManager(); 
        
        $this->initSurveySession();
    }
    
    /**
     * This method handles initialization of the plugin manager
     * 
     * When you want to insert your own plugin manager, or experiment with different settings
     * then this is where you should do that.
     */
    public function initPluginManager()
    {
        Yii::import('application.libraries.PluginManager.*');
        Yii::import('application.libraries.PluginManager.Storage.*');
        Yii::import('application.libraries.PluginManager.Question.*');
        $this->pluginManager = new PluginManager('LimesurveyApi');
        
        // And load the active plugins
        $this->pluginManager->loadPlugins();
    }
    
    public function initSurveySession()
    {
        Yii::import('application.helpers.SurveySessionHelper', true);
        $this->surveySession = new SurveySession();
    }

    /**
    * Loads a helper
    *
    * @access public
    * @param string $helper
    * @return void
    */
    public function loadHelper($helper)
    {
        Yii::import('application.helpers.' . $helper . '_helper', true);
    }

    /**
    * Loads a library
    *
    * @access public
    * @param string $helper
    * @return void
    */
    public function loadLibrary($library)
    {
        Yii::import('application.libraries.'.$library, true);
    }

    /**
    * Sets a configuration variable into the config
    *
    * @access public
    * @param string $name
    * @param mixed $value
    * @return void
    */
    public function setConfig($name, $value)
    {
        $this->config[$name] = $value;
    }
    
    /**
     * Set a 'flash message'. 
     * 
     * A flahs message will be shown on the next request and can contain a message
     * to tell that the action was successful or not. The message is displayed and
     * cleared when it is shown in the view using the widget:
     * <code>
     * $this->widget('application.extensions.FlashMessage.FlashMessage');
     * </code> 
     * 
     * @param string $message
     * @return LSYii_Application Provides a fluent interface
     */
    public function setFlashMessage($message)
    {
        $this->session['flashmessage'] = $message;
        return $this;
    }

    /**
    * Loads a config from a file
    *
    * @access public
    * @param string $file
    * @return void
    */
    public function loadConfig($file)
    {
        $config = require_once(Yii::app()->basePath . '/config/' . $file . '.php');
        if(is_array($config))
        {
            foreach ($config as $k => $v)
                $this->setConfig($k, $v);
        }
    }

    /**
    * Returns a config variable from the config
    *
    * @access public
    * @param string $name
    * @return mixed
    */
    public function getConfig($name)
    {
        return isset($this->config[$name]) ? $this->config[$name] : false;
    }

    /**
     * Get the script manager.
     * @return LimeScript
     */
    public function getLimeScript()
    {
        return $this->getComponent('limescript');
    }
    /**
     * Get the pluginManager
     * 
     * @return PluginManager
     */
    public function getPluginManager()
    {
        return $this->pluginManager;
    }
    
    /**
     * Get the survey session manager.
     * @return SurveySession
     */
    public function getSurveySession()
    {
        return $this->surveySession;
    }
}

/**
 * If debug = 2 in application/config.php this will produce output in the console / firebug
 * similar to var_dump. It will also include the filename and line that called this method.
 * 
 * @param mixed $variable The variable to be dumped
 * @param int $depth Maximum depth to go into the variable, default is 10
 */
function traceVar($variable, $depth = 10) {
    $msg = CVarDumper::dumpAsString($variable, $depth, false);
    $fullTrace = debug_backtrace();
    $trace=array_shift($fullTrace);
	if(isset($trace['file'],$trace['line']) && strpos($trace['file'],YII_PATH)!==0)
	{
        $msg = $trace['file'].' ('.$trace['line']."):\n" . $msg;
    }
    Yii::trace($msg, 'vardump');
}