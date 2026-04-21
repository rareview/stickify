/**
 * WordPress dependencies
 */
const { render } = wp.element;

/**
 * Internal dependencies
 */
import StickyPostTypesSettingsApp from './components/StickyPostTypesSettingsApp';

const mountNode = document.getElementById('sticky-post-types-settings-app');

if (mountNode) {
	render(<StickyPostTypesSettingsApp />, mountNode);
}
