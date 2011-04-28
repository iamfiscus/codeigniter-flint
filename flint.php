#!/usr/bin/php
<?php

class Flint {
	
	// current working directory
	public $wd;
	
	// file path
	public $filepath;
	
	// arguments from command line
	private $argv;
	
	// number of arguments
	private $argc;
	
	// type of structure we are creating
	private $type;
	
	// singular name of structure
	private $name;
	
	// plural name of structure
	private $plural_name;
	
	// use mvc defaults
	public $mvc = 'model,view,controller';
	
	public $views = 'index,view,create,edit,delete,_form';
	
	// use datamapper
	public $datamapper = FALSE;
	
	// constructor
	public function __construct($argv, $argc) {
		// set directory
		$this->wd = getcwd();
		
		// set params
		$this->argv = $argv;
		$this->argc = $argc;
		
		// check flags
		$this->process_args();
		
		// process command
		$this->process_command();
	}
	
	// show help text
	private function help() {
		$output = <<<EOD

Flint 0.1
---------
Copyright (c) 2011 Aaron Kuzemchak <http://kuzemchak.net/> and JD Fiscus <http://iamfiscus.com/>
License: MIT License <http://www.opensource.org/licenses/mit-license.php>

USAGE:
    php flint.php --help
    php flint.php generate controller dictionaries
    php flint.php generate [--datamapper] model dictionary
    php flint.php generate views dictionaries
    php flint.php generate [--datamapper] mvc dictionary [--plural=dictionaries]

NOTE:
    In the above examples, "dictionary" and "dictionaries" are NOT special keywords. These
    simply represent the name of the controller/model/etc. that you are generating. So, if
    you wanted a controller named "news" you would do so as follows:
    
    php flint.php generate controller news

MVC:
    When generating a mvc, Cigen will create a controller with the plural
    name, a model with the singular name, and view files for index, create,
    edit, view, delete, and a form inside a folder with the plural name.
    
    To specify a plural name when MVCing, simply separate the singular and
    plural names with a forward slash (see example above).

OPTIONS:
    --datamapper:
        Use this to create a DataMapper model instead of a traditional one\n\n
EOD;
		print($output);
		exit;
	}
	
	// check arguments that were passed
	private function process_args() {
		foreach ($this->argv as $k => $a) {
			if (strpos($a,'--')===0) {
				// check for help flag
				if($a == '--help' || $this->argv < 4) {
					$this->help();
				}
				
				// check for datamapper flag
				if($a == '--datamapper') {
					$this->datamapper = TRUE;
				}
				
				// check for plural flag
				if(preg_match("/^--plural=(.+)$/", $a, $matches)) {
					$this->plural_name = strtolower($matches[1]);
				}
				
				// check for mvc array flag
				if(preg_match("/^--mvc=(.+)$/", $a, $matches)) {
					$this->mvc = strtolower($matches[1]);
				}
				
				// Unset value 
				unset($this->argv[$k]);
				
			}
			
		}
		
		// Reindex argv array
		$this->argv = array_values($this->argv);
		
		// set name and type of structure
		$this->type = strtolower($this->argv[2]);
		$this->name = strtolower($this->argv[3]);
		$this->plural_name = ($this->plural_name) ? $this->plural_name : $this->name.'s';
		
		//var_dump($this);
		
	}
	
	// determines which command to run
	private function process_command() {
		switch(strtolower($this->argv[1])) {
			case 'generate':
				call_user_func(array($this, "generate_{$this->type}"));
				break;
			default:
				$this->help();
		}
	}
	
	// Prompt user if the file exists
	private function file_exits_prompt() {
		// Check if file exists
		if (file_exists($this->filepath)) {

			// Confirmation Prompt
			$pathinfo = pathinfo($this->filepath);
			$pretty = basename($pathinfo['dirname']) . '/' . $pathinfo['basename'];
			
			$message   =  "Are you sure you replace $pretty? [Y/n]";
			print $message;
			$confirmation  = trim( fgets( STDIN ) );

			// No exit
			if ( strtolower($confirmation) == 'n' ) {
			   // The user did not say 'y'.
			   exit (0);
			}
		}
	}
	
	// generates controller
	private function generate_controller() {
		if ($this->datamapper) {
			$template = <<<EOD
<?php

class %1\$s extends CI_Controller {

	// constructor
	public function __construct() {
		parent::__construct();
	}

	// index
	public function index() {
		\$i = new %2\$s;
		\$i->get();

		\$this->load->view('$this->plural_name/index');
	}

	// view
	public function view(\$id) {
		\$v = new %2\$s;
		\$v->get_by_id(\$id);

		\$this->load->view('$this->plural_name/view');
	}

	// create
	public function create() {
		if (\$this->input->post('submit')) {
			\$c = new %2\$s;
			\$c->save(\$id);
		}


		\$this->load->view('$this->plural_name/create');
	}

	// edit
	public function edit(\$id) {
		if (\$this->input->post('submit')) {
			\$e = new %2\$s;
			\$e->update(\$id);
		}
		

		\$this->load->view('$this->plural_name/edit');
	}

	// delete
	public function delete(\$id) {
		if (\$this->input->post('submit')) {
			\$d = new %2\$s;
			\$d->delete(\$id);
		}
		

		\$this->load->view('$this->plural_name/delete');
	}

}
EOD;
		} else {
			$template = <<<EOD
<?php

class %1\$s extends CI_Controller {
	
	// constructor
	public function __construct() {
		parent::__construct();
	}
	
	// index
	public function index() {
		\$this->load->view('$this->name/index');
	}
	
	// view
	public function view(\$id) {
		\$this->load->view('$this->name/view');
	}
	
	// create
	public function create() {
		\$this->load->view('$this->name/create');
	}
	
	// edit
	public function edit(\$id) {
		\$this->load->view('$this->name/edit');
	}
	
	// delete
	public function view(\$id) {
		echo 'delete';
	}
	
}
EOD;
		}
		// filename
		$filename = ($this->datamapper) ? $this->plural_name : $this->name;
		
		// filepath 
		$this->filepath = $this->wd."/application/controllers/{$filename}.php";
		
		// file exists prompt
		if (!$this->file_exits_prompt($this->plural_name)) {
			$output = sprintf($template, ucfirst($this->plural_name), ucfirst($this->name));
			file_put_contents($this->filepath, $output);
			print("Created controller at application/controllers/{$filename}.php\n");
			
		}
		
	}
	
	// generates model
	private function generate_model($datamapper = false) {
		if ($this->datamapper) {
			$template = <<<EOD
<?php

class %1\$s extends DataMapper {
	
	// plural table name
	public \$table = '$this->plural_name';
	
	// relationships
	public \$has_one = array();
	public \$has_many = array();

	// validations
	public \$validation = array(
		'field' => array(
			'label' => 'Label',
			'rules' => array('required')
		)
	);

	// constructor
	public function __construct() {
		parent::__construct();
	}

}
EOD;
		} else {
			$template = <<<EOD
<?php

class %1\$s extends CI_Model {

	// constructor
	public function __construct() {
		parent::__construct();
	}

}
EOD;
		}
		
		// file path
		$this->filepath = $this->wd."/application/models/{$this->name}.php";
		
		// file exists prompt
		if (!$this->file_exits_prompt($this->plural_name)) {
			$output = sprintf($template, ucfirst($this->name), $this->plural_name);
			file_put_contents($this->filepath, $output);
			print("Created model at application/models/{$this->name}.php\n");
		}
		
	}
	
	// generates views
	private function generate_view() {

// View build
$template['view'] = <<<EOD
<?php

EOD;

// View Form Include build
$template['view_inc'] = <<<EOD
<?php
	
	\$this->load->view("%1\$s/_form.php");
?>
	
EOD;

// View _form build
$template['view_form'] = <<<EOD
<form action="" method="post">
	

	<p><input type="submit" value="Continue &rarr;"></p>
</form>
EOD;

		// Create files for default veiws 
		foreach (explode(',',$this->views) as $i) {
			
			// Datamapper changes filename convention
			$filename = ($this->datamapper) ? $this->plural_name : $this->name;
			
			// Create folder
			if (!is_dir($this->wd."/application/views/$filename")) {
				mkdir($this->wd."/application/views/$filename");
			}
			
			// file path
			$this->filepath = $this->wd."/application/views/{$filename}/$i.php";
			
			// file exists prompt
			if (!$this->file_exits_prompt($i)) {
				
				if ($i == 'create' || $i == 'edit' || $i == 'delete') {
					$view = $template['view_inc'];
				} elseif($i == '_form') {
					$view = $template['view_form'];				
				} else {
					$view = $template['view'];
				}

				
				$output = sprintf($view, $filename, $i);
				file_put_contents($this->filepath, $output);
				print("Created veiw at application/view/{$filename}/$i.php \n");
			}
			
		}

	
	}
	
	// generates mvc
	private function generate_mvc() {
		$this->mvc = explode(',',$this->mvc);
		foreach ($this->mvc as $s) {
			call_user_func(array($this, "generate_{$s}"));
		}
	}
	
}

$cigen = new Flint($argv, $argc);

?>