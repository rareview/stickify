/**
 * Editor scripts.
 *
 * This file is used to add custom scripts to the WordPress block editor.
 *
 * @author Rareview <hello@rareview.com>
 *
 * @package Stickify
 */

import StickifySidebar from './components/StickifySidebar';

const { registerPlugin } = wp.plugins;

registerPlugin('rv-stickify-sidebar', {
	render() {
		return <StickifySidebar />;
	},
});
