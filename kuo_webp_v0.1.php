<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'kuo_webp';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.1';
$plugin['author'] = 'Pete';
$plugin['author_uri'] = 'https://textpattern.fi/';
$plugin['description'] = 'Create WebP versions of images.';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '9';

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin['type'] = '3';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '0';

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

/** Uncomment me, if you need a textpack
$plugin['textpack'] = <<< EOT
#@admin
#@language en-gb
abc_sample_string => Sample String
abc_one_more => One more
#@language de-de
abc_sample_string => Beispieltext
abc_one_more => Noch einer
EOT;
**/
// End of textpack

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
register_callback(
	'kuo_webp_cta',
	'image_ui',
	'image_edit'
);

register_callback(
	'kuo_webp_launcher',
	'image_ui',
	'image_edit'
);

function kuo_webp_cta() {

	global $img_dir;
	
	$result = array();
	
	$image = safe_row(
		'*',
		'txp_image',
		'id = '.intval($_GET['id']),
		false
	);
	
	if (strcmp($image['ext'],'.webp') != 0) {
		
		$result[] = href(
			'💾 WebP',
			'?event=image&step=image_edit&id='.$_GET['id'].'&action=webp',
			'title="&#10227; Create or recreate WebP files"'
		);
	}
	
	$webp = array(
		'full'=>IMPATH.$image['id'].'.webp',
		'thumbnail'=>IMPATH.$image['id'].'t.webp',
	);
	
	if (file_exists($webp['full'])) {
		
		$result[] = href(
			'🖼 Full size',
			ihu.$img_dir.'/'.$image['id'].'.webp',
			'target="_new" rel="prefetch"'
		);
	}
	
	if (file_exists($webp['thumbnail'])) {
		
		$result[] = href(
			'🖼 Thumbnail',
			ihu.$img_dir.'/'.$image['id'].'t.webp',
			'target="_new"'
		);
	}
	
	echo implode(
		'&nbsp;&nbsp;&nbsp;',
		$result
	);
}

function kuo_webp_launcher() {

	$image = safe_row(
		'*',
		'txp_image',
		'id = '.intval($_GET['id']),
		false
	);

	if (
		(isset($_GET['action']))
		&&
		(strcmp($_GET['action'],'webp') == 0)
		&&
		(!empty($image))
	) {
		
		$source['full'] = IMPATH.$image['id'].$image['ext'];
		$new['full'] = IMPATH.kuo_extension_changer($image['id'].$image['ext']);
		
		$type = kuo_image_type($source['full']);
		$isAcceptedType = kuo_is_accepted_type($type);
		
		# Copying the original image.
		if ($isAcceptedType) {
			
			kuo_webp_processor(
				$type,
				$source['full'],
				$new['full']
			);
		}
		
		if (
			(isset($image['thumbnail']))
			&&
			($image['thumbnail'] == 1)
		) {
			
			$source['thumbnail'] = IMPATH.$image['id'].'t'.$image['ext'];
			$new['thumbnail'] = IMPATH.kuo_extension_changer($image['id'].'t'.$image['ext']);
		}
		
		# Optionally copying the thumbnail.
		if (isset($new['thumbnail'],$source['thumbnail'])) {
			
			$type = kuo_image_type($source['thumbnail']);
			$isAcceptedType = kuo_is_accepted_type($type);
			
			if ($isAcceptedType) {
				
				kuo_webp_processor(
					$type,
					$source['thumbnail'],
					$new['thumbnail']
				);
			}
		}
		
		if (!headers_sent()) {
			
			header('Location: ?event=image&step=image_edit&id='.$_GET['id']);
		}
	}
}

function kuo_image_type(string $file):string {

	$result = '';
	
	$image = getimagesize($file);
	
	if (isset($image['mime'])) {
	
		$result = $image['mime'];
		$result = strtolower($result);
	}

	return $result;
}

function kuo_is_accepted_type(string $type):bool {

	$result = false;
	
	static $accepted = array(
		'image/avif',
		'image/bmp',
		'image/gif',
		'image/jpg',
		'image/jpeg',
		'image/png',
		'image/tga',
	);

	if (in_array($type,$accepted,true)) {
	
		$result = true;
	}

	return $result;
}

function kuo_extension_changer(string $file,string $extension = 'webp'):string {

	$name = pathinfo(
		$file,
		PATHINFO_FILENAME
	);
	
	return $name.'.'.$extension;
}

function kuo_webp_processor(string $type,string $original,string $new):bool {

	$result = false;

	if (
		(strcmp($type,'image/avif') == 0)
		&&
		(function_exists('imagecreatefromavif'))
	) {

		$image = imagecreatefromavif($original);
	}
	elseif (
		(strcmp($type,'image/bmp') == 0)
		&&
		(function_exists('imagecreatefrombmp'))
	) {
	
		$image = imagecreatefrombmp($original);
	}
	elseif (
		(strcmp($type,'image/gif') == 0)
		&&
		(function_exists('imagecreatefromgif'))
	) {
	
		$image = imagecreatefromgif($original);
	}
	elseif (
		(in_array($type,array('image/jpg','image/jpeg'),true))
		&&
		(function_exists('imagecreatefromjpeg'))
	) {
	
		$image = imagecreatefromjpeg($original);
	}
	elseif (
		(strcmp($type,'image/png') == 0)
		&&
		(function_exists('imagecreatefrompng'))
	) {
	
		$image = imagecreatefrompng($original);
	}
	elseif (
		(strcmp($type,'image/tga') == 0)
		&&
		(function_exists('imagecreatefromtga'))
	) {
	
		$image = imagecreatefromtga($original);
	}
	
	if (
		(isset($image))
		&&
		(is_resource($image))
	) {
		
		imagepalettetotruecolor($image);
		
		imagealphablending(
			$image,
			true
		);
		
		imagesavealpha(
			$image,
			true
		);
		
		imagewebp(
			$image,
			$new,
			100
		);
		
		$result = imagedestroy($image);
	}
	
	return $result;
}
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
Original image file and thumbnail of it can be copied in WebP format.
# --- END PLUGIN HELP ---
-->
<?php
}
?>