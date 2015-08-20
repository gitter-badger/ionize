<?php namespace Model\Data;

/**
 * @package	Ionize
 * @author	Adam Liszkai <contact@liszkaiadam.hu>
 * @link	http://ionizecms.com
 * @since	Version 2.0.0
 */
class Content implements \DataModel
{
	/* ------------------------------------------------------------------------------------------------------------- */
	
	/**
	 * @var $id integer Content ID or NULL
	 * @access private
	 */	
	private $id = NULL;
	/* ------------------------------------------------------------------------------------------------------------- */
	
	/**
	 * @var $data array Content parsed data from $_data
	 * @access public static
	 */
	public static $data = array();
	/* ------------------------------------------------------------------------------------------------------------- */
	
	/**
	 * @var $instance \Data\Content Static Reference for this class 
	 * @access public static
	 */
	public static $instance = NULL;
	/* ------------------------------------------------------------------------------------------------------------- */
	
	/**
	 * @var $_data array Parsed Content data
	 * @access private
	 */
	private $_data = array();
	/* ------------------------------------------------------------------------------------------------------------- */
	
	/**
	 * @var $childrens array Child Content instances array
	 * @access private
	 */
	private $childrens = array();
	/* ------------------------------------------------------------------------------------------------------------- */
	
	/**
	 * @var $benchmark \CI_Benchmark Codeigniter Benchmark Class
	 * @access private
	 */
	private $benchmark = NULL;
	
	/**
	 * @var $_cache \CI_Cache Codeigniter Cache Class
	 * @access private
	 */
	private $cache = NULL;
	/* ------------------------------------------------------------------------------------------------------------- */
	
	/**
	 * @var $cache_time int Cache time in seconds
	 * @access private
	 */
	private $cache_time = 60 * 60 * 24 * 7;
	/* ------------------------------------------------------------------------------------------------------------- */
	
	/* ------------------------------------------------------------------------------------------------------------- */
	/* Magick Methods ---------------------------------------------------------------------------------------------- */
	/* ------------------------------------------------------------------------------------------------------------- */
	
	/**
	 * __get()
	 *
	 * @param	object	$data	Content object for initialization
	 * @return	void
	 */
	public function __construct( $data = NULL )
	{
		$codeigniter =& get_instance();		
		$codeigniter->benchmark->mark('Content_class_construct_start');
		
		// Set the cache time from the config file
		$codeigniter->config->load('ionize', TRUE);
		$this->cache_time = $codeigniter->config->config['ionize']['data_model_cache'];
		
		// Generating class from data
		if( $data != NULL ) $this->initialize( $data );
		
		// Saving instance reference
		self::$instance = $this;
		
		$codeigniter->benchmark->mark('Content_class_construct_end');
	}
	/* ------------------------------------------------------------------------------------------------------------- */
	
	/**
	 * __get()
	 *
	 * @param	string	$key	Content data key
	 * @return	mixed
	 */
	public function __get($key)
	{
		if (isset($this->_data[$key])) return $this->_data[$key];
		return NULL;
	}
	/* ------------------------------------------------------------------------------------------------------------- */

	/**
	 * __set()
	 *
	 * @param	string	$key	Content data key
	 * @param	mixed	$value	Content data value
	 * @return	void
	 */
	public function __set($key, $value)
	{
		$this->_data[$key] = $value;
	}
	/* ------------------------------------------------------------------------------------------------------------- */
	
	/**
	 * __sleep()
	 *
	 * During serialization save only the id, raw_data and _data properties
	 *
	 * @return	array
	 */
	public function __sleep()
	{
		$serialize_array = array('id','_data');
		return $serialize_array;
	}
	/* ------------------------------------------------------------------------------------------------------------- */
	
	/**
	 * __wakeup()
	 *
	 * After unserialize reinitialize the class
	 *
	 * @return	void
	 */
	public function __wakeup()
	{
		$codeigniter =& get_instance();
		$codeigniter->benchmark->mark('Content_class___wakeup_start');
		
		// Initialize class by raw data
		$class = $this->initialize( $this->_data );
		
		// Saving instance reference
		self::$instance = $this;
		
		$codeigniter->benchmark->mark('Content_class___wakeup_end');
	}
	/* ------------------------------------------------------------------------------------------------------------- */
	
	/**
	 * __toString()
	 *
	 * If the Class called as String then the output pull be json string
	 *
	 * @return	json
	 */
	public function __toString()
	{
 		$json_data = json_encode($this->_data);
		return $json_data;
	}
	/* ------------------------------------------------------------------------------------------------------------- */
		
	/**
	 * __destruct()
	 *
	 * Saving the Content data to the cache folder
	 *
	 * @return	void
	 */
	function __destruct()
	{
		// If has data to cache
		if($this->id != NULL && $this->cache_time > 0)
		{
			$codeigniter =& get_instance();
			// If cache not available then create cache from data
			$cache = $codeigniter->cache->file->get(md5($this->id).'.Content');
			if($cache == FALSE)
			{
				$codeigniter->benchmark->mark('Content_class___destruct_start');
				$codeigniter->cache->file->save(md5($this->id).'.Content', serialize($this), $this->cache_time);
				$codeigniter->benchmark->mark('Content_class___destruct_end');
			}
		}
   	}
   	/* ------------------------------------------------------------------------------------------------------------- */
   	
	/* ------------------------------------------------------------------------------------------------------------- */
	/* Public Functions -------------------------------------------------------------------------------------------- */
	/* ------------------------------------------------------------------------------------------------------------- */

	public function initialize( $data )
	{
		$codeigniter =& get_instance();
		$codeigniter->benchmark->mark('Content_class_initialize_start');
		
		// Saving data to properties
		$this->_data = (array) $data;
		
		// Creating content ID property
		if($this->id == NULL) $this->id = $data->id_content;
		
		// Declarating Children Contents
		if($this->children != "")
		{
			$children = explode(',',$this->children);
			if(count($children) > 0)
			{
				foreach($children as $index => $id_content)
				{
					$this->childrens[] = \Model\Data\Content::get_instance()->getByID( $id_content );
				}
			}
		}
		
		// Create reference to datas for static
		self::$data = $this->_data;
		
		$codeigniter->benchmark->mark('Content_class_initialize_end');
		return $this;
	}
	/* ------------------------------------------------------------------------------------------------------------- */
	
	public static function get_instance()
	{
		return self::$instance;
	}
	/* ------------------------------------------------------------------------------------------------------------- */
	
	public function is( $type )
	{
		if(strtolower($type) === "active") if($this->isActive() === TRUE) return TRUE;
		return (strtolower($type) === "content" ? TRUE : FALSE);
	}
	/* ------------------------------------------------------------------------------------------------------------- */
	
	public function isActive()
	{
		if($this->id == \Output::$current_content->getID()) return TRUE;
		else return FALSE;
	}
	/* ------------------------------------------------------------------------------------------------------------- */
	
	public function getID()
	{
		if($this->id != NULL) return $this->id;
		else
		{
			log_message('ERROR', 'Data\Content->getID(): Data must be loaded first before geting ID');
			throw new \RuntimeException('Model\Data\Content->getID(): Data must be loaded first before geting ID');
		}
	}
	/* ------------------------------------------------------------------------------------------------------------- */
	
	public function getByID( $id = NULL, $forceSelect = FALSE )
	{
		$codeigniter =& get_instance();
		$codeigniter->benchmark->mark('Content_class_getByID_start');
		if( $forceSelect === FALSE )
		{
			// Restoring cached data
			$cache = $codeigniter->cache->file->get(md5($id).'.Content');
			if($cache != FALSE)
			{
				log_message('DEBUG', 'Data\Content->getByID(): Load Content Data from Cache: #'.$id);
				$codeigniter->benchmark->mark('Content_class_getByID_end');
				return unserialize($cache);
			}
			// If class has the current content data already
			else if( $this->id === $id ) return $this;
			// If cache was unsuccesfull then select the content
			else return $this->getByID( $id, TRUE );
		}
		else if(is_numeric($id))
		{
			$content = \Model\Database\Content::get_instance();
			$query = $content->where('id_content', $id)->get();
			if($query->num_rows() > 0)
			{
				log_message('DEBUG', 'Data\Content->getByID(): Load Content Data from Database: #'.$id);
				$codeigniter->benchmark->mark('Content_class_getByID_end');
				return $this->initialize( $query->row() );
			}
		}
		else
		{
			log_message('ERROR', 'Data\Content->getByID(): ID parameter must be numeric type');
			$codeigniter->benchmark->mark('Content_class_getByID_end');
			
			// Throwning Exception about invalid parameter
			throw new \InvalidArgumentException('Model\Data\Content->getByID(): ID parameter must be numeric type');
		}
	}
	/* ------------------------------------------------------------------------------------------------------------- */
}
/* End of file: Content.php */
/* Location: ./ionize/models/data/Content.php */