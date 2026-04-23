/**
 * WordPress dependencies
 */
/* global stickyPostTypesAdmin */
const apiFetch = wp.apiFetch;
const {
	Button,
	Card,
	CardBody,
	CheckboxControl,
	Notice,
	Spinner,
	TextControl,
} = wp.components;
const { useEffect, useMemo, useState } = wp.element;
const { __ } = wp.i18n;

const SETTINGS_REST_PATH = '/wp/v2/settings';
const SETTINGS_OPTION_KEY = 'sticky_post_types_post_types';
const CACHE_LENGTH_OPTION_KEY = 'sticky_post_types_cache_length';
const CLEAR_CACHE_REST_PATH = '/sticky-post-types/v1/cache/clear';
const STICKY_POSTS_REST_PATH = '/sticky-post-types/v1/sticky-posts';
const CLEAR_STICKY_POSTS_REST_PATH = '/sticky-post-types/v1/sticky-posts/clear';

const StickyPostTypesSettingsApp = () => {
	const [isLoading, setIsLoading] = useState(true);
	const [isSaving, setIsSaving] = useState(false);
	const [isClearingCache, setIsClearingCache] = useState(false);
	const [isLoadingStickyPosts, setIsLoadingStickyPosts] = useState(false);
	const [isClearingStickyPosts, setIsClearingStickyPosts] = useState(false);
	const [errorMessage, setErrorMessage] = useState('');
	const [successMessage, setSuccessMessage] = useState('');
	const [selectedPostTypes, setSelectedPostTypes] = useState([]);
	const [cacheLength, setCacheLength] = useState('15');
	const [stickyPostsByType, setStickyPostsByType] = useState({});
	const [selectedStickyIdsByType, setSelectedStickyIdsByType] = useState({});

	const availablePostTypes = useMemo(() => {
		return Object.entries(
			stickyPostTypesAdmin?.availablePostTypes || {}
		).map(([slug, label]) => ({
			slug,
			label,
		}));
	}, []);

	useEffect(() => {
		const fetchInitialData = async () => {
			try {
				const response = await apiFetch({ path: SETTINGS_REST_PATH });
				const postTypes = Array.isArray(response?.[SETTINGS_OPTION_KEY])
					? response[SETTINGS_OPTION_KEY]
					: [];
				const resolvedCacheLength = Number.parseInt(
					response?.[CACHE_LENGTH_OPTION_KEY],
					10
				);

				setSelectedPostTypes(postTypes);
				setCacheLength(
					Number.isNaN(resolvedCacheLength)
						? '15'
						: String(Math.max(1, resolvedCacheLength))
				);
			} catch (error) {
				setErrorMessage(
					error?.message ||
						__('Unable to load settings.', 'sticky-post-types')
				);
			} finally {
				setIsLoading(false);
			}
		};

		fetchInitialData();
	}, []);

	useEffect(() => {
		if (isLoading) {
			return;
		}

		const fetchStickyPosts = async () => {
			setIsLoadingStickyPosts(true);

			try {
				const response = await apiFetch({
					path: STICKY_POSTS_REST_PATH,
				});
				setStickyPostsByType(response || {});
				setSelectedStickyIdsByType({});
			} catch (error) {
				setErrorMessage(
					error?.message ||
						__('Unable to load sticky posts.', 'sticky-post-types')
				);
			} finally {
				setIsLoadingStickyPosts(false);
			}
		};

		fetchStickyPosts();
	}, [isLoading]);

	const fetchStickyPosts = async () => {
		setIsLoadingStickyPosts(true);

		try {
			const response = await apiFetch({ path: STICKY_POSTS_REST_PATH });
			setStickyPostsByType(response || {});
			setSelectedStickyIdsByType({});
		} catch (error) {
			setErrorMessage(
				error?.message ||
					__('Unable to load sticky posts.', 'sticky-post-types')
			);
		} finally {
			setIsLoadingStickyPosts(false);
		}
	};

	const togglePostType = (slug, isChecked) => {
		setSelectedPostTypes((current) => {
			if (isChecked) {
				return [...new Set([...current, slug])];
			}

			return current.filter((postType) => postType !== slug);
		});
	};

	const toggleStickySelection = (postType, postId, isChecked) => {
		setSelectedStickyIdsByType((current) => {
			const existing = current[postType] || [];

			if (isChecked) {
				return {
					...current,
					[postType]: [...new Set([...existing, postId])],
				};
			}

			return {
				...current,
				[postType]: existing.filter((id) => id !== postId),
			};
		});
	};

	const saveSettings = async () => {
		setIsSaving(true);
		setErrorMessage('');
		setSuccessMessage('');

		const normalizedCacheLength = Math.max(
			1,
			Number.parseInt(cacheLength, 10) || 15
		);

		try {
			await apiFetch({
				path: SETTINGS_REST_PATH,
				method: 'POST',
				data: {
					[SETTINGS_OPTION_KEY]: selectedPostTypes,
					[CACHE_LENGTH_OPTION_KEY]: normalizedCacheLength,
				},
			});

			setCacheLength(String(normalizedCacheLength));
			setSuccessMessage(__('Settings saved.', 'sticky-post-types'));
			await fetchStickyPosts();
		} catch (error) {
			setErrorMessage(
				error?.message ||
					__('Unable to save settings.', 'sticky-post-types')
			);
		} finally {
			setIsSaving(false);
		}
	};

	const clearCache = async () => {
		setIsClearingCache(true);
		setErrorMessage('');
		setSuccessMessage('');

		try {
			const response = await apiFetch({
				path: CLEAR_CACHE_REST_PATH,
				method: 'POST',
			});

			let cacheMessage = __(
				'No sticky caches needed clearing.',
				'sticky-post-types'
			);

			if (response?.cleared > 0) {
				cacheMessage = __(
					'Sticky caches cleared.',
					'sticky-post-types'
				);
			}

			setSuccessMessage(cacheMessage);
		} catch (error) {
			setErrorMessage(
				error?.message ||
					__('Unable to clear sticky caches.', 'sticky-post-types')
			);
		} finally {
			setIsClearingCache(false);
		}
	};

	const clearStickyPosts = async (postIds) => {
		if (!Array.isArray(postIds) || postIds.length === 0) {
			return;
		}

		setIsClearingStickyPosts(true);
		setErrorMessage('');
		setSuccessMessage('');

		const clearStickyPayload = {
			// eslint-disable-next-line camelcase
			post_ids: postIds,
		};

		try {
			const response = await apiFetch({
				path: CLEAR_STICKY_POSTS_REST_PATH,
				method: 'POST',
				data: clearStickyPayload,
			});

			let clearMessage = __(
				'No sticky posts were cleared.',
				'sticky-post-types'
			);

			if (response?.cleared > 0) {
				clearMessage = __(
					'Sticky behavior removed from selected posts.',
					'sticky-post-types'
				);
			}

			setSuccessMessage(clearMessage);
			await fetchStickyPosts();
		} catch (error) {
			setErrorMessage(
				error?.message ||
					__('Unable to clear sticky behavior.', 'sticky-post-types')
			);
		} finally {
			setIsClearingStickyPosts(false);
		}
	};

	const clearSingleStickyPost = async (postId) => {
		await clearStickyPosts([postId]);
	};

	const clearSelectedStickyPosts = async (postType) => {
		const selectedIds = selectedStickyIdsByType[postType] || [];
		await clearStickyPosts(selectedIds);
	};

	const formatTimestamp = (timestamp) => {
		if (!timestamp) {
			return __('Not set', 'sticky-post-types');
		}

		return new Date(timestamp * 1000).toLocaleString();
	};

	const getStickyDateLabels = (post) => {
		const labels = [];
		const now = Math.floor(Date.now() / 1000);

		if (post?.stickyStart > now) {
			labels.push(__('Sticky (Upcoming)', 'sticky-post-types'));
		}

		if (post?.stickyUntil > 0 && post?.stickyUntil <= now) {
			labels.push(__('Sticky (Expired)', 'sticky-post-types'));
		}

		return labels;
	};

	const clearSelectedLabel = isClearingStickyPosts
		? __('Clearing…', 'sticky-post-types')
		: __('Clear Selected', 'sticky-post-types');

	if (isLoading) {
		return (
			<Card>
				<CardBody>
					<Spinner />
				</CardBody>
			</Card>
		);
	}

	if (availablePostTypes.length === 0) {
		return (
			<Card>
				<CardBody>
					{__(
						'No public custom post types were found on this site.',
						'sticky-post-types'
					)}
				</CardBody>
			</Card>
		);
	}

	return (
		<Card>
			<CardBody>
				{errorMessage && (
					<Notice status="error" isDismissible={false}>
						{errorMessage}
					</Notice>
				)}
				{successMessage && (
					<Notice status="success" isDismissible={false}>
						{successMessage}
					</Notice>
				)}

				<p>
					{__(
						'Enable sticky behavior for these post types:',
						'sticky-post-types'
					)}
				</p>

				{availablePostTypes.map(({ slug, label }) => (
					<CheckboxControl
						key={slug}
						label={label}
						checked={selectedPostTypes.includes(slug)}
						onChange={(isChecked) =>
							togglePostType(slug, isChecked)
						}
					/>
				))}

				<TextControl
					type="number"
					min="1"
					label={__('Cache length in minutes', 'sticky-post-types')}
					help={__(
						'How long sticky query results should be cached.',
						'sticky-post-types'
					)}
					value={cacheLength}
					onChange={(value) => setCacheLength(value)}
				/>

				<Button
					variant="primary"
					onClick={saveSettings}
					disabled={
						isSaving || isClearingCache || isClearingStickyPosts
					}
					style={{ marginRight: '1em' }}
				>
					{isSaving
						? __('Saving…', 'sticky-post-types')
						: __('Save Changes', 'sticky-post-types')}
				</Button>

				<Button
					variant="secondary"
					onClick={clearCache}
					disabled={
						isSaving || isClearingCache || isClearingStickyPosts
					}
				>
					{isClearingCache
						? __('Clearing cache…', 'sticky-post-types')
						: __('Clear Cache Now', 'sticky-post-types')}
				</Button>

				<hr style={{ margin: '24px 0' }} />
				<h2>{__('Sticky Posts', 'sticky-post-types')}</h2>

				{isLoadingStickyPosts && <Spinner />}

				{!isLoadingStickyPosts &&
					Object.entries(stickyPostsByType).map(
						([postType, payload]) => {
							const posts = Array.isArray(payload?.posts)
								? payload.posts
								: [];
							const selectedIds =
								selectedStickyIdsByType[postType] || [];

							return (
								<div
									key={postType}
									style={{ marginBottom: '20px' }}
								>
									<h3>{payload?.label || postType}</h3>

									{posts.length === 0 && (
										<p>
											{__(
												'No posts are currently marked sticky.',
												'sticky-post-types'
											)}
										</p>
									)}

									{posts.length > 0 && (
										<div>
											<Button
												variant="secondary"
												onClick={() =>
													clearSelectedStickyPosts(
														postType
													)
												}
												disabled={
													selectedIds.length === 0 ||
													isClearingStickyPosts ||
													isSaving ||
													isClearingCache
												}
												style={{ marginBottom: '10px' }}
											>
												{clearSelectedLabel}
											</Button>

											{posts.map((post) => {
												const dateLabels =
													getStickyDateLabels(post);

												return (
													<div
														key={post.id}
														style={{
															display: 'flex',
															alignItems:
																'center',
															gap: '10px',
															marginBottom: '8px',
														}}
													>
														<input
															type="checkbox"
															checked={selectedIds.includes(
																post.id
															)}
															onChange={(event) =>
																toggleStickySelection(
																	postType,
																	post.id,
																	event.target
																		.checked
																)
															}
														/>

														<div
															style={{ flex: 1 }}
														>
															<strong>
																{post.title}
															</strong>{' '}
															({post.status})
															{dateLabels.length >
																0 && (
																<span>
																	{' '}
																	[
																	{dateLabels.join(
																		', '
																	)}
																	]
																</span>
															)}
															<div>
																{__(
																	'Start:',
																	'sticky-post-types'
																)}{' '}
																{formatTimestamp(
																	post.stickyStart
																)}
																{' | '}
																{__(
																	'Until:',
																	'sticky-post-types'
																)}{' '}
																{formatTimestamp(
																	post.stickyUntil
																)}
															</div>
														</div>

														{post.editLink && (
															<a
																href={
																	post.editLink
																}
																target="_blank"
																rel="noreferrer"
															>
																{__(
																	'Edit',
																	'sticky-post-types'
																)}
															</a>
														)}

														<Button
															variant="secondary"
															onClick={() =>
																clearSingleStickyPost(
																	post.id
																)
															}
															disabled={
																isClearingStickyPosts ||
																isSaving ||
																isClearingCache
															}
														>
															{__(
																'Clear Sticky',
																'sticky-post-types'
															)}
														</Button>
													</div>
												);
											})}
										</div>
									)}
								</div>
							);
						}
					)}
			</CardBody>
		</Card>
	);
};

export default StickyPostTypesSettingsApp;
