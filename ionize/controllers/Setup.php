<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package	Ionize
 * @author	Adam Liszkai <contact@liszkaiadam.hu>
 * @link	http://ionizecms.com
 * @since	Version 2.0.0
 */
 class Setup extends IO_Controller
{
	/* ------------------------------------------------------------------------------------------------------------- */
	
	public function __construct()
	{
		parent::__construct();
		
		$this->lang->load('setup', $this->language);
		
		$this->data['checkconfig_cls'] = 'class=""';
		$this->data['database_cls'] = 'class=""';
		$this->data['migration_cls'] = 'class=""';
		$this->data['user_cls'] = 'class=""';
		$this->data['data_cls'] = 'class=""';
		$this->data['finish_cls'] = 'class=""';
	}
	/* ------------------------------------------------------------------------------------------------------------- */

	public function index()
	{
		$this->render( 'index' );
	}
	/* ------------------------------------------------------------------------------------------------------------- */
	
	public function checkconfig()
	{
		$this->data['checkconfig_cls'] = 'class="active"';
		$this->data['database_cls'] = 'class=""';
		$this->data['migration_cls'] = 'class=""';
		$this->data['user_cls'] = 'class=""';
		$this->data['data_cls'] = 'class=""';
		$this->data['finish_cls'] = 'class=""';
		
		$checklist = array();
		
		// PHP version >= 5
		$checklist['php_version'] = version_compare(substr(phpversion(), 0, 4), '5.4', '>=');

		// MySQL support
		$checklist['mysql_support']  = function_exists('mysqli_connect');
		
		// Safe Mode
		$checklist['safe_mode']  = (ini_get('safe_mode')) ? FALSE : TRUE;
		
		// Files upload
		$checklist['file_uploads'] = (ini_get('file_uploads')) ? TRUE : FALSE;
		
		// GD lib
		$checklist['gd_lib'] = function_exists('imagecreatetruecolor');
		
		// Check files rights
		$files = array(
			'ionize/config/config.php',
			'ionize/config/database.php'
		);

		$check_files = array();
		foreach($files as $file)
			$check_files[$file] = is_really_writable(FCPATH . $file);

		// Check folders rights
		$folders = array(
			'ionize/config',
			'static',
			'themes'
		);
		
		$check_folders = array();
		foreach($folders as $folder)
			$check_folders[$folder] = $this->_test_dir(FCPATH. $folder, true);
		
		$this->data['check_config_error'] = "";
		$this->data['next_step'] = true;
		
		foreach($checklist as $config)
		{
			if ( ! $config)
			{
				$this->data['next_step'] = false;
				$this->data['check_config_error'] = lang('config_check_errors');
			}
		}
		
		$this->data['check_files'] = $check_files;
		$this->data['check_folders'] = $check_folders;
		$this->data = array_merge($this->data, $checklist);
		
		$this->render( 'checkconfig' );
	}
	/* ------------------------------------------------------------------------------------------------------------- */
	
	/**
	 * Tests if a dir is writable
	 *
	 * @param	string		folder path to test
	 * @param	boolean		if true, check all directories recursively
	 *
	 * @return	boolean		true if every tested dir is writable, false if one is not writable
	 *
	 */
	private function _test_dir($dir, $recursive = false)
	{
		if ( ! is_really_writable($dir) OR !$dh = opendir($dir))
			return false;
		if ($recursive)
		{
			while (($file = readdir($dh)) !== false)
				if (@filetype($dir.$file) == 'dir' && $file != '.' && $file != '..')
					if (!$this->_test_dir($dir.$file, true))
						return false;
		}
		
		closedir($dh);
		return true;
	}
	
	/* ------------------------------------------------------------------------------------------------------------- */
	
	public function database()
	{
		$this->data['checkconfig_cls'] = 'class="done"';
		$this->data['database_cls'] = 'class="active"';
		$this->data['migration_cls'] = 'class=""';
		$this->data['user_cls'] = 'class=""';
		$this->data['data_cls'] = 'class=""';
		$this->data['finish_cls'] = 'class=""';
		
		$this->render( 'database' );
	}
	/* ------------------------------------------------------------------------------------------------------------- */
	
	public function migrate()
	{
		$this->render( 'migrate' );
	}
	/* ------------------------------------------------------------------------------------------------------------- */
	
	public function user()
	{
		$this->render( 'user' );
	}
	/* ------------------------------------------------------------------------------------------------------------- */
	
	public function data()
	{
		$this->render( 'data' );
	}
	/* ------------------------------------------------------------------------------------------------------------- */
	
	public function finish()
	{
		$this->render( 'finish' );
	}
	/* ------------------------------------------------------------------------------------------------------------- */
	
	private function render( $page = 'index' )
	{
		$this->load->view('setup/header', $this->data);
		$this->load->view('setup/'.$page, $this->data);
		$this->load->view('setup/footer', $this->data);
	}
	/* ------------------------------------------------------------------------------------------------------------- */
}
/* End of file: Setup.php */
/* Location: ./ionize/controllers/Setup.php */
