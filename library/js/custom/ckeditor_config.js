/**
 * Custom configuration for ckeditor
 *
 * @package OpenEMR
 * @link    http://www.open-emr.org
 * @author  Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2017 Brady Miller <brady.g.miller@gmail.com>
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 *
 */

CKEDITOR.editorConfig = function( config ) {
	config.removeButtons = 'Underline,Subscript,Superscript';
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
};