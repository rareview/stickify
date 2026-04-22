/**
 * WordPress dependencies
 */
const { TextControl, PanelRow } = wp.components;

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
const MetaDateControlInput = ({ metaKey, label, postMeta, setPostMeta }) => {
	return (
		Object.prototype.hasOwnProperty.call(postMeta, metaKey) && (
			<PanelRow>
				<TextControl
					type="datetime-local"
					value={
						postMeta[metaKey]
							? new Date(postMeta[metaKey] * 1000)
								.toLocaleString('sv-SE')
								.replace(' ', 'T')
								.slice(0, 16)
							: ''
					}
					onChange={(value) => {
						if (!value) {
							setPostMeta({
								[metaKey]: 0,
							});
							return;
						}

						const timestamp = Math.floor(
							new Date(value).getTime() / 1000
						);

						setPostMeta({
							[metaKey]: timestamp,
						});
					}}
					label={label}
				/>
			</PanelRow>
		)
	);
};

export default MetaDateControlInput;
