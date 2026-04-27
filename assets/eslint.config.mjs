import stylistic from '@stylistic/eslint-plugin';
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
		plugins: {
			'@stylistic': stylistic,
		},
		rules: {
			'@stylistic/array-bracket-spacing': ['error', 'never'],
			'@stylistic/arrow-spacing': ['error', { before: true, after: true }],
			'@stylistic/block-spacing': ['error', 'always'],
			'@stylistic/computed-property-spacing': ['error', 'never'],
			'@stylistic/indent': ['error', 'tab'],
			'@stylistic/no-multi-spaces': 'error',
			'@stylistic/space-in-parens': ['error', 'never'],
			'@stylistic/spaced-comment': ['error', 'always'],
			'@stylistic/template-curly-spacing': ['error', 'never'],
			'brace-style': ['error', '1tbs'],
			'comma-spacing': ['error', { before: false, after: true }],
			'eol-last': ['error', 'always'],
			'func-call-spacing': ['error', 'never'],
			'keyword-spacing': ['error', { before: true, after: true }],
			'no-multiple-empty-lines': ['error', { max: 1, maxEOF: 1, maxBOF: 0 }],
			'no-trailing-spaces': 'error',
			'no-dupe-keys': 'error',
			'no-undef': 'error',
			'no-unreachable': 'error',
			'object-curly-spacing': ['error', 'always'],
			'prefer-template': 'error',
			quotes: ['error', 'single', { avoidEscape: true }],
			semi: ['error', 'always'],
			'space-before-function-paren': ['error', { anonymous: 'always', named: 'always', asyncArrow: 'always' }],
			'space-infix-ops': 'error',
		},
	},
];
