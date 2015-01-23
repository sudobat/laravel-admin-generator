<?php namespace Sudobat\AdminGenerator;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use \File;
use \Mustache_Engine;
use \Config;

class AdminGeneratorCommand extends Command {


	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'admin:make';


	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Creates an admin skeleton.';


	/**
	 * The command parameters
	 * 
	 * @var array
	 */
	protected $params = [];


	/**
	 * The template files
	 * 
	 * @var array
	 */
	protected $templates = [];


	/**
	 * Dependency injection this->mustache template manager
	 * 
	 * @var \Mustache
	 */
	protected $mustache;


	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}


	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		try
		{	
		# Cute Banner
		$this->showGreetings();

		# Validate the command options
		$this->parseOptions();

		# Create the directories
		$this->createDirectories();

		# Load the templates
		$this->loadTemplates();

		# Create resource files
		$this->createFiles();

		# Add the proper routes to the routes.php file
		$this->addRoutes();

		# Shown at the end of the command
		$this->info('Admin generation successful!');
		}
		catch(NoResourcesProvided $e)
		{
			$this->error('                        ');
			$this->error(' No Resources Provided! ');
			$this->error('                        ');
			$this->info('');
			$this->info('To see how it works, run the following command:');
			$this->info('');
			$this->line('php artisan admin:make --help');
			$this->info('');
		}
	}


	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [

			# ['example', InputArgument::OPTIONAL, 'An example argument.'],

		];
	}


	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [

			['base', null, InputOption::VALUE_OPTIONAL, 'A base directory.', 'app'],
			['prefix', null, InputOption::VALUE_OPTIONAL, 'A prefix to put the generated resources in.', 'admin'],
			['namespace', null, InputOption::VALUE_OPTIONAL, 'A namespace to organize the controllers and models.', 'Admin'],
			['resources', null, InputOption::VALUE_REQUIRED, 'A list of comma separated resources.', null],

		];
	}


	/**
	 * Shows a welcome message
	 */
	private function showGreetings()
	{
		$this->info('');
		$this->info('+-------------------------+');
		$this->info('| Laravel Admin Generator |');
		$this->info('+-------------------------+');
		$this->info('');
	}


	/**
	 * Get all the options and return them into an array
	 * 
	 * @return array
	 */
	private function parseOptions()
	{
		$this->params['base']		= $this->option('base');
		$this->params['prefix']		= $this->option('prefix');
		$this->params['namespace'] 	= $this->option('namespace');
		$this->params['resources'] 	= $this->parseResources($this->option('resources'));
	}


	/**
	 * Get a string of comma separated controller names and convert it to array
	 * 
	 * @param string
	 * 
	 * @return array
	 */
	private function parseResources($resources = "")
	{
		# Check that resources is not empty
		if ($resources == "")
		{
			throw new NoResourcesProvided("Resources Option is empty, please specify a comma-separated list of resources");
		}

		# Convert comma-separated string to array
		$resources = explode(',', $resources);

		# Loop through each controller
		foreach($resources as $k => $resource)
		{
			# Remove left/right spaces
			$nspResource = trim($resource);

			$resources[$k] = $nspResource;
		}

		return $resources;
	}


	/**
	 * Create the directory structure
	 * 
	 */
	private function createDirectories()
	{
		File::makeDirectory($this->params['base'].'/controllers/'.$this->params['prefix'], 511, true, true);
		File::makeDirectory($this->params['base'].'/models/'.$this->params['prefix'], 511, true, true);
		foreach($this->params['resources'] as $resource)
		{
			File::makeDirectory($this->params['base'].'/views/'.$this->params['prefix'].'/'.strtolower($resource), 511, true, true);
		}
	}


	/**
	 * Loads the template files to generate the admin
	 * 
	 */
	private function loadTemplates()
	{
		$this->mustache = new Mustache_Engine;
		$this->mustache->addHelper('case', [
			'lower' => function($value) { return strtolower((string) $value); }
		]);
		$this->mustache->addHelper('plural', function($value) { return str_plural((string) $value); });
		
		$this->templates['model'] 		= file_get_contents(Config::get('admin-generator::model_template_path') . '/' . 'model.template');
		$this->templates['controller'] 	= file_get_contents(Config::get('admin-generator::controller_template_path') . '/' . 'controller.template');
		$this->templates['views'] 		= [
			'index'		=> file_get_contents(Config::get('admin-generator::view_template_path') . '/' . 'index.template'),
			'create'	=> file_get_contents(Config::get('admin-generator::view_template_path') . '/' . 'create.template'),
			'edit' 		=> file_get_contents(Config::get('admin-generator::view_template_path') . '/' . 'edit.template')
		];
	}


	/**
	 * Creates all the admin files
	 * 
	 */
	private function createFiles()
	{
		foreach($this->params['resources'] as $resource)
		{
			# Create Controller files
			$controllerTpl = $this->mustache->render($this->templates['controller'], [
				'prefix'	=> $this->params['prefix'],
				'name' 		=> $resource,
				'namespace'	=> $this->params['namespace']
			]);
			file_put_contents(
				"{$this->params['base']}/controllers/{$this->params['prefix']}/{$resource}Controller.php",
				$controllerTpl
			);

			# Create Model files
			$modelTpl = $this->mustache->render($this->templates['model'], [
				'name'		=> $resource,
				'namespace'	=> $this->params['namespace']
			]);
			file_put_contents(
				"{$this->params['base']}/models/{$this->params['prefix']}/{$resource}.php",
				$modelTpl
			);

			# Create View files
			$lowerResource = strtolower($resource);
			foreach($this->templates['views'] as $viewName => $viewTemplate)
			{
				$viewTpl = $this->mustache->render($viewTemplate, [
					'prefix'	=> $this->params['prefix'],
					'name' 		=> $resource,
					'namespace'	=> $this->params['namespace']
				]);
				file_put_contents("{$this->params['base']}/views/{$this->params['prefix']}/{$lowerResource}/{$viewName}.blade.php", $viewTpl);
			}
		}
	}


	/**
	 * Adds Admin routes to the end of the routes.php file
	 * 
	 * 
	 */
	private function addRoutes()
	{
		$routes_file_path = app_path('routes.php');

		$routes_content = "\n";
		$routes_content .= "Route::group(['prefix' => 'admin', 'before' => 'auth'], function()\n";
		$routes_content .= "{\n";

		foreach($this->params['resources'] as $resource)
		{
			$lower_resource 		= strtolower($resource);
			$namespaced_resource 	= $this->params['namespace'] . '\\'. $resource . 'Controller';
			
			$routes_content .= "\tRoute::resource('{$lower_resource}', '{$namespaced_resource}');\n";
		}

		$routes_content .= "});\n";

		File::append($routes_file_path, $routes_content);
	}

}
