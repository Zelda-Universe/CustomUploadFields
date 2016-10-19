<?php
/**
 * CustomUploadFields Extension
 *
 * Adds additional fields to the Upload form
 * 
 * In order to add new fields, edit efCustomUploadFieldsForm
 * In order to determine how they're processed, edit efCustomUploadFieldsProcess

 * Because there is no way to properly edit $pageText,
 * you must edit includes/specials/SpecialUpload.php to properly install this extension
 * REMEMBER TO BACKUP!
 * In the getInitialPageText function, edit it so it says the following:

			if ( $license != '' ) {
				$pageText = $license;

 * Will try to find a way to do it without having to edit the Special page
 */

if ( !defined( 'MEDIAWIKI' ) ) die( 'Invalid entry point.' );

$wgExtensionCredits[ 'specialpage' ][] = array(
	'path'          => __FILE__,
	'name'          => 'CustomUploadFields',
	'author'        => '[http://www.zeldawiki.org Abdullah Abduldayem]',
	'decriptionmsg' => 'Adds custom fields to the [[Special:Upload|upload form]]'
);

// Register internationalisation file
$wgExtensionMessagesFiles[ 'CustomUploadFields' ] = dirname( __FILE__ ) . '/CustomUploadFields.i18n.php';

// Register required hooks
$wgHooks[ 'UploadForm:initial'          ][] = 'efCustomUploadFieldsForm';
$wgHooks[ 'UploadForm:BeforeProcessing' ][] = 'efCustomUploadFieldsProcess';
$wgHooks[ 'BeforePageDisplay'           ][] = 'onBeforePageDisplay';

//Override the default Special:Upload with our own modified class
global $wgAutoloadLocalClasses;
$wgAutoloadLocalClasses['SpecialUpload'] = dirname( __FILE__ ) . '/SpecialUploadModified.php';

function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
	// Hide the old Licenses Field.
	// $out->addInlineScript would not work, so we're adding the style via HTML
	$out->addHTML('
		<style>
		.mw-htmlform-field-Licenses{display:none;}
		</style>');
	
	return true;
}

/**
 * efCustomUploadFieldsForm
 * 
 * Entries are in the form:
 * createFormField ( $uploadFormObj, Type, Name);

 * Type: Either 'dropdown' or 'input'
 * Name: The name of the new field

 * ==Dropdown menus==
 *   Options are stored in Mediawiki:Name
 *   Field label is stored in Mediawiki:Name-label
 *   Default value is stored in Mediawiki:Name-none
 *
 *   When processing, getText from 'wp'+name (All lowercase)
 */
function efCustomUploadFieldsForm( $uploadFormObj ) {
	// Bring the licenses above the Edittools. Original hidden with CSS. No Ajax previews

	if ($uploadFormObj->mForReUpload == false) {
		createFormField ( $uploadFormObj, 'dropdown', 'licenses');
		createFormField ( $uploadFormObj, 'dropdown', 'filetype');
		createFormField ( $uploadFormObj, 'dropdown', 'games');
		createFormField ( $uploadFormObj, 'input', 'source');
	}

	return true;
}




/**
 * efCustomUploadFieldsProcess
 * 
 * First, getText from your newly created element(s)
 * In order to append your changes to the sent data, use the following:
 * $uploadFormObj->mComment .= '[...]';

 * To replace the sent data entirely, use:
 * $uploadFormObj->mComment = '[...]';
 */
function efCustomUploadFieldsProcess( $uploadFormObj ) {
	global $wgRequest;

	$summary = $wgRequest->getText( 'wpUploadDescription' );
	//$license = $wgRequest->getText( 'wpLicense' );

	$source = $wgRequest->getText( 'wpsource' );
	$game = $wgRequest->getText( 'wpgames' );
	$type = $wgRequest->getText( 'wpfiletype' );
	$license = $wgRequest->getText( 'wplicenses' );

	$uploadFormObj->mLicense =
		"{{FileInfo\n" .
		'|summary= ' . $summary . "\n".
		'|source= '  . $source  . "\n".
		'|type= '    . $type    . "\n".
		'|game= '    . $game    . "\n".
		'|licensing= ' . $license . "\n".
		'}}';

	return true;
}



//-----------------------------------------------------------------------



function createFormField ( $uploadFormObj, $type, $msg ) {
	$field =
		Xml::openElement( 'tr' ) .
		Xml::openElement( 'td', array( 'align' => 'right' ) ) .
		Xml::label( wfMsg( $msg . '-label' ), 'wp'. $msg ) .
		Xml::closeElement( 'td' ) .
		Xml::openElement( 'td' );

		switch ($type) {
		case 'dropdown':
			$field .= Xml::openElement( 'select', array( 'name' => 'wp'. $msg , 'id' => 'wp'. $msg ) );

			//Default Message
			$field .= Xml::option( wfMsg( $msg . '-none' ), '' );

			//Custom Options
			$field .= getDropdownOptions( $msg );

			$field .= Xml::closeElement( 'select' );
			break;

		case 'input':
			$field .= Xml::input( 'wp'. $msg , 60);
			break;
		}

	$field .= 	Xml::closeElement( 'td' ) .
				Xml::closeElement( 'tr' );

	$old = $uploadFormObj->uploadFormTextAfterSummary;
	$uploadFormObj->uploadFormTextAfterSummary = $field . $old;
}



function getDropdownOptions( $str ) {
	$lines = explode( "\n", msg ($str) );

	$return = "";

	foreach ( $lines as $line ) {
		if ( strpos( $line, '*' ) !== 0 )
			continue;
		else {
			list( $level, $line ) = trimStars( $line );

			if ( strpos( $line, '|' ) !== false ) {
				list( $val, $line ) = getPipeValue( $line );
				$line = str_repeat( /* &nbsp */ "\xc2\xa0", $level * 2 ) . $line;

				$return .= Xml::option( $line, $val);

			} else {
				$line = str_repeat( /* &nbsp */ "\xc2\xa0", $level * 2 ) . $line;

				$return .= Xml::element( 'option', array('disabled' => 'disabled', 'style' => 'color: GrayText'), $line ) ;
			}
		}
	}
	return $return;
}

function msg( $str ) {
	$out = wfMsg( $str );
	return wfEmptyMsg( $str, $out ) ? $str : $out;
}

function trimStars( $str ) {
	$numStars = strspn( $str, '*' );
	return array( $numStars-1, ltrim( substr( $str, $numStars ), ' ' ) );
}

function getPipeValue( $str ) {
	$pipePos = strpos( $str, '|' );
	$str = ltrim( $str, ' ');

	return array( substr($str, 0, $pipePos), substr($str, $pipePos+1) );
}