/**
 * Editor scripts.
 *
 * This file is used to add custom scripts to the WordPress block editor.
 *
 * @author Rareview® <hello@rareview.com>
 *
 * @package
 */

import { registerPlugin } from '@wordpress/plugins';

import RareviewScheduledStickyPostsSidebar from './components/RareviewScheduledStickyPostsSidebar';

registerPlugin( 'rareview-scheduled-sticky-posts-sidebar', {
	render() {
		return <RareviewScheduledStickyPostsSidebar />;
	},
} );
