<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once(__DIR__ . "/vendor/autoload.php");

$app = new Silex\Application();
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app['debug'] = true;

// Smart http protocol
$app->post("{repo}/git-upload-pack",							'mlabrum\grack::service_rpc')->value("type", "upload-pack");
$app->post("{repo}/git-receive-pack",							'mlabrum\grack::service_rpc')->value("type", "receive-pack");
$app->get("{repo}/info/refs",									'mlabrum\grack::get_info_refs');

// Handle the index page
$app->get("/", function(Request $request, \Silex\Application $app){
	$links = [];
	foreach($app['config']['Repositories'] as $name => $repo){
		$links[] = "<a href='" . $app['url_generator']->generate('repo.base', ['repo' => $name]) . "'>$name</a>";
	}
	
	return new Response(implode("<br/>", $links));
});

// Handle the repo urls
$app->get("{repo}/", function(Request $request){
	return new Response("Push code to me");
})->bind("repo.base");

// Load the configuration
$app['config'] = $app->share(function(){
	return Symfony\Component\Yaml\Yaml::parse(__DIR__ . "/config.yml");
});

// Run the application
$app->run();