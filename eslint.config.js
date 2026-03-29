import js from '@eslint/js';

export default [
	{
		files: ['src/Resources/js/**/*.js'],
		languageOptions: {
			ecmaVersion: 2020,
			sourceType: 'module',
			globals: {
				browser: 'readonly',
				caches: 'readonly',
				clearTimeout: 'readonly',
				clients: 'readonly',
				console: 'readonly',
				document: 'readonly',
				Event: 'readonly',
				fetch: 'readonly',
				FormData: 'readonly',
				getComputedStyle: 'readonly',
				history: 'readonly',
				Image: 'readonly',
				localStorage: 'readonly',
				location: 'readonly',
				navigator: 'readonly',
				Response: 'readonly',
				self: 'readonly',
				setTimeout: 'readonly',
				URL: 'readonly',
				URLSearchParams: 'readonly',
				window: 'readonly',
				XMLHttpRequest: 'readonly',
			},
		},
		rules: {
			'no-const-assign': 'warn',
			'no-this-before-super': 'warn',
			'no-undef': 'warn',
			'no-unreachable': 'warn',
			'no-unused-vars': 'warn',
			'constructor-super': 'warn',
			'valid-typeof': 'warn',
		},
	},
];
