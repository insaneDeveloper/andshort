<?php

require_once('Abstract_Service.php');

/**
 * The ScriptingController class which provides dynamic server-side loading for scripts
 * @class ScriptingController
 * @namespace PHP
 */
class ScriptingController extends Abstract_Service {
    /*
    * @property request
    * @type Control_Request
    * @default null
    */
    public $request;
    /*
    * @property params
    * @type StdClass
    * @default null
    */
    public $params;
    /*
    * @property params
    * @type Bool
    * @default true
    */
    public $useDefaultService=true;
    /*
    * @property renderView
    * @type Bool
    * @default false
    */
    public $renderView=false;
    /*
    * @property __name
    * @type Bool
    */
    protected $__name = 'scripting';

    /*
    * @property cacheControl
    * @type Instropy_Storage_Memcache
    * @default null
    */
    private $cacheControl;

    const YUI_VERSION='2.7.0';
    const EXPIRES='60'; //seconds
	const PACKER = 'PACKER';
    const YUI ='YUI';
    const SHRINKSAFE='SHRINKSAFE';
	const GCLOSURE='GCLOSURE';

    public static $FILETYPE=array(
    'js' => 'JSCRIPT'
    ,'css' => 'CSS'
    );
    /**
     *
     * @var <array>
     */
    private static $COMPRESSORS=array(
    self::PACKER=>'shrinkPack'
    ,self::YUI=>'yuiCompress'
    ,self::SHRINKSAFE=>'shrinkSafeCompress'
	,self::GCLOSURE=>'gClosure'
    );

    /**
     *
     */
    public function __construct() {
        Depends("Instropy::Storage::Memcache");
		Depends("Instropy::Storage::Session");
        $this->cacheControl = new Instropy_Storage_Session();
        parent::__construct();
    }
    /**
     *
     * @param <type> $key
     * @return <type>
     */
    private function onCache($key,$callback=NULL) {
        
		//die("onCache ".$key." , ".$callback);
		//$cache = $this->cacheControl->get($key);
		$cache = NULL;
        
		if(!$cache && $callback!=NULL):
			//echo "!cached";
            $return = call_user_func(array($this,$callback),$key);
			//$this->cacheControl->set($key,$return);
			return $return;
        else:
			//echo "cached";
            return $cache;
    	endif;
    }
    /**
     *The defaultAction wich is called at /$serviceName/
     */
    public function defaultAction() {
        $args = $this->args;

        if($file = $this->requestedFile()):
            $template = new View_Template($file);

            foreach($args as $c=>$v):
                $template->AssignValue($c,$v);
            endforeach;

            echo $template->Flush(true);

    endif;
    }
    /**
     *
     * @param <type> $content
     * @param <type> $compressor
     * @return <type>
     */
    public function minifyJsContent($content,$compressor) {

    //echo $compressor;
		$content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
        $tmpfile = tempnam("/tmp","jsC");
        $method = self::$COMPRESSORS[$compressor];
        file_put_contents($tmpfile,$content);
        $compress_data = call_user_func(array($this,$method),$tmpfile);

        //echo "<br/> method : $method";

        return $compress_data;
    }
    /**
     * minify the output of javascritp and css files
     * @param <type> $f
     * @param <type> $type
     * @param <type> $compressor
     * @return <type>
     */
    public function minifyScript($f,$type='JSCRIPT',$compressor='') {
        $filename = ROOT.DIRECTORY_SEPARATOR.$f;

        //echo $compressor;

        if($type=='JSCRIPT'):
            header("Content-type: text/javascript");
            
			if(strpos($compressor,'|')!==FALSE) {
				
				if(!$this->onCache($filename)) {
	
					$contents = file_get_contents($filename);
					$compressors = explode("|",$compressor);
	
					while($co = array_pop($compressors)):
						$content = $this->minifyJsContent($contents,$co);
					endwhile;
	
					$this->cacheControl->set($filename,$content);
				}else {
	
					$content = $this->onCache($filename);
				}
	
				return $content;


            }else {
				
				header(sprintf("Content-Generator: andshort :: compressor :: %s",$compressor));				
                switch($compressor) {
                    case self::PACKER:
                        $return  = $this->onCache($filename,'shrinkPack');
                        break;
                    case self::YUI:
                        $return  =  $this->onCache($filename,'yuiCompress');
                        break;
					case self::GCLOSURE:
						$return  =  $this->onCache($filename,'gClosure');
						break;
                    case self::SHRINKSAFE:
                    default:
                        $return  =  $this->onCache($filename,'shrinkSafeCompress');
                        break;
                }
				return $return;
            }
        elseif($type=='CSS'):
        	header("Content-type: text/css");
            $script = $this->compress_css(file_get_contents($filename));
            return $script;
    endif;
    }

	private function remove_comments($buffer){
		/* remove comments */
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
		
		return $buffer;
	}
    /**
     *
     * @param <type> $buffer
     * @return <type>
     */
    private function compress_css($buffer) {
		/* remove comments */
        $buffer = $this->remove_comments($buffer);
		/* remove tabs, spaces, newlines, etc. */
        $buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);

        return $buffer;
    }

    /**
     *
     * @return <string>
     */
    private function requestedFile() {

        $params = $this->request->getParams();
        $args = $this->args;

        if(isset($args['file'])):
            $file = str_replace("|","/",urldecode($args['file']));
            return $file;
        endif;

        return NULL;
    }
    /**
     *
     * @param <type> $filename
     * @param <type> $outputToFile
     */
    protected function shrinkPack($filename) {
        require_once "Packer/class.JavaScriptPacker.php";

        $script = file_get_contents($filename);
        $packer = new JavaScriptPacker($script, 'None');
        $packed = $packer->pack();
        
        //$minified = ROOT.'/public/js/min/'.basename($filename).'.minified.js';
        //file_put_contents($minified, $packed);

        return $packed;
    }
    /**
     *
     * @param <string> $filename
     */
    protected function shrinkSafeCompress($filename) {
        $compresor = sprintf(ROOT.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR.'shrinksafe.jar');
        $command = sprintf("java -jar %s %s",$compresor,$filename);
        exec($command,$output,$return);

        return implode(' ',$output);
    }
    /**
     *
     * @param <type> $filename
     * @return <type>
     */
    protected function yuiCompress($filename) {
        $compresor = sprintf(ROOT.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR.'yuicompressor-2.4.2.jar');
        $command = sprintf("java -jar %s %s --charset utf-8",$compresor,$filename);		
		
        exec($command,$results,$ret);
		
		$content = implode(' ',$results);
		
		//echo print_r($results,true); exit;
		
		return $this->remove_comments($content);

    }
	
	protected function gClosure($filename){
		$script = file_get_contents($filename);
		$ch = curl_init('http://closure-compiler.appspot.com/compile');
		
		$code_url = sprintf('http://%s/public/js/%s','www.zik.bz',basename($filename));
		$CURLOPT_POSTFIELDS		 = 'output_info=compiled_code&output_format=text&compilation_level=ADVANCED_OPTIMIZATIONS&code_url=' . $code_url;
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'output_info=compiled_code&output_format=text&compilation_level=ADVANCED_OPTIMIZATIONS&js_code=' . urlencode(file_get_contents($filename)));
		
		//curl_setopt($ch, CURLOPT_POSTFIELDS, $CURLOPT_POSTFIELDS);
		
		$output = curl_exec($ch);
		curl_close($ch);
		
		return $output;
	}
    /**
     *
     * @return <type>
     */
    protected function yuiCompressDependences() {
    //Create a custom module metadata set
        $customConfig = array(
            "customJS" => array(
            "name" => 'customJS',
            "type" => 'js', // 'js' or 'css'
            // "path" => 'path/to/file3.css', // includes base
            "fullpath" => '/public/js/shorturl.js', // overrides path
            "global" => true, // globals are always loaded
            "requires" => array (0 => 'event', 1 => 'dom', 2 => 'json'),
            // "supersedes" => array (0 => 'something'), // if a rollup
            // "rollup" => 3 // the rollup threshold
            ),
            "customCSS" => array(
            "name" => 'customCSS',
            "type" => 'css', // 'js' or 'css'
            // "path" => 'path/to/file3.css', // includes base
            "fullpath" => '/public/style.css', // overrides path
            "global" => true, // globals are always loaded
            // "supersedes" => array (0 => 'something'), // if a rollup
            // "rollup" => 3 // the rollup threshold
            )
        );
        $loader = new YAHOO_util_Loader(self::YUI_VERSION,'my_custom_config_'.rand(), $customConfig);
        //$loader->load($f);
        $loader->load("customJS", "customCSS");
        return $loader -> script();
    }

}