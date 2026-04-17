import globals from 'globals';

export default [
	{
		ignores: ['assets/js/lib/**', 'js/lib/**'],
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
