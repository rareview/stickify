/**
 * WordPress dependencies
 */
const apiFetch = wp.apiFetch;
const { compose } = wp.compose;
const { withSelect, withDispatch, useSelect } = wp.data;
const { PluginDocumentSettingPanel } = wp.editPost;
const { useEffect, useState } = wp.element;
const { __ } = wp.i18n;
const STICKY_META_KEY = '_rv_sticky_post_types';
const STICKY_UNTIL_META_KEY = '_rv_sticky_post_types_until';
const STICKY_START_META_KEY = '_rv_sticky_post_types_start';

/**
 * Internal dependencies
 */
import MetaToggleControlInput from './MetaToggleControlInput';
import MetaDateControlInput from './MetaDateControlInput';

const StickyPostTypesSidebar = ({ postType, postMeta, setPostMeta }) => {
	const [isLoading, setIsLoading] = useState(true);
	const [postTypes, setPostTypes] = useState([]);

	/**
	 * Fetch events from the REST API.
	 */
	useEffect(() => {
		const fetchStickyPostTypes = async () => {
			try {
				const response = await apiFetch({
					path: '/sticky-post-types/v1/post-types',
				});

				setPostTypes(response);
			} catch (error) {
				setPostTypes([]);
			} finally {
				setIsLoading(false);
			}
		};

		fetchStickyPostTypes();
	}, [isLoading]);

	const supportsCustomFields = useSelect((select) => {
		const settings = select('core').getPostType(postType);
		return settings.supports['custom-fields'] || false;
	}, []);

	if (
		!isLoading &&
		postTypes.length > 0 &&
		postTypes.includes(postType) &&
		supportsCustomFields
	) {
		return (
			<PluginDocumentSettingPanel
				title={__('Sticky Post Types Settings', 'sticky-post-types')}
				icon="edit"
				initialOpen="true"
			>
				<MetaToggleControlInput
					metaKey={STICKY_META_KEY}
					label={__(
						'Move this post to the front of the archive?',
						'sticky-post-types'
					)}
					postMeta={postMeta}
					setPostMeta={setPostMeta}
				/>
				<MetaDateControlInput
					metaKey={STICKY_START_META_KEY}
					label={__(
						'From when should this post be sticky? (optional)',
						'sticky-post-types'
					)}
					postMeta={postMeta}
					setPostMeta={setPostMeta}
				/>
				<MetaDateControlInput
					metaKey={STICKY_UNTIL_META_KEY}
					label={__(
						'Until when should this post be sticky? (optional)',
						'sticky-post-types'
					)}
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
])(StickyPostTypesSidebar);
