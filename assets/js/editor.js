/**
 * Editor scripts.
 *
 * This file is used to add custom scripts to the WordPress block editor.
 *
 * @author Rareview <hello@rareview.com>
 *
 * @package StickyPostTypes
 */

import StickyPostTypesSidebar from './components/StickyPostTypesSidebar';

const { registerPlugin } = wp.plugins;

registerPlugin('rv-sticky-post-types-sidebar', {
	render() {
		return <StickyPostTypesSidebar />;
	},
});
