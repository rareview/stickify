/**
 * WordPress dependencies
 */
/* global stickifyAdmin */
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
const SETTINGS_OPTION_KEY = 'stickify_post_types';
const CACHE_LENGTH_OPTION_KEY = 'stickify_cache_length';
const CLEAR_CACHE_REST_PATH = '/stickify/v1/cache/clear';
const STICKIFY_POSTS_REST_PATH = '/stickify/v1/sticky-posts';
const CLEAR_STICKIFY_POSTS_REST_PATH = '/stickify/v1/sticky-posts/clear';

const StickifySettingsApp = () => {
	const [isLoading, setIsLoading] = useState(true);
	const [isSaving, setIsSaving] = useState(false);
	const [isClearingCache, setIsClearingCache] = useState(false);
	const [isLoadingStickifyPosts, setIsLoadingStickifyPosts] = useState(false);
	const [isClearingStickifyPosts, setIsClearingStickifyPosts] = useState(false);
	const [errorMessage, setErrorMessage] = useState('');
	const [successMessage, setSuccessMessage] = useState('');
	const [selectedPostTypes, setSelectedPostTypes] = useState([]);
	const [cacheLength, setCacheLength] = useState('15');
	const [stickyPostsByType, setStickifyPostsByType] = useState({});
	const [selectedStickyIdsByType, setSelectedStickyIdsByType] = useState({});

	const availablePostTypes = useMemo(() => {
		return Object.entries(
			stickifyAdmin?.availablePostTypes || {}
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
						__('Unable to load settings.', 'stickify')
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

		const fetchStickifyPosts = async () => {
			setIsLoadingStickifyPosts(true);

			try {
				const response = await apiFetch({
					path: STICKIFY_POSTS_REST_PATH,
				});
				setStickifyPostsByType(response || {});
				setSelectedStickyIdsByType({});
			} catch (error) {
				setErrorMessage(
					error?.message ||
						__('Unable to load sticky posts.', 'stickify')
				);
			} finally {
				setIsLoadingStickifyPosts(false);
			}
		};

		fetchStickifyPosts();
	}, [isLoading]);

	const fetchStickifyPosts = async () => {
		setIsLoadingStickifyPosts(true);

		try {
			const response = await apiFetch({ path: STICKIFY_POSTS_REST_PATH });
			setStickifyPostsByType(response || {});
			setSelectedStickyIdsByType({});
		} catch (error) {
			setErrorMessage(
				error?.message ||
					__('Unable to load sticky posts.', 'stickify')
			);
		} finally {
			setIsLoadingStickifyPosts(false);
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
			setSuccessMessage(__('Settings saved.', 'stickify'));
			await fetchStickifyPosts();
		} catch (error) {
			setErrorMessage(
				error?.message ||
					__('Unable to save settings.', 'stickify')
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
				'stickify'
			);

			if (response?.cleared > 0) {
				cacheMessage = __(
					'Sticky caches cleared.',
					'stickify'
				);
			}

			setSuccessMessage(cacheMessage);
		} catch (error) {
			setErrorMessage(
				error?.message ||
					__('Unable to clear sticky caches.', 'stickify')
			);
		} finally {
			setIsClearingCache(false);
		}
	};

	const clearStickifyPosts = async (postIds) => {
		if (!Array.isArray(postIds) || postIds.length === 0) {
			return;
		}

		setIsClearingStickifyPosts(true);
		setErrorMessage('');
		setSuccessMessage('');

		const clearStickifyPayload = {
			// eslint-disable-next-line camelcase
			post_ids: postIds,
		};

		try {
			const response = await apiFetch({
				path: CLEAR_STICKIFY_POSTS_REST_PATH,
				method: 'POST',
				data: clearStickifyPayload,
			});

			let clearMessage = __(
				'No sticky posts were cleared.',
				'stickify'
			);

			if (response?.cleared > 0) {
				clearMessage = __(
					'Sticky behavior removed from selected posts.',
					'stickify'
				);
			}

			setSuccessMessage(clearMessage);
			await fetchStickifyPosts();
		} catch (error) {
			setErrorMessage(
				error?.message ||
					__('Unable to clear sticky behavior.', 'stickify')
			);
		} finally {
			setIsClearingStickifyPosts(false);
		}
	};

	const clearSingleStickifyPost = async (postId) => {
		await clearStickifyPosts([postId]);
	};

	const clearSelectedStickifyPosts = async (postType) => {
		const selectedIds = selectedStickyIdsByType[postType] || [];
		await clearStickifyPosts(selectedIds);
	};

	const formatTimestamp = (timestamp) => {
		if (!timestamp) {
			return __('Not set', 'stickify');
		}

		return new Date(timestamp * 1000).toLocaleString();
	};

	const getStickyDateLabels = (post) => {
		const labels = [];
		const now = Math.floor(Date.now() / 1000);

		if (post?.stickyStart > now) {
			labels.push(__('Sticky (Upcoming)', 'stickify'));
		}

		if (post?.stickyUntil > 0 && post?.stickyUntil <= now) {
			labels.push(__('Sticky (Expired)', 'stickify'));
		}

		return labels;
	};

	const clearSelectedLabel = isClearingStickifyPosts
		? __('Clearing…', 'stickify')
		: __('Clear Selected', 'stickify');

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
						'stickify'
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
						'stickify'
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
					label={__('Cache length in minutes', 'stickify')}
					help={__(
						'How long sticky query results should be cached.',
						'stickify'
					)}
					value={cacheLength}
					onChange={(value) => setCacheLength(value)}
				/>

				<Button
					variant="primary"
					onClick={saveSettings}
					disabled={
						isSaving || isClearingCache || isClearingStickifyPosts
					}
					style={{ marginRight: '1em' }}
				>
					{isSaving
						? __('Saving…', 'stickify')
						: __('Save Changes', 'stickify')}
				</Button>

				<Button
					variant="secondary"
					onClick={clearCache}
					disabled={
						isSaving || isClearingCache || isClearingStickifyPosts
					}
				>
					{isClearingCache
						? __('Clearing cache…', 'stickify')
						: __('Clear Cache Now', 'stickify')}
				</Button>

				<hr style={{ margin: '24px 0' }} />
				<h2>{__('Sticky Posts', 'stickify')}</h2>

				{isLoadingStickifyPosts && <Spinner />}

				{!isLoadingStickifyPosts &&
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
												'stickify'
											)}
										</p>
									)}

									{posts.length > 0 && (
										<div>
											<Button
												variant="secondary"
												onClick={() =>
													clearSelectedStickifyPosts(
														postType
													)
												}
												disabled={
													selectedIds.length === 0 ||
													isClearingStickifyPosts ||
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
																	'stickify'
																)}{' '}
																{formatTimestamp(
																	post.stickyStart
																)}
																{' | '}
																{__(
																	'Until:',
																	'stickify'
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
																	'stickify'
																)}
															</a>
														)}

														<Button
															variant="secondary"
															onClick={() =>
																clearSingleStickifyPost(
																	post.id
																)
															}
															disabled={
																isClearingStickifyPosts ||
																isSaving ||
																isClearingCache
															}
														>
															{__(
																'Clear Sticky',
																'stickify'
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

export default StickifySettingsApp;
