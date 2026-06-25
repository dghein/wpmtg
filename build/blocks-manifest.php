<?php
// This file is generated. Do not modify it manually.
return array(
	'featured-card' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'create-block/featured-card',
		'version' => '0.1.0',
		'title' => 'Featured Card',
		'category' => 'widgets',
		'icon' => 'smiley',
		'description' => 'Example block scaffolded with Create Block tool.',
		'example' => array(
			
		),
		'supports' => array(
			'html' => false
		),
		'textdomain' => 'featured-card',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js'
	),
	'pack-opener' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'wpmtg/pack-opener',
		'version' => '0.2.0',
		'title' => 'Pack Opener',
		'category' => 'widgets',
		'icon' => 'images-alt2',
		'description' => 'Let visitors simulate opening a booster pack from a selected Magic card set.',
		'example' => array(
			
		),
		'attributes' => array(
			'setSlug' => array(
				'type' => 'string',
				'default' => ''
			),
			'setName' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'supports' => array(
			'html' => false
		),
		'textdomain' => 'wpmtg',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php'
	)
);
