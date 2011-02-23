( {
	appDir: "../",
	baseUrl: "scripts",
	dir: "../scripts-build",
	optimize: "uglify",
	optimizeCss: "standard",
	cssImportIgnore: null,
	inlineText: true,
	useStrict: false,
	modules: [
		{
			name: "main",
			include: ["helpers/_", "helpers/can","ui/TabBar"]
		},
		{
			name: "helpers/history",
			exclude: ["helpers/_"]
		},
		{
			name: "ui/TowerMap",
			exclude: ["helpers/_"]
		},
		{
			name: "ui/MethodView",
			exclude: ["helpers/_"]
		}
	]
} )
