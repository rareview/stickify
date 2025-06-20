/**
 * WordPress dependencies
 */
const apiFetch = wp.apiFetch;
const { compose } = wp.compose;
const { withSelect, withDispatch, useSelect } = wp.data;
const { PluginDocumentSettingPanel } = wp.editPost;
const { useEffect, useState } = wp.element;
const { __ } = wp.i18n;
const STICKY_POST_META_KEY = '_rv_sticky_cpts';

/**
 * Internal dependencies
 */
import MetaToggleControlInput from './MetaToggleControlInput';

const StickyCPTsSidebar = ({ postType, postMeta, setPostMeta }) => {
	const [isLoading, setIsLoading] = useState(true);
	const [postTypes, setPostTypes] = useState([]);

	/**
	 * Fetch events from the REST API.
	 */
	useEffect(() => {
		const fetchStickyCPTsPostTypes = async () => {
			try {
				const response = await apiFetch({
					path: '/sticky-cpts/v1/post-types',
				});
				
				setPostTypes(response);
			} catch (error) {
				setPostTypes([]);
			} finally {
				setIsLoading(false);
			}
		};

		fetchStickyCPTsPostTypes();
	}, [isLoading]);

	const supportsCustomFields = useSelect(
		(select) => {
			const settings = select('core').getPostType(postType);
			return settings.supports['custom-fields'] || false;
		},
		[]
	);

	if (
		!isLoading &&
		postTypes.length > 0 &&
		postTypes.includes(postType) &&
		supportsCustomFields
	) {
		return (
			<PluginDocumentSettingPanel
				title={__('Sticky CPTs Settings', 'sticky-cpts')}
				icon="edit"
				initialOpen="true"
			>
				<MetaToggleControlInput
					metaKey={STICKY_POST_META_KEY}
					label={__('Move this post to the front of the archive?', 'sticky-cpts')}
					postMeta={postMeta}
					setPostMeta={setPostMeta}
				/>
			</PluginDocumentSettingPanel>
		);
	}
	
	if (
		(!isLoading && postTypes.length === 0) ||
		(!isLoading && postTypes.length > 0 && !postTypes.includes(postType)) ||
		!supportsCustomFields
	) {
		return null;
	}
};

export default compose([
	withSelect((select) => {
		return {
			postMeta: select('core/editor').getEditedPostAttribute('meta'),
			postType: select('core/editor').getCurrentPostType(),
		};
	}),
	withDispatch((dispatch) => {
		return {
			setPostMeta(newMeta) {
				dispatch('core/editor').editPost({ meta: newMeta });
			},
		};
	}),
])(StickyCPTsSidebar);
