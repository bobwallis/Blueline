( {
	appDir: "../../web/scripts",
	baseUrl: "./",
	dir: "../../web/scripts.built",
	optimize: "uglify",
	optimizeCss: "none",
	modules: [
		{
			name: "app",
			exclude: ["helpers/Can", "ui/TowerMap"]
		},
		{
			name: "main",
			include: ["ui/TabBar"],
			exclude: ["app"]
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
