<?php //-->
/*
 * This file is part of the Eden package.
 * (c) 2009-2011 Christian Blanquera <cblanquera@gmail.com>
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */
 
require dirname(__FILE__).'/route.php';

/**
 * The base class for all classes wishing to integrate with Eve.
 * Extending this class will allow your methods to seemlessly be 
 * overloaded and overrided as well as provide some basic class
 * loading patterns.
 *
 * @package    Eden
 * @category   framework	
 * @author     Christian Blanquera <cblanquera@gmail.com>
 * @version    $Id: class.php 1 2010-01-02 23:06:36Z blanquera $
 */
class Eden_Class {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	private static $_instance = NULL;
	private static $_instances = array();
	
	/* Private Properties
	-------------------------------*/
	/* Get
	-------------------------------*/
	public static function get() {
		$class = __CLASS__;
		if(is_null(self::$_instance)) {
			self::$_instance = new $class();
		}
		
		return self::$_instance;
	}
	
	/* Magic
	-------------------------------*/
	public function __toString() {
		return get_class($this);
	}
	
	public function __call($name, $args) {
		//if the method name starts with a capital letter
		//most likely they want a class
		if(preg_match("/^[A-Z]/", $name)) {
			//lets first consider that they may just
			//want to load a class so lets try
			try {
				//return the class
				return Eden_Route::get()->getClassArray($name, $args);
			//only if there's a route exception do we want to catch it
			//this is because a class can throw an exception in their construct
			//so if that happens then we do know that the class has actually
			//been called and an exception is suppose to happen
			} catch(Eden_Route_Error $e) {}
		}
		
		try {
			//let the router handle this
			return Eden_Route::get()->callMethod($this, $name, true, $args);
		} catch(Eden_Route_Error $e) {
			throw new Eden_Error_Model($e->getMessage());
		}
	}
	
	/* Public Methods
	-------------------------------*/
	/**
	 * Creates a class route for this class.
	 * 
	 * @param *string the class route name
	 * @return Eden_Class
	 */
	public function routeThisClass($route) {
		//argument 1 must be a string
		Eden_Error_Validate::get()->argument(0, 'string');
		
		Eden_Route::get()->routeClass($route, get_class($this));
		return $this;
	}
	
	/**
	 * Creates a method route for this class.
	 * 
	 * @param *string the method route name
	 * @param *string the class name to route to
	 * @param *string the method name to route to
	 * @return Eden_Class
	 */
	public function routeThisMethod($routeMethod, $class, $method) {
		//argument 1-3 must be a string
		Eden_Error_Validate::get()->argument(1, 'string')->argument(2, 'string')->argument(3, 'string');
		
		Eden_Route::get()->routeMethod(get_class($this), $routeMethod, $class, $method);
		return $this;
	}
	
	/**
	 * Calls a method in this class and allows 
	 * argumetns to be passed as an array
	 *
	 * @param string
	 * @param array
	 * @return mixed
	 */
	public function callThisMethod($method, array $args = array()) {
		//argument 1 must be a string
		Eden_Error_Validate::get()->argument(0, 'string');
		
		return Eden_Route::get()->callMethod($this, $method, $args);
	}
	
	/* Protected Methods
	-------------------------------*/
	protected static function _getSingleton($class) {
		if(!isset(self::$_instances[$class])) {
			$args = func_get_args();
			array_shift($args);
		
			if(count($args) === 0) {
				self::$_instances[$class] = new $class();
			} else if(count($args) === 1) {
				self::$_instances[$class] = new $class($args[0]);
			} else if(count($args) === 2) { 
				self::$_instances[$class] = new $class($args[0], $args[1]);
			} else {
				//set it
				try {
					$reflect = new ReflectionClass($class);
					self::$_instances[$class] = $reflect->newInstanceArgs($args);
				} catch(Reflection_Exception $e) {
					Eden_Error_Model::get(Eden_Error_Model::REFLECTION_ERROR)
						->addParameter($class)
						->addParameter($method)
						->render();
				}
			}
		}
		
		return self::$_instances[$class];
	}
	
	protected static function _getMultiple($class) {
		$args = func_get_args();
		array_shift($args);
		
		if(count($args) === 0) {
			return new $class();
		} else if(count($args) === 1) {
			return new $class($args[0]);
		} else if(count($args) === 2) { 
			return new $class($args[0], $args[1]);
		} else {
			//set it
			try {
				$reflect = new ReflectionClass($class);
				return $reflect->newInstanceArgs($args);
			} catch(Reflection_Exception $e) {
				Eden_Error_Model::get(Eden_Error_Model::REFLECTION_ERROR)
					->addParameter($class)
					->addParameter($method)
					->render();
			}
		}
		
	}
	
	/* Private Methods
	-------------------------------*/
}