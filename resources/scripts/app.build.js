( {
	appDir: "../../www",
	baseUrl: "scripts",
	dir: "../../www-build",
	optimize: "uglify",
	optimizeCss: "standard",
	modules: [
		{
			name: "history",
			exclude: ["helpers/Can"]
		},
		{
			name: "main",
			include: ["ui/TabBar"],
			exclude: ["history"]
		},
		{
			name: "ui/TowerMap",
			exclude: ["helpers/Can"]
		},
		{
			name: "ui/MethodView",
			exclude: ["helpers/Can"]
		}
	]
} )
