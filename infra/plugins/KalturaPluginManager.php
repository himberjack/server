<?php
/**
 * @package infra
 * @subpackage Plugins
 */
class KalturaPluginManager
{
	/**
	 * Array of all installed plugin classes
	 * @var array<string, string> in the form array[pluginName] = pluginClass
	 */
	protected static $plugins = array();
	
	/**
	 * Array of all installed plugin instantiated classes
	 * @var array<KalturaPlugin>
	 */
	protected static $pluginInstances = array();
	
	/**
	 * Were all the plugins loaded or not 
	 * @var boolean
	 */
	protected static $loadedAllPlugins = false;
	
	/**
	 * A list of interfaces for which the plugins were loaded
	 * @var array
	 */
	protected static $loadedInterfaces = array();
	
	/**
	 * Should the list of plugins implementing some interface be cached
	 * @var boolean
	 */
	protected static $useCache = true;
	
	protected function __construct()
	{
		
	}
	
	/**
	 * Loads an extended object that extended by plugin
	 * @param string $baseClass
	 * @param string $enumValue
	 * @param array $constructorArgs
	 * @return object
	 */
	public static function loadObject($baseClass, $enumValue, array $constructorArgs = null)
	{
		$pluginInstances = self::getPluginInstances('IKalturaObjectLoader');
		foreach($pluginInstances as $pluginName => $pluginInstance)
		{
			$obj = $pluginInstance->loadObject($baseClass, $enumValue, $constructorArgs);
			if($obj)
				return $obj;
		}
		
		KalturaLog::debug("Object [$baseClass] not found, enum value [$enumValue], constructor arguments [" . print_r($constructorArgs, true) . "], plugins [" . print_r(array_keys($pluginInstances), true) . "]");
		return null;
	}
	
	/**
	 * Return all enum values that extend the base enum value
	 * @param string $baseClass
	 * @param string $enumValue
	 * @return array
	 */
	public static function getExtendedTypes($baseClass, $enumValue)
	{
		$values = array($enumValue);
		$pluginInstances = self::getPluginInstances('IKalturaTypeExtender');
		foreach($pluginInstances as $pluginName => $pluginInstance)
		{
			$pluginValues = $pluginInstance->getExtendedTypes($baseClass, $enumValue);
			if($pluginValues && count($pluginValues))
				foreach($pluginValues as $pluginValue)
					$values[] = $pluginValue;
		}
		
		return $values;
	}
	
	/**
	 * @param Iterator $srcConfig
	 * @param Iterator $newConfig
	 * @param bool $valuesOnly
	 * @return Iterator
	 */
	protected static function mergeConfigItem(Iterator $srcConfig, Iterator $newConfig, $valuesOnly)
	{
		$returnedConfig = $srcConfig;
		
		if($valuesOnly)
		{
			foreach($srcConfig as $key => $value)
			{
				if(!$newConfig->$key) // nothing to append
					continue;
				elseif($value instanceof Iterator)
					$returnedConfig->$key = self::mergeConfigItem($srcConfig->$key, $newConfig->$key, $valuesOnly);
				else
					$returnedConfig->$key = $srcConfig->$key . ',' . $newConfig->$key;
			}
		}
		else
		{
			foreach($newConfig as $key => $value)
			{
				if($value instanceof Iterator)
				{
					if(!$srcConfig->$key)
						$srcConfig->$key = new Zend_Config(array(), true);
					$returnedConfig->$key = self::mergeConfigItem($srcConfig->$key, $newConfig->$key, $valuesOnly);
				}
				else
				{
					if ($srcConfig->$key)
						$returnedConfig->$key .= ',';
					$returnedConfig->$key .= $newConfig->$key;
				}
			}
		}
		
		return $returnedConfig;
	}
	
	/**
	 * Merge configuration data from the plugins
	 * 
	 * @param Iterator $config the configuration to be merged
	 * @param string $configName
	 * @param bool $valuesOnly if true, new keys won't be added to the original config
	 * @return Iterator
	 */
	public static function mergeConfigs(Iterator $config, $configName, $valuesOnly = true)
	{
		$pluginInstances = self::getPluginInstances('IKalturaConfigurator');
		foreach($pluginInstances as $pluginName => $pluginInstance)
		{
			$pluginConfig = $pluginInstance->getConfig($configName);
			if($pluginConfig)
				$config = self::mergeConfigItem($config, $pluginConfig, $valuesOnly);
		}
		
		return $config;
	}
	
	/**
	 * Finds extended class the extended by plugin
	 * @param string $baseClass
	 * @param string $enumValue
	 * @return object
	 */
	public static function getObjectClass($baseClass, $enumValue)
	{
		$pluginInstances = self::getPluginInstances('IKalturaObjectLoader');
		foreach($pluginInstances as $pluginName => $pluginInstance)
		{
			$cls = $pluginInstance->getObjectClass($baseClass, $enumValue);
			if($cls)
			{
//				KalturaLog::debug("Found class[$cls] in plugin[$pluginName] for object type[$objectType] and enum value[$enumValue]");
				return $cls;
			}
		}
		
		return null;
	}
	
	/**
	 * Validates plugin according to its dependencies
	 * @param string $pluginClass 
	 * @param array $validatedPlugins list of plugins that already validated
	 * @return bool false if a required dependency is missing.
	 */
	protected static function isValid($pluginClass, array $validatedPlugins = null)
	{
		$pluginClassReplection = new ReflectionClass($pluginClass);
		if(!$pluginClassReplection->implementsInterface('IKalturaPending'))
			return true;
			
		// TODO remove call_user_func after moving to php 5.3
		$pendingPlugins = call_user_func(array($pluginClass, 'dependsOn'));
//		$pendingPlugins = $pluginClass::dependsOn();
		if(!$pendingPlugins || !count($pendingPlugins))
			return true;
			
		$availablePlugins = self::getPlugins();
		foreach($pendingPlugins as $pendingPlugin)
		{
			$pendingPluginName = $pendingPlugin->getPluginName();
			
			// check if the required plugin is configured to be loaded
			if(!isset($availablePlugins[$pendingPluginName]))
			{
				KalturaLog::err("Pending plugin name [$pendingPluginName] is not available, plugin [$pluginClass] could not be loaded.");
				return false;
			}
				
			$pendingPluginClass = $availablePlugins[$pendingPluginName];
			$pendingPluginReplection = new ReflectionClass($pendingPluginClass);
				
			// check if the required plugin already validated
			if(in_array($pendingPluginName, $validatedPlugins))
				continue;
			
			// check if the version compatible
			$pendingPluginMinVersion = $pendingPlugin->getMinimumVersion();
			if($pendingPluginMinVersion && $pendingPluginReplection->implementsInterface('IKalturaVersion'))
			{
				// TODO remove call_user_func after moving to php 5.3
				$pendingPluginVersion = call_user_func(array($pendingPluginClass, 'getVersion'));
//				$pendingPluginVersion = $pendingPluginClass::getVersion();
				if(!$pendingPluginVersion->isCompatible($pendingPluginMinVersion))
				{
					KalturaLog::err("Pending plugin name [$pendingPluginName] version [$pendingPluginVersion] is not compatible with required version [$pendingPluginMinVersion], plugin [$pluginClass] could not be loaded.");
					return false;
				}
			}
			
			// adds tested plugin name to the list of validated in order to avoid endless recursion
			$tempValidatedPlugins = $validatedPlugins;
			// TODO remove call_user_func after moving to php 5.3
			$tempValidatedPlugins[] = call_user_func(array($pluginClass, 'getPluginName'));
//			$tempValidatedPlugins[] = $pluginClass::getPluginName();
			if(!self::isValid($pendingPluginClass, $tempValidatedPlugins))
			{
				KalturaLog::err("Plugin [$pluginClass] could not be loaded.");
				return false;
			}
				
			// adds the last tested dependency plugin to the valid list
			$validatedPlugins[] = $pendingPluginName;
		}
		
		return true;
	}
	
	/**
	 * Loads the specified list of plugins
	 * @param array $plugins
	 * @param boolean $validateDependencies
	 */
	protected static function loadPlugins(array $plugins, $validateDependencies = true)
	{
		if (self::$loadedAllPlugins)
			return;		// already loaded everything
		
		foreach($plugins as $pluginName => $pluginClass)
		{
			if (isset(self::$pluginInstances[$pluginName]))
				continue;		// already loaded
			
			if (!$pluginClass || !class_exists($pluginClass))
				continue;		// class does not exist

			if ($validateDependencies && 
				!self::isValid($pluginClass, array_keys(self::$pluginInstances)))
					continue;		// missing dependencies
				
			$pluginObject = new $pluginClass();
			if (!($pluginObject instanceof IKalturaPlugin))
				continue;		// the plugin does not implement the base interface 
				
			self::$pluginInstances[$pluginName] = $pluginObject;
		}
	}
	
	/**
	 * Loads all available plugins
	 */
	protected static function loadAllPlugins()
	{				
		self::loadPlugins(self::getPlugins());
		self::$loadedAllPlugins = true;
	}
	
	/**
	 * Gets the implementations of $interface from the plugins listed in $pluginNames
	 * @param array $pluginNames
	 * @param string $interface
	 */
	protected static function getPluginInstancesByNames(array $pluginNames, $interface)
	{
		$instances = array();
		foreach ($pluginNames as $pluginName)
		{
			if (!isset(self::$pluginInstances[$pluginName]))
				continue;		// not loaded
			
			$pluginInstance = self::$pluginInstances[$pluginName];
			$instance = $pluginInstance->getInstance($interface);
			if (!$instance)
				continue;		// doesn't implement the required interface
			
			$instances[strtolower($pluginName)] = $instance;
		}
		return $instances;
	}
	
	/**
	 * Returns all instances that implement the requested interface or all of them in not supplied
	 * @param string $interface
	 * @return array<KalturaPlugin>
	 */
	public static function getPluginInstances($interface = null)
	{
		$cacheStore = kCacheManager::getCache(kCacheManager::APC);
		if ($cacheStore && self::$useCache)
		{
			$cacheKey = "pluginsByInterface_$interface";
			$plugins = $cacheStore->get($cacheKey);
			if ($plugins !== false)
			{
				if (!in_array($interface, self::$loadedInterfaces))
				{
					self::loadPlugins($plugins, false);
					self::$loadedInterfaces[] = $interface;
				}
				return self::getPluginInstancesByNames(array_keys($plugins), $interface);
			}
		}
		
		self::loadAllPlugins();
		
		if(is_null($interface))
			return self::$pluginInstances;
		
		$plugins = array();
		$instances = array();
		foreach(self::$pluginInstances as $pluginName => $pluginInstance)
		{
			$instance = $pluginInstance->getInstance($interface);
			if (!$instance)
				continue;
			$plugins[$pluginName] = self::$plugins[$pluginName];
			$instances[strtolower($pluginName)] = $instance;
		}
		if ($cacheStore && self::$useCache)
			$cacheStore->set($cacheKey, $plugins);
		return $instances;
	}
	
	/**
	 * Returns a single plugin instance by its name
	 * @param string pluginName
	 * @return KalturaPlugin
	 */
	public static function getPluginInstance($pluginName)
	{
		//TODO - do we need to get all the instances? maybe create just the required plugin.
		// unless they are all created at bootstrap anyway for event handling purposes
		$plugins = self::getPluginInstances();
		if (!isset($plugins[$pluginName]))
			return null;
		return $plugins[$pluginName];
	}
	
	/**
	 * @param string $pluginClass
	 */
	public static function addPlugin($pluginClass)
	{
		self::getPluginInstances();
		$plugin = new $pluginClass();
		$pluginName = $plugin->getPluginName();
		self::$plugins[$pluginName] = $pluginClass;
		self::$pluginInstances[$pluginName] = $plugin;
		self::$useCache = false;		// disable the cache so that the added plugin will have effect
	}
	
	/**
	 * @return array<string, string> in the form array[pluginName] = pluginClass
	 */
	public static function getPlugins()
	{
		if(count(self::$plugins))
			return self::$plugins;
			
		$configDir = kEnvironment::getConfigDir();
		$configFile = "$configDir/plugins.ini";
		if(!file_exists($configFile))
			return array();
		
		$pluginNames = file($configFile);
		$plugins = array();
		foreach($pluginNames as $pluginName)
		{
			if(!preg_match('/^[A-Z][\w\d]+$/', $pluginName))
				continue;
				
			$pluginClass = $pluginName . 'Plugin';
			if(!class_exists($pluginClass))
			{
				KalturaLog::err("Plugin [$pluginName] not found with class [$pluginClass].");
				continue;
			}
			
			$plugins[] = $pluginClass;
		}
			
		self::$plugins = array();
		foreach($plugins as $pluginClass)	
		{		
			// TODO remove call_user_func after moving to php 5.3
			$pluginName = call_user_func(array($pluginClass, 'getPluginName'));
//			$pluginName = $pluginClass::getPluginName();
			self::$plugins[$pluginName] = $pluginClass;
		}
			
		return self::$plugins;
	}
}
