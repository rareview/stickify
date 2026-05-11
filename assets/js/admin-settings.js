/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import RareviewScheduledStickyPostsSettingsApp from './components/RareviewScheduledStickyPostsSettingsApp';

const mountNode = document.getElementById(
	'rareview-scheduled-sticky-posts-settings-app'
);

if ( mountNode ) {
	createRoot( mountNode ).render( <RareviewScheduledStickyPostsSettingsApp /> );
}
