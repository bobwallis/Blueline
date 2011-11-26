( {
	appDir: "../../web/scripts",
	baseUrl: "./",
	dir: "../../web/scripts.built",
	optimize: "uglify",
	optimizeCss: "none",
	paths: {
		"jquery": "lib/jquery"
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
			excludeShallow: ["app", "lib/History", "lib/History.adapter.jquery", "ui/Window", "ui/Header", "ui/Content", "helpers/Page", "helpers/Page/Cache", "helpers/Page/Fetch", "helpers/Page/Cache/Null", "helpers/Page/Cache/IndexedDB", "helpers/Page/Cache/WebSQL"],
			include: ["ui/TabBar"]
		},
		{
			name: "ui/TowerMap",
			exclude: ["jquery"]
		},
		{
			name: "ui/MethodView",
			exclude: ["jquery", "helpers/Can"]
		}
	]
} )
