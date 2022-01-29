module.exports = {
	lintOnSave: false,
	chainWebpack: config => {
		config.plugins.delete('html');
	},
	outputDir: "dist2",
};
