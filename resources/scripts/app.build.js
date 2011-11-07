( {
	appDir: "../../web/scripts",
	baseUrl: "./",
	dir: "../../web/scripts.built",
	optimize: "uglify",
	optimizeCss: "none",
	paths: {
		"jquery": "helpers/jquery"
	},
	modules: [
		{
			name: "app",
			exclude: ["jquery", "helpers/Can"]
		},
		{
			name: "main",
			// This is more complicated that it seems it should be. The issue is that 
			// app and main share a dependency (Can), but excluding app excludes Can
			// from the built version of main.
			excludeShallow: ["app", "helpers/History", "helpers/History.adapter.jquery", "ui/Window", "ui/Header", "ui/Content", "helpers/ContentCache", "helpers/ContentGetter"],
			include: ["ui/TabBar"]
		},
		{
			name: "ui/TowerMap",
			exclude: ["jquery"]
		},
		{
			name: "ui/TowerMap_offline",
			exclude: ["jquery"]
		},
		{
			name: "ui/MethodView",
			exclude: ["jquery", "helpers/Can"]
		}
	]
} )
