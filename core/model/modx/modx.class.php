<?php
/*
 * MODX Revolution
 *
 * Copyright 2006-2011 by MODX, LLC.
 * All rights reserved.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 */

/**
 * This is the main file to include in your scripts to use MODX.
 *
 * For detailed information on using this class, see {@tutorial modx/modx.pkg}.
 *
 * @package modx
 */
/* fix for PHP float bug: http://bugs.php.net/bug.php?id=53632 (php 4 <= 4.4.9 and php 5 <= 5.3.4) */
if (strstr(str_replace('.','',serialize(array_merge($_GET, $_POST, $_COOKIE))), '22250738585072011')) {
    header('Status: 422 Unprocessable Entity');
    die();
}

if (!defined('MODX_CORE_PATH')) {
    define('MODX_CORE_PATH', dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR);
}
if (!defined('MODX_CONFIG_KEY')) {
    define('MODX_CONFIG_KEY', 'config');
}
require_once (MODX_CORE_PATH . 'xpdo/xpdo.class.php');

/**
 * This is the MODX gateway class.
 *
 * It can be used to interact with the MODX framework and serves as a front
 * controller for handling requests to the virtual resources managed by the MODX
 * Content Management Framework.
 *
 * @package modx
 */
class modX extends xPDO {
    /**
     * The level for fatal, application-ending errors
     * @const LOG_LEVEL_FATAL
     */
    const LOG_LEVEL_FATAL = 0;
    /**
     * The level for error messages
     * @const LOG_LEVEL_ERROR
     */
    const LOG_LEVEL_ERROR = 1;
    /**
     * The level for warning messages
     * @const LOG_LEVEL_WARN
     */
    const LOG_LEVEL_WARN = 2;
    /**
     * The level for general information messages
     * @const LOG_LEVEL_INFO
     */
    const LOG_LEVEL_INFO = 3;
    /**
     * The level for debugging information messages
     * @const LOG_LEVEL_DEBUG
     */
    const LOG_LEVEL_DEBUG = 4;

    /**
     * The parameter for when a session state is not able to be accessed
     * @const SESSION_STATE_UNAVAILABLE
     */
    const SESSION_STATE_UNAVAILABLE = -1;
    /**
     * The parameter for when a session has not yet been instantiated
     * @const SESSION_STATE_UNINITIALIZED
     */
    const SESSION_STATE_UNINITIALIZED = 0;
    /**
     * The parameter for when a session has been fully initialized
     * @const SESSION_STATE_INITIALIZED
     */
    const SESSION_STATE_INITIALIZED = 1;
    /**
     * The parameter marking when a session is being controlled by an external provider
     * @const SESSION_STATE_EXTERNAL
     */
    const SESSION_STATE_EXTERNAL = 2;
    /**
     * @var modContext The Context represents a unique section of the site which
     * this modX instance is controlling.
     */
    public $context= null;
    /**
     * @var array An array of secondary contexts loaded on demand.
     */
    public $contexts= array();
    /**
     * @var modRequest Represents a web request and provides helper methods for
     * dealing with request parameters and other attributes of a request.
     */
    public $request= null;
    /**
     * @var modResponse Represents a web response, providing helper methods for
     * managing response header attributes and the body containing the content of
     * the response.
     */
    public $response= null;
    /**
     * @var modParser The modParser registered for this modX instance,
     * responsible for content tag parsing, and loaded only on demand.
     */
    public $parser= null;
    /**
     * @var array An array of supplemental service classes for this modX instance.
     */
    public $services= array ();
    /**
     * @var array A listing of site Resources and Context-specific meta data.
     */
    public $resourceListing= null;
    /**
     * @var array A hierarchy map of Resources.
     */
    public $resourceMap= null;
    /**
     * @var array A lookup listing of Resource alias values and associated
     * Resource Ids
     */
    public $aliasMap= null;
    /**
     * @var modSystemEvent The current event being handled by modX.
     */
    public $event= null;
    /**
     * @var array A map of elements registered to specific events.
     */
    public $eventMap= null;
    /**
     * @var array A map of actions registered to the manager interface.
     */
    public $actionMap= null;
    /**
     * @var array A map of already processed Elements.
     */
    public $elementCache= array ();
    /**
     * @var array An array of key=> value pairs that can be used by any Resource
     * or Element.
     */
    public $placeholders= array ();
    /**
     * @var modResource An instance of the current modResource controlling the
     * request.
     */
    public $resource= null;
    /**
     * @var string The preferred Culture key for the current request.
     */
    public $cultureKey= '';
    /**
     * @var modLexicon Represents a localized dictionary of common words and phrases.
     */
    public $lexicon= null;
    /**
     * @var modUser The current user object, if one is authenticated for the
     * current request and context.
     */
    public $user= null;
    /**
     * @var array Represents the modContentType instances that can be delivered
     * by this modX deployment.
     */
    public $contentTypes= null;
    /**
     * @var mixed The resource id or alias being requested.
     */
    public $resourceIdentifier= null;
    /**
     * @var string The method to use to locate the Resource, 'id' or 'alias'.
     */
    public $resourceMethod= null;
    /**
     * @var boolean Indicates if the resource was generated during this request.
     */
    public $resourceGenerated= false;
    /**
     * @var array Version information for this MODX deployment.
     */
    public $version= null;
    /**
     * @var boolean Indicates if modX has been successfully initialized for a
     * modContext.
     */
    protected $_initialized= false;
    /**
     * @var array An array of javascript content to be inserted into the HEAD
     * of an HTML resource.
     */
    public $sjscripts= array ();
    /**
     * @var array An array of javascript content to be inserted into the BODY
     * of an HTML resource.
     */
    public $jscripts= array ();
    /**
     * @var array An array of already loaded javascript/css code
     */
    public $loadedjscripts= array ();
    /**
     * @var string Stores the virtual path for a request to MODX if the
     * friendly_alias_paths option is enabled.
     */
    public $virtualDir;
    /**
     * @var object An error_handler for the modX instance.
     */
    public $errorHandler= null;
    /**
     * @var array An array of regex patterns regulary cleansed from content.
     */
    public $sanitizePatterns = array(
        'scripts'   => '@<script[^>]*?>.*?</script>@si',
        'entities'  => '@&#(\d+);@e',
        'tags'      => '@\[\[(.[^\[\[]*?)\]\]@si',
    );
    /**
     * @var integer An integer representing the session state of modX.
     */
    protected $_sessionState= modX::SESSION_STATE_UNINITIALIZED;
    /**
     * @var array A config array that stores the bootstrap settings.
     */
    protected $_config= null;
    /**
     * @var array A config array that stores the system-wide settings.
     */
    public $_systemConfig= null;
    /**
     * @var array A config array that stores the user settings.
     */
    public $_userConfig= array();
    /**
     * @var int The current log sequence
     */
    protected $_logSequence= 0;

    /**
     * @var array An array of plugins that have been cached for execution
     */
    public $pluginCache= array();
    /**
     * @var array The elemnt source cache used for caching and preparing Element data
     */
    public $sourceCache= array(
        'modChunk' => array()
        ,'modSnippet' => array()
        ,'modTemplateVar' => array()
    );

    /**
     * @deprecated
     * @var modSystemEvent $Event
     */
    public $Event= null;
    /**
     * @deprecated
     * @var string $documentOutput
     */
    public $documentOutput= null;
    /**
     * @deprecated
     * @var boolean $stopOnNotice
     */
    public $stopOnNotice= false;

    /**
     * Harden the environment against common security flaws.
     *
     * @static
     */
    public static function protect() {
        if (isset ($_SERVER['QUERY_STRING']) && strpos(urldecode($_SERVER['QUERY_STRING']), chr(0)) !== false) die();
        if (@ ini_get('register_globals') && isset ($_REQUEST)) {
            while (list($key, $value)= each($_REQUEST)) {
                $GLOBALS[$key] = null;
                unset ($GLOBALS[$key]);
            }
        }
        $targets= array ('PHP_SELF', 'HTTP_USER_AGENT', 'HTTP_REFERER', 'QUERY_STRING');
        foreach ($targets as $target) {
            $_SERVER[$target] = isset ($_SERVER[$target]) ? htmlspecialchars($_SERVER[$target], ENT_QUOTES) : null;
        }
    }

    /**
     * Sanitize values of an array using regular expression patterns.
     *
     * @static
     * @param array $target The target array to sanitize.
     * @param array|string $patterns A regular expression pattern, or array of
     * regular expression patterns to apply to all values of the target.
     * @param integer $depth The maximum recursive depth to sanitize if the
     * target contains values that are arrays.
     * @param integer $nesting The maximum nesting level in which to dive
     * @return array The sanitized array.
     */
    public static function sanitize(array &$target, array $patterns= array(), $depth= 3, $nesting= 10) {
        while (list($key, $value)= each($target)) {
            if (is_array($value) && $depth > 0) {
                modX :: sanitize($value, $patterns, $depth-1);
            } elseif (is_string($value)) {
                if (!empty($patterns)) {
                    foreach ($patterns as $pattern) {
                        $nesting = ((integer) $nesting ? (integer) $nesting : 10);
                        $iteration = 1;
                        while ($iteration <= $nesting && preg_match($pattern, $value)) {
                            $value= preg_replace($pattern, '', $value);
                            $iteration++;
                        }
                    }
                }
                if (get_magic_quotes_gpc()) {
                    $target[$key]= stripslashes($value);
                } else {
                    $target[$key]= $value;
                }
            }
        }
        return $target;
    }

    /**
     * Sanitizes a string
     *
     * @param string $str The string to sanitize
     * @param array $chars An array of chars to remove
     * @param string $allowedTags A list of tags to allow.
     * @return string The sanitized string.
     */
    public function sanitizeString($str,$chars = array('/',"'",'"','(',')',';','>','<'),$allowedTags = '') {
        $str = str_replace($chars,'',strip_tags($str,$allowedTags));
        return preg_replace("/[^A-Za-z0-9_\-\.\/]/",'',$str);
    }

    /**
     * Turn an associative array into a valid query string.
     *
     * @static
     * @param array $parameters An associative array of parameters.
     * @return string A valid query string representing the parameters.
     */
    public static function toQueryString(array $parameters = array()) {
        $qs = array();
        foreach ($parameters as $paramKey => $paramVal) {
            $qs[] = urlencode($paramKey) . '=' . urlencode($paramVal);
        }
        return implode('&', $qs);
    }

    /**
     * Construct a new modX instance.
     *
     * @param string $configPath An absolute filesystem path to look for the config file.
     * @param array $options Options that can be passed to the instance.
     * @return modX A new modX instance.
     */
    public function __construct($configPath= '', array $options = array()) {
        global $database_dsn, $database_user, $database_password, $config_options, $table_prefix, $site_id, $uuid;
        modX :: protect();
        if (empty ($configPath)) {
            $configPath= MODX_CORE_PATH . 'config/';
        }
        if (@ include ($configPath . MODX_CONFIG_KEY . '.inc.php')) {
            $cachePath= MODX_CORE_PATH . 'cache/';
            if (MODX_CONFIG_KEY !== 'config') $cachePath .= MODX_CONFIG_KEY . '/';
            $options = array_merge(
                array (
                    xPDO::OPT_CACHE_KEY => 'default',
                    xPDO::OPT_CACHE_HANDLER => 'xPDOFileCache',
                    xPDO::OPT_CACHE_PATH => $cachePath,
                    xPDO::OPT_TABLE_PREFIX => $table_prefix,
                    xPDO::OPT_HYDRATE_FIELDS => true,
                    xPDO::OPT_HYDRATE_RELATED_OBJECTS => true,
                    xPDO::OPT_HYDRATE_ADHOC_FIELDS => true,
                    xPDO::OPT_LOADER_CLASSES => array('modAccessibleObject'),
                    xPDO::OPT_VALIDATOR_CLASS => 'validation.modValidator',
                    xPDO::OPT_VALIDATE_ON_SAVE => true,
                    'cache_system_settings' => true,
                    'cache_system_settings_key' => 'system_settings'
                ),
                $config_options,
                $options
            );
            parent :: __construct(
                $database_dsn,
                $database_user,
                $database_password,
                $options,
                array (
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
                    PDO::ATTR_PERSISTENT => false,
                )
            );
            $this->setPackage('modx', MODX_CORE_PATH . 'model/', $table_prefix);
            $this->setLogTarget($this->getOption('log_target', null, 'FILE'));
            if (!empty($site_id)) $this->site_id = $site_id;
            if (!empty($uuid)) $this->uuid = $uuid;
        } else {
            $this->sendError($this->getOption('error_type', null, 'unavailable'), $options);
        }
    }

    /**
     * Initializes the modX engine.
     *
     * This includes preparing the session, pre-loading some common
     * classes and objects, the current site and context settings, extension
     * packages used to override session handling, error handling, or other
     * initialization classes
     *
     * @param string $contextKey Indicates the context to initialize.
     * @return void
     */
    public function initialize($contextKey= 'web') {
        if (!$this->_initialized) {
            if (!$this->startTime) {
                $this->startTime= $this->getMicroTime();
            }

            $this->loadClass('modAccess');
            $this->loadClass('modAccessibleObject');
            $this->loadClass('modAccessibleSimpleObject');
            $this->loadClass('modResource');
            $this->loadClass('modElement');
            $this->loadClass('modScript');
            $this->loadClass('modPrincipal');
            $this->loadClass('modUser');

            $this->getCacheManager();
            $this->getConfig();
            $this->_initContext($contextKey);
            $this->_loadExtensionPackages();
            $this->_initSession();
            $this->_initErrorHandler();
            $this->_initCulture();

            $this->getService('registry', 'registry.modRegistry');

            if (is_array ($this->config)) {
                $this->setPlaceholders($this->config, '+');
            }

            $this->_initialized= true;
        }
    }

    /**
     * Loads any specified extension packages
     */
    protected function _loadExtensionPackages() {
        $extPackages = $this->getOption('extension_packages');
        if (empty($extPackages)) return;
        $extPackages = $this->fromJSON($extPackages);
        if (!empty($extPackages)) {
            foreach ($extPackages as $extPackage) {
                if (!is_array($extPackage)) continue;

                foreach ($extPackage as $packageName => $package) {
                    if (!empty($package) && !empty($package['path'])) {
                        $package['tablePrefix'] = !empty($package['tablePrefix']) ? $package['tablePrefix'] : null;
                        $package['path'] = str_replace(array(
                            '[[++core_path]]',
                            '[[++base_path]]',
                            '[[++assets_path]]',
                            '[[++manager_path]]',
                        ),array(
                            $this->config['core_path'],
                            $this->config['base_path'],
                            $this->config['assets_path'],
                            $this->config['manager_path'],
                        ),$package['path']);
                        $this->addPackage($packageName,$package['path'],$package['tablePrefix']);
                        if (!empty($package['serviceName']) && !empty($package['serviceClass'])) {
                            $packagePath = str_replace('//','/',$package['path'].$packageName.'/');
                            $this->getService($package['serviceName'],$package['serviceClass'],$packagePath);
                        }
                    }
                }
            }
        }
    }

    /**
     * Sets the debugging features of the modX instance.
     *
     * @param boolean|int $debug Boolean or bitwise integer describing the
     * debug state and/or PHP error reporting level.
     * @param boolean $stopOnNotice Indicates if processing should stop when
     * encountering PHP errors of type E_NOTICE.
     * @return boolean|int The previous value.
     */
    public function setDebug($debug= true, $stopOnNotice= false) {
        $oldValue= $this->getDebug();
        if ($debug === true) {
            error_reporting(-1);
            parent :: setDebug(true);
        } elseif ($debug === false) {
            error_reporting(0);
            parent :: setDebug(false);
        } else {
            error_reporting(intval($debug));
            parent :: setDebug(intval($debug));
        }
        $this->stopOnNotice= $stopOnNotice;
        return $oldValue;
    }

    /**
     * Get an extended xPDOCacheManager instance responsible for MODX caching.
     *
     * @param string $class The class name of the cache manager to load
     * @param array $options An array of options to send to the cache manager instance
     * @return object A modCacheManager registered for this modX instance.
     */
    public function getCacheManager($class= 'cache.xPDOCacheManager', $options = array('path' => XPDO_CORE_PATH, 'ignorePkg' => true)) {
        if ($this->cacheManager === null) {
            if ($this->loadClass($class, $options['path'], $options['ignorePkg'], true)) {
                $cacheManagerClass= $this->getOption('modCacheManager.class', null, 'modCacheManager');
                if ($className= $this->loadClass($cacheManagerClass, '', false, true)) {
                    if ($this->cacheManager= new $className ($this)) {
                        $this->_cacheEnabled= true;
                    }
                }
            }
        }
        return $this->cacheManager;
    }

    /**
     * Gets the MODX parser.
     *
     * Returns an instance of modParser responsible for parsing tags in element
     * content, performing actions, returning content and/or sending other responses
     * in the process.
     *
     * @return object The modParser for this modX instance.
     */
    public function getParser() {
        return $this->getService('parser', 'modParser');
    }

    /**
     * Gets all of the parent resource ids for a given resource.
     *
     * @param integer $id The resource id for the starting node.
     * @param integer $height How many levels max to search for parents (default 10).
     * @param array $options An array of filtering options, such as 'context' to specify the context to grab from
     * @return array An array of all the parent resource ids for the specified resource.
     */
    public function getParentIds($id= null, $height= 10,array $options = array()) {
        $parentId= 0;
        $parents= array ();
        if ($id && $height > 0) {

            $context = '';
            if (!empty($options['context'])) {
                $this->getContext($options['context']);
                $context = $options['context'];
            }
            $resourceMap = !empty($context) && !empty($this->contexts[$context]->resourceMap) ? $this->contexts[$context]->resourceMap : $this->resourceMap;

            foreach ($resourceMap as $parentId => $mapNode) {
                if (array_search($id, $mapNode) !== false) {
                    $parents[]= $parentId;
                    break;
                }
            }
            if ($parentId && !empty($parents)) {
                $height--;
                $parents= array_merge($parents, $this->getParentIds($parentId,$height,$options));
            }
        }
        return $parents;
    }

    /**
     * Gets all of the child resource ids for a given resource.
     *
     * @see getTree for hierarchical node results
     * @param integer $id The resource id for the starting node.
     * @param integer $depth How many levels max to search for children (default 10).
     * @param array $options An array of filtering options, such as 'context' to specify the context to grab from
     * @return array An array of all the child resource ids for the specified resource.
     */
    public function getChildIds($id= null, $depth= 10,array $options = array()) {
        $children= array ();
        if ($id !== null && intval($depth) >= 1) {
            $id= is_int($id) ? $id : intval($id);

            $context = '';
            if (!empty($options['context'])) {
                $this->getContext($options['context']);
                $context = $options['context'];
            }
            $resourceMap = !empty($context) && !empty($this->contexts[$context]->resourceMap) ? $this->contexts[$context]->resourceMap : $this->resourceMap;
            
            if (isset ($resourceMap["{$id}"])) {
                if ($children= $resourceMap["{$id}"]) {
                    foreach ($children as $child) {
                        $processDepth = $depth - 1;
                        if ($c= $this->getChildIds($child,$processDepth,$options)) {
                            $children= array_merge($children, $c);
                        }
                    }
                }
            }
        }
        return $children;
    }

    /**
     * Get a site tree from a single or multiple modResource instances.
     *
     * @see getChildIds for linear results
     * @param int|array $id A single or multiple modResource ids to build the
     * tree from.
     * @param int $depth The maximum depth to build the tree (default 10).
     * @return array An array containing the tree structure.
     */
    public function getTree($id= null, $depth= 10) {
        $tree= array ();
        if ($id !== null) {
            if (is_array ($id)) {
                foreach ($id as $k => $v) {
                    $tree[$v]= $this->getTree($v, $depth);
                }
            }
            elseif ($branch= $this->getChildIds($id, 1)) {
                foreach ($branch as $key => $child) {
                    if ($depth > 0 && $leaf= $this->getTree($child, $depth--)) {
                        $tree[$child]= $leaf;
                    } else {
                        $tree[$child]= $child;
                    }
                }
            }
        }
        return $tree;
    }

    /**
     * Sets a placeholder value.
     *
     * @param string $key The unique string key which identifies the
     * placeholder.
     * @param mixed $value The value to set the placeholder to.
     */
    public function setPlaceholder($key, $value) {
        if (is_string($key)) {
            $this->placeholders["{$key}"]= $value;
        }
    }

    /**
     * Sets a collection of placeholders stored in an array or as object vars.
     *
     * An optional namespace parameter can be prepended to each placeholder key in the collection,
     * to isolate the collection of placeholders.
     *
     * Note that unlike toPlaceholders(), this function does not add separators between the
     * namespace and the placeholder key. Use toPlaceholders() when working with multi-dimensional
     * arrays or objects with variables other than scalars so each level gets delimited by a
     * separator.
     *
     * @param array|object $placeholders An array of values or object to set as placeholders.
     * @param string $namespace A namespace prefix to prepend to each placeholder key.
     */
    public function setPlaceholders($placeholders, $namespace= '') {
        $this->toPlaceholders($placeholders, $namespace, '');
    }

    /**
     * Sets placeholders from values stored in arrays and objects.
     *
     * Each recursive level adds to the prefix, building an access path using an optional separator.
     *
     * @param array|object $subject An array or object to process.
     * @param string $prefix An optional prefix to be prepended to the placeholder keys. Recursive
     * calls prepend the parent keys.
     * @param string $separator A separator to place in between the prefixes and keys. Default is a
     * dot or period: '.'.
     * @param boolean $restore Set to true if you want overwritten placeholder values returned.
     * @return array A multi-dimensional array containing up to two elements: 'keys' which always
     * contains an array of placeholder keys that were set, and optionally, if the restore parameter
     * is true, 'restore' containing an array of placeholder values that were overwritten by the method.
     */
    public function toPlaceholders($subject, $prefix= '', $separator= '.', $restore= false) {
        $keys = array();
        $restored = array();
        if (is_object($subject)) {
            if ($subject instanceof xPDOObject) {
                $subject= $subject->toArray();
            } else {
                $subject= get_object_vars($subject);
            }
        }
        if (is_array($subject)) {
            foreach ($subject as $key => $value) {
                $rv = $this->toPlaceholder($key, $value, $prefix, $separator, $restore);
                if (isset($rv['keys'])) {
                    foreach ($rv['keys'] as $rvKey) $keys[] = $rvKey;
                }
                if ($restore === true && isset($rv['restore'])) {
                    $restored = array_merge($restored, $rv['restore']);
                }
            }
        }
        $return = array('keys' => $keys);
        if ($restore === true) $return['restore'] = $restored;
        return $return;
    }

    /**
     * Recursively validates and sets placeholders appropriate to the value type passed.
     *
     * @param string $key The key identifying the value.
     * @param mixed $value The value to set.
     * @param string $prefix A string prefix to prepend to the key. Recursive calls prepend the
     * parent keys as well.
     * @param string $separator A separator placed in between the prefix and the key. Default is a
     * dot or period: '.'.
     * @param boolean $restore Set to true if you want overwritten placeholder values returned.
     * @return array A multi-dimensional array containing up to two elements: 'keys' which always
     * contains an array of placeholder keys that were set, and optionally, if the restore parameter
     * is true, 'restore' containing an array of placeholder values that were overwritten by the method.
     */
    public function toPlaceholder($key, $value, $prefix= '', $separator= '.', $restore= false) {
        $return = array('keys' => array());
        if ($restore === true) $return['restore'] = array();
        if (!empty($prefix) && !empty($separator)) {
            $prefix .= $separator;
        }
        if (is_array($value) || is_object($value)) {
            $return = $this->toPlaceholders($value, "{$prefix}{$key}", $separator, $restore);
        } elseif (is_scalar($value)) {
            $return['keys'][] = "{$prefix}{$key}";
            if ($restore === true && array_key_exists("{$prefix}{$key}", $this->placeholders)) {
                $return['restore']["{$prefix}{$key}"] = $this->getPlaceholder("{$prefix}{$key}");
            }
            $this->setPlaceholder("{$prefix}{$key}", $value);
        }
        return $return;
    }

    /**
     * Get a placeholder value by key.
     *
     * @param string $key The key of the placeholder to a return a value from.
     * @return mixed The value of the requested placeholder, or an empty string if not located.
     */
    public function getPlaceholder($key) {
        $placeholder= null;
        if (is_string($key) && array_key_exists($key, $this->placeholders)) {
            $placeholder= & $this->placeholders["{$key}"];
        }
        return $placeholder;
    }

    /**
     * Unset a placeholder value by key.
     *
     * @param string $key The key of the placeholder to unset.
     */
    public function unsetPlaceholder($key) {
        if (is_string($key) && array_key_exists($key, $this->placeholders)) {
            unset($this->placeholders[$key]);
        }
    }

    /**
     * Unset multiple placeholders, either by prefix or an array of keys.
     *
     * @param string|array $keys A string prefix or an array of keys indicating
     * the placeholders to unset.
     */
    public function unsetPlaceholders($keys) {
        if (is_array($keys)) {
            foreach ($keys as $key) {
                if (is_string($key)) $this->unsetPlaceholder($key);
                if (is_array($key)) $this->unsetPlaceholders($key);
            }
        } elseif (is_string($keys)) {
            $placeholderKeys = array_keys($this->placeholders);
            foreach ($placeholderKeys as $key) {
                if (strpos($key, $keys) === 0) $this->unsetPlaceholder($key);
            }
        }
    }

    /**
     * Generates a URL representing a specified resource.
     *
     * @param integer $id The id of a resource.
     * @param string $context Specifies a context to limit URL generation to.
     * @param string $args A query string to append to the generated URL.
     * @param mixed $scheme The scheme indicates in what format the URL is generated.<br>
     * <pre>
     *      -1 : (default value) URL is relative to site_url
     *       0 : see http
     *       1 : see https
     *    full : URL is absolute, prepended with site_url from config
     *     abs : URL is absolute, prepended with base_url from config
     *    http : URL is absolute, forced to http scheme
     *   https : URL is absolute, forced to https scheme
     * </pre>
     * @return string The URL for the resource.
     */
    public function makeUrl($id, $context= '', $args= '', $scheme= -1) {
        $url= '';
        if ($validid = intval($id)) {
            $id = $validid;
            if ($context == '' || $this->context->get('key') == $context) {
                $url= $this->context->makeUrl($id, $args, $scheme);
            }
            if (empty($url) && ($context !== $this->context->get('key'))) {
                $ctx= null;
                if ($context == '') {
                    if ($results = $this->query("SELECT context_key FROM " . $this->getTableName('modResource') . " WHERE id = {$id}")) {
                        $contexts= $results->fetchAll(PDO::FETCH_COLUMN);
                        if ($contextKey = reset($contexts)) {
                            $ctx = $this->getContext($contextKey);
                        }
                    }
                } else {
                    $ctx = $this->getContext($context);
                }
                if ($ctx) {
                    $url= $ctx->makeUrl($id, $args, 'full');
                }
            }

            if (!empty($url) && $this->getOption('xhtml_urls',null,false)) {
                $url= preg_replace("/&(?!amp;)/","&amp;", $url);
            }
        } else {
            $this->log(modX::LOG_LEVEL_ERROR, '`' . $id . '` is not a valid integer and may not be passed to makeUrl()');
        }
        return $url;
    }

    /**
     * Send the user to a type-specific core error page and halt PHP execution.
     *
     * @param string $type The type of error to present.
     * @param array $options An array of options to provide for the error file.
     */
    public function sendError($type = '', $options = array()) {
        if (!is_string($type) || empty($type)) $type = $this->getOption('error_type', $options, 'unavailable');
        while (@ob_end_clean()) {}
        if (file_exists(MODX_CORE_PATH . "error/{$type}.include.php")) {
            @include(MODX_CORE_PATH . "error/{$type}.include.php");
        }
        header($this->getOption('error_header', $options, 'HTTP/1.1 503 Service Unavailable'));
        $errorPageTitle = $this->getOption('error_pagetitle', $options, 'Error 503: Site temporarily unavailable');
        $errorMessage = $this->getOption('error_message', $options, '<h1>' . $this->getOption('site_name', $options, 'Error 503') . '</h1><p>Site temporarily unavailable.</p>');
        echo "<html><head><title>{$errorPageTitle}</title></head><body>{$errorMessage}</body></html>";
        @session_write_close();
        exit();
    }

    /**
     * Sends a redirect to the specified URL using the specified options.
     *
     * Valid 'type' option values include:
     *    REDIRECT_REFRESH  Uses the header refresh method
     *    REDIRECT_META  Sends a a META HTTP-EQUIV="Refresh" tag to the output
     *    REDIRECT_HEADER  Uses the header location method
     *
     * REDIRECT_HEADER is the default.
     *
     * @param string $url The URL to redirect the client browser to.
     * @param array|boolean $options An array of options for the redirect OR
     * indicates if redirect attempts should be counted and limited to 3 (latter is deprecated
     * usage; use count_attempts in options array).
     * @param string $type The type of redirection to attempt (deprecated, use type in
     * options array).
     * @param string $responseCode The type of HTTP response code HEADER to send for the
     * redirect (deprecated, use responseCode in options array)
     */
    public function sendRedirect($url, $options= false, $type= '', $responseCode = '') {
        if (!$this->getResponse()) {
            $this->log(modX::LOG_LEVEL_FATAL, "Could not load response class.");
        }
        $this->response->sendRedirect($url, $options, $type, $responseCode);
    }

    /**
     * Forwards the request to another resource without changing the URL.
     *
     * @param integer $id The resource identifier.
     * @param string $options An array of options for the process.
     */
    public function sendForward($id, $options = null) {
        if (!$this->getRequest()) {
            $this->log(modX::LOG_LEVEL_FATAL, "Could not load request class.");
        }
        $idInt = intval($id);
        if (is_string($options) && !empty($options)) {
            $options = array('response_code' => $options);
        } elseif (!is_array($options)) {
            $options = array();
        }
        $this->elementCache = array();
        if ($idInt > 0) {
            $merge = array_key_exists('merge', $options) && !empty($options['merge']);
            $currentResource = array();
            if ($merge) {
                $excludes = array_merge(
                    explode(',', $this->getOption('forward_merge_excludes', $options, 'type,published,class_key,context_key')),
                    array(
                        'content'
                        ,'pub_date'
                        ,'unpub_date'
                        ,'richtext'
                        ,'_content'
                        ,'_processed'
                    )
                );
                reset($this->resource->_fields);
                while (list($fkey, $fval) = each($this->resource->_fields)) {
                    if (!in_array($fkey, $excludes)) {
                        if (is_scalar($fval) && $fval !== '') {
                            $currentResource[$fkey] = $fval;
                        } elseif (is_array($fval) && count($fval) === 5 && $fval[1] !== '') {
                            $currentResource[$fkey] = $fval;
                        }
                    }
                }
            }
            $this->resource= $this->request->getResource('id', $idInt, array('forward' => true));
            if ($this->resource) {
                if ($merge && !empty($currentResource)) {
                    $this->resource->_fields = array_merge($this->resource->_fields, $currentResource);
                    $this->elementCache = array();
                    unset($currentResource);
                }
                $this->resourceIdentifier= $this->resource->get('id');
                $this->resourceMethod= 'id';
                if (isset($options['response_code']) && !empty($options['response_code'])) {
                    header($options['response_code']);
                }
                $this->request->prepareResponse();
                exit();
            }
            $options= array_merge(
                array(
                    'error_type' => '404'
                    ,'error_header' => $this->getOption('error_page_header', $options,'HTTP/1.1 404 Not Found')
                    ,'error_pagetitle' => $this->getOption('error_page_pagetitle', $options,'Error 404: Page not found')
                    ,'error_message' => $this->getOption('error_page_message', $options,'<h1>Page not found</h1><p>The page you requested was not found.</p>')
                ),
                $options
            );
        }
        $this->sendError($id, $options);
    }

    /**
     * Send the user to a MODX virtual error page.
     *
     * @uses invokeEvent() The OnPageNotFound event is invoked before the error page is forwarded
     * to.
     * @param array $options An array of options to provide for the OnPageNotFound event and error
     * page.
     */
    public function sendErrorPage($options = null) {
        if (!is_array($options)) $options = array();
        $options= array_merge(
            array(
                'response_code' => $this->getOption('error_page_header', $options, 'HTTP/1.1 404 Not Found')
                ,'error_type' => '404'
                ,'error_header' => $this->getOption('error_page_header', $options, 'HTTP/1.1 404 Not Found')
                ,'error_pagetitle' => $this->getOption('error_page_pagetitle', $options, 'Error 404: Page not found')
                ,'error_message' => $this->getOption('error_page_message', $options, '<h1>Page not found</h1><p>The page you requested was not found.</p>')
            ),
            $options
        );
        $this->invokeEvent('OnPageNotFound', $options);
        $this->sendForward($this->getOption('error_page', $options, '404'), $options);
    }

    /**
     * Send the user to the MODX unauthorized page.
     *
     * @uses invokeEvent() The OnPageUnauthorized event is invoked before the unauthorized page is
     * forwarded to.
     * @param array $options An array of options to provide for the OnPageUnauthorized
     * event and unauthorized page.
     */
    public function sendUnauthorizedPage($options = null) {
        if (!is_array($options)) $options = array();
        $options= array_merge(
            array(
                'response_code' => $this->getOption('unauthorized_page_header' ,$options ,'HTTP/1.1 401 Unauthorized')
                ,'error_type' => '401'
                ,'error_header' => $this->getOption('unauthorized_page_header', $options,'HTTP/1.1 401 Unauthorized')
                ,'error_pagetitle' => $this->getOption('unauthorized_page_pagetitle',$options, 'Error 401: Unauthorized')
                ,'error_message' => $this->getOption('unauthorized_page_message', $options,'<h1>Unauthorized</h1><p>You are not authorized to view the requested content.</p>')
            ),
            $options
        );
        $this->invokeEvent('OnPageUnauthorized', $options);
        $this->sendForward($this->getOption('unauthorized_page', $options, '401'), $options);
    }

    /**
     * Get the current authenticated User and assigns it to the modX instance.
     *
     * @param string $contextKey An optional context to get the user from.
     * @param boolean $forceLoadSettings If set to true, will load settings
     * regardless of whether the user has an authenticated context or not.
     * @return modUser The user object authenticated for the request.
     */
    public function getUser($contextKey= '',$forceLoadSettings = false) {
        if ($contextKey == '') {
            if ($this->context !== null) {
                $contextKey= $this->context->get('key');
            }
        }
        if ($this->user === null || !is_object($this->user)) {
            $this->user= $this->getAuthenticatedUser($contextKey);
            if ($contextKey !== 'mgr' && !$this->user) {
                $this->user= $this->getAuthenticatedUser('mgr');
            }
        }
        if ($this->user !== null && is_object($this->user)) {
            if ($this->user->hasSessionContext($contextKey) || $forceLoadSettings) {
                if (isset ($_SESSION["modx.{$contextKey}.user.config"])) {
                    $this->_userConfig= $_SESSION["modx.{$contextKey}.user.config"];
                } else {
                    $settings= $this->user->getMany('UserSettings');
                    if (is_array($settings) && !empty ($settings)) {
                        foreach ($settings as $setting) {
                            $v= $setting->get('value');
                            $matches= array();
                            if (preg_match_all('~\{(.*?)\}~', $v, $matches, PREG_SET_ORDER)) {
                                $matchValue= '';
                                foreach ($matches as $match) {
                                    if (isset ($this->config["{$match[1]}"])) {
                                        $matchValue= $this->config["{$match[1]}"];
                                    } else {
                                        $matchValue= '';
                                    }
                                    $v= str_replace($match[0], $matchValue, $v);
                                }
                            }
                            $this->_userConfig[$setting->get('key')]= $v;
                        }
                    }
                }
                if (is_array ($this->_userConfig) && !empty ($this->_userConfig)) {
                    $_SESSION["modx.{$contextKey}.user.config"]= $this->_userConfig;
                    $this->config= array_merge($this->config, $this->_userConfig);
                }
            }
        } else {
            $this->user = $this->newObject('modUser', array(
                    'id' => 0,
                    'username' => '(anonymous)'
                )
            );
        }
        ksort($this->config);
        $this->toPlaceholders($this->user->get(array('id','username')),'modx.user');
        return $this->user;
    }

    /**
     * Gets the user authenticated in the specified context.
     *
     * @param string $contextKey Optional context key; uses current context by default.
     * @return modUser|null The user object that is authenticated in the specified context,
     * or null if no user is authenticated.
     */
    public function getAuthenticatedUser($contextKey= '') {
        $user= null;
        if ($contextKey == '') {
            if ($this->context !== null) {
                $contextKey= $this->context->get('key');
            }
        }
        if ($contextKey && isset ($_SESSION['modx.user.contextTokens'][$contextKey])) {
            $user= $this->getObject('modUser', intval($_SESSION['modx.user.contextTokens'][$contextKey]), true);
            if ($user) {
                $user->getSessionContexts();
            }
        }
        return $user;
    }

    /**
     * Checks to see if the user has a session in the specified context.
     *
     * @param string $sessionContext The context to test for a session key in.
     * @return boolean True if the user is valid in the context specified.
     */
    public function checkSession($sessionContext= 'web') {
        $hasSession = false;
        if ($this->user !== null) {
            $hasSession = $this->user->hasSessionContext($sessionContext);
        }
        return $hasSession;
    }

    /**
     * Gets the modX core version data.
     *
     * @return array The version data loaded from the config version file.
     */
    public function getVersionData() {
        if ($this->version === null) {
            $this->version= @ include_once MODX_CORE_PATH . "docs/version.inc.php";
        }
        return $this->version;
    }

    /**
     * Reload the config settings.
     *
     * @return array An associative array of configuration key/values
     */
    public function reloadConfig() {
        $this->getCacheManager();
        $this->cacheManager->refresh();

        if (!$this->_loadConfig()) {
            $this->log(modX::LOG_LEVEL_ERROR, 'Could not reload core MODX configuration!');
        }
        return $this->config;
    }

    /**
     * Get the configuration for the site.
     *
     * @return array An associate array of configuration key/values
     */
    public function getConfig() {
        if (!$this->_initialized || !is_array($this->config) || empty ($this->config)) {
            if (!isset ($this->config['base_url']))
                $this->config['base_url']= MODX_BASE_URL;
            if (!isset ($this->config['base_path']))
                $this->config['base_path']= MODX_BASE_PATH;
            if (!isset ($this->config['core_path']))
                $this->config['core_path']= MODX_CORE_PATH;
            if (!isset ($this->config['url_scheme']))
                $this->config['url_scheme']= MODX_URL_SCHEME;
            if (!isset ($this->config['http_host']))
                $this->config['http_host']= MODX_HTTP_HOST;
            if (!isset ($this->config['site_url']))
                $this->config['site_url']= MODX_SITE_URL;
            if (!isset ($this->config['manager_path']))
                $this->config['manager_path']= MODX_MANAGER_PATH;
            if (!isset ($this->config['manager_url']))
                $this->config['manager_url']= MODX_MANAGER_URL;
            if (!isset ($this->config['assets_path']))
                $this->config['assets_path']= MODX_ASSETS_PATH;
            if (!isset ($this->config['assets_url']))
                $this->config['assets_url']= MODX_ASSETS_URL;
            if (!isset ($this->config['connectors_path']))
                $this->config['connectors_path']= MODX_CONNECTORS_PATH;
            if (!isset ($this->config['connectors_url']))
                $this->config['connectors_url']= MODX_CONNECTORS_URL;
            if (!isset ($this->config['processors_path']))
                $this->config['processors_path']= MODX_PROCESSORS_PATH;
            if (!isset ($this->config['request_param_id']))
                $this->config['request_param_id']= 'id';
            if (!isset ($this->config['request_param_alias']))
                $this->config['request_param_alias']= 'q';
            if (!isset ($this->config['https_port']))
                $this->config['https_port']= isset($GLOBALS['https_port']) ? $GLOBALS['https_port'] : 443;
            if (!isset ($this->config['error_handler_class']))
                $this->config['error_handler_class']= 'error.modErrorHandler';

            $this->_config= $this->config;
            if (!$this->_loadConfig()) {
                $this->log(modX::LOG_LEVEL_FATAL, "Could not load core MODX configuration!");
                return null;
            }
        }
        return $this->config;
    }

    /**
     * Initialize, cleanse, and process a request made to a modX site.
     *
     * @return mixed The result of the request handler.
     */
    public function handleRequest() {
        if ($this->getRequest()) {
            return $this->request->handleRequest();
        }
        return '';
    }

    /**
     * Attempt to load the request handler class, if not already loaded.
     *
     * @access public
     * @param string $class The class name of the response class to load. Defaults to
     * modRequest; is ignored if the Setting "modRequest.class" is set.
     * @param string $path The absolute path by which to load the response class from.
     * Defaults to the current MODX model path.
     * @return boolean Returns true if a valid request handler object was
     * loaded on this or any previous call to the function, false otherwise.
     */
    public function getRequest($class= 'modRequest', $path= '') {
        if ($this->request === null || !($this->request instanceof modRequest)) {
            $requestClass = $this->getOption('modRequest.class',$this->config,$class);
            if ($className= $this->loadClass($requestClass, $path, !empty($path), true))
                $this->request= new $className ($this);
        }
        return is_object($this->request) && $this->request instanceof modRequest;
    }

    /**
     * Attempt to load the response handler class, if not already loaded.
     *
     * @access public
     * @param string $class The class name of the response class to load. Defaults to
     * modResponse; is ignored if the Setting "modResponse.class" is set.
     * @param string $path The absolute path by which to load the response class from.
     * Defaults to the current MODX model path.
     * @return boolean Returns true if a valid response handler object was
     * loaded on this or any previous call to the function, false otherwise.
     */
    public function getResponse($class= 'modResponse', $path= '') {
        $responseClass= $this->getOption('modResponse.class',$this->config,$class);
        $className= $this->loadClass($responseClass, $path, !empty($path), true);
        if ($this->response === null || !($this->response instanceof $className)) {
            if ($className) $this->response= new $className ($this);
        }
        return $this->response instanceof $className;
    }

    /**
     * Register CSS to be injected inside the HEAD tag of a resource.
     *
     * @param string $src The CSS to be injected before the closing HEAD tag in
     * an HTML response.
     * @return void
     */
    public function regClientCSS($src) {
        if (isset ($this->loadedjscripts[$src]) && $this->loadedjscripts[$src]) {
            return;
        }
        $this->loadedjscripts[$src]= true;
        if (strpos(strtolower($src), "<style") !== false || strpos(strtolower($src), "<link") !== false) {
            $this->sjscripts[count($this->sjscripts)]= $src;
        } else {
            $this->sjscripts[count($this->sjscripts)]= '<link rel="stylesheet" href="' . $src . '" type="text/css" />';
        }
    }

    /**
     * Register JavaScript to be injected inside the HEAD tag of a resource.
     *
     * @param string $src The JavaScript to be injected before the closing HEAD
     * tag of an HTML response.
     * @param boolean $plaintext Optional param to treat the $src as plaintext
     * rather than assuming it is JavaScript.
     * @return void
     */
    public function regClientStartupScript($src, $plaintext= false) {
        if (!empty ($src) && !array_key_exists($src, $this->loadedjscripts)) {
            if (isset ($this->loadedjscripts[$src]))
                return;
            $this->loadedjscripts[$src]= true;
            if ($plaintext == true) {
                $this->sjscripts[count($this->sjscripts)]= $src;
            } elseif (strpos(strtolower($src), "<script") !== false) {
                $this->sjscripts[count($this->sjscripts)]= $src;
            } else {
                $this->sjscripts[count($this->sjscripts)]= '<script type="text/javascript" src="' . $src . '"></script>';
            }
        }
    }

    /**
     * Register JavaScript to be injected before the closing BODY tag.
     *
     * @param string $src The JavaScript to be injected before the closing BODY
     * tag in an HTML response.
     * @param boolean $plaintext Optional param to treat the $src as plaintext
     * rather than assuming it is JavaScript.
     * @return void
     */
    public function regClientScript($src, $plaintext= false) {
        if (isset ($this->loadedjscripts[$src]))
            return;
        $this->loadedjscripts[$src]= true;
        if ($plaintext == true) {
            $this->jscripts[count($this->jscripts)]= $src;
        } elseif (strpos(strtolower($src), "<script") !== false) {
            $this->jscripts[count($this->jscripts)]= $src;
        } else {
            $this->jscripts[count($this->jscripts)]= '<script type="text/javascript" src="' . $src . '"></script>';
        }
    }

    /**
     * Register HTML to be injected before the closing HEAD tag.
     *
     * @param string $html The HTML to be injected.
     */
    public function regClientStartupHTMLBlock($html) {
        return $this->regClientStartupScript($html, true);
    }

    /**
     * Register HTML to be injected before the closing BODY tag.
     *
     * @param string $html The HTML to be injected.
     */
    public function regClientHTMLBlock($html) {
        return $this->regClientScript($html, true);
    }

    /**
     * Returns all registered JavaScripts.
     *
     * @access public
     * @return string The parsed HTML of the client scripts.
     */
    public function getRegisteredClientScripts() {
        $string= '';
        if (is_array($this->jscripts)) {
            $string= implode("\n",$this->jscripts);
        }
        return $string;
    }

    /**
     * Returns all registered startup CSS, JavaScript, or HTML blocks.
     *
     * @access public
     * @return string The parsed HTML of the startup scripts.
     */
    public function getRegisteredClientStartupScripts() {
        $string= '';
        if (is_array ($this->sjscripts)) {
            $string= implode("\n", $this->sjscripts);
        }
        return $string;
    }

    /**
     * Invokes a specified Event with an optional array of parameters.
     *
     * @todo refactor this completely, yuck!!
     *
     * @access public
     * @param string $eventName Name of an event to invoke.
     * @param array $params Optional params provided to the elements registered with an event.
     * @return boolean
     */
    public function invokeEvent($eventName, array $params= array ()) {
        if (!$eventName)
            return false;
        if ($this->eventMap === null)
            $this->_initEventMap($this->context->get('key'));
        if (!isset ($this->eventMap[$eventName])) {
            //$this->log(modX::LOG_LEVEL_DEBUG,'System event '.$eventName.' was executed but does not exist.');
            return false;
        }
        $results= array ();
        if (count($this->eventMap[$eventName])) {
            $this->event= new modSystemEvent();
            foreach ($this->eventMap[$eventName] as $pluginId => $pluginPropset) {
                $plugin= null;
                $this->Event= & $this->event;
                $this->event->resetEventObject();
                $this->event->name= $eventName;
                if (isset ($this->pluginCache[$pluginId])) {
                    $plugin= $this->newObject('modPlugin');
                    $plugin->fromArray($this->pluginCache[$pluginId], '', true, true);
                    $plugin->_processed = false;
                    if ($plugin->get('disabled')) {
                        $plugin= null;
                    }
                } else {
                    $plugin= $this->getObject('modPlugin', array ('id' => intval($pluginId), 'disabled' => '0'), true);
                }
                if ($plugin && !$plugin->get('disabled')) {
                    $this->event->activated= true;
                    $this->event->activePlugin= $plugin->get('name');
                    $this->event->propertySet= (($pspos = strpos($pluginPropset, ':')) >= 1) ? substr($pluginPropset, $pspos + 1) : '';

                    /* merge in plugin properties */
                    $eventParams = array_merge($plugin->getProperties(),$params);

                    $msg= $plugin->process($eventParams);
                    $results[]= $this->event->_output;
                    if ($msg && is_string($msg)) {
                        $this->log(modX::LOG_LEVEL_ERROR, '[' . $this->event->name . ']' . $msg);
                    } elseif ($msg === false) {
                        $this->log(modX::LOG_LEVEL_ERROR, '[' . $this->event->name . '] Plugin failed!');
                    }
                    $this->event->activePlugin= '';
                    $this->event->propertySet= '';
                    if (!$this->event->isPropagatable()) {
                        break;
                    }
                }
            }
        }
        return $results;
    }

    /**
     * Loads and runs a specific processor.
     *
     * @param string $action The processor to run, eg: context/update
     * @param array $scriptProperties Optional. An array of parameters to pass to the processor.
     * @param array $options Optional. An array of options for running the processor, such as:
     *
     * - processors_path - If specified, will override the default MODX processors path.
     * - location - A prefix to load processor files from, will prepend to the action parameter
     * (Note: location will be deprecated in future Revolution versions.)
     *
     * @return mixed The result of the processor.
     */
    public function runProcessor($action = '',$scriptProperties = array(),$options = array()) {
        if (!$this->loadClass('modProcessor','',false,true)) {
            $this->log(modX::LOG_LEVEL_ERROR,'Could not load modProcessor class.');
            return false;
        }

        $result = null;
        /* backwards compat for $options['action']
         * @deprecated Removing in 2.2
         */
        if (empty($action)) {
            if (!empty($options['action'])) {
                $action = $options['action'];
            } else {
                return $result;
            }
        }

        /* calculate processor file path from options and action */
        $processorFile = isset($options['processors_path']) && !empty($options['processors_path']) ? $options['processors_path'] : $this->config['processors_path'];
        if (isset($options['location']) && !empty($options['location'])) $processorFile .= ltrim($options['location'],'/') . '/';
        $processorFile .= ltrim(str_replace('../', '', $action . '.php'),'/');

        $response = '';
        if (file_exists($processorFile)) {
            if (!isset($this->lexicon)) $this->getService('lexicon', 'modLexicon');
            if (!isset($this->error)) $this->request->loadErrorHandler();

            $processor = new modProcessor($this);
            $processor->setPath($processorFile);
            $processor->setProperties($scriptProperties);
            $response = $processor->run();
        } else {
            $this->log(modX::LOG_LEVEL_ERROR, "Processor {$processorFile} does not exist; " . print_r($options, true));
        }
        return $response;
    }

    /**
     * Returns the current user ID, for the current or specified context.
     *
     * @param string $context The key of a valid modContext so you can retrieve
     * the current user ID from a different context than the current.
     * @return integer The ID of the current user.
     */
    public function getLoginUserID($context= '') {
        $userId = 0;
        if (empty($context) && $this->context instanceof modContext && $this->user instanceof modUser) {
            if ($this->user->hasSessionContext($this->context->get('key'))) {
                $userId = $this->user->get('id');
            }

        } else {
            $user = $this->getAuthenticatedUser($context);
            if ($user instanceof modUser) {
                $userId = $user->get('id');
            }
        }
        return $userId;
    }

    /**
     * Returns the current user name, for the current or specified context.
     *
     * @param string $context The key of a valid modContext so you can retrieve
     * the username from a different context than the current.
     * @return string The username of the current user.
     */
    public function getLoginUserName($context= '') {
        $userName = '';
        if (empty($context) && $this->context instanceof modContext && $this->user instanceof modUser) {
            if ($this->user->hasSessionContext($this->context->get('key'))) {
                $userName = $this->user->get('username');
            }

        } else {
            $user = $this->getAuthenticatedUser($context);
            if ($user instanceof modUser) {
                $userName = $user->get('username');
            }
        }
        return $userName;
    }

    /**
     * Returns whether modX instance has been initialized or not.
     *
     * @access public
     * @return boolean
     */
    public function isInitialized() {
        return $this->_initialized;
    }

    /**
     * Legacy fatal error message.
     *
     * @deprecated
     * @param string $msg
     * @param string $query
     * @param bool $is_error
     * @param string $nr
     * @param string $file
     * @param string $source
     * @param string $text
     * @param string $line
     */
    public function messageQuit($msg='unspecified error', $query='', $is_error=true, $nr='', $file='', $source='', $text='', $line='') {
        $this->log(modX::LOG_LEVEL_FATAL, 'msg: ' . $msg . "\n" . 'query: ' . $query . "\n" . 'nr: ' . $nr . "\n" . 'file: ' . $file . "\n" . 'source: ' . $source . "\n" . 'text: ' . $text . "\n" . 'line: ' . $line . "\n");
    }

    /**
     * Process and return the output from a PHP snippet by name.
     *
     * @param string $snippetName The name of the snippet.
     * @param array $params An associative array of properties to pass to the
     * snippet.
     * @return string The processed output of the snippet.
     */
    public function runSnippet($snippetName, array $params= array ()) {
        $output= '';
        if (array_key_exists($snippetName, $this->sourceCache['modSnippet'])) {
            $snippet = $this->newObject('modSnippet');
            $snippet->fromArray($this->sourceCache['modSnippet'][$snippetName]['fields'], '', true, true);
            $snippet->setPolicies($this->sourceCache['modSnippet'][$snippetName]['policies']);
        } else {
            $snippet= $this->getObject('modSnippet', array ('name' => $snippetName), true);
            if (!empty($snippet)) {
                $this->sourceCache['modSnippet'][$snippetName] = array (
                    'fields' => $snippet->toArray(),
                    'policies' => $snippet->getPolicies()
                );
            }
        }
        if (!empty($snippet)) {
            $snippet->setCacheable(false);
            $output= $snippet->process($params);
        }
        return $output;
    }

    /**
     * Process and return the output from a Chunk by name.
     *
     * @param string $chunkName The name of the chunk.
     * @param array $properties An associative array of properties to process
     * the Chunk with, treated as placeholders within the scope of the Element.
     * @return string The processed output of the Chunk.
     */
    public function getChunk($chunkName, array $properties= array ()) {
        $output= '';
        if (array_key_exists($chunkName, $this->sourceCache['modChunk'])) {
            $chunk = $this->newObject('modChunk');
            $chunk->fromArray($this->sourceCache['modChunk'][$chunkName]['fields'], '', true, true);
            $chunk->setPolicies($this->sourceCache['modChunk'][$chunkName]['policies']);
        } else {
            $chunk= $this->getObject('modChunk', array ('name' => $chunkName), true);
            if (!empty($chunk) || $chunk === '0') {
                $this->sourceCache['modChunk'][$chunkName]= array (
                    'fields' => $chunk->toArray(),
                    'policies' => $chunk->getPolicies()
                );
            }
        }
        if (!empty($chunk) || $chunk === '0') {
            $chunk->setCacheable(false);
            $output= $chunk->process($properties);
        }
        return $output;
    }

    /**
     * Parse a chunk using an associative array of replacement variables.
     *
     * @param string $chunkName The name of the chunk.
     * @param array $chunkArr An array of properties to replace in the chunk.
     * @param string $prefix The placeholder prefix, defaults to [[+.
     * @param string $suffix The placeholder suffix, defaults to ]].
     * @return string The processed chunk with the placeholders replaced.
     */
    public function parseChunk($chunkName, $chunkArr, $prefix='[[+', $suffix=']]') {
        $chunk= $this->getChunk($chunkName);
        if (!empty($chunk) || $chunk === '0') {
            if(is_array($chunkArr)) {
                reset($chunkArr);
                while (list($key, $value)= each($chunkArr)) {
                    $chunk= str_replace($prefix.$key.$suffix, $value, $chunk);
                }
            }
        }
        return $chunk;
    }

    /**
     * Strip unwanted HTML and PHP tags and supplied patterns from content.
     *
     * @see modX::$sanitizePatterns
     * @param string $html The string to strip
     * @param string $allowed An array of allowed HTML tags
     * @param array $patterns An array of patterns to sanitize with; otherwise will use modX::$sanitizePatterns
     * @param int $depth The depth in which the parser will strip given the patterns specified
     * @return boolean True if anything was stripped
     */
    public function stripTags($html, $allowed= '', $patterns= array(), $depth= 10) {
        $stripped= strip_tags($html, $allowed);
        if (is_array($patterns)) {
            if (empty($patterns)) {
                $patterns = $this->sanitizePatterns;
            }
            foreach ($patterns as $pattern) {
                $depth = ((integer) $depth ? (integer) $depth : 10);
                $iteration = 1;
                while ($iteration <= $depth && preg_match($pattern, $stripped)) {
                    $stripped= preg_replace($pattern, '', $stripped);
                    $iteration++;
                }
            }
        }
        return $stripped;
    }

    /**
     * Returns true if user has the specified policy permission.
     *
     * @param string $pm Permission key to check.
     * @return boolean
     */
    public function hasPermission($pm) {
        $state = $this->context->checkPolicy($pm);
        return $state;
    }

    /**
     * Logs a manager action.
     * @access public
     * @param string $action The action to pull from the lexicon module.
     * @param string $class_key The class key that the action is being performed
     * on.
     * @param mixed $item The primary key id or array of keys to grab the object
     * with
     * @return modManagerLog The newly created modManagerLog object
     */
    public function logManagerAction($action,$class_key,$item) {
        $ml = $this->newObject('modManagerLog');
        $ml->set('user',$this->user->get('id'));
        $ml->set('occurred',strftime('%Y-%m-%d %H:%M:%S'));
        $ml->set('action',$action);
        $ml->set('classKey',$class_key);
        $ml->set('item',$item);

        if (!$ml->save()) {
            $this->log(modX::LOG_LEVEL_ERROR,$this->lexicon('manager_log_err_save'));
            return null;
        }
        return $ml;
    }

    /**
     * Remove an event from the eventMap so it will not be invoked.
     *
     * @param string $event
     * @return boolean false if the event parameter is not specified or is not
     * present in the eventMap.
     */
    public function removeEventListener($event) {
        $removed = false;
        if (!empty($event) && isset($this->eventMap[$event])) {
            unset ($this->eventMap[$event]);
            $removed = true;
        }
        return $removed;
    }

    /**
     * Remove all registered events for the current request.
     */
    public function removeAllEventListener() {
        unset ($this->eventMap);
        $this->eventMap= array ();
    }

    /**
     * Add a plugin to the eventMap within the current execution cycle.
     *
     * @param string $event Name of the event.
     * @param integer $pluginId Plugin identifier to add to the event.
     * @return boolean true if the event is successfully added, otherwise false.
     */
    public function addEventListener($event, $pluginId) {
        $added = false;
        if ($event && $pluginId) {
            if (!isset($this->eventMap[$event]) || empty ($this->eventMap[$event])) {
                $this->eventMap[$event]= array();
            }
            $this->eventMap[$event][]= $pluginId;
            $added= true;
        }
        return $added;
    }

    /**
     * Switches the primary Context for the modX instance.
     *
     * Be aware that switching contexts does not allow custom session handling
     * classes to be loaded. The gateway defines the session handling that is
     * applied to a single request. To create a context with a custom session
     * handler you must create a unique context gateway that initializes that
     * context directly.
     *
     * @param string $contextKey The key of the context to switch to.
     * @return boolean True if the switch was successful, otherwise false.
     */
    public function switchContext($contextKey) {
        $switched= false;
        if ($this->context->key != $contextKey) {
            $switched= $this->_initContext($contextKey);
            if ($switched) {
                if (is_array($this->config)) {
                    $this->setPlaceholders($this->config, '+');
                }
            }
        }
        return $switched;
    }

    /**
     * Retrieve a context by name without initializing it.
     *
     * Within a request, contexts retrieved using this function will cache the
     * context data into the modX::$contexts array to avoid loading the same
     * context multiple times.
     *
     * @access public
     * @param string $contextKey The context to retrieve.
     * @return modContext A modContext object retrieved from cache or
     * database.
     */
    public function getContext($contextKey) {
        if (!isset($this->contexts[$contextKey])) {
            $this->contexts[$contextKey]= $this->getObject('modContext', $contextKey);
            if ($this->contexts[$contextKey]) {
                $this->contexts[$contextKey]->prepare();
            }
        }
        return $this->contexts[$contextKey];
    }

    /**
     * Gets a map of events and registered plugins for the specified context.
     *
     * Service #s:
     * 1 - Parser Service Events
     * 2 - Manager Access Events
     * 3 - Web Access Service Events
     * 4 - Cache Service Events
     * 5 - Template Service Events
     * 6 - User Defined Events
     *
     * @param string $contextKey Context identifier.
     * @return array A map of events and registered plugins for each.
     */
    public function getEventMap($contextKey) {
        $eventElementMap= array ();
        if ($contextKey) {
            switch ($contextKey) {
                case 'mgr':
                    /* dont load Web Access Service Events */
                    $service= "Event.service IN (1,2,4,5,6) AND";
                    break;
                default:
                    /* dont load Manager Access Events */
                    $service= "Event.service IN (1,3,4,5,6) AND";
            }
            $pluginEventTbl= $this->getTableName('modPluginEvent');
            $eventTbl= $this->getTableName('modEvent');
            $pluginTbl= $this->getTableName('modPlugin');
            $propsetTbl= $this->getTableName('modPropertySet');
            $sql= "
                SELECT
                    Event.name AS event,
                    PluginEvent.pluginid,
                    PropertySet.name AS propertyset
                FROM {$pluginEventTbl} PluginEvent
                    INNER JOIN {$pluginTbl} Plugin ON Plugin.id = PluginEvent.pluginid AND Plugin.disabled = 0
                    INNER JOIN {$eventTbl} Event ON {$service} Event.name = PluginEvent.event
                    LEFT JOIN {$propsetTbl} PropertySet ON PluginEvent.propertyset = PropertySet.id
                ORDER BY Event.name, PluginEvent.priority ASC
            ";
            $stmt= $this->prepare($sql);
            if ($stmt && $stmt->execute()) {
                while ($ee = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $eventElementMap[$ee['event']][(string) $ee['pluginid']]= $ee['pluginid'] . (!empty($ee['propertyset']) ? ':' . $ee['propertyset'] : '');
                }
            }
        }
        return $eventElementMap;
    }

    /**
     * Checks for locking on a page.
     *
     * @param integer $id Id of the user checking for a lock.
     * @param string $action The action identifying what is locked.
     * @param string $type Message indicating the kind of lock being checked.
     * @return string|boolean If locked, will return a locked message
     */
    public function checkForLocks($id,$action,$type) {
        $msg= false;
        $id= intval($id);
        if (!$id) $id= $this->getLoginUserID();
        if ($au = $this->getObject('modActiveUser',array('action' => $action, 'internalKey:!=' => $id))) {
            $msg = $this->lexicon('lock_msg',array(
                'name' => $au->get('username'),
                'object' => $type,
            ));
        }
        return $msg;
    }

    /**
     * Grabs a processed lexicon string.
     *
     * @access public
     * @param string $key
     * @param array $params
     * @return null|string The translated string, or null if none is set
     */
    public function lexicon($key,$params = array()) {
        if ($this->lexicon) {
            return $this->lexicon->process($key,$params);
        } else {
            $this->log(modX::LOG_LEVEL_ERROR,'Culture not initialized; cannot use lexicon.');
        }
        return null;
    }

    /**
     * Returns the state of the SESSION being used by modX.
     *
     * The possible values for session state are:
     *
     * modX::SESSION_STATE_UNINITIALIZED
     * modX::SESSION_STATE_UNAVAILABLE
     * modX::SESSION_STATE_EXTERNAL
     * modX::SESSION_STATE_INITIALIZED
     *
     * @return integer Returns an integer representing the session state.
     */
    public function getSessionState() {
        if ($this->_sessionState == modX::SESSION_STATE_UNINITIALIZED) {
            if (XPDO_CLI_MODE) {
                $this->_sessionState = modX::SESSION_STATE_UNAVAILABLE;
            }
            elseif (isset($_SESSION)) {
                $this->_sessionState = modX::SESSION_STATE_EXTERNAL;
            }
        }
        return $this->_sessionState;
    }

    /**
     * Executed before parser processing of an element.
     */
    public function beforeProcessing() {}

    /**
     * Executed before the response is rendered.
     */
    public function beforeRender() {}

    /**
     * Executed before the handleRequest function.
     */
    public function beforeRequest() {}

    /**
     * Determines the current site_status.
     *
     * @return boolean True if the site is online or the user has a valid
     * user session in the 'mgr' context; false otherwise.
     */
    public function checkSiteStatus() {
        $status = false;
        if ($this->config['site_status'] == '1' || $this->hasPermission('view_offline')) {
            $status = true;
        }
        return $status;
    }

    /**
     * Loads a specified Context.
     *
     * Merges any context settings with the modX::$config, and performs any
     * other context specific initialization tasks.
     *
     * @access protected
     * @param string $contextKey A context identifier.
     * @return boolean True if the context was properly initialized
     */
    protected function _initContext($contextKey) {
        $initialized= false;
        $oldContext = is_object($this->context) ? $this->context->get('key') : '';
        if (isset($this->contexts[$contextKey])) {
            $this->context= & $this->contexts[$contextKey];
        } else {
            $this->context= $this->newObject('modContext');
            $this->context->_fields['key']= $contextKey;
        }
        if ($this->context) {
            if (!$this->context->prepare()) {
                $this->log(modX::LOG_LEVEL_ERROR, 'Could not prepare context: ' . $contextKey);
            } else {
                if ($this->context->checkPolicy('load')) {
                    $this->aliasMap= & $this->context->aliasMap;
                    $this->resourceMap= & $this->context->resourceMap;
                    $this->eventMap= & $this->context->eventMap;
                    $this->pluginCache= & $this->context->pluginCache;
                    $this->config= array_merge($this->_systemConfig, $this->context->config);
                    if ($this->_initialized) {
                        $this->getUser();
                    }
                    $initialized = true;
                } elseif (isset($this->contexts[$oldContext])) {
                    $this->context =& $this->contexts[$oldContext];
                } else {
                    $this->log(modX::LOG_LEVEL_ERROR, 'Could not load context: ' . $contextKey);
                }
            }
        }
        return $initialized;
    }

    /**
     * Initializes the culture settings.
     *
     * @access protected
     */
    protected function _initCulture() {
        $cultureKey = $this->getOption('cultureKey',null,'en');
        if (!empty($_SESSION['cultureKey'])) $cultureKey = $_SESSION['cultureKey'];
        if (!empty($_REQUEST['cultureKey'])) $cultureKey = $_REQUEST['cultureKey'];
        $this->cultureKey = $cultureKey;
        $this->getService('lexicon','modLexicon');
        $this->invokeEvent('OnInitCulture');
    }

    /**
     * Loads the error handler for this instance.
     * @access protected
     */
    protected function _initErrorHandler() {
        if ($this->errorHandler == null || !is_object($this->errorHandler)) {
            if (isset ($this->config['error_handler_class']) && strlen($this->config['error_handler_class']) > 1) {
                if ($ehClass= $this->loadClass($this->config['error_handler_class'], '', false, true)) {
                    if ($this->errorHandler= new $ehClass($this)) {
                        $result= set_error_handler(array ($this->errorHandler, 'handleError'));
                        if ($result === false) {
                            $this->log(modX::LOG_LEVEL_ERROR, 'Could not set error handler.  Make sure your class has a function called handleError(). Result: ' . print_r($result, true));
                        }
                    }
                }
            }
        }
    }

    /**
     * Populates the map of events and registered plugins for each.
     *
     * @access protected
     * @param string $contextKey Context identifier.
     */
    protected function _initEventMap($contextKey) {
        if ($this->eventMap === null) {
            $this->eventMap= $this->getEventMap($contextKey);
        }
    }

    /**
     * Loads the session handler and starts the session.
     * @access protected
     */
    protected function _initSession() {
        $contextKey= $this->context->get('key');
        if ($this->getSessionState() == modX::SESSION_STATE_UNINITIALIZED) {
            $sh= false;
            if ($sessionHandlerClass = $this->getOption('session_handler_class')) {
                if ($shClass= $this->loadClass($sessionHandlerClass, '', false, true)) {
                    if ($sh= new $shClass($this)) {
                        session_set_save_handler(
                            array (& $sh, 'open'),
                            array (& $sh, 'close'),
                            array (& $sh, 'read'),
                            array (& $sh, 'write'),
                            array (& $sh, 'destroy'),
                            array (& $sh, 'gc')
                        );
                    }
                }
            }
            if (!$sh) {
                $sessionSavePath = $this->getOption('session_save_path');
                if ($sessionSavePath && is_writable($sessionSavePath)) {
                    session_save_path($sessionSavePath);
                }
            }
            $cookieDomain= $this->getOption('session_cookie_domain',null,'');
            $cookiePath= $this->getOption('session_cookie_path',null,MODX_BASE_URL);
            if (empty($cookiePath)) $cookiePath = $this->getOption('base_url',null,MODX_BASE_URL);
            $cookieSecure= (boolean) $this->getOption('session_cookie_secure',null,false);
            $cookieLifetime= (integer) $this->getOption('session_cookie_lifetime',null,0);
            $gcMaxlifetime = (integer) $this->getOption('session_gc_maxlifetime',null,$cookieLifetime);
            if ($gcMaxlifetime > 0) {
                ini_set('session.gc_maxlifetime', $gcMaxlifetime);
            }
            $site_sessionname= $this->getOption('session_name', null,'');
            if (!empty($site_sessionname)) session_name($site_sessionname);
            session_set_cookie_params($cookieLifetime, $cookiePath, $cookieDomain, $cookieSecure);
            session_start();
            $this->_sessionState = modX::SESSION_STATE_INITIALIZED;
            $this->getUser($contextKey);
            $cookieExpiration= 0;
            if (isset ($_SESSION['modx.' . $contextKey . '.session.cookie.lifetime'])) {
                $sessionCookieLifetime= (integer) $_SESSION['modx.' . $contextKey . '.session.cookie.lifetime'];
                if ($sessionCookieLifetime !== $cookieLifetime) {
                    if ($sessionCookieLifetime) {
                        $cookieExpiration= time() + $sessionCookieLifetime;
                    }
                    setcookie(session_name(), session_id(), $cookieExpiration, $cookiePath, $cookieDomain, $cookieSecure);
                }
            }
        }
    }

    /**
     * Loads the modX system configuration settings.
     *
     * @access protected
     * @return boolean True if successful.
     */
    protected function _loadConfig() {
        $this->config= $this->_config;

        $this->getCacheManager();
        $config = $this->cacheManager->get('config', array(
            xPDO::OPT_CACHE_KEY => $this->getOption('cache_system_settings_key', null, 'system_settings'),
            xPDO::OPT_CACHE_HANDLER => $this->getOption('cache_system_settings_handler', null, $this->getOption(xPDO::OPT_CACHE_HANDLER)),
            xPDO::OPT_CACHE_FORMAT => (integer) $this->getOption('cache_system_settings_format', null, $this->getOption(xPDO::OPT_CACHE_FORMAT, null, xPDOCacheManager::CACHE_PHP))
        ));
        if (empty($config)) {
            $config = $this->cacheManager->generateConfig();
        }
        if (empty($config)) {
            $config = array();
            if (!$settings= $this->getCollection('modSystemSetting')) {
                return false;
            }
            foreach ($settings as $setting) {
                $config[$setting->get('key')]= $setting->get('value');
            }
        }
        $this->config = array_merge($this->config, $config);
        $this->_systemConfig= $this->config;
        return true;
    }

    /**
     * Provides modX the ability to use modRegister instances as log targets.
     *
     * {@inheritdoc}
     */
    protected function _log($level, $msg, $target= '', $def= '', $file= '', $line= '') {
        if (empty($target)) {
            $target = $this->logTarget;
        }
        $targetOptions = array();
        $targetObj = $target;
        if (is_array($target)) {
            if (isset($target['options'])) $targetOptions = $target['options'];
            $targetObj = isset($target['target']) ? $target['target'] : 'ECHO';
        }
        if (is_object($targetObj) && $targetObj instanceof modRegister) {
            if ($level === modX::LOG_LEVEL_FATAL) {
                if (empty ($file)) $file= (isset ($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : (isset ($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '');
                $this->_logInRegister($targetObj, $level, $msg, $def, $file, $line);
                $this->sendError('fatal');
            }
            if ($this->_debug === true || $level <= $this->logLevel) {
                if (empty ($file)) $file= (isset ($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : (isset ($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '');
                $this->_logInRegister($targetObj, $level, $msg, $def, $file, $line);
            }
        } else {
            if ($level === modX::LOG_LEVEL_FATAL) {
                while (@ob_end_clean()) {}
                if ($targetObj == 'FILE' && $cacheManager= $this->getCacheManager()) {
                    $filename = isset($targetOptions['filename']) ? $targetOptions['filename'] : 'error.log';
                    $filepath = isset($targetOptions['filepath']) ? $targetOptions['filepath'] : $this->getCachePath() . xPDOCacheManager::LOG_DIR;
                    $cacheManager->writeFile($filepath . $filename, '[' . strftime('%Y-%m-%d %H:%M:%S') . '] (' . $this->_getLogLevel($level) . $def . $file . $line . ') ' . $msg . "\n" . ($this->getDebug() === true ? '<pre>' . "\n" . print_r(debug_backtrace(), true) . "\n" . '</pre>' : ''), 'a');
                }
                $this->sendError('fatal');
            }
            parent :: _log($level, $msg, $target, $def, $file, $line);
        }
    }

    /**
     * Provides custom logging functionality for modRegister targets.
     *
     * @access protected
     * @param modRegister $register The modRegister instance to send to
     * @param int $level The level of error or message that occurred
     * @param string $msg The message to send to the register
     * @param string $def The type of error that occurred
     * @param string $file The filename of the file that the message occurs for
     * @param string $line The line number of the file that the message occurs for
     */
    protected function _logInRegister($register, $level, $msg, $def, $file, $line) {
        $timestamp = strftime('%Y-%m-%d %H:%M:%S');
        $messageKey = (string) time();
        $messageKey .= '-' . sprintf("%06d", $this->_logSequence);
        $message = array(
            'timestamp' => $timestamp,
            'level' => $this->_getLogLevel($level),
            'msg' => $msg,
            'def' => $def,
            'file' => $file,
            'line' => $line
        );
        $options = array();
        if ($level === xPDO::LOG_LEVEL_FATAL) {
            $options['kill'] = true;
        }
        $register->send('', array($messageKey => $message), $options);
        $this->_logSequence++;
    }

    /**
     * Executed after the response is sent and execution is completed.
     *
     * @access protected
     */
    public function _postProcess() {
        if ($this->resourceGenerated && $this->getOption('cache_resource', null, true)) {
            if (is_object($this->resource) && $this->resource instanceof modResource && $this->resource->get('id') && $this->resource->get('cacheable')) {
                $this->invokeEvent('OnBeforeSaveWebPageCache');
                $this->cacheManager->generateResource($this->resource);
            }
        }
        $this->invokeEvent('OnWebPageComplete');
    }
}

/**
 * Represents a modEvent when invoking events.
 * @package modx
 */
class modSystemEvent {
    /**
     * @var const For new creations of objects in model events
     */
    const MODE_NEW = 'new';
    /**
     * @var const For updating objects in model events
     */
    const MODE_UPD = 'upd';
    /**
     * The name of the Event
     * @var string $name
     */
    public $name = '';
    /**
     * The name of the active plugin being invoked
     * @var string $activePlugin
     * @deprecated
     */
    public $activePlugin = '';
    /**
     * @var string The name of the active property set for the invoked Event
     * @deprecated
     */
    public $propertySet = '';
    /**
     * Whether or not to allow further execution of Plugins for this event
     * @var boolean $_propagate
     */
    protected $_propagate = true;
    /**
     * The current output for the event
     * @var string $_output
     */
    public $_output;
    /**
     * Whether or not this event has been activated
     * @var boolean
     */
    public $activated;
    /**
     * Any returned values for this event
     * @var mixed $returnedValues
     */
    public $returnedValues;
    /**
     * Any params passed to this event
     * @var array $params
     */
    public $params;

    /**
     * Display a message to the user during the event.
     *
     * @todo Remove this; the centralized modRegistry will handle configurable
     * logging of any kind of message or data to any repository or output
     * context.  Use {@link modX::_log()} in the meantime.
     * @param string $msg The message to display.
     */
    public function alert($msg) {}

    /**
     * Render output from the event.
     * @param string $output The output to render.
     */
    public function output($output) {
        $this->_output .= $output;
    }

    /**
     * Stop further execution of plugins for this event.
     */
    public function stopPropagation() {
        $this->_propagate = false;
    }

    /**
     * Returns whether the event will propagate or not.
     *
     * @access public
     * @return boolean
     */
    public function isPropagatable() {
        return $this->_propagate;
    }

    /**
     * Reset the event instance for reuse.
     */
    public function resetEventObject(){
        $this->returnedValues = null;
        $this->name = '';
        $this->_output = '';
        $this->_propagate = true;
        $this->activated = false;
    }
}
