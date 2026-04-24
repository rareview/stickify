/**
 * WordPress dependencies
 */
const { render } = wp.element;

/**
 * Internal dependencies
 */
import StickifySettingsApp from './components/StickifySettingsApp';

const mountNode = document.getElementById('stickify-settings-app');

if (mountNode) {
	render(<StickifySettingsApp />, mountNode);
}
