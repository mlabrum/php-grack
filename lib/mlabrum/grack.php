<?php

namespace mlabrum;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Symfony\Component\Process\Process;

class Grack{
	
	/**
	 * Fetches the Info refs
	 * @link https://github.com/schacon/grack/blob/master/lib/git_http.rb#L77
	 * @param \Silex\Application $app
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function get_info_refs(Application $app, Request $request){
		$service_name = str_replace("git-", "", $request->query->get("service", "none"));
		
		$cmd = $this->git_command($service_name . " --stateless-rpc --advertise-refs .", $this->get_active_config($request, $app['config']));
		$cmd->run();
		$refs = $cmd->getOutput();
		
		$response = new Response();
		$response->headers->set("Content-Type", sprintf("application/x-git-%s-advertisement", $service_name));
		
		// Disable http cache
		// TODO
		
		$content  = $this->pkt_write(sprintf("# service=git-%s\n", $service_name));
		$content .= $this->pkt_flush();
		$content .= $refs;
		$response->setContent($content);
		
		return $response;
	}
	
	public function service_rpc(Application $app, Request $request){
		$service_name	= $request->attributes->get('type');
		$config			= $this->get_active_config($request, $app['config']);
				 
		$response = new Response();
		$response->headers->set('Content-Type', sprintf('application/x-git-%s-result', $service_name));
		$response->headers->set("Connection", "Close");
		
		$old_path = getcwd();
		chdir($config['path']);
		
		$cmd = $this->git_command($service_name . " --stateless-rpc " . $config['path'], $config)->getCommandLine();
		
		// Symfony is freezing so implement the process ourselfs
		
		$descriptorspec = array(
			0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
			1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
			2 => array("pipe", "w") // stderr is a file to write to
		 );
		
		// Set the min environment variables
		$env = Array(
			"PATH"	=> getenv('PATH'),
			"TMP"	=> getenv("temp"),
			"PWD"	=> getcwd(),
		);
		
		$process = proc_open($cmd, $descriptorspec, $pipes, $config['path'], $env);
		
		if(is_resource($process)){
			fwrite($pipes[0], $request->getContent());
			$response->setContent(stream_get_contents($pipes[1]));
			fclose($pipes[1]);
		}else{
			throw new \Exception("Unable to open process");
		}
		
		proc_close($process);
		
		chdir($old_path);
		
		return $response;
	}
	
	/**
	 * Executes a git command
	 * @param String $cmd
	 * @param Array $config
	 * @return Process
	 */
	public function git_command($cmd, $config){
		$git_exe	= $config['git-path'];
		$repo		= $config['path'];
		
		$process = new Process($git_exe . " " . $cmd);
		$process->setWorkingDirectory($repo);
		
		return $process;
	}
	
	/**
	 * Returns the flush packet
	 * @return string
	 */
	public function pkt_flush(){
		return '0000';
	}
	
	/**
	 * Encodes a packet
	 * @param type $str
	 * @return type
	 */
	public function pkt_write($str){
		return str_pad(base_convert(strlen($str) + 4, 10, 16), 4, "0", STR_PAD_LEFT) . $str;
	}
	
	/**
	 * Fetches the active config for the current repository
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param Array $config
	 * @return Array
	 */
	public function get_active_config(Request $request, $config){
		return $config['Core'] + $config['Repositories'][$request->attributes->get('repo')];
	}
}