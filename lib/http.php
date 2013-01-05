<?php

class HTTP
{
	const RESPONSE_NOTMODIFIED = 304;
    const RESPONSE_BADREQUEST = 400;
	const RESPONSE_FORBIDDEN = 403;
	const RESPONSE_FILENOTFOUND = 404;
    const RESPONSE_METHODNOTALLOWED = 405;
	const RESPONSE_INTERNALSERVERERROR = 500;

    const REQUEST_POST = 'POST';
	
	
	protected static $responses = 
							array(
									HTTP::RESPONSE_NOTMODIFIED => 'HTTP/1.1 304 Not Modified',
                                    HTTP::RESPONSE_BADREQUEST => 'HTTP/1.1 400 Bad Request',
									HTTP::RESPONSE_FORBIDDEN => 'HTTP/1.1 403 Forbidden',
									HTTP::RESPONSE_FILENOTFOUND => 'HTTP/1.1 404 File Not Found',
                                    HTTP::RESPONSE_METHODNOTALLOWED => 'HTTP/1.1 405 Method Not Allowed',
									HTTP::RESPONSE_INTERNALSERVERERROR => 'HTTP/1.1 500 Internal Server Error'
								 );
								 
								 
	public static function respond($responseCode, $replaceHeaders = true) {

		Logger::info("Responding: ". self::$responses[$responseCode]);
		
		//Add headers - replace if requested
		header(self::$responses[$responseCode], $replaceHeaders, $responseCode);	
	}
	
	public static function redirect($destination) {
	    $config = Configuration::get();
	   
		if (isset($config->ProjectRoot)) {

			$destination = str_replace('§', $config->ProjectRoot, $destination);
		}			
		
		Logger::debug("Redirecting to '". $destination. "'");
		header("Location: ". $destination); exit;
	}


    public static function reload() {
        Logger::debug("Reloading page");
        header("Location: ". self::getRequestURI()); exit;
    }

    public static function reloadWithNewParams($params = null) {

        $urlParts = parse_url(self::getRequestURI());

        $uri = $urlParts['path'];
        $queryString = http_build_query($params);

        Logger::debug("Reloading page with parameters: ". $queryString);

        if (isset($params))
            header("Location: ". $uri. '?'. $queryString);
        else
            header("Location: ". $uri);

        exit;
    }
	
	/**
	 * Populates global variable $request
	 *
	 */
	public static function initRequest() {
		
		global $request;
		$conf = Configuration::get();
		
		//Temporaty solution for rewrite problems occurring when the URL has a trailing slash
		//Added by JOT 2010/08/07		
	
		if ($conf->ServerPlatform == 'windows' && substr($_SERVER['REQUEST_URI'], -1, 1) == '/') { header(rtrim($_SERVER['REQUEST_URI'], '/')); exit; }
		//--------------------------------------------------------------------------------------
		
		$req = isset($_GET['req']) ? $_GET['req'] : '';
		$request = trim($req, "/\t ");
		
		Logger::info("Request from [". self::getClientIP(). "] initialised - $request");			
	}
	
	public static function replaceLinks($out) {	
		
		//Replace links
		$callbackBody = '
		$conf = Configuration::get();
		
		if (isset($conf->ProjectRoot)) {
			$ret = (isset($conf->ProjectRoot)) ? $conf->ProjectRoot : "/";
		}
		
		return $ret;';
	
		return preg_replace_callback("/\§\//", create_function('$match', $callbackBody), $out);
	}
	
	
	public static function send() {
		throw new Exception("HTTP send request: Not Implemented");
	}
	
	
	public static function requireSSL() {
		if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off')
			throw new SecurityException("SSL Connection asserted");
	}
	

	public static function getRequestMethod() {
		return $_SERVER['REQUEST_METHOD'];
	}
	
	public static function getRequestURI() {
		return $_SERVER['REQUEST_URI'];
	}
	
	public static function getRequestVars() {
		return $_REQUEST;
	}
	
	public static function getRawRequest() {
		return file_get_contents("php://input");
	}
	
	public static function getClientIP() {
		return $_SERVER['REMOTE_ADDR'];
	}
	
	
	/**
	 * Raw outputs a file to the output buffer - e.g. a CSS-file, an image etc.
	 * basicly anything else but a page-file
	 *
	 * @param string $file
	 */
	public static function output($file, $forceDownload = false) {
		
			$config = Configuration::get();
			$exactfile = $config->UIRoot. $file;
		
		    if (!is_readable($exactfile) || !is_file($exactfile)) {
		    		return false;;
		    } else {
		    	
		    		//Check for the right permissions before serving request
					Security::query($file, Security::OPERATION_READ);
					
					//Detect file MIME-type if possible
		    		$f = new IOFile($exactfile);
		    		$mime = $f->getMimeType() == null ? "application/octet-stream" : $f->getMimeType();
		    		$length = filesize($exactfile);
		    		
		    		if ($forceDownload) {
						header("Content-Disposition: attachment; filename=". basename($exactfile, true));
					} else {
						switch ($mime) {
							
							case 'text/css':
							case 'application/x-javascript':
								$out = HTTP::replaceLinks(file_get_contents($exactfile));
								$length = strlen($out);
						}
					}

		    		header("Cache-Control: max-age=86400");
		    		header("Expires: ". date('r',strtotime("+1 year", time())));
		    		header("Pragma: ");
		
		    		header("Content-Type: $mime");
		    		header("Content-Length: ". $length);
		    	
		    		Logger::debug("Outputting raw file to browser: $exactfile ($mime)");
					if (isset($out)) 
						echo $out;
					else
						@readfile($exactfile);

                    return true;
		    }	
	}
}
