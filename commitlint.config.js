module.exports = {
	extends: [ '@commitlint/config-conventional' ],
	rules: {
		'subject-case': [
			2,
			'never',
			[
				'lower-case',
				'upper-case',
				'pascal-case',
				'camel-case',
				'snake-case',
				'kebab-case',
				'start-case',
			],
		],
	},
};
