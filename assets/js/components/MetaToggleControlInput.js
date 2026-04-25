/**
 * WordPress dependencies
 */
import { ToggleControl, PanelRow } from '@wordpress/components';

/**
 * Create the input for managing a toggle control
 *
 * @param {Object}   Props                   React props.
 * @param {string}   Props.metaKey           The meta key.
 * @param {string}   Props.label             The control label.
 * @param {string}   Props.postType          The current post type.
 * @param {Object}   Props.postMeta          The Post meta.
 * @param {Function} Props.updateStickyState The function to set post meta.
 * @param {boolean}  Props.coreSticky        The sticky status determined by core sticky functionality.
 *
 * @return {false|object} The component or false.
 */
const MetaToggleControlInput = ( {
	metaKey,
	label,
	postType,
	postMeta,
	updateStickyState,
	coreSticky,
} ) => {
	const isEnabled =
		'post' === postType
			? Boolean( coreSticky ) || Boolean( postMeta?.[ metaKey ] )
			: Boolean( postMeta?.[ metaKey ] );

	return (
		Object.prototype.hasOwnProperty.call( postMeta, metaKey ) && (
			<PanelRow>
				<ToggleControl
					checked={ isEnabled }
					onChange={ ( value ) =>
						updateStickyState( postType, metaKey, value )
					}
					label={ label }
				/>
			</PanelRow>
		)
	);
};

export default MetaToggleControlInput;
