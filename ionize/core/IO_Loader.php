<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package	Ionize
 * @author	Adam Liszkai <contact@liszkaiadam.hu>
 * @link	http://ionizecms.com
 * @since	Version 2.0.0
 */
class IO_Loader extends CI_Loader
{
	/* ------------------------------------------------------------------------------------------------------------ */
	
    /**
     * List of paths to namespaces from
     *
     * @var int
     * @access protected
     */
    protected $_ci_namespaces_paths      = array();
    /* ------------------------------------------------------------------------------------------------------------ */
    
    /**
     * List of paths to interfaces from
     *
     * @var array
     * @access protected
     */
    protected $_ci_interfaces_paths     = array();
    /* ------------------------------------------------------------------------------------------------------------ */
    
    /**
     * List of loaded namespaces
     *
     * @var array
     * @access protected
     */
    protected $_ci_namespaces            = array();
    /* ------------------------------------------------------------------------------------------------------------ */
    
    /**
     * List of loaded interfaces
     *
     * @var array
     * @access protected
     */
    protected $_ci_interfaces           = array();
    /* ------------------------------------------------------------------------------------------------------------ */

    /**
     * Constructor
     *
     * Sets the path to the view files and gets the initial output buffering level
     */
    function __construct()
    {
        parent::__construct();
        $this->_ci_namespaces_paths = array(APPPATH);
        $this->_ci_interfaces_paths = array(APPPATH);
        log_message('debug', "Loader Class Initialized");
    }
	/* ------------------------------------------------------------------------------------------------------------ */

    /**
     * Initialize the Loader
     *
     * This method is called once in CI_Controller.
     *
     * @param   array
     * @return  object
     */
    public function initialize()
    {
        $this->_ci_interfaces = array();
        $this->_ci_namespaces = array();
        $this->_ci_autoloader();

        return $this;
    }
	/* ------------------------------------------------------------------------------------------------------------ */

    /**
     * Interface Loader
     *
     * This function lets users load and instantiate interfaces.
     *
     * @param   string  the name of the class
     * @param   string  name for the interface
     * @param   bool    database connection
     * @return  void
     */
    public function interfaces($interfaces, $name = '', $db_conn = FALSE)
    {
        if (is_array($interfaces))
        {
            foreach ($interfaces as $babe)
            {
                $this->interfaces($babe);
            }
            return;
        }

        if ($interfaces == '')
        {
            return;
        }

        $path = '';

        // Is the abstracts in a sub-folder? If so, parse out the filename and path.
        if (($last_slash = strrpos($interfaces, '/')) !== FALSE)
        {
            // The path is in front of the last slash
            $path = substr($interfaces, 0, $last_slash + 1);

            // And the model name behind it
            $interfaces = substr($interfaces, $last_slash + 1);
        }

        if ($name == '') $name = $interfaces;

        if (in_array($name, $this->_ci_interfaces, TRUE)) return;

        foreach ($this->_ci_interfaces_paths as $mod_path)
        {
            if ( ! file_exists($mod_path.'interfaces/'.$path.$interfaces.'.php'))
            {
                continue;
            }
            
            require_once($mod_path.'interfaces/'.$path.$interfaces.'.php');
            $this->_ci_interfaces[] = $name;
            return;
        }

        // couldn't find the interfaces
        show_error('Unable to locate the interfaces you have specified: '.$interfaces);
    }
	/* ------------------------------------------------------------------------------------------------------------ */
    
    /**
     * Namespaces Loader
     *
     * This function lets users load namespaces default codes and functions
     *
     * @param   string  the name of the namespace
     * @return  void
     */
    public function namespaces($namespace)
    {
        if (is_array($namespace))
        {
            foreach ($namespace as $item)
            {
                $this->namespaces($item);
            }
            return;
        }

        if ($namespace == '')
        {
            return;
        }

        $path = '';

        // Is the abstracts in a sub-folder? If so, parse out the filename and path.
        if (($last_slash = strrpos($namespace, '/')) !== FALSE)
        {
            // The path is in front of the last slash
            $path = substr($namespace, 0, $last_slash + 1);

            // And the model name behind it
            $namespace = substr($namespace, $last_slash + 1);
        }

		// If already loaded the namespace then exit the function
        if (in_array($namespace, $this->_ci_namespaces, TRUE)) return;
        
        foreach ($this->_ci_namespaces_paths as $mod_path)
        {
            if (!file_exists($mod_path.'namespaces/'.$path.$namespace.'.php'))
            	continue;

            require_once($mod_path.'namespaces/'.$path.$namespace.'.php');

            $this->_ci_abstracts[] = $namespace;
            return;
        }

        // couldn't find the abstracts
        show_error('Unable to locate the namespace you have specified: '.$namespace);
    }
	/* ------------------------------------------------------------------------------------------------------------ */

    /**
     * Autoloader
     *
     * The config/autoload.php file contains an array that permits sub-systems,
     * libraries, and helpers to be loaded automatically.
     *
     * @param   array
     * @return  void
     */
    protected function _ci_autoloader()
    {
    	if (file_exists(APPPATH.'config/autoload.php'))
		{
			include(APPPATH.'config/autoload.php');
		}

		if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/autoload.php'))
		{
			include(APPPATH.'config/'.ENVIRONMENT.'/autoload.php');
		}

		if ( ! isset($autoload))
		{
			return;
		}

		// Autoload packages
		if (isset($autoload['packages']))
		{
			foreach ($autoload['packages'] as $package_path)
			{
				$this->add_package_path($package_path);
			}
		}

		// Load any custom config file
		if (count($autoload['config']) > 0)
		{
			foreach ($autoload['config'] as $val)
			{
				$this->config($val);
			}
		}

		// Autoload helpers and languages
		foreach (array('helper', 'language') as $type)
		{
			if (isset($autoload[$type]) && count($autoload[$type]) > 0)
			{
				$this->$type($autoload[$type]);
			}
		}

		// Autoload drivers
		if (isset($autoload['drivers']))
		{
			foreach ($autoload['drivers'] as $item)
			{
				$this->driver($item);
			}
		}
		
		// Interfaces models
        if (isset($autoload['interfaces']))
        {
            $this->interfaces($autoload['interfaces']);
        }
        
        // Namespaces models
        if (isset($autoload['namespaces']))
        {
            $this->namespaces($autoload['namespaces']);
        }

		// Load libraries
		if (isset($autoload['libraries']) && count($autoload['libraries']) > 0)
		{
			// Load the database driver.
			if (in_array('database', $autoload['libraries']))
			{
				$this->database();
				$autoload['libraries'] = array_diff($autoload['libraries'], array('database'));
			}

			// Load all other libraries
			$this->library($autoload['libraries']);
		}

		// Autoload models
		if (isset($autoload['model']))
		{
			$this->model($autoload['model']);
		}
    }

    // --------------------------------------------------------------------
    
    /**
	 * Internal CI Library Instantiator
	 *
	 * @used-by	CI_Loader::_ci_load_stock_library()
	 * @used-by	CI_Loader::_ci_load_library()
	 *
	 * @param	string		$class		Class name
	 * @param	string		$prefix		Class name prefix
	 * @param	array|null|bool	$config		Optional configuration to pass to the class constructor:
	 *						FALSE to skip;
	 *						NULL to search in config paths;
	 *						array containing configuration data
	 * @param	string		$object_name	Optional object name to assign to
	 * @return	void
	 */
	protected function _ci_init_library($class, $prefix, $config = FALSE, $object_name = NULL)
	{
		// Is there an associated config file for this class? Note: these should always be lowercase
		if ($config === NULL)
		{
			// Fetch the config paths containing any package paths
			$config_component = $this->_ci_get_component('config');

			if (is_array($config_component->_config_paths))
			{
				$found = FALSE;
				foreach ($config_component->_config_paths as $path)
				{
					// We test for both uppercase and lowercase, for servers that
					// are case-sensitive with regard to file names. Load global first,
					// override with environment next
					if (file_exists($path.'config/'.strtolower($class).'.php'))
					{
						include($path.'config/'.strtolower($class).'.php');
						$found = TRUE;
					}
					elseif (file_exists($path.'config/'.ucfirst(strtolower($class)).'.php'))
					{
						include($path.'config/'.ucfirst(strtolower($class)).'.php');
						$found = TRUE;
					}

					if (file_exists($path.'config/'.ENVIRONMENT.'/'.strtolower($class).'.php'))
					{
						include($path.'config/'.ENVIRONMENT.'/'.strtolower($class).'.php');
						$found = TRUE;
					}
					elseif (file_exists($path.'config/'.ENVIRONMENT.'/'.ucfirst(strtolower($class)).'.php'))
					{
						include($path.'config/'.ENVIRONMENT.'/'.ucfirst(strtolower($class)).'.php');
						$found = TRUE;
					}

					// Break on the first found configuration, thus package
					// files are not overridden by default paths
					if ($found === TRUE)
					{
						break;
					}
				}
			}
		}

		$class_name = $prefix.$class;

		// Is the class name valid?
		if ( ! class_exists($class_name, FALSE))
		{
			log_message('error', 'Non-existent class: '.$class_name);
			show_error('Non-existent class: '.$class_name);
		}

		// Set the variable name we will assign the class to
		// Was a custom class name supplied? If so we'll use it
		if (empty($object_name))
		{
			$object_name = strtolower($class);
			if (isset($this->_ci_varmap[$object_name]))
			{
				$object_name = $this->_ci_varmap[$object_name];
			}
		}

		// Don't overwrite existing properties
		$CI =& get_instance();
		if (isset($CI->$object_name))
		{
			if ($CI->$object_name instanceof $class_name)
			{
				log_message('debug', $class_name." has already been instantiated as '".$object_name."'. Second attempt aborted.");
				return;
			}

			show_error("Resource '".$object_name."' already exists and is not a ".$class_name." instance.");
		}

		// Save the class name and object name
		$this->_ci_classes[$object_name] = $class;

		$class = new ReflectionClass($class_name);
		$abstract = $class->isAbstract();

		if( !$abstract )
		{
			// Instantiate the class
			$CI->$object_name = isset($config)
				? new $class_name($config)
				: new $class_name();
		}
	}

}

/* End of file IO_Loader.php */
/* Location: ./ionize/core/IO_Loader.php */
