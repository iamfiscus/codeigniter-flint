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
	
	// use smarty
	private $smarty = FALSE;
	private $view = 'load->view';
	private $view_file_ext = '.php';
	
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
    When generating a mvc, Flint will create a controller with the plural
    name, a model with the singular name, and view files for index, create,
    edit, view, delete, and a form inside a folder with the plural name.
    
    To specify a plural name when MVCing, simply separate the singular and
    plural names with a forward slash (see example above).

OPTIONS:
    --datamapper:
        Use this to create a DataMapperORM stucture instead of a traditional one\n
		For CodeIgniter install: http://datamapper.wanwizard.eu/pages/download.html\n
		For Spark install: http://getsparks.org/packages/DataMapper-ORM/versions/HEAD/show\n\n
	--plural=:
        Use this to define the plural of a word to be used, instead of the default\n 
		which is appending 's'\n\n
	--smarty:
        Use this to create a view files that utilize Smarty Template\n
		For CodeIgniter install: https://github.com/akuzemchak/smartyview\n
		For Spark install: http://getsparks.org/packages/smartyview/versions/HEAD/show\n\n
		
FILES GENERATED:
	[controller]
		/application/controllers/[controller_name].php
	[model]
		/application/models/[model_name].php
	[view]
		/application/views/[view_name]/_form.[php/tpl]
		/application/views/[view_name]/index.[php/tpl]
		/application/views/[view_name]/create.[php/tpl]
		/application/views/[view_name]/edit.[php/tpl]
		/application/views/[view_name]/view.[php/tpl]
		/application/views/[view_name]/delete.[php/tpl]
		
EOD;
		print($output);
		exit;
	}
	
	// check arguments that were passed
	private function process_args() {
		foreach ($this->argv as $k => $a) {
			if (strpos($a,'--')===0) {
				// check for help flag
				if($a == '--help' || $this->argc < 4) {
					$this->help();
				}
				
				// check for datamapper flag
				elseif($a == '--datamapper') {
					$this->datamapper = TRUE;
				}
				
				// check for plural flag
				elseif(preg_match("/^--plural=(.+)$/", $a, $matches)) {
					$this->plural_name = strtolower($matches[1]);
				}
				
				// check for datamapper flag
				elseif($a == '--smarty') {
					$this->smarty = TRUE;
					$this->view = 'smarty_template->render';
					$this->view_file_ext = '.tpl';
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
		
		$file_ext = ($this->smarty == TRUE) ? $this->view_file_ext : '';
		
		if ($this->datamapper) {
			$template = <<<EOD
<?php defined('BASEPATH') OR exit('No direct script access allowed');

class %1\$s extends CI_Controller {
	
	// default variables
	private \$data;

	// constructor
	public function __construct() {
		parent::__construct();
	}

	// index
	public function index() {
		\$i = new %2\$s();
		\$i->get();

		\$this->{$this->view}('{$this->plural_name}/index{$file_ext}', \$this->data);
	}

	// view
	public function view(\$id) {
		\$v = new %2\$s(\$id);

		\$this->{$this->view}('{$this->plural_name}/view{$file_ext}', \$this->data);
	}

	// create
	public function create() {
		if (\$this->input->post('submit')) {
			\$c = new %2\$s;
			\$c->save();
		}


		\$this->{$this->view}('{$this->plural_name}/create{$file_ext}', \$this->data);
	}

	// edit
	public function edit(\$id) {
		if (\$this->input->post('submit')) {
			\$e = new %2\$s(\$id);
			\$e->save();
		}
		

		\$this->{$this->view}'{$this->plural_name}/edit{$file_ext}', \$this->data);
	}

	// delete
	public function delete(\$id) {
		if (\$this->input->post('submit')) {
			\$d = new %2\$s;
			\$d->delete(\$id);
		}
		

		\$this->{$this->view}('$this->plural_name/delete{$file_ext}', \$this->data);
	}

}

/* End of file %3\$s.php */
EOD;
		} else {
			$template = <<<EOD
<?php defined('BASEPATH') OR exit('No direct script access allowed');

class %1\$s extends CI_Controller {
	
	// default variables
	private \$data;
	
	// constructor
	public function __construct() {
		parent::__construct();
	}
	
	// index
	public function index() {
		\$this->{$this->view}('$this->name/index{$file_ext}', \$this->data);
	}
	
	// view
	public function view(\$id) {
		\$this->{$this->view}('$this->name/view{$file_ext}', \$this->data);
	}
	
	// create
	public function create() {
		\$this->{$this->view}('$this->name/create{$file_ext}', \$this->data);
	}
	
	// edit
	public function edit(\$id) {
		\$this->{$this->view}('$this->name/edit{$file_ext}', \$this->data);
	}
	
	// delete
	public function view(\$id) {
		\$this->{$this->view}('$this->name/delete{$file_ext}', \$this->data);
	}
	
}

/* End of file %3\$s.php */
EOD;
		}
		// filename
		$filename = ($this->datamapper) ? $this->plural_name : $this->name;
		
		// filepath 
		$this->filepath = $this->wd."/application/controllers/{$filename}.php";
		
		// file exists prompt
		if (!$this->file_exits_prompt($this->plural_name)) {
			$output = sprintf($template, ucfirst($this->plural_name), ucfirst($this->name), $filename);
			file_put_contents($this->filepath, $output);
			print("Created controller at application/controllers/{$filename}.php\n");
			
		}
		
	}
	
	// generates model
	private function generate_model($datamapper = false) {
		if ($this->datamapper) {
			$template = <<<EOD
<?php defined('BASEPATH') OR exit('No direct script access allowed');

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
	public function __construct(\$id = null) {
		parent::__construct(\$id);
	}

}

/* End of file %1\$s.php */
EOD;
		} else {
			$template = <<<EOD
<?php defined('BASEPATH') OR exit('No direct script access allowed');

class %1\$s extends CI_Model {

	// constructor
	public function __construct() {
		parent::__construct();
	}

}

/* End of file %1\$s.php */
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
// Use Smarty?
if ($this->smarty) {
	
// Form error format
if ($this->datamapper) {
	$form_errors = "
	<ul>
		{foreach \$errors as \$e}
			<li>{\$e}</li>
		{/foreach}
	</ul>";
} else {
	$form_errors = "{validation_errors()}";
}


// View build
$template['view'] = <<<EOD
{extends 'layouts/master.tpl'}

{block 'main_content'}
	
	{include '%1\$s/_form.tpl'}
	
{/block}
EOD;

// View Form Include build
$template['view_inc'] = <<<EOD
{extends 'layouts/master.tpl'}

{block 'main_content'}

{/block}
EOD;

// View _form build
$template['view_form'] = <<<EOD
{if (\$errors)}
	<div class="errors">
		$form_errors
	</div>
{/if}
{form_open('',['id'=>""])}


	<p><input type="submit" value="Continue &rarr;"></p>
{form_close()}
EOD;
	
} else {
	// Not using Smarty
	
// Form error format
if ($this->datamapper) {
	$form_errors = "
		<ul>
			<?php foreach (\$errors as \$e): ?>
				<li><?php echo \$e ?></li>
			<?php endforeach ?>
		</ul>
	";
} else {
	$form_errors = "<?php echo validation_errors(); ?>";
}


// View build
$template['view'] = <<<EOD

EOD;

// View Form Include build
$template['view_inc'] = <<<EOD
<?php \$this->load->view("layouts/header.php"); ?>

<?php \$this->load->view("%1\$s/_form.php"); ?>

<?php \$this->load->view("layouts/footer.php"); ?>

EOD;

// View _form build
$template['view_form'] = <<<EOD
<?php if (\$errors): ?>
	<div class="errors">
		$form_errors
	</div>
<?php endif ?>
<?php echo form_open('',array('id'=>"")); ?>


	<p><input type="submit" value="Continue &rarr;"></p>
<?php echo form_close(); ?>
EOD;


}


		// Create files for default veiws 
		foreach (explode(',',$this->views) as $i) {
			
			// Datamapper changes filename convention
			$filename = ($this->datamapper) ? $this->plural_name : $this->name;
			
			// Create folder
			if (!is_dir($this->wd."/application/views/$filename")) {
				mkdir($this->wd."/application/views/$filename");
			}
			
			// file path
			$this->filepath = $this->wd."/application/views/{$filename}/$i{$this->view_file_ext}";
			
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
				print("Created veiw at application/view/{$filename}/$i{$this->view_file_ext} \n");
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