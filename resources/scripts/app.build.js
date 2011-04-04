( {
	appDir: "../../www",
	baseUrl: "scripts",
	dir: "../../www-build",
	optimize: "uglify",
	optimizeCss: "standard",
	modules: [
		{ name: "helpers/Paper" },
		{ name: "helpers/history" },
		{ name: "history" },
		{
			name: "main",
			includeRequire: true,
			include: ["ui/TabBar"],
			exclude: ["history"]
		},
		{ name: "ui/TowerMap" }
	]
} )