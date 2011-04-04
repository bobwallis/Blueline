( {
	appDir: "../../www",
	baseUrl: "scripts",
	dir: "../../www-build",
	optimize: "uglify",
	optimizeCss: "standard",
	modules: [
		{
			name: "main",
			includeRequire: true,
			exclude: ["history"]
		},
		{
			name: "history"
		}
	]
} )