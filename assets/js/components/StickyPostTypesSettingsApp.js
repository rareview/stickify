/**
 * WordPress dependencies
 */
/* global stickyPostTypesAdmin */
const apiFetch = wp.apiFetch;
const { Button, Card, CardBody, CheckboxControl, Notice, Spinner } =
	wp.components;
const { useEffect, useMemo, useState } = wp.element;
const { __ } = wp.i18n;

const SETTINGS_REST_PATH = '/wp/v2/settings';
const SETTINGS_OPTION_KEY = 'sticky_post_types_post_types';

const StickyPostTypesSettingsApp = () => {
	const [isLoading, setIsLoading] = useState(true);
	const [isSaving, setIsSaving] = useState(false);
	const [errorMessage, setErrorMessage] = useState('');
	const [successMessage, setSuccessMessage] = useState('');
	const [selectedPostTypes, setSelectedPostTypes] = useState([]);

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

				setSelectedPostTypes(postTypes);
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

		try {
			await apiFetch({
				path: SETTINGS_REST_PATH,
				method: 'POST',
				data: {
					[SETTINGS_OPTION_KEY]: selectedPostTypes,
				},
			});

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

				<Button
					variant="primary"
					onClick={saveSettings}
					disabled={isSaving}
				>
					{isSaving
						? __('Saving…', 'sticky-post-types')
						: __('Save Changes', 'sticky-post-types')}
				</Button>
			</CardBody>
		</Card>
	);
};

export default StickyPostTypesSettingsApp;
