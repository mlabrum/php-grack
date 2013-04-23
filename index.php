<?php

require_once(__DIR__ . "/vendor/autoload.php");

$app = new Silex\Application();
$app['debug'] = true;

// Smart http protocol
$app->post("{repo}/git-upload-pack",							'mlabrum\grack::service_rpc')->value("type", "upload-pack");
$app->post("{repo}/git-receive-pack",							'mlabrum\grack::service_rpc')->value("type", "receive-pack");
$app->get("{repo}/info/refs",									'mlabrum\grack::get_info_refs');

//$app->get("{repo}/HEAD",										'mlabrum\grack::get_text_file');
//$app->get("{repo}/objects/info/alternates",						'mlabrum\grack::get_text_file');
//$app->get("{repo}/objects/info/http-alternates",				'mlabrum\grack::get_text_file');

//$app->get("{repo}/objects/info/packs",							'mlabrum\grack::get_info_packs');
//$app->get("{repo}/objects/info/{id}",							'mlabrum\grack::get_text_file');
//$app->get("{repo}/objects/{id}/{id2}",							'mlabrum\grack::get_loose_object')->assert('id', '[0-9a-f]{2}')->assert('id2', '[0-9a-f]{38}');
//$app->get("{repo}/objects/pack/pack-{id}\\.pack",				'mlabrum\grack::get_loose_object')->assert('id', '[0-9a-f]{40}');
//$app->get("{repo}/objects/pack/pack-{id}\\.idx",				'mlabrum\grack::get_loose_object')->assert('id', '[0-9a-f]{40}');

// Load the configuration
$app['config'] = $app->share(function(){
	return Symfony\Component\Yaml\Yaml::parse(__DIR__ . "/config.yml");
});

$app->run();