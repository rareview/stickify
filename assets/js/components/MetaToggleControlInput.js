/**
 * WordPress dependencies
 */
const { ToggleControl, PanelRow } = wp.components;

/**
 * Create the input for managing a toggle control
 *
 * @param {object}   Props             React props.
 * @param {string}   Props.metaKey     The meta key.
 * @param {string}   Props.label       The control label.
 * @param {object}   Props.postMeta    The Post meta.
 * @param {Function} Props.setPostMeta The function to set post meta.
 *
 * @returns {false|object} The component or false.
 */
const MetaToggleControlInput = ({ metaKey, label, postMeta, setPostMeta }) => {
	return (
		Object.prototype.hasOwnProperty.call(postMeta, metaKey) && (
			<PanelRow>
				<ToggleControl
					checked={postMeta[metaKey] ? Boolean(postMeta[metaKey]) : false}
					onChange={(value) =>
						setPostMeta({
							[metaKey]: value,
						})
					}
					label={label}
				/>
			</PanelRow>
		)
	);
};

export default MetaToggleControlInput;
