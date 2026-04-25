/**
 * Editor scripts.
 *
 * This file is used to add custom scripts to the WordPress block editor.
 *
 * @author Rareview <hello@rareview.com>
 *
 * @package
 */

import { registerPlugin } from '@wordpress/plugins';

import StickifySidebar from './components/StickifySidebar';

registerPlugin( 'rv-stickify-sidebar', {
	render() {
		return <StickifySidebar />;
	},
} );
