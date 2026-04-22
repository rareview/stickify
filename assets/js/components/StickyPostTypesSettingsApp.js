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

const StickyPostTypesSettingsApp = () => {
	const [isLoading, setIsLoading] = useState(true);
	const [isSaving, setIsSaving] = useState(false);
	const [isClearingCache, setIsClearingCache] = useState(false);
	const [errorMessage, setErrorMessage] = useState('');
	const [successMessage, setSuccessMessage] = useState('');
	const [selectedPostTypes, setSelectedPostTypes] = useState([]);
	const [cacheLength, setCacheLength] = useState('15');

	const availablePostTypes = useMemo(() => {
		return Object.entries(
			stickyPostTypesAdmin?.availablePostTypes || {}
		).map(([slug, label]) => ({
			slug,
			label,
		}));
	}, []);

	useEffect(() => {
		const fetchSettings = async () => {
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

		fetchSettings();
	}, []);

	const togglePostType = (slug, isChecked) => {
		setSelectedPostTypes((current) => {
			if (isChecked) {
				return [...new Set([...current, slug])];
			}

			return current.filter((postType) => postType !== slug);
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
					disabled={isSaving || isClearingCache}
					style={{ marginRight: '1em' }}
				>
					{isSaving
						? __('Saving…', 'sticky-post-types')
						: __('Save Changes', 'sticky-post-types')}
				</Button>

				<Button
					variant="secondary"
					onClick={clearCache}
					disabled={isSaving || isClearingCache}
				>
					{isClearingCache
						? __('Clearing cache…', 'sticky-post-types')
						: __('Clear Cache Now', 'sticky-post-types')}
				</Button>
			</CardBody>
		</Card>
	);
};

export default StickyPostTypesSettingsApp;
