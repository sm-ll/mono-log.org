<?php
/**
 * Statamic_View
 * Manages display rendering within Statamic
 *
 * @author      Mubashar Iqbal
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @copyright   2013 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */
class Statamic_View extends \Slim\View
{
    protected static $_layout = null;
    protected static $_templates = null;
    protected static $_template_location = null;
    protected static $_control_panel = false;
    protected static $_extra_data = null;
    public static $_dataStore = array();


    /**
     * set_templates
     * Interface for setting templates
     *
     * @param mixed $list Template (or array of templates, in order of preference) to use for page render
     * @return void
     */
    public static function set_templates($list, $location=false)
    {
        self::$_templates = $list;

        self::$_template_location = ($location) ? $location : Path::assemble(BASE_PATH, Config::getTemplatesPath(), 'templates');
    }

    /**
     * set_layout
     * Interface for setting page layout
     *
     * @param string $layout Layout to use for page render
     * @return void
     */
    public static function set_layout($layout = null)
    {
        self::$_layout = Path::assemble(BASE_PATH, Config::getTemplatesPath(), $layout);

        $layout = Parse::frontMatter(File::get(self::$_layout . '.html'));
        self::$_extra_data = $layout['data'];
    }

    /**
     * set_cp_view
     * Let the view controller know we are in the control panel
     *
     * @return void
     */
    public static function set_cp_view()
    {
        self::$_control_panel = true;
    }

    /**
     * render
     * Finds and chooses the correct template, then renders the page
     *
     * @param string $template Template (or array of templates, in order of preference) to render the page with
     * @return string
     */
    public function render($template)
    {        
        $html = '<p style="text-align:center; font-size:28px; font-style:italic; padding-top:50px;">No template found.</p>';

        $list = $template ? $list = array($template) : self::$_templates;
        $template_type = 'html';

        // Allow setting where to get the template from
        if ( ! self::$_template_location) {
            self::$_template_location = Path::assemble(BASE_PATH, Config::getTemplatesPath(), 'templates');
        }

        foreach ($list as $template) {
            $template_path = Path::assemble(self::$_template_location, $template);
            $override_path = Path::assemble(BASE_PATH, Config::getThemesPath(), Config::getTheme(), 'admin', $template);

            if (File::exists($template_path . '.html') || file_exists($template_path . '.php')) {
                // set debug information
                Debug::setValue('template', $template);
                Debug::setvalue('layout', str_replace('layouts/', '', basename(self::$_layout)));
                Debug::setValue('statamic_version', STATAMIC_VERSION);
                Debug::setValue('php_version', phpversion());
                Debug::setValue('theme', array_get($this->data, '_theme', null));
                Debug::setValue('environment', array_get($this->data, 'environment', '(none)'));
                
                $this->data['_debug'] = array(
                    'template'          => Debug::getValue('template'),
                    'layout'            => Debug::getValue('layout'),
                    'version'           => Debug::getValue('statamic_version'),
                    'statamic_version'  => Debug::getValue('statamic_version'),
                    'php_version'       => Debug::getValue('php_version'),
                    'theme'             => Debug::getValue('theme'),
                    'environment'       => Debug::getValue('environment')
                );

                # standard lex-parsed template
                if (File::exists($template_path . '.html')) {
                    $template_type = 'html';

                    $this->appendNewData($this->data);

                    // Fetch template and parse any front matter
                    $template = Parse::frontMatter(File::get($template_path . '.html'));
                    
                    self::$_extra_data = $template['data'] + self::$_extra_data;
                    $this->prependNewData(self::$_extra_data);

                    $html = Parse::template($template['content'], Statamic_View::$_dataStore, array($this, 'callback'));
                    break;

                # lets forge into raw data
                } elseif (File::exists($override_path . '.php') || File::exists($template_path . '.php')) {

                    $template_type = 'php';
                    extract($this->data);
                    ob_start();

                    if (File::exists($override_path . '.php')) {
                        $template_path = $override_path;
                    }

                    require $template_path . ".php";
                    $html = ob_get_clean();
                    break;

                } else {
                    Log::error("Template does not exist: '${template_path}'", 'core');
                }
            }
        }
        
        // mark milestone for debug panel
        Debug::markMilestone('template rendered');

        // get rendered HTML
        $rendered = $this->_render_layout($html, $template_type);
        
        // mark milestone for debug panel
        Debug::markMilestone('layout rendered');

        // store it into the HTML cache if needed
        if (Addon::getAPI('html_caching')->isEnabled()) {
            Addon::getAPI('html_caching')->putCachedPage($rendered);
        }

        // return rendered HTML
        return $rendered;
    }

    /**
     * _render_layout
     * Renders the page
     *
     * @param string $_html HTML of the template to use
     * @param string $template_type Content type of the template
     * @return string
     */
    public function _render_layout($_html, $template_type = 'html')
    {
        if (self::$_layout) {
            $this->data['layout_content'] = $_html;

            if ($template_type != 'html' OR self::$_control_panel) {
                extract($this->data);
                ob_start();
                require self::$_layout . ".php";
                $html = ob_get_clean();

            } else {
                if ( ! File::exists(self::$_layout . ".html")) {
                    Log::fatal("Can't find the specified theme.", 'core', 'template');

                    return '<p style="text-align:center; font-size:28px; font-style:italic; padding-top:50px;">We can\'t find your theme files. Please check your settings.';
                }

                $this->appendNewData($this->data);

                // Fetch layout and parse any front matter
                $layout = Parse::frontMatter(File::get(self::$_layout . '.html'), false);

                $html = Parse::template($layout['content'], Statamic_View::$_dataStore, array($this, 'callback'));
            }
            
            // inject noparse
            $html = Lex\Parser::injectNoparse($html);
        } else {
            $html = $_html;
        }

        // post-render hook
        $html = \Hook::run('_render', 'after', 'replace', $html, $html);
        
        Debug::setValue('memory_used', File::getHumanSize(memory_get_usage(true)));
        return $html;
    }
      
      
    

    /**
     * callback
     * Attempts to load a plugin?
     *
     * @param string $name
     * @param array $attributes
     * @param string $content
     * @param array $context
     * @return string
     * @throws Exception
     */
    public static function callback($name, $attributes, $content, $context=array())
    {
        $now = time();
            
        $output = false;
        $pos    = strpos($name, ':');

        # single function plugins
        if ($pos === false) {
            $plugin = $name;
            $call   = "index";
        } else {
            $plugin = substr($name, 0, $pos);
            $call   = substr($name, $pos + 1);
        }

        // mark start of debug timing
        $hash = Debug::markStart('plugins', $plugin . ':' . $call, $now);

        // if nothing to call, abort
        if (!$call) {
            Debug::markEnd($hash);
            return null;
        }

        try {
            // will throw an exception if resource isn't available
            $plugin_obj = Resource::loadPlugin($plugin);
            
            if (!is_callable(array($plugin_obj, $call))) {
                throw new Exception('Method not callable.');
            }

            $plugin_obj->attributes = $attributes;
            $plugin_obj->content    = $content;
            $plugin_obj->context    = $context;

            Debug::increment('plugins', $plugin);

            $output = call_user_func(array($plugin_obj, $call));

            if (is_array($output)) {
                $output = Parse::template($content, $output);
            }
            
        } catch (\Slim\Exception\Stop $e) {
            // allow plugins to halt the app
            throw $e;
        } catch (\Slim\Exception\Pass $e) {
            // allow plugins to halt the app
            throw $e;
        } catch (ResourceNotFoundException $e) {
            // resource not found, do nothing

        } catch (FatalException $e) {
            throw $e;
            
        } catch (Exception $e) {
            // everything else, do nothing if debug is off
            if (Config::get('debug')) {
                throw $e;
            }            
        }

        Debug::markEnd($hash);

        return $output;
    }


    /**
     * Appends any new data into this view's data store
     * 
     * @param $data  array  Array of data to merge
     * @return void
     */
    function appendNewData($data)
    {
        foreach ($data as $key => $item) {
            if (is_object($item)) {
                unset($data[$key]);
            }
        }

        Statamic_View::$_dataStore = $data + Statamic_View::$_dataStore;
    }

    /**
     * Prepend any new data into this view's data store
     * 
     * @param $data  array  Array of data to merge
     * @return void
     */
    public static function prependNewData($data)
    {
        foreach ($data as $key => $item) {
            if (is_object($item)) {
                unset($data[$key]);
            }
        }

        Statamic_View::$_dataStore = Statamic_View::$_dataStore + $data;
    }
}
