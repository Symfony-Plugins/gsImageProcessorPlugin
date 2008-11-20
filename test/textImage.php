<?php

	require_once('../lib/gdImage.class.php');
	require_once('../lib/gsImageHelper.class.php');
	require_once('../lib/gsTextOverlayProcessor.class.php');

	/*
	@param params
     *      string filename_prepend - text to prepend to filename
     * 		string dirpath				- path/to/image/dir
     *      string font             - path/to/font.ttf
     *      array dimensions        - ('height'=>'', 'width'=>'')
     * 		array dropshadow_color  - text has drop shadow of this color (default 0,0,0)
     * 		array dropshadow_offset - x, y offset of shadow (default -2, 2)
     * 		array text_position		- top left x, y coordinate of text (default (3, fontSize - 5))
     * 		array text_color        - (R,G,B) (default 0,0,0)
     * 		array bg_color			- (R,G,B) (default transparent)
     * 		string text             - the text to render
     * 		int font_size           - the font size (default 12)
     *      int leading				- the leading (default 0)
     * 		boolean transparent 	- transparent bg or not (default 0), true will use the value of
     * 								  bg_color for the matte
     */
	$params = Array(
		'text_overlay' => Array
		(
			'filename_prepend' => 'mytest',
			'dirpath' => './images/',
			'font' => './fonts/nosegrind.ttf',
			'text_color' => Array(255, 200, 2),
			'bg_color' => Array(23,184,255),
			'text' => 'a longer bit of text to test m',
			'font_size' => 16,
			'leading' => 50,
			//'dropshadow' => Array('enabled' => true, 'offset' => Array(2,2), 'color' => Array(25,25,25)),
			'transparency' => Array('enabled' => true, 'type' => '32bit')
		)
	);

	$testImage = gsImageProcessor::processImage(new gdImage(), $params);

	echo '<pre>';
	print_r($testImage);
	echo '</pre>';

?>