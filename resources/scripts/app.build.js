( {
	appDir: "../../www",
	baseUrl: "scripts",
	dir: "../../www-build",
	optimize: "uglify",
	optimizeCss: "standard",
	modules: [
		{
			name: "history",
			exclude: ["helpers/can"]
		},
		{
			name: "main",
			includeRequire: true,
			include: ["ui/TabBar"],
			exclude: ["history"]
		},
		{
			name: "ui/TowerMap",
			exclude: ["helpers/can"]
		},
		{
			name: "ui/MethodView",
			exclude: ["helpers/can"]
		}
	]
} )
