module.exports = {
	lintOnSave: false,
	chainWebpack: config => {
		config.plugins.delete('html');
		// config.plugin.copy.use(require('copy-webpack-plugin')).tap((args) => {
		// 	return [[...(args[0] ?? []), {from: path.resolve('admin/public')}]];
		// });
	},
	outputDir: "dist2",
};
