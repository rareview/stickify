/**
 * WordPress dependencies
 */
const { ToggleControl, PanelRow } = wp.components;

/**
 * Create the input for managing a toggle control
 *
 * @param {object}   Props                   React props.
 * @param {string}   Props.metaKey           The meta key.
 * @param {string}   Props.label             The control label.
 * @param {string}   Props.postType          The current post type.
 * @param {object}   Props.postMeta          The Post meta.
 * @param {Function} Props.updateStickyState The function to set post meta.
 * @param {boolean}  Props.coreSticky        The sticky status determined by core sticky functionality.
 *
 * @returns {false|object} The component or false.
 */
const MetaToggleControlInput = ({
	metaKey,
	label,
	postType,
	postMeta,
	updateStickyState,
	coreSticky,
}) => {
	const isEnabled =
		'post' === postType
			? Boolean(coreSticky) || Boolean(postMeta?.[metaKey])
			: Boolean(postMeta?.[metaKey]);

	return (
		Object.prototype.hasOwnProperty.call(postMeta, metaKey) && (
			<PanelRow>
				<ToggleControl
					checked={isEnabled}
					onChange={(value) =>
						updateStickyState(postType, metaKey, value)
					}
					label={label}
				/>
			</PanelRow>
		)
	);
};

export default MetaToggleControlInput;
