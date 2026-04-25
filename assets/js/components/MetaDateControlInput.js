/**
 * WordPress dependencies
 */
import { TextControl, PanelRow } from '@wordpress/components';

/**
 * Create the input for managing a toggle control
 *
 * @param {Object}   Props             React props.
 * @param {string}   Props.metaKey     The meta key.
 * @param {string}   Props.label       The control label.
 * @param {Object}   Props.postMeta    The Post meta.
 * @param {Function} Props.setPostMeta The function to set post meta.
 *
 * @return {false|object} The component or false.
 */
const MetaDateControlInput = ( { metaKey, label, postMeta, setPostMeta } ) => {
	/* eslint-disable prettier/prettier */
	const dateValue = postMeta[ metaKey ]
		? new Date( postMeta[ metaKey ] * 1000 )
			.toLocaleString( 'sv-SE' )
			.replace( ' ', 'T' )
			.slice( 0, 16 )
		: '';
	/* eslint-enable prettier/prettier */

	return (
		Object.prototype.hasOwnProperty.call( postMeta, metaKey ) && (
			<PanelRow>
				<TextControl
					type="datetime-local"
					value={ dateValue }
					onChange={ ( value ) => {
						if ( ! value ) {
							setPostMeta( {
								[ metaKey ]: 0,
							} );
							return;
						}

						const timestamp = Math.floor(
							new Date( value ).getTime() / 1000
						);

						setPostMeta( {
							[ metaKey ]: timestamp,
						} );
					} }
					label={ label }
				/>
			</PanelRow>
		)
	);
};

export default MetaDateControlInput;
