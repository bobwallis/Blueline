( {
	appDir: "../../web/scripts",
	baseUrl: "./",
	dir: "../../web/scripts.built",
	optimize: "uglify",
	uglify: {
	},
	optimizeCss: "none",
	paths: {
		"jquery": "lib/jquery"
	},
	modules: [
		{
			name: "app",
			exclude: ["jquery", "helpers/Can", "helpers/Settings"],
			include: ["plugins/font"]
		},
		{
			name: "main",
			include: ["ui/TabBar"]
		},
		{
			name: "ui/TowerMap",
			exclude: ["jquery", "helpers/Can", "helpers/Settings"],
			include: ["plugins/google"]
		},
		{
			name: "ui/MethodView",
			exclude: ["jquery", "helpers/Can", "helpers/Settings"]
		}
	]
} )
