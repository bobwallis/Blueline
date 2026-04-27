import globals from 'globals';

export default [
	{
		ignores: ['js/lib/**'],
	},
	{
		files: ['assets/js/**/*.js', 'js/**/*.js'],
		languageOptions: {
			ecmaVersion: 'latest',
			sourceType: 'module',
			globals: {
				...globals.browser,
				...globals.serviceworker,
			},
		},
		rules: {
			'no-dupe-keys': 'error',
			'no-undef': 'error',
			'no-unreachable': 'error',
		},
	},
];
