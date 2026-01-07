<?php

/**
 * Base Module Class
 */

abstract class Attorney_Hub_Module {
	
	protected $module_name = 'Base Module';
	
	public function __construct() {
		error_log('ATTORNEY HUB: Loading module: ' . $this->module_name);
		
		if ($this->check_dependencies()) {
			$this->init();
			error_log('ATTORNEY HUB: Module initialized: ' . $this->module_name);
		} else {
			error_log('ATTORNEY HUB: Module dependencies failed: ' . $this->module_name);
		}
	}
	
	abstract protected function init();
	
	protected function check_dependencies() {
		return true;
	}
}