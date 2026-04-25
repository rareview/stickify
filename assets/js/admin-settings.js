/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import StickifySettingsApp from './components/StickifySettingsApp';

const mountNode = document.getElementById( 'stickify-settings-app' );

if ( mountNode ) {
	createRoot( mountNode ).render( <StickifySettingsApp /> );
}
