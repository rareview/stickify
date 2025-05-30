/**
 * Editor scripts.
 *
 * This file is used to add custom scripts to the WordPress block editor.
 *
 * @author Rareview <hello@rareview.com>
 *
 * @package Sticky CPTs
 */

import StickyCPTsSidebar from './components/StickyCPTsSidebar';

const { registerPlugin } = wp.plugins;

registerPlugin('rv-sticky-cpts-sidebar', {
	render() {
		return <StickyCPTsSidebar />;
	},
});