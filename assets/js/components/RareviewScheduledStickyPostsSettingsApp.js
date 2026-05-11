/**
 * WordPress dependencies
 */
/* global rareviewScheduledStickyPostsAdmin */
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Card,
	CardBody,
	CheckboxControl,
	Notice,
	Spinner,
	TextControl,
} from '@wordpress/components';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const SETTINGS_REST_PATH = '/wp/v2/settings';
const SETTINGS_OPTION_KEY = 'rareview_scheduled_sticky_posts_post_types';
const CACHE_LENGTH_OPTION_KEY = 'rareview_scheduled_sticky_posts_cache_length';
const CLEAR_CACHE_REST_PATH =
	'/rareview-scheduled-sticky-posts/v1/cache/clear';
const RAREVIEW_SCHEDULED_STICKY_POSTS_POSTS_REST_PATH =
	'/rareview-scheduled-sticky-posts/v1/sticky-posts';
const CLEAR_RAREVIEW_SCHEDULED_STICKY_POSTS_POSTS_REST_PATH =
	'/rareview-scheduled-sticky-posts/v1/sticky-posts/clear';

const RareviewScheduledStickyPostsSettingsApp = () => {
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ isClearingCache, setIsClearingCache ] = useState( false );
	const [ isLoadingRareviewScheduledStickyPostsPosts, setIsLoadingRareviewScheduledStickyPostsPosts ] =
		useState( false );
	const [ isClearingRareviewScheduledStickyPostsPosts, setIsClearingRareviewScheduledStickyPostsPosts ] =
		useState( false );
	const [ errorMessage, setErrorMessage ] = useState( '' );
	const [ successMessage, setSuccessMessage ] = useState( '' );
	const [ selectedPostTypes, setSelectedPostTypes ] = useState( [] );
	const [ cacheLength, setCacheLength ] = useState( '15' );
	const [ stickyPostsByType, setRareviewScheduledStickyPostsPostsByType ] = useState( {} );
	const [ selectedStickyIdsByType, setSelectedStickyIdsByType ] = useState(
		{}
	);

	const availablePostTypes = useMemo( () => {
		return Object.entries(
			rareviewScheduledStickyPostsAdmin?.availablePostTypes || {}
		).map( ( [ slug, label ] ) => ( {
			slug,
			label,
		} ) );
	}, [] );

	useEffect( () => {
		const fetchInitialData = async () => {
			try {
				const response = await apiFetch( { path: SETTINGS_REST_PATH } );
				const postTypes = Array.isArray(
					response?.[ SETTINGS_OPTION_KEY ]
				)
					? response[ SETTINGS_OPTION_KEY ]
					: [];
				const resolvedCacheLength = Number.parseInt(
					response?.[ CACHE_LENGTH_OPTION_KEY ],
					10
				);

				setSelectedPostTypes( postTypes );
				setCacheLength(
					Number.isNaN( resolvedCacheLength )
						? '15'
						: String( Math.max( 1, resolvedCacheLength ) )
				);
			} catch ( error ) {
				setErrorMessage(
					error?.message ||
						__( 'Unable to load settings.', 'rareview-scheduled-sticky-posts' )
				);
			} finally {
				setIsLoading( false );
			}
		};

		fetchInitialData();
	}, [] );

	useEffect( () => {
		if ( isLoading ) {
			return;
		}

		const fetchRareviewScheduledStickyPostsPosts = async () => {
			setIsLoadingRareviewScheduledStickyPostsPosts( true );

			try {
				const response = await apiFetch( {
					path: RAREVIEW_SCHEDULED_STICKY_POSTS_POSTS_REST_PATH,
				} );
				setRareviewScheduledStickyPostsPostsByType( response || {} );
				setSelectedStickyIdsByType( {} );
			} catch ( error ) {
				setErrorMessage(
					error?.message ||
						__( 'Unable to load sticky posts.', 'rareview-scheduled-sticky-posts' )
				);
			} finally {
				setIsLoadingRareviewScheduledStickyPostsPosts( false );
			}
		};

		fetchRareviewScheduledStickyPostsPosts();
	}, [ isLoading ] );

	const fetchRareviewScheduledStickyPostsPosts = async () => {
		setIsLoadingRareviewScheduledStickyPostsPosts( true );

		try {
			const response = await apiFetch( {
				path: RAREVIEW_SCHEDULED_STICKY_POSTS_POSTS_REST_PATH,
			} );
			setRareviewScheduledStickyPostsPostsByType( response || {} );
			setSelectedStickyIdsByType( {} );
		} catch ( error ) {
			setErrorMessage(
				error?.message ||
					__( 'Unable to load sticky posts.', 'rareview-scheduled-sticky-posts' )
			);
		} finally {
			setIsLoadingRareviewScheduledStickyPostsPosts( false );
		}
	};

	const togglePostType = ( slug, isChecked ) => {
		setSelectedPostTypes( ( current ) => {
			if ( isChecked ) {
				return [ ...new Set( [ ...current, slug ] ) ];
			}

			return current.filter( ( postType ) => postType !== slug );
		} );
	};

	const toggleStickySelection = ( postType, postId, isChecked ) => {
		setSelectedStickyIdsByType( ( current ) => {
			const existing = current[ postType ] || [];

			if ( isChecked ) {
				return {
					...current,
					[ postType ]: [ ...new Set( [ ...existing, postId ] ) ],
				};
			}

			return {
				...current,
				[ postType ]: existing.filter( ( id ) => id !== postId ),
			};
		} );
	};

	const saveSettings = async () => {
		setIsSaving( true );
		setErrorMessage( '' );
		setSuccessMessage( '' );

		const normalizedCacheLength = Math.max(
			1,
			Number.parseInt( cacheLength, 10 ) || 15
		);

		try {
			await apiFetch( {
				path: SETTINGS_REST_PATH,
				method: 'POST',
				data: {
					[ SETTINGS_OPTION_KEY ]: selectedPostTypes,
					[ CACHE_LENGTH_OPTION_KEY ]: normalizedCacheLength,
				},
			} );

			setCacheLength( String( normalizedCacheLength ) );
			setSuccessMessage( __( 'Settings saved.', 'rareview-scheduled-sticky-posts' ) );
			await fetchRareviewScheduledStickyPostsPosts();
		} catch ( error ) {
			setErrorMessage(
				error?.message || __( 'Unable to save settings.', 'rareview-scheduled-sticky-posts' )
			);
		} finally {
			setIsSaving( false );
		}
	};

	const clearCache = async () => {
		setIsClearingCache( true );
		setErrorMessage( '' );
		setSuccessMessage( '' );

		try {
			const response = await apiFetch( {
				path: CLEAR_CACHE_REST_PATH,
				method: 'POST',
			} );

			let cacheMessage = __(
				'No sticky caches needed clearing.',
				'rareview-scheduled-sticky-posts'
			);

			if ( response?.cleared > 0 ) {
				cacheMessage = __( 'Sticky caches cleared.', 'rareview-scheduled-sticky-posts' );
			}

			setSuccessMessage( cacheMessage );
		} catch ( error ) {
			setErrorMessage(
				error?.message ||
					__( 'Unable to clear sticky caches.', 'rareview-scheduled-sticky-posts' )
			);
		} finally {
			setIsClearingCache( false );
		}
	};

	const clearRareviewScheduledStickyPostsPosts = async ( postIds ) => {
		if ( ! Array.isArray( postIds ) || postIds.length === 0 ) {
			return;
		}

		setIsClearingRareviewScheduledStickyPostsPosts( true );
		setErrorMessage( '' );
		setSuccessMessage( '' );

		const clearRareviewScheduledStickyPostsPayload = {
			// eslint-disable-next-line camelcase
			post_ids: postIds,
		};

		try {
			const response = await apiFetch( {
				path: CLEAR_RAREVIEW_SCHEDULED_STICKY_POSTS_POSTS_REST_PATH,
				method: 'POST',
				data: clearRareviewScheduledStickyPostsPayload,
			} );

			let clearMessage = __(
				'No sticky posts were cleared.',
				'rareview-scheduled-sticky-posts'
			);

			if ( response?.cleared > 0 ) {
				clearMessage = __(
					'Sticky behavior removed from selected posts.',
					'rareview-scheduled-sticky-posts'
				);
			}

			setSuccessMessage( clearMessage );
			await fetchRareviewScheduledStickyPostsPosts();
		} catch ( error ) {
			setErrorMessage(
				error?.message ||
					__( 'Unable to clear sticky behavior.', 'rareview-scheduled-sticky-posts' )
			);
		} finally {
			setIsClearingRareviewScheduledStickyPostsPosts( false );
		}
	};

	const clearSingleRareviewScheduledStickyPostsPost = async ( postId ) => {
		await clearRareviewScheduledStickyPostsPosts( [ postId ] );
	};

	const clearSelectedRareviewScheduledStickyPostsPosts = async ( postType ) => {
		const selectedIds = selectedStickyIdsByType[ postType ] || [];
		await clearRareviewScheduledStickyPostsPosts( selectedIds );
	};

	const formatTimestamp = ( timestamp ) => {
		if ( ! timestamp ) {
			return __( 'Not set', 'rareview-scheduled-sticky-posts' );
		}

		return new Date( timestamp * 1000 ).toLocaleString();
	};

	const getStickyDateLabels = ( post ) => {
		const labels = [];
		const now = Math.floor( Date.now() / 1000 );

		if ( post?.stickyStart > now ) {
			labels.push( __( 'Sticky (Upcoming)', 'rareview-scheduled-sticky-posts' ) );
		}

		if ( post?.stickyUntil > 0 && post?.stickyUntil <= now ) {
			labels.push( __( 'Sticky (Expired)', 'rareview-scheduled-sticky-posts' ) );
		}

		return labels;
	};

	const clearSelectedLabel = isClearingRareviewScheduledStickyPostsPosts
		? __( 'Clearing…', 'rareview-scheduled-sticky-posts' )
		: __( 'Clear Selected', 'rareview-scheduled-sticky-posts' );

	if ( isLoading ) {
		return (
			<Card>
				<CardBody>
					<Spinner />
				</CardBody>
			</Card>
		);
	}

	if ( availablePostTypes.length === 0 ) {
		return (
			<Card>
				<CardBody>
					{ __(
						'No public custom post types were found on this site.',
						'rareview-scheduled-sticky-posts'
					) }
				</CardBody>
			</Card>
		);
	}

	return (
		<Card>
			<CardBody>
				{ errorMessage && (
					<Notice status="error" isDismissible={ false }>
						{ errorMessage }
					</Notice>
				) }
				{ successMessage && (
					<Notice status="success" isDismissible={ false }>
						{ successMessage }
					</Notice>
				) }

				<p>
					{ __(
						'Enable sticky behavior for these post types:',
						'rareview-scheduled-sticky-posts'
					) }
				</p>

				{ availablePostTypes.map( ( { slug, label } ) => (
					<CheckboxControl
						key={ slug }
						label={ label }
						checked={ selectedPostTypes.includes( slug ) }
						onChange={ ( isChecked ) =>
							togglePostType( slug, isChecked )
						}
					/>
				) ) }

				<TextControl
					type="number"
					min="1"
					label={ __( 'Cache length in minutes', 'rareview-scheduled-sticky-posts' ) }
					help={ __(
						'How long sticky query results should be cached.',
						'rareview-scheduled-sticky-posts'
					) }
					value={ cacheLength }
					onChange={ ( value ) => setCacheLength( value ) }
				/>

				<Button
					variant="primary"
					onClick={ saveSettings }
					disabled={
						isSaving || isClearingCache || isClearingRareviewScheduledStickyPostsPosts
					}
					style={ { marginRight: '1em' } }
				>
					{ isSaving
						? __( 'Saving…', 'rareview-scheduled-sticky-posts' )
						: __( 'Save Changes', 'rareview-scheduled-sticky-posts' ) }
				</Button>

				<Button
					variant="secondary"
					onClick={ clearCache }
					disabled={
						isSaving || isClearingCache || isClearingRareviewScheduledStickyPostsPosts
					}
				>
					{ isClearingCache
						? __( 'Clearing cache…', 'rareview-scheduled-sticky-posts' )
						: __( 'Clear Cache Now', 'rareview-scheduled-sticky-posts' ) }
				</Button>

				<hr style={ { margin: '24px 0' } } />
				<h2>{ __( 'Sticky Posts', 'rareview-scheduled-sticky-posts' ) }</h2>

				{ isLoadingRareviewScheduledStickyPostsPosts && <Spinner /> }

				{ ! isLoadingRareviewScheduledStickyPostsPosts &&
					Object.entries( stickyPostsByType ).map(
						( [ postType, payload ] ) => {
							const posts = Array.isArray( payload?.posts )
								? payload.posts
								: [];
							const selectedIds =
								selectedStickyIdsByType[ postType ] || [];

							return (
								<div
									key={ postType }
									style={ { marginBottom: '20px' } }
								>
									<h3>{ payload?.label || postType }</h3>

									{ posts.length === 0 && (
										<p>
											{ __(
												'No posts are currently marked sticky.',
												'rareview-scheduled-sticky-posts'
											) }
										</p>
									) }

									{ posts.length > 0 && (
										<div>
											<Button
												variant="secondary"
												onClick={ () =>
													clearSelectedRareviewScheduledStickyPostsPosts(
														postType
													)
												}
												disabled={
													selectedIds.length === 0 ||
													isClearingRareviewScheduledStickyPostsPosts ||
													isSaving ||
													isClearingCache
												}
												style={ {
													marginBottom: '10px',
												} }
											>
												{ clearSelectedLabel }
											</Button>

											{ posts.map( ( post ) => {
												const dateLabels =
													getStickyDateLabels( post );

												return (
													<div
														key={ post.id }
														style={ {
															display: 'flex',
															alignItems:
																'center',
															gap: '10px',
															marginBottom: '8px',
														} }
													>
														<input
															type="checkbox"
															checked={ selectedIds.includes(
																post.id
															) }
															onChange={ (
																event
															) =>
																toggleStickySelection(
																	postType,
																	post.id,
																	event.target
																		.checked
																)
															}
														/>

														<div
															style={ {
																flex: 1,
															} }
														>
															<strong>
																{ post.title }
															</strong>{ ' ' }
															({ post.status })
															{ dateLabels.length >
																0 && (
																<span>
																	{ ' ' }
																	[
																	{ dateLabels.join(
																		', '
																	) }
																	]
																</span>
															) }
															<div>
																{ __(
																	'Start:',
																	'rareview-scheduled-sticky-posts'
																) }{ ' ' }
																{ formatTimestamp(
																	post.stickyStart
																) }
																{ ' | ' }
																{ __(
																	'Until:',
																	'rareview-scheduled-sticky-posts'
																) }{ ' ' }
																{ formatTimestamp(
																	post.stickyUntil
																) }
															</div>
														</div>

														{ post.editLink && (
															<a
																href={
																	post.editLink
																}
																target="_blank"
																rel="noreferrer"
															>
																{ __(
																	'Edit',
																	'rareview-scheduled-sticky-posts'
																) }
															</a>
														) }

														<Button
															variant="secondary"
															onClick={ () =>
																clearSingleRareviewScheduledStickyPostsPost(
																	post.id
																)
															}
															disabled={
																isClearingRareviewScheduledStickyPostsPosts ||
																isSaving ||
																isClearingCache
															}
														>
															{ __(
																'Clear Sticky',
																'rareview-scheduled-sticky-posts'
															) }
														</Button>
													</div>
												);
											} ) }
										</div>
									) }
								</div>
							);
						}
					) }
			</CardBody>
		</Card>
	);
};

export default RareviewScheduledStickyPostsSettingsApp;
