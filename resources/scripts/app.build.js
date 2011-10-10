( {
	appDir: "../../web/scripts",
	baseUrl: "./",
	dir: "../../web/scripts.built",
	optimize: "uglify",
	optimizeCss: "none",
	modules: [
		{
			name: "app",
			exclude: ["helpers/Can"]
		},
		{
			name: "main",
			include: ["ui/TabBar"],
			exclude: ["app"]
		},
		{
			name: "ui/TowerMap"
		},
		{
			name: "ui/MethodView",
			exclude: ["helpers/Can"]
		}
	]
} )
